<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentLog;
use App\Models\DocumentVersion;
use App\Models\TenantJob;
use App\Support\DocFlow;
use App\Support\DocLibrary;
use App\Support\JobLibrary;
use App\Support\SopLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * التحكم في الوثائق (SH-014): السجل · قائمة الانتظار · الإصدارات والاعتماد
 * الإصدار إما ملف مرفوع، أو نص مدمج من مكتبة إتقان (إجراء · وصف وظيفي · مؤشرات) يملأ المخزن فراغاته ويطبعه بشعاره.
 */
class DocumentController extends Controller
{
    /* ===================== السجل ===================== */

    public function index(Request $request): View
    {
        $type   = (string) $request->query('type', '');
        $status = (string) $request->query('status', '');
        $q      = trim((string) $request->query('q', ''));
        $today  = Carbon::today('Africa/Cairo')->toDateString();

        $docs = Document::query()->with(['current', 'versions'])->orderBy('code')->get();

        $rows = $docs->map(function (Document $d) use ($today) {
            $w = $d->working();
            $cur = $d->current;
            $state = ! $d->is_active ? 'obsolete'
                : ($w ? 'pending' : ($cur ? ($cur->review_due && $cur->review_due->toDateString() < $today ? 'review' : 'effective') : 'none'));

            return ['doc' => $d, 'working' => $w, 'current' => $cur, 'state' => $state];
        })->filter(function ($r) use ($type, $status, $q) {
            if ($type !== '' && $r['doc']->type !== $type) return false;
            if ($status !== '' && $r['state'] !== $status) return false;
            if ($q !== '' && mb_stripos($r['doc']->code . ' ' . $r['doc']->title, $q) === false) return false;
            return true;
        })->values();

        $all = $docs->map(fn ($d) => $d->is_active ? ($d->working() ? 'pending' : ($d->current ? 'effective' : 'none')) : 'obsolete');

        return view('documents.index', [
            'rows'   => $rows,
            'f'      => compact('type', 'status', 'q'),
            'types'  => Document::TYPES,
            'stats'  => [
                'total'     => $docs->where('is_active', true)->count(),
                'effective' => $all->filter(fn ($s) => $s === 'effective')->count(),
                'pending'   => $all->filter(fn ($s) => $s === 'pending')->count(),
                'none'      => $all->filter(fn ($s) => $s === 'none')->count(),
                'review'    => $docs->filter(fn ($d) => $d->is_active && $d->current?->review_due && $d->current->review_due->toDateString() < $today)->count(),
            ],
            'canManage' => DocFlow::canManage($request->user()),
            'waiting'   => DocFlow::waiting($request->user()),
            'lib'       => [
                'missing' => count(array_diff_key(DocLibrary::catalog(), $docs->keyBy('code')->all())),
                'empty'   => $docs->filter(fn ($d) => $d->is_active && $d->versions->isEmpty() && DocLibrary::has($d))->count(),
                'drafts'  => DocumentVersion::query()->where('prepared_by', $request->user()->id)->where('source', 'library')
                    ->whereIn('stage', ['draft', 'returned'])->count(),
            ],
        ]);
    }

    /** قائمة الانتظار: ما ينتظر مراجعتي أو اعتمادي · ما أُعيد لي · مسوداتي */
    public function queue(Request $request): View
    {
        $user = $request->user();
        $stages = DocFlow::myStages($user);

        $mine = DocumentVersion::query()->with(['document', 'preparer'])
            ->whereIn('stage', $stages ?: ['-'])->orderBy('submitted_at')->get();
        $returned = DocumentVersion::query()->with(['document'])
            ->where('stage', 'returned')->where('prepared_by', $user->id)->orderByDesc('updated_at')->get();
        $drafts = DocumentVersion::query()->with(['document'])
            ->where('stage', 'draft')->where('prepared_by', $user->id)->orderByDesc('updated_at')->get();

        return view('documents.queue', [
            'mine'     => $mine,
            'returned' => $returned,
            'drafts'   => $drafts,
            'stages'   => $stages,
            'labels'   => DocFlow::STEP_LABEL,
            'nextOf'   => fn (DocumentVersion $v) => DocFlow::next($v->stage, $v->document->type, $user->tenant_id),
        ]);
    }

    /* ===================== الوثيقة ===================== */

    public function store(Request $request): RedirectResponse
    {
        abort_unless(DocFlow::canManage($request->user()), 403);
        $data = $this->validateDoc($request);
        $doc = Document::create($data + ['tenant_id' => $request->user()->tenant_id]);
        $this->log($doc, null, 'إضافة للسجل', $doc->code . ' · ' . $doc->title);

        return redirect()->route('documents.show', $doc)->with('status', 'تمت إضافة الوثيقة. ارفع ملف الإصدار الأول.');
    }

    public function show(Request $request, Document $document): View
    {
        $document->load(['versions.preparer', 'current', 'logs.user', 'logs.version']);
        $user = $request->user();
        $w = $document->working();

        return view('documents.show', [
            'doc'        => $document,
            'working'    => $w,
            'steps'      => DocFlow::steps($document->type, $user->tenant_id),
            'labels'     => DocFlow::STEP_LABEL,
            'types'      => Document::TYPES,
            'canManage'  => DocFlow::canManage($user),
            'canPrepare' => DocFlow::canPrepare($user, $w),
            'canAct'     => $w && DocFlow::canAct($user, $w->stage),
            'nextVer'    => DocFlow::nextVersion($document),
            'hasLib'     => DocLibrary::has($document),
            'libVer'     => DocLibrary::version($document),
            'hasBlanks'  => DocLibrary::hasBlanks($document),
            'blanks'     => $w && $w->isLibrary() && $document->type === 'sop' ? $this->blankStats($document, $w) : null,
        ]);
    }

    public function update(Request $request, Document $document): RedirectResponse
    {
        abort_unless(DocFlow::canManage($request->user()), 403);
        $document->update($this->validateDoc($request, $document->id));
        $this->log($document, null, 'تعديل البيانات', $document->code . ' · ' . $document->title);

        return back()->with('status', 'تم حفظ بيانات الوثيقة.');
    }

    public function toggle(Request $request, Document $document): RedirectResponse
    {
        abort_unless(DocFlow::canManage($request->user()), 403);
        $document->update(['is_active' => ! $document->is_active]);
        $this->log($document, null, $document->is_active ? 'إعادة تفعيل' : 'إلغاء الوثيقة', $request->input('reason'));

        return back()->with('status', $document->is_active ? 'أُعيد تفعيل الوثيقة.' : 'تم إلغاء الوثيقة (تبقى في السجل للرجوع إليها).');
    }

    /* ===================== الإصدارات ===================== */

    /** بدء إصدار جديد (مسودة) مع رفع الملف */
    public function startVersion(Request $request, Document $document): RedirectResponse
    {
        abort_if($document->working(), 422, 'يوجد إصدار جارٍ لهذه الوثيقة.');
        abort_unless($document->is_active, 422, 'الوثيقة ملغاة.');
        if (preg_match('/^\d{1,3}$/', (string) $request->input('version'))) {
            $request->merge(['version' => str_pad((string) (int) $request->input('version'), 2, '0', STR_PAD_LEFT)]);
        }
        $data = $request->validate([
            'version'        => ['required', 'regex:/^\d{1,3}$/', Rule::unique('document_versions', 'version')->where('document_id', $document->id)],
            'change_summary' => ['required', 'string', 'max:2000'],
            'file'           => $this->fileRule(true),
        ], $this->messages());

        $v = DocumentVersion::create([
            'tenant_id'      => $document->tenant_id,
            'document_id'    => $document->id,
            'version'        => $data['version'],
            'stage'          => 'draft',
            'change_summary' => $data['change_summary'],
            'prepared_by'    => $request->user()->id,
        ]);
        $this->storeFile($request, $document, $v);
        $this->log($document, $v, 'إنشاء إصدار', "الإصدار {$v->version} · {$v->file_name}");

        return back()->with('status', "تم إنشاء الإصدار {$v->version}. راجعه ثم أرسله لمدير إدارة الجودة.");
    }

    /** رفع ملف معدل: المُعد في المسودة / المعاد، أو المراجع في مرحلته (خيار التعديل) */
    public function upload(Request $request, Document $document, DocumentVersion $version): RedirectResponse
    {
        $this->own($document, $version);
        $user = $request->user();
        $asReviewer = DocFlow::canAct($user, $version->stage);
        abort_unless($asReviewer || DocFlow::canPrepare($user, $version), 403);

        $data = $request->validate([
            'file'           => $this->fileRule(true),
            'change_summary' => ['nullable', 'string', 'max:2000'],
            'edit_note'      => [$asReviewer ? 'required' : 'nullable', 'string', 'max:1000'],
        ], $this->messages() + ['edit_note.required' => 'اكتب ما الذي عدلته في الملف.']);

        $old = $version->file_name ?: ($version->isLibrary() ? 'نص مكتبة إتقان' : '');
        $this->storeFile($request, $document, $version);
        if ($version->isLibrary()) {
            $version->update(['source' => 'file']);     // تعديل أكبر من الفراغات: يصبح الإصدار ملفاً مرفوعاً
        }
        if (! empty($data['change_summary'])) {
            $version->update(['change_summary' => $data['change_summary']]);
        }
        $this->log($document, $version, $asReviewer ? 'تعديل بواسطة المراجع' : 'تحديث الملف',
            trim(($data['edit_note'] ?? '') . " · {$old} ← {$version->file_name}", ' ·'));

        return back()->with('status', 'تم رفع الملف المعدل.');
    }

    /** إرسال لمدير إدارة الجودة */
    public function submit(Request $request, Document $document, DocumentVersion $version): RedirectResponse
    {
        $this->own($document, $version);
        abort_unless(DocFlow::canPrepare($request->user(), $version), 403);
        if (! $version->file_path && ! $version->isLibrary()) {
            return back()->withErrors(['file' => 'ارفع ملف الإصدار أولاً.']);
        }
        if (! trim((string) $version->change_summary)) {
            return back()->withErrors(['change_summary' => 'اكتب ملخص التعديل.']);
        }

        $this->doSubmit($document, $version);

        return back()->with('status', 'تم الإرسال لمدير إدارة الجودة.');
    }

    /** إرسال كل مسوداتي من المكتبة دفعة واحدة */
    public function submitAll(Request $request): RedirectResponse
    {
        $user = $request->user();
        $list = DocumentVersion::query()->with('document')->where('prepared_by', $user->id)->where('source', 'library')
            ->whereIn('stage', ['draft', 'returned'])->get();
        $n = 0;
        foreach ($list as $v) {
            if ($v->document && $v->document->is_active && DocFlow::canPrepare($user, $v) && trim((string) $v->change_summary) !== '') {
                $this->doSubmit($v->document, $v);
                $n++;
            }
        }

        return back()->with('status', $n ? "تم إرسال {$n} وثيقة لمدير إدارة الجودة." : 'لا توجد مسودات من المكتبة للإرسال.');
    }

    private function doSubmit(Document $document, DocumentVersion $version): void
    {
        // الوصف الوظيفي: تُحفظ فراغاته كما هي وقت الإرسال (من شاشة الهيكل الوظيفي)
        if ($version->isLibrary() && $document->type === 'jd') {
            $version->fills = TenantJob::query()->where('job_code', $document->code)->value('blanks') ?? [];
        }
        $version->fill(['stage' => 'qa', 'submitted_at' => now(), 'approvals' => [], 'return_note' => null])->save();
        $this->log($document, $version, 'إرسال للمراجعة', 'إلى ' . DocFlow::STEP_LABEL['qa']);
    }

    /** اعتماد المرحلة الحالية */
    public function approve(Request $request, Document $document, DocumentVersion $version): RedirectResponse
    {
        $this->own($document, $version);
        $user = $request->user();
        $stage = $version->stage;
        abort_unless(DocFlow::canAct($user, $stage), 403, 'هذه المرحلة ليست من صلاحيتك.');
        $comment = mb_substr(trim((string) $request->input('comment')), 0, 1000);
        $next = $this->doApprove($request->user(), $document, $version, $comment);

        return redirect()->route('documents.queue')->with('status', $next
            ? "تم الاعتماد. أُرسلت الوثيقة {$document->code} إلى " . DocFlow::STEP_LABEL[$next] . '.'
            : "تم الاعتماد النهائي. الإصدار {$version->version} من {$document->code} ساري من اليوم.");
    }

    /** اعتماد عدة وثائق من قائمة الانتظار دفعة واحدة (نفس المرحلة لكل منها) */
    public function approveBulk(Request $request): RedirectResponse
    {
        $user = $request->user();
        $ids = array_map('intval', (array) $request->input('ids', []));
        if (! $ids) {
            return back()->withErrors(['ids' => 'اختر الوثائق التي تريد اعتمادها.']);
        }
        $comment = mb_substr(trim((string) $request->input('comment')), 0, 1000);
        $done = 0;
        $final = 0;
        foreach (DocumentVersion::query()->with('document')->whereIn('id', $ids)->get() as $v) {
            if (! $v->document || ! DocFlow::canAct($user, $v->stage)) {
                continue;
            }
            $next = $this->doApprove($user, $v->document, $v, $comment);
            $done++;
            $final += $next ? 0 : 1;
        }

        return back()->with('status', $done
            ? "تم اعتماد {$done} وثيقة" . ($final ? " (منها {$final} اعتماد نهائي وأصبحت سارية)" : '') . '.'
            : 'لم يتم اعتماد أي وثيقة (ليست في مرحلتك).');
    }

    /** تنفيذ الاعتماد: يرجع المرحلة التالية أو null عند الاعتماد النهائي */
    private function doApprove($user, Document $document, DocumentVersion $version, string $comment): ?string
    {
        $stage = $version->stage;
        $approvals = $version->approvals ?? [];
        $approvals[] = ['step' => $stage, 'user_id' => $user->id, 'name' => $user->displayName(), 'role' => $user->role?->name_ar, 'at' => now('Africa/Cairo')->format('Y-m-d H:i'), 'comment' => $comment];
        $next = DocFlow::next($stage, $document->type, $user->tenant_id);

        DB::transaction(function () use ($document, $version, $approvals, $next) {
            if ($next) {
                $version->update(['stage' => $next, 'approvals' => $approvals]);
                return;
            }
            // الاعتماد النهائي: يصبح الإصدار سارياً ويُلغى السابق
            if ($document->current_version_id && (int) $document->current_version_id !== (int) $version->id) {
                DocumentVersion::whereKey($document->current_version_id)->update(['stage' => 'superseded']);
            }
            $today = Carbon::today('Africa/Cairo');
            $version->update([
                'stage' => 'effective', 'approvals' => $approvals,
                'effective_date' => $today->toDateString(),
                'review_due' => $today->copy()->addYears(DocFlow::REVIEW_YEARS)->toDateString(),
            ]);
            $document->update(['current_version_id' => $version->id]);
        });

        $this->log($document, $version, $next ? 'اعتماد' : 'اعتماد نهائي وسريان',
            DocFlow::STEP_LABEL[$stage] . ($next ? ' ← ' . DocFlow::STEP_LABEL[$next] : '') . ($comment ? ' · ' . $comment : ''));

        return $next;
    }

    /** إعادة للمُعد مع التعليق */
    public function returnBack(Request $request, Document $document, DocumentVersion $version): RedirectResponse
    {
        $this->own($document, $version);
        abort_unless(DocFlow::canAct($request->user(), $version->stage), 403);
        $data = $request->validate(['comment' => ['required', 'string', 'max:1000']], ['comment.required' => 'اكتب سبب الإعادة والمطلوب تعديله.']);

        $from = $version->stage;
        $version->update(['stage' => 'returned', 'return_note' => $data['comment']]);
        $this->log($document, $version, 'إعادة للمُعد', DocFlow::STEP_LABEL[$from] . ' · ' . $data['comment']);

        return redirect()->route('documents.queue')->with('status', "أُعيدت الوثيقة {$document->code} للمُعد.");
    }

    /** حذف مسودة لم تُرسل */
    public function destroyVersion(Request $request, Document $document, DocumentVersion $version): RedirectResponse
    {
        $this->own($document, $version);
        abort_unless($version->stage === 'draft' && DocFlow::canPrepare($request->user(), $version), 403);
        if ($version->file_path) {
            Storage::disk('local')->delete($version->file_path);
        }
        $this->log($document, null, 'حذف مسودة', "الإصدار {$version->version}");
        $version->delete();

        return back()->with('status', 'تم حذف المسودة.');
    }

    public function download(Document $document, DocumentVersion $version): StreamedResponse
    {
        $this->own($document, $version);
        abort_unless($version->file_path && Storage::disk('local')->exists($version->file_path), 404, 'هذا الإصدار نص من مكتبة إتقان: استخدم «طباعة».');

        return Storage::disk('local')->download($version->file_path, $version->file_name);
    }

    /** تحميل مكتبة إتقان في السجل: ٣٤ إجراء · الأوصاف الوظيفية · مجموعات مؤشرات الأداء */
    public function seed(Request $request): RedirectResponse
    {
        abort_unless(DocFlow::canManage($request->user()), 403);
        $tenantId = $request->user()->tenant_id;
        $have = Document::query()->pluck('code')->all();
        $n = 0;
        foreach (DocLibrary::catalog() as $code => $d) {
            if (! in_array($code, $have, true)) {
                Document::create(['tenant_id' => $tenantId, 'code' => $code, 'type' => $d['type'], 'title' => $d['title'], 'dept' => $d['dept']]);
                $n++;
            }
        }

        return back()->with('status', $n ? "تمت إضافة {$n} وثيقة من مكتبة إتقان للسجل. أنشئ مسوداتها من المكتبة ثم أرسلها للمراجعة." : 'كل وثائق مكتبة إتقان موجودة بالفعل في السجل.');
    }

    /* ===================== مكتبة إتقان ===================== */

    /** إصدار جديد من نص المكتبة (بدون ملف) */
    public function startLibrary(Request $request, Document $document): RedirectResponse
    {
        abort_if($document->working(), 422, 'يوجد إصدار جارٍ لهذه الوثيقة.');
        abort_unless($document->is_active && DocLibrary::has($document), 422, 'لا يوجد نص لهذه الوثيقة في مكتبة إتقان.');
        $first = ! $document->versions()->exists();
        if (preg_match('/^\d{1,3}$/', (string) $request->input('version'))) {
            $request->merge(['version' => str_pad((string) (int) $request->input('version'), 2, '0', STR_PAD_LEFT)]);
        }
        $data = $request->validate([
            'version'        => ['required', 'regex:/^\d{1,3}$/', Rule::unique('document_versions', 'version')->where('document_id', $document->id)],
            'change_summary' => ['required', 'string', 'max:2000'],
        ], $this->messages());

        $v = $this->createLibraryVersion($request, $document, $data['version'], $data['change_summary'], $first);

        return redirect()->route($document->type === 'sop' && DocLibrary::hasBlanks($document) ? 'documents.fills' : 'documents.show',
            $document->type === 'sop' && DocLibrary::hasBlanks($document) ? [$document, $v] : $document)
            ->with('status', "تم إنشاء الإصدار {$v->version} من نص مكتبة إتقان." . ($document->type === 'sop' ? ' املأ الفراغات ثم أرسله للمراجعة.' : ' راجعه ثم أرسله.'));
    }

    /** مسودات من المكتبة لكل الوثائق التي ليس لها أي إصدار بعد */
    public function libraryAll(Request $request): RedirectResponse
    {
        abort_unless(DocFlow::canManage($request->user()), 403);
        $n = 0;
        foreach (Document::query()->with('versions')->where('is_active', true)->get() as $d) {
            if ($d->versions->isEmpty() && DocLibrary::has($d)) {
                $this->createLibraryVersion($request, $d, DocLibrary::version($d), 'إصدار أول في المخزن من نص مكتبة إتقان (الإصدار ' . DocLibrary::version($d) . ').', true);
                $n++;
            }
        }

        return back()->with('status', $n ? "تم إنشاء {$n} مسودة من مكتبة إتقان. املأ فراغات الإجراءات ثم «أرسل كل مسوداتي للمراجعة»." : 'لا توجد وثائق من المكتبة بدون إصدار.');
    }

    private function createLibraryVersion(Request $request, Document $document, string $version, string $summary, bool $first): DocumentVersion
    {
        $v = DocumentVersion::create([
            'tenant_id'      => $document->tenant_id,
            'document_id'    => $document->id,
            'version'        => $version,
            'stage'          => 'draft',
            'source'         => 'library',
            'lib_version'    => DocLibrary::version($document),
            'fills'          => $this->previousFills($document),
            'change_summary' => $summary,
            'prepared_by'    => $request->user()->id,
        ]);
        $this->log($document, $v, 'إنشاء إصدار', "الإصدار {$v->version} · نص مكتبة إتقان " . DocLibrary::version($document) . ($first ? '' : ' (فراغات الإصدار السابق)'));

        return $v;
    }

    /** فراغات آخر إصدار من المكتبة (لا يعيد المخزن ملأها عند المراجعة الدورية) */
    private function previousFills(Document $document): array
    {
        return $document->versions()->where('source', 'library')->orderByDesc('id')->value('fills') ?? [];
    }

    /** شاشة ملء الفراغات */
    public function fills(Request $request, Document $document, DocumentVersion $version): View
    {
        $this->own($document, $version);
        $sop = SopLibrary::get($document->code);
        abort_unless($sop && $version->isLibrary() && $document->type === 'sop', 404);
        $user = $request->user();
        $tenant = $user->tenant;
        $values = $version->fills ?? [];

        $items = collect(SopLibrary::placeholders($sop))->map(fn ($p, $i) => $p + [
            'i'     => $i,
            'value' => array_key_exists($i, $values) ? (string) $values[$i] : SopLibrary::defaultFor($p['raw'], $tenant),
            'unit'  => SopLibrary::unit($p['raw']),
            'set'   => array_key_exists($i, $values) && trim((string) $values[$i]) !== '',
        ]);

        return view('documents.fills', [
            'doc'      => $document,
            'v'        => $version,
            'groups'   => $items->groupBy('sec'),
            'count'    => $items->count(),
            'editable' => $this->canEditFills($user, $version),
        ]);
    }

    public function saveFills(Request $request, Document $document, DocumentVersion $version): RedirectResponse
    {
        $this->own($document, $version);
        abort_unless($version->isLibrary() && $document->type === 'sop' && $this->canEditFills($request->user(), $version), 403);
        $sop = SopLibrary::get($document->code) ?? abort(404);
        $n = count(SopLibrary::placeholders($sop));
        $data = $request->validate(['f' => ['array'], 'f.*' => ['nullable', 'string', 'max:300']]);

        $fills = [];
        for ($i = 0; $i < $n; $i++) {
            $fills[$i] = trim((string) ($data['f'][$i] ?? ''));
        }
        $changed = collect($fills)->filter(fn ($v, $i) => $v !== (string) (($version->fills ?? [])[$i] ?? ''))->count();
        $version->update(['fills' => $fills]);
        if ($changed) {
            $this->log($document, $version, DocFlow::canAct($request->user(), $version->stage) ? 'تعديل بواسطة المراجع' : 'ملء الفراغات', "{$changed} فراغ");
        }

        return redirect()->route('documents.show', $document)->with('status', 'تم حفظ الفراغات.' . ($changed ? " ({$changed} تعديل)" : ''));
    }

    private function canEditFills($user, DocumentVersion $v): bool
    {
        return DocFlow::canPrepare($user, $v) || DocFlow::canAct($user, $v->stage);
    }

    private function blankStats(Document $document, DocumentVersion $v): array
    {
        $sop = SopLibrary::get($document->code);
        $all = $sop ? SopLibrary::placeholders($sop) : [];
        $tenant = request()->user()->tenant;
        $empty = 0;
        foreach ($all as $i => $p) {
            $val = trim((string) (($v->fills ?? [])[$i] ?? ''));
            if ($val === '' && SopLibrary::defaultFor($p['raw'], $tenant) === '') {
                $empty++;
            }
        }

        return ['total' => count($all), 'empty' => $empty];
    }

    /* ===================== الطباعة ===================== */

    /**
     * طباعة الوثيقة بشعار المخزن: الإصدار الساري (افتراضياً) أو إصدار محدد ?v= · نموذج واحد فارغ ?form=SH-001-F02
     */
    public function print(Request $request, Document $document): View|RedirectResponse|StreamedResponse
    {
        $v = $request->filled('v')
            ? $document->versions()->whereKey((int) $request->query('v'))->firstOrFail()
            : ($document->current ?? $document->working() ?? null);

        if ($document->type === 'jd') {
            return redirect()->route('jobs.print', $document->code);
        }
        if ($v && ! $v->isLibrary()) {
            return $this->download($document, $v);
        }
        abort_unless(DocLibrary::has($document), 404, 'لا يوجد نص لهذه الوثيقة في مكتبة إتقان.');

        $tenant = $request->user()->tenant;
        $prev = $v ? $document->versions()->where('id', '<', $v->id)->whereIn('stage', ['effective', 'superseded'])->orderByDesc('id')->value('version') : null;
        $base = [
            'doc' => $document, 'v' => $v, 'tenant' => $tenant, 'prev' => $prev,
            'sig' => DocLibrary::signatures($v), 'draft' => ! $v || $v->stage !== 'effective',
            'printedBy' => $request->user()->displayName(), 'printedAt' => now('Africa/Cairo')->format('Y-m-d H:i'),
        ];

        if ($document->type === 'kpi') {
            return view('documents.print-kpi', $base + ['set' => DocLibrary::kpi($document->code), 'approvers' => DocLibrary::KPI_APPROVERS,
                'bands' => \App\Support\KpiSets::BANDS, 'gateCap' => \App\Support\KpiSets::GATE_CAP]);
        }

        $sop = SopLibrary::fill(SopLibrary::get($document->code), $v?->fills ?? [], $tenant);
        $form = $request->query('form');

        return view('documents.print', $base + [
            'sop'   => $sop,
            'forms' => $form ? array_values(array_filter($sop['forms'], fn ($f) => $f['code'] === $form)) : $sop['forms'],
            'only'  => (bool) $form,
        ]);
    }

    /** طباعة قائمة الوثائق المحددة (الكود والعنوان والإصدار والحالة فقط) */
    public function printList(Request $request): View
    {
        $v = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:1000'], 'ids.*' => ['integer']],
            ['ids.required' => 'حدد وثيقة واحدة على الأقل.']);
        $today = Carbon::today('Africa/Cairo')->toDateString();
        $rows = Document::query()->with(['current', 'versions'])->whereIn('id', $v['ids'])->orderBy('code')->get()->map(function (Document $d) use ($today) {
            $w = $d->working();
            $cur = $d->current;
            $state = ! $d->is_active ? 'obsolete'
                : ($w ? 'pending' : ($cur ? ($cur->review_due && $cur->review_due->toDateString() < $today ? 'review' : 'effective') : 'none'));

            return ['doc' => $d, 'current' => $cur, 'working' => $w, 'state' => $state];
        });
        $types = $rows->pluck('doc.type')->unique();

        return view('documents.print-list', [
            'rows' => $rows, 'tenant' => $request->user()->tenant,
            'title' => $types->count() === 1 ? 'قائمة ' . (['sop' => 'إجراءات التشغيل القياسية', 'jd' => 'الأوصاف الوظيفية', 'kpi' => 'مجموعات مؤشرات الأداء', 'form' => 'النماذج', 'policy' => 'السياسات'][$types->first()] ?? 'الوثائق') : 'قائمة الوثائق',
            'printedAt' => now('Africa/Cairo')->format('Y-m-d H:i'), 'printedBy' => $request->user()->displayName(),
        ]);
    }

    /**
     * طباعة مجمعة من سجل الوثائق: الوثائق المحددة (أو كل نتائج الفلتر) في ملف طباعة واحد · كل وثيقة تبدأ في صفحة جديدة
     * ?ids[]=…  · ?forms=1 لإضافة نماذج كل إجراء · الوثائق المرفوعة كملفات (Word/PDF) تُذكر فقط ولا تُدمج
     */
    public function printBatch(Request $request): View
    {
        $u = $request->user();
        $v = $request->validate(['ids' => ['required', 'array', 'min:1', 'max:80'], 'ids.*' => ['integer']],
            ['ids.required' => 'حدد وثيقة واحدة على الأقل.', 'ids.max' => 'الحد الأقصى ٨٠ وثيقة في الطباعة الواحدة.']);
        $docs = Document::query()->with(['current', 'versions'])->whereIn('id', $v['ids'])->orderBy('code')->get();
        $tenant = $u->tenant;
        $reviewer = DocFlow::myStages($u) !== [];
        $viewer = \App\Support\Access::can($u, 'documents', 'view');
        $withForms = $request->boolean('forms');
        $jobs = app(JobController::class);

        $items = [];
        $skipped = [];
        foreach ($docs as $doc) {
            $ver = $doc->current ?? $doc->working();
            $open = $ver ? $ver->stage === 'effective' : true;
            if (! $open && (int) $ver?->prepared_by !== $u->id && ! $reviewer && ! $viewer) {
                $skipped[] = [$doc->code, 'غير مسموح بطباعة نسخة غير سارية'];
                continue;
            }
            if ($ver && ! $ver->isLibrary()) {
                $skipped[] = [$doc->code, 'ملف مرفوع (Word / PDF): يُطبع من صفحة الوثيقة'];
                continue;
            }
            if ($doc->type === 'jd') {
                $job = \App\Support\JobLibrary::get($doc->code);
                if ($job) {
                    $items[] = ['type' => 'jd', 'code' => $doc->code, 'vars' => $jobs->jdData($request, $job, null)];
                } else {
                    $skipped[] = [$doc->code, 'غير موجود في المكتبة'];
                }
                continue;
            }
            if (! DocLibrary::has($doc)) {
                $skipped[] = [$doc->code, 'لا يوجد نص في مكتبة إتقان'];
                continue;
            }
            $prev = $ver ? $doc->versions->where('id', '<', $ver->id)->whereIn('stage', ['effective', 'superseded'])->sortByDesc('id')->first()?->version : null;
            $base = ['doc' => $doc, 'v' => $ver, 'tenant' => $tenant, 'prev' => $prev, 'sig' => DocLibrary::signatures($ver),
                'draft' => ! $ver || $ver->stage !== 'effective', 'printedBy' => $u->displayName(), 'printedAt' => now('Africa/Cairo')->format('Y-m-d H:i')];
            if ($doc->type === 'kpi') {
                $items[] = ['type' => 'kpi', 'code' => $doc->code, 'vars' => $base + ['set' => DocLibrary::kpi($doc->code), 'approvers' => DocLibrary::KPI_APPROVERS,
                    'bands' => \App\Support\KpiSets::BANDS, 'gateCap' => \App\Support\KpiSets::GATE_CAP]];
                continue;
            }
            $sop = SopLibrary::fill(SopLibrary::get($doc->code), $ver?->fills ?? [], $tenant);
            $items[] = ['type' => 'sop', 'code' => $doc->code, 'vars' => $base + ['sop' => $sop, 'forms' => $withForms ? $sop['forms'] : [], 'only' => false]];
        }

        return view('documents.print-batch', ['items' => $items, 'skipped' => $skipped, 'withForms' => $withForms, 'ids' => $v['ids']]);
    }

    /* ======================================================= */

    private function own(Document $document, DocumentVersion $version): void
    {
        abort_unless((int) $version->document_id === (int) $document->id, 404);
    }

    private function storeFile(Request $request, Document $document, DocumentVersion $v): void
    {
        $file = $request->file('file');
        $ext = strtolower($file->getClientOriginalExtension());
        $safe = Str::slug($document->code) ?: 'doc';
        $name = "{$safe}_v{$v->version}_" . now()->format('YmdHis') . ".{$ext}";
        $path = $file->storeAs("documents/{$document->tenant_id}/{$safe}", $name, 'local');
        if ($v->file_path && $v->file_path !== $path) {
            Storage::disk('local')->delete($v->file_path);
        }
        $v->update(['file_path' => $path, 'file_name' => "{$document->code} v{$v->version}.{$ext}", 'file_size' => $file->getSize()]);
    }

    private function fileRule(bool $required): array
    {
        return [$required ? 'required' : 'nullable', 'file', 'max:20480', 'extensions:docx,doc,pdf,xlsx,xls'];
    }

    private function validateDoc(Request $request, ?int $id = null): array
    {
        $tenantId = $request->user()->tenant_id;
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        return $request->validate([
            'code'  => ['required', 'string', 'max:40', 'regex:/^[A-Z0-9][A-Z0-9\-\.]*$/', Rule::unique('documents', 'code')->where('tenant_id', $tenantId)->ignore($id)],
            'type'  => ['required', Rule::in(array_keys(Document::TYPES))],
            'title' => ['required', 'string', 'max:255'],
            'dept'  => ['nullable', 'string', 'max:100'],
        ], $this->messages());
    }

    private function messages(): array
    {
        return [
            'code.required'           => 'اكتب كود الوثيقة.',
            'code.regex'              => 'الكود حروف إنجليزية كبيرة وأرقام وشرطة فقط (مثال: SH-001 أو JD-WH-07).',
            'code.unique'             => 'هذا الكود مسجل لوثيقة أخرى.',
            'title.required'          => 'اكتب عنوان الوثيقة.',
            'version.required'        => 'اكتب رقم الإصدار.',
            'version.regex'           => 'رقم الإصدار أرقام فقط (مثال: 02).',
            'version.unique'          => 'رقم الإصدار مستخدم من قبل لهذه الوثيقة.',
            'change_summary.required' => 'اكتب ملخص التعديل في هذا الإصدار.',
            'file.required'           => 'اختر ملف الوثيقة.',
            'file.max'                => 'حجم الملف أكبر من ٢٠ ميجابايت.',
            'file.extensions'         => 'الملف يجب أن يكون Word أو PDF أو Excel.',
        ];
    }

    private function log(Document $doc, ?DocumentVersion $v, string $action, ?string $comment): void
    {
        $user = request()->user();
        DocumentLog::create([
            'tenant_id'   => $doc->tenant_id,
            'document_id' => $doc->id,
            'version_id'  => $v?->id,
            'user_id'     => $user?->id,
            'role'        => $user?->role?->name_ar,
            'action'      => $action,
            'comment'     => $comment ? mb_substr($comment, 0, 1000) : null,
        ]);
    }
}