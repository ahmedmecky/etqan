<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Support\Xlsx;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/**
 * بيانات المخزن الأساسية التي تُرفع من Excel: الأصناف · الصيدليات
 * كل رفع يستبدل القائمة بالكامل
 */
class MasterDataController extends Controller
{
    private const TYPES = [
        'products' => [
            'model'  => Product::class,
            'title'  => 'الأصناف',
            'single' => 'صنف',
            'cols'   => ['name' => 'اسم الصنف', 'price' => 'سعر الجمهور', 'location' => 'المنطقة / الموقع'],
            'order'  => 'name',
            'sample' => [['اسم الصنف', 'سعر الجمهور', 'المنطقة / الموقع'], ['بنادول اكسترا ٢٤ قرص', 42.5, 'A-01'], ['كونكور ٥ مجم ٣٠ قرص', 85, 'B-12']],
            'hint'   => 'العمود A اسم الصنف · B سعر الجمهور · C المنطقة / الموقع. الصيدلي يرى الاسم والسعر فقط.',
        ],
        'pharmacies' => [
            'model'  => Pharmacy::class,
            'title'  => 'الصيدليات (العملاء)',
            'single' => 'صيدلية',
            'cols'   => ['code' => 'كود الصيدلية', 'name' => 'اسم الصيدلية', 'address' => 'العنوان'],
            'order'  => 'code',
            'sample' => [['كود الصيدلية', 'اسم الصيدلية', 'العنوان'], ['1001', 'صيدلية الشفاء', 'سموحة · ش ١٤ مايو'], ['1002', 'صيدلية النور', 'العجمي · البيطاش']],
            'hint'   => 'العمود A كود الصيدلية (كما يُكتب على الفاتورة) · B اسم الصيدلية · C العنوان. الكود لا يتكرر.',
        ],
    ];

    public function index(Request $request, string $type): View
    {
        $t = $this->type($type);
        $q = trim((string) $request->query('q', ''));

        $query = $t['model']::query()
            ->when($q !== '', function ($w) use ($q, $t) {
                $w->where(function ($x) use ($q, $t) {
                    foreach (array_keys($t['cols']) as $col) {
                        if ($col !== 'price') {
                            $x->orWhere($col, 'like', "%{$q}%");
                        }
                    }
                });
            })
            ->orderBy($t['order']);

        return view('client.master', [
            'type'    => $type,
            't'       => $t,
            'q'       => $q,
            'rows'    => $query->paginate(50)->withQueryString(),
            'total'   => $t['model']::count(),
            'updated' => $t['model']::max('updated_at'),
        ]);
    }

    /** رفع ملف Excel يستبدل القائمة بالكامل */
    public function upload(Request $request, string $type): RedirectResponse
    {
        $t = $this->type($type);
        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'extensions:xlsx'],
        ], [
            'file.required'   => 'اختر ملف Excel.',
            'file.max'        => 'حجم الملف أكبر من ١٠ ميجابايت.',
            'file.extensions' => 'الملف يجب أن يكون Excel بصيغة .xlsx (احفظه من Excel باسم Excel Workbook).',
        ]);

        try {
            $rows = Xlsx::read($request->file('file')->getRealPath());
        } catch (Throwable $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        // الصف الأول عناوين إذا لم يكن بيانات صالحة
        if ($rows && $this->isHeader($type, $rows[0])) {
            array_shift($rows);
        }

        [$items, $skipped, $dups] = $this->parse($type, $rows);
        if (! $items) {
            return back()->withErrors(['file' => 'لا توجد صفوف صالحة في الملف. ' . $t['hint']]);
        }

        $tenantId = $request->user()->tenant_id;
        $now = now();
        DB::transaction(function () use ($t, $tenantId, $items, $now) {
            $t['model']::query()->delete();     // مقصورة على مخزن المستخدم (BelongsToTenant)
            foreach (array_chunk($items, 500) as $chunk) {
                $t['model']::insert(array_map(fn ($r) => $r + ['tenant_id' => $tenantId, 'created_at' => $now, 'updated_at' => $now], $chunk));
            }
        });

        $msg = 'تم رفع ' . count($items) . ' ' . $t['single'] . ' واستبدال القائمة السابقة.';
        if ($skipped) $msg .= " تم تجاهل {$skipped} صف غير مكتمل.";
        if ($dups)    $msg .= " تم دمج {$dups} كود مكرر (آخر صف هو المعتمد).";

        return redirect()->route('client.data.index', $type)->with('status', $msg);
    }

    /** إضافة صف واحد يدوياً */
    public function store(Request $request, string $type): RedirectResponse
    {
        $t = $this->type($type);
        $data = $request->validate($this->rules($type), $this->messages());
        $t['model']::create($data);

        return back()->with('status', 'تمت إضافة ' . $t['single'] . ': ' . $data['name']);
    }

    public function update(Request $request, string $type, int $id): RedirectResponse
    {
        $t = $this->type($type);
        $row = $t['model']::findOrFail($id);
        $data = $request->validate($this->rules($type, $id), $this->messages());
        $row->update($data);

        return back()->with('status', 'تم حفظ التعديل.');
    }

    public function destroy(string $type, int $id): RedirectResponse
    {
        $t = $this->type($type);
        $t['model']::findOrFail($id)->delete();

        return back()->with('status', 'تم الحذف.');
    }

    /** حذف القائمة كلها */
    public function clear(string $type): RedirectResponse
    {
        $t = $this->type($type);
        $n = $t['model']::query()->delete();

        return redirect()->route('client.data.index', $type)->with('status', "تم حذف القائمة ({$n}).");
    }

    /** قالب Excel فارغ بالأعمدة الصحيحة */
    public function template(string $type): BinaryFileResponse
    {
        $t = $this->type($type);
        $path = tempnam(sys_get_temp_dir(), 'etq') . '.xlsx';
        Xlsx::write($path, $t['sample'], $t['title']);

        return response()->download($path, "etqan-{$type}-template.xlsx")->deleteFileAfterSend();
    }

    /** تنزيل القائمة الحالية */
    public function export(string $type): BinaryFileResponse
    {
        $t = $this->type($type);
        $cols = array_keys($t['cols']);
        $rows = [array_values($t['cols'])];
        foreach ($t['model']::query()->orderBy($t['order'])->get() as $r) {
            $rows[] = array_map(fn ($c) => $c === 'price' && $r->$c !== null ? (float) $r->$c : (string) $r->$c, $cols);
        }
        $path = tempnam(sys_get_temp_dir(), 'etq') . '.xlsx';
        Xlsx::write($path, $rows, $t['title']);

        return response()->download($path, "etqan-{$type}-" . now()->format('Y-m-d') . '.xlsx')->deleteFileAfterSend();
    }

    /* ======================================================= */

    private function type(string $type): array
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type];
    }

    private function isHeader(string $type, array $row): bool
    {
        return $type === 'products'
            ? ! is_numeric(str_replace(',', '', (string) ($row[1] ?? '')))
            : ! preg_match('/\d/', (string) ($row[0] ?? ''));
    }

    /** تحويل صفوف Excel إلى بيانات · يرجع [الصفوف، المتجاهل، المكرر] */
    private function parse(string $type, array $rows): array
    {
        $items = [];
        $skipped = 0;
        $dups = 0;
        $clean = fn ($v, $max) => mb_substr(trim(preg_replace('/\s+/u', ' ', (string) $v)), 0, $max);

        foreach ($rows as $row) {
            if ($type === 'products') {
                $name  = $clean($row[0] ?? '', 200);
                $price = str_replace([',', ' '], '', (string) ($row[1] ?? ''));
                if ($name === '') { $skipped++; continue; }
                $items[] = [
                    'name'     => $name,
                    'price'    => is_numeric($price) ? round((float) $price, 2) : null,
                    'location' => $clean($row[2] ?? '', 100) ?: null,
                ];
            } else {
                $code = $clean($row[0] ?? '', 40);
                $name = $clean($row[1] ?? '', 200);
                if ($code === '' || $name === '') { $skipped++; continue; }
                if (isset($items[$code])) $dups++;
                $items[$code] = ['code' => $code, 'name' => $name, 'address' => $clean($row[2] ?? '', 255) ?: null];
            }
        }

        return [array_values($items), $skipped, $dups];
    }

    private function rules(string $type, ?int $id = null): array
    {
        $tenantId = auth()->user()->tenant_id;

        return $type === 'products'
            ? [
                'name'     => ['required', 'string', 'max:200'],
                'price'    => ['nullable', 'numeric', 'min:0', 'max:9999999'],
                'location' => ['nullable', 'string', 'max:100'],
            ]
            : [
                'code'    => ['required', 'string', 'max:40', Rule::unique('pharmacies', 'code')->where('tenant_id', $tenantId)->ignore($id)],
                'name'    => ['required', 'string', 'max:200'],
                'address' => ['nullable', 'string', 'max:255'],
            ];
    }

    private function messages(): array
    {
        return [
            'name.required' => 'الاسم مطلوب.',
            'code.required' => 'الكود مطلوب.',
            'code.unique'   => 'هذا الكود مسجل لصيدلية أخرى.',
            'price.numeric' => 'السعر يجب أن يكون رقماً.',
        ];
    }
}