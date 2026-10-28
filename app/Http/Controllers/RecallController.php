<?php

namespace App\Http\Controllers;

use App\Models\Pharmacy;
use App\Models\Recall;
use App\Models\RecallCustomer;
use App\Support\Notifier;
use App\Support\Recalls;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * الاستدعاء والسحب (SH-010): السجل F01 · السحب وعملاؤه F02 · الإقرارات المطبوعة F05 · التسوية والإغلاق F03
 */
class RecallController extends Controller
{
    public function index(Request $request): View
    {
        $this->view($request);
        $recalls = Recall::query()->with('customers')->orderByRaw("status = 'open' desc")->latest('received_on')->latest('id')->get();

        return view('recalls.index', [
            'recalls' => $recalls,
            'stats'   => $recalls->mapWithKeys(fn ($r) => [$r->id => Recalls::stats($r)]),
            'manage'  => Recalls::canManage($request->user()),
            'next'    => Recalls::nextNumber($request->user()->tenant_id),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $u = $request->user();
        $this->manage($request);
        $data = $this->recallData($request);
        $r = Recall::create($data + ['tenant_id' => $u->tenant_id, 'number' => Recalls::nextNumber($u->tenant_id), 'status' => 'open', 'created_by' => $u->id]);

        Notifier::event($u->tenant_id, 'recall', 'سحب تشغيلة: ' . $r->product,
            'تشغيلة ' . $r->batch . ' · ' . $r->classLabel() . ($r->deadline ? ' · إبلاغ العملاء قبل ' . $r->deadline->format('Y-m-d') : '') . ' · ' . $r->number,
            route('recalls.show', $r), 'recall-' . $r->id, $r->class === '1');

        return redirect()->route('recalls.show', $r)->with('status', "تم فتح السحب {$r->number}. أضف العملاء الذين اشتروا التشغيلة ثم اطبع الإقرارات.");
    }

    public function show(Request $request, Recall $recall): View
    {
        $this->view($request);
        $recall->load('customers.editor', 'creator', 'closer');

        return view('recalls.show', [
            'r'      => $recall,
            's'      => Recalls::stats($recall),
            'manage' => Recalls::canManage($request->user()),
            'pharmacies' => Pharmacy::query()->orderBy('name')->get(['code', 'name', 'address']),
        ]);
    }

    public function update(Request $request, Recall $recall): RedirectResponse
    {
        $this->manage($request);
        $recall->update($this->recallData($request));

        return back()->with('status', 'تم حفظ بيانات السحب.');
    }

    /** تسوية وإغلاق (F03) */
    public function close(Request $request, Recall $recall): RedirectResponse
    {
        $this->manage($request);
        $v = $request->validate([
            'qty_received'         => ['nullable', 'required_if:action,close', 'integer', 'min:0'],
            'qty_in_stock'         => ['nullable', 'required_if:action,close', 'integer', 'min:0'],
            'disposal'             => ['nullable', 'required_if:action,close', Rule::in(array_keys(Recalls::DISPOSALS))],
            'disposal_ref'         => ['nullable', 'string', 'max:80'],
            'eda_notified_on'      => ['nullable', 'date'],
            'supplier_notified_on' => ['nullable', 'date'],
            'dev_no'               => ['nullable', 'string', 'max:30'],
            'notes'                => ['nullable', 'string', 'max:2000'],
            'action'               => ['required', Rule::in(['save', 'close'])],
        ], [
            'qty_received.required_if' => 'للإغلاق: اكتب الكمية المستلمة من التشغيلة (أ).',
            'qty_in_stock.required_if' => 'للإغلاق: اكتب الكمية الموجودة بالمخزن والمنقولة للمسحوبات (ج).',
            'disposal.required_if'     => 'للإغلاق: حدد التصرف في الكميات المسحوبة.',
        ]);
        $close = $v['action'] === 'close';
        unset($v['action']);
        $recall->update($v + ($close ? ['status' => 'closed', 'closed_at' => now(), 'closed_by' => $request->user()->id] : []));

        return back()->with('status', $close ? "أُغلق السحب {$recall->number}." : 'تم حفظ التسوية.');
    }

    public function reopen(Request $request, Recall $recall): RedirectResponse
    {
        $this->manage($request);
        $recall->update(['status' => 'open', 'closed_at' => null, 'closed_by' => null]);

        return back()->with('status', "أُعيد فتح السحب {$recall->number}.");
    }

    /* ===================== العملاء ===================== */

    /**
     * إضافة العملاء: سطر لكل عميل «الكود، الكمية» أو «الاسم، الكمية»
     * الكود يُكمل من قائمة الصيدليات (الاسم والعنوان)
     */
    public function addCustomers(Request $request, Recall $recall): RedirectResponse
    {
        $this->work($request);
        abort_unless($recall->isOpen(), 422, 'السحب مغلق.');
        $v = $request->validate(['lines' => ['required', 'string', 'max:20000']], ['lines.required' => 'اكتب عميلاً واحداً على الأقل.']);
        $ph = Pharmacy::query()->get()->keyBy(fn ($p) => mb_strtolower(trim($p->code)));
        $have = $recall->customers()->pluck('code')->filter()->map(fn ($c) => mb_strtolower($c))->flip();

        $added = 0;
        $skipped = [];
        foreach (preg_split('/\r\n|\r|\n/', $v['lines']) as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }
            $parts = array_map('trim', preg_split('/\t|[,،;؛]/u', $line));
            $first = $parts[0] ?? '';
            $qty = isset($parts[1]) && preg_match('/^\d+$/', $this->digits($parts[1])) ? (int) $this->digits($parts[1]) : null;
            $p = $ph->get(mb_strtolower($first));
            $code = $p?->code ?? (preg_match('/^[A-Za-z0-9\-]{1,20}$/', $first) ? $first : null);
            if ($code && $have->has(mb_strtolower($code))) {
                $skipped[] = $first;
                continue;
            }
            RecallCustomer::create([
                'tenant_id' => $recall->tenant_id, 'recall_id' => $recall->id, 'code' => $code,
                'name' => mb_substr($p?->name ?? ($code && ! $p ? 'عميل ' . $code : $first), 0, 200),
                'address' => $p?->address, 'phone' => isset($parts[2]) ? mb_substr($parts[2], 0, 40) : null,
                'qty_sold' => $qty, 'updated_by' => $request->user()->id,
            ]);
            if ($code) {
                $have[mb_strtolower($code)] = true;
            }
            $added++;
        }

        return back()->with('status', "تمت إضافة {$added} عميل." . ($skipped ? ' مكرر وتُرك: ' . implode('، ', array_slice($skipped, 0, 8)) : ''));
    }

    public function updateCustomer(Request $request, Recall $recall, RecallCustomer $customer): RedirectResponse
    {
        $this->work($request);
        abort_unless((int) $customer->recall_id === (int) $recall->id, 404);
        $v = $request->validate([
            'name'         => ['required', 'string', 'max:200'],
            'code'         => ['nullable', 'string', 'max:40'],
            'address'      => ['nullable', 'string', 'max:255'],
            'phone'        => ['nullable', 'string', 'max:40'],
            'qty_sold'     => ['nullable', 'integer', 'min:0'],
            'notified'     => ['nullable', 'date'],
            'method'       => ['nullable', Rule::in(array_keys(Recalls::METHODS))],
            'declaration'  => ['required', Rule::in(array_keys(Recalls::DECLARATIONS))],
            'qty_returned' => ['nullable', 'integer', 'min:0'],
            'returned_on'  => ['nullable', 'date'],
            'pharmacist'   => ['nullable', 'string', 'max:120'],
            'note'         => ['nullable', 'string', 'max:500'],
            'file'         => ['nullable', 'file', 'max:10240', 'extensions:jpg,jpeg,png,webp,heic,pdf'],
        ], ['declaration.in' => 'حدد نتيجة الإقرار.']);

        if ($v['declaration'] === 'has' && empty($v['qty_returned'])) {
            throw \Illuminate\Validation\ValidationException::withMessages(['qty_returned' => 'اكتب الكمية التي تم استرجاعها من العميل.']);
        }
        $data = collect($v)->except(['notified', 'file'])->all();
        $data['notified_at'] = ! empty($v['notified']) ? \Illuminate\Support\Carbon::parse($v['notified'], Recalls::TZ) : null;
        if ($v['declaration'] === 'none') {
            $data['qty_returned'] = 0;
        }
        $data['updated_by'] = $request->user()->id;
        $customer->update($data);

        if ($request->hasFile('file')) {
            $f = $request->file('file');
            if ($customer->file_path) {
                Storage::disk('local')->delete($customer->file_path);
            }
            $path = $f->storeAs('recalls/' . $recall->tenant_id . '/' . $recall->id, 'decl-' . $customer->id . '-' . now()->format('YmdHis') . '.' . strtolower($f->getClientOriginalExtension()), 'local');
            $customer->update(['file_path' => $path, 'file_name' => mb_substr($f->getClientOriginalName(), 0, 250)]);
        }

        return redirect()->to(route('recalls.show', $recall) . '#c' . $customer->id)->with('status', "تم حفظ {$customer->name}.");
    }

    /** تسجيل الإبلاغ بضغطة (الآن) */
    public function notified(Request $request, Recall $recall, RecallCustomer $customer): RedirectResponse
    {
        $this->work($request);
        abort_unless((int) $customer->recall_id === (int) $recall->id, 404);
        $v = $request->validate(['method' => ['required', Rule::in(array_keys(Recalls::METHODS))]]);
        $customer->update(['notified_at' => now(), 'method' => $v['method'], 'updated_by' => $request->user()->id]);

        return redirect()->to(route('recalls.show', $recall) . '#c' . $customer->id)->with('status', "سُجل إبلاغ {$customer->name}.");
    }

    public function destroyCustomer(Request $request, Recall $recall, RecallCustomer $customer): RedirectResponse
    {
        $this->manage($request);
        abort_unless((int) $customer->recall_id === (int) $recall->id, 404);
        if ($customer->file_path) {
            Storage::disk('local')->delete($customer->file_path);
        }
        $customer->delete();

        return back()->with('status', "حُذف {$customer->name} من السحب.");
    }

    public function file(Request $request, Recall $recall, RecallCustomer $customer): StreamedResponse
    {
        $this->view($request);
        abort_unless((int) $customer->recall_id === (int) $recall->id && $customer->file_path && Storage::disk('local')->exists($customer->file_path), 404);

        return Storage::disk('local')->response($customer->file_path, $customer->file_name);
    }

    /* ===================== الطباعة ===================== */

    /** إقرارات الصيدليات (F05): ?c=ID لعميل واحد · ?all=1 للكل · الافتراضي: من لم يصل إقراره */
    public function declarations(Request $request, Recall $recall): View
    {
        $this->view($request);
        $q = $recall->customers();
        if ($request->filled('c')) {
            $q->whereKey((int) $request->query('c'));
        } elseif (! $request->boolean('all')) {
            $q->where('declaration', 'pending');
        }

        return view('recalls.declaration', [
            'r' => $recall, 'list' => $q->get(), 'tenant' => $request->user()->tenant,
            'blank' => $request->boolean('blank'),
        ]);
    }

    /** سجل المتابعة F02 + التسوية F03 */
    public function report(Request $request, Recall $recall): View
    {
        $this->view($request);
        $recall->load('customers', 'closer');

        return view('recalls.report', [
            'r' => $recall, 's' => Recalls::stats($recall), 'tenant' => $request->user()->tenant,
            'printedAt' => now(Recalls::TZ)->format('Y-m-d H:i'), 'printedBy' => $request->user()->displayName(),
        ]);
    }

    /* ===================== مساعدات ===================== */

    private function view(Request $request): void
    {
        abort_unless(Recalls::canWork($request->user()), 403, 'شاشة السحب للمدير الصيدلي ومدير الجودة والمبيعات والتوزيع.');
    }

    private function work(Request $request): void
    {
        $this->view($request);
    }

    private function manage(Request $request): void
    {
        abort_unless(Recalls::canManage($request->user()), 403, 'فتح السحب وتسويته وإغلاقه للمدير الصيدلي ومدير الجودة.');
    }

    /** أرقام عربية ← إنجليزية */
    private function digits(string $s): string
    {
        return strtr(trim($s), ['٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
    }

    private function recallData(Request $request): array
    {
        return $request->validate([
            'circular_no' => ['nullable', 'string', 'max:80'],
            'source'      => ['required', Rule::in(array_keys(Recalls::SOURCES))],
            'received_on' => ['required', 'date'],
            'product'     => ['required', 'string', 'max:200'],
            'batch'       => ['required', 'string', 'max:80'],
            'expiry'      => ['nullable', 'date'],
            'class'       => ['required', Rule::in(array_keys(Recalls::CLASSES))],
            'reason'      => ['nullable', 'string', 'max:2000'],
            'deadline'    => ['nullable', 'date'],
        ], [
            'product.required' => 'اكتب اسم الصنف.',
            'batch.required'   => 'اكتب رقم التشغيلة.',
        ]);
    }
}