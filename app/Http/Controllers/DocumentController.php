<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentLog;
use App\Models\DocumentVersion;
use App\Support\DocFlow;
use App\Support\InspectionBank;
use App\Support\KpiSets;
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

        $old = $version->file_name;
        $this->storeFile($request, $document, $version);
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
        if (! $version->file_path) {
            return back()->withErrors(['file' => 'ارفع ملف الإصدار أولاً.']);
        }
        if (! trim((string) $version->change_summary)) {
            return back()->withErrors(['change_summary' => 'اكتب ملخص التعديل.']);
        }

        $version->update(['stage' => 'qa', 'submitted_at' => now(), 'approvals' => [], 'return_note' => null]);
        $this->log($document, $version, 'إرسال للمراجعة', 'إلى ' . DocFlow::STEP_LABEL['qa']);

        return back()->with('status', 'تم الإرسال لمدير إدارة الجودة.');
    }

    /** اعتماد المرحلة الحالية */
    public function approve(Request $request, Document $document, DocumentVersion $version): RedirectResponse
    {
        $this->own($document, $version);
        $user = $request->user();
        $stage = $version->stage;
        abort_unless(DocFlow::canAct($user, $stage), 403, 'هذه المرحلة ليست من صلاحيتك.');
        $comment = mb_substr(trim((string) $request->input('comment')), 0, 1000);

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

        return redirect()->route('documents.queue')->with('status', $next
            ? "تم الاعتماد. أُرسلت الوثيقة {$document->code} إلى " . DocFlow::STEP_LABEL[$next] . '.'
            : "تم الاعتماد النهائي. الإصدار {$version->version} من {$document->code} ساري من اليوم.");
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
        abort_unless($version->file_path && Storage::disk('local')->exists($version->file_path), 404);

        return Storage::disk('local')->download($version->file_path, $version->file_name);
    }

    /** تحميل قائمة الإجراءات SH-001..SH-034 ومجموعات المؤشرات في السجل */
    public function seed(Request $request): RedirectResponse
    {
        abort_unless(DocFlow::canManage($request->user()), 403);
        $tenantId = $request->user()->tenant_id;
        $have = Document::query()->pluck('code')->all();
        $n = 0;
        foreach (InspectionBank::BANK as $code => $p) {
            if (! in_array($code, $have, true)) {
                Document::create(['tenant_id' => $tenantId, 'code' => $code, 'type' => 'sop', 'title' => $p['title']]);
                $n++;
            }
        }
        foreach (KpiSets::SETS as $s) {
            if (! in_array($s['code'], $have, true)) {
                Document::create(['tenant_id' => $tenantId, 'code' => $s['code'], 'type' => 'kpi', 'title' => 'مؤشرات أداء ' . $s['title']]);
                $n++;
            }
        }

        return back()->with('status', $n ? "تمت إضافة {$n} وثيقة للسجل. ارفع ملف كل منها وأرسله للمراجعة." : 'كل الإجراءات والمؤشرات موجودة بالفعل في السجل.');
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