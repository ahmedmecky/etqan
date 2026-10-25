<?php

namespace App\Http\Controllers;

use App\Models\FormRecord;
use App\Support\FormRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * سجل الشكاوى وتحليل الاتجاهات (SH-007-F03)
 */
class ComplaintController extends Controller
{
    private const FORM = 'complaint';

    public const STAGES = [
        'draft'         => 'مسودة',
        'reported'      => 'بانتظار التصنيف',
        'investigating' => 'قيد التحقيق',
        'closed'        => 'مغلقة',
    ];

    public const PRIORITIES = ['critical' => 'حرجة', 'major' => 'رئيسية', 'minor' => 'ثانوية'];

    public function index(Request $request): View
    {
        $all = $this->rows();

        $tab  = (string) $request->query('tab', 'open');
        $prio = (string) $request->query('prio', '');
        $type = (string) $request->query('type', '');
        $year = (string) $request->query('year', '');
        $q    = trim((string) $request->query('q', ''));
        $src  = (string) $request->query('src', '');

        $rows = $all->filter(function ($r) use ($tab, $prio, $type, $year, $q, $src) {
            if ($src === 'portal' && ! $r['portal']) return false;
            if ($src === 'staff' && $r['portal']) return false;
            if ($tab === 'open' && $r['stage'] === 'closed') return false;
            if ($tab === 'late' && ! $r['late']) return false;
            if ($tab === 'closed' && $r['stage'] !== 'closed') return false;
            if ($prio !== '' && $r['prio'] !== $prio) return false;
            if ($type !== '' && $r['type'] !== $type) return false;
            if ($year !== '' && substr((string) $r['date'], 0, 4) !== $year) return false;
            if ($q !== '' && mb_stripos($r['number'] . ' ' . $r['customer'] . ' ' . $r['products'] . ' ' . $r['details'], $q) === false) return false;
            return true;
        })->values();

        $open   = $all->where('stage', '!=', 'closed');
        $closed = $all->where('stage', 'closed');
        $timed  = $closed->filter(fn ($r) => $r['due'] && $r['closed_at']);

        $stats = [
            'open'          => $open->count(),
            'portal_new'    => $all->where('portal', true)->where('stage', 'reported')->count(),
            'critical_open' => $open->where('prio', 'critical')->count(),
            'late'          => $all->where('late', true)->count(),
            'closed'        => $closed->count(),
            'on_time'       => $timed->count() ? round($timed->filter(fn ($r) => $r['closed_at'] <= $r['due'])->count() / $timed->count() * 100) : null,
            'unsatisfied'   => $closed->where('sat', 'N')->count(),
        ];

        // الاتجاهات: آخر ٦ أشهر حسب النوع
        $months = collect(range(5, 0))->map(fn ($i) => Carbon::today('Africa/Cairo')->startOfMonth()->subMonths($i)->format('Y-m'));
        $types  = $all->pluck('type')->filter()->unique()->sort()->values();
        $trend  = $types->mapWithKeys(fn ($t) => [$t => $months->mapWithKeys(fn ($m) => [
            $m => $all->filter(fn ($r) => $r['type'] === $t && str_starts_with((string) $r['date'], $m))->count(),
        ])]);
        $monthTotals = $months->mapWithKeys(fn ($m) => [$m => $all->filter(fn ($r) => str_starts_with((string) $r['date'], $m))->count()]);

        // التكرار خلال ١٢ شهراً: نفس العميل · نفس الصنف · نفس القسم
        $since  = Carbon::today('Africa/Cairo')->subYear()->toDateString();
        $recent = $all->filter(fn ($r) => $r['date'] >= $since);
        $repeat = fn (string $key) => $recent->flatMap(fn ($r) => (array) $r[$key])->filter()
            ->countBy()->filter(fn ($n) => $n > 1)->sortDesc()->take(8);

        return view('complaints.index', [
            'rows'        => $rows,
            'stats'       => $stats,
            'months'      => $months,
            'trend'       => $trend,
            'monthTotals' => $monthTotals,
            'repeats'     => [
                'العملاء'  => $repeat('customer'),
                'الأصناف'  => $repeat('product_list'),
                'الأقسام'  => $repeat('dept'),
            ],
            'stages'     => self::STAGES,
            'priorities' => self::PRIORITIES,
            'types'      => $types,
            'years'      => $all->map(fn ($r) => substr((string) $r['date'], 0, 4))->filter()->unique()->sortDesc()->values(),
            'f'          => compact('tab', 'prio', 'type', 'year', 'q', 'src'),
            'formUrl'    => route('forms.page', self::FORM),
        ]);
    }

    private function rows(): Collection
    {
        $code  = FormRegistry::get(self::FORM)['code'];
        $today = Carbon::today('Africa/Cairo')->toDateString();

        return FormRecord::query()
            ->form($code)
            ->orderByDesc('record_date')
            ->orderByDesc('id')
            ->get()
            ->map(function (FormRecord $r) use ($today) {
                $d     = $r->data ?? [];
                $stage = $r->isFinal() ? 'closed' : ($d['stage'] ?? 'draft');
                $date  = $r->record_date?->toDateString();
                $due   = $d['closeDue'] ?? null;
                $prods = collect($d['prods'] ?? [])->filter(fn ($p) => trim((string) ($p['name'] ?? '')) !== '');

                $late = [];
                if ($stage === 'draft' && $date && $date < $today) {
                    $late[] = 'لم تُحوّل للجودة';
                }
                if ($stage !== 'closed' && $due && $due < $today) {
                    $late[] = 'تجاوزت موعد الإغلاق ' . $this->days($due, $today) . ' يوم';
                }

                $closedAt = $stage === 'closed' ? $r->finalized_at?->timezone('Africa/Cairo')->toDateString() : null;

                return [
                    'id'           => $r->id,
                    'number'       => $r->number,
                    'date'         => $date,
                    'customer'     => trim((string) ($d['customer'] ?? '')),
                    'cust_type'    => $d['custType'] ?? '',
                    'details'      => trim((string) ($d['details'] ?? '')),
                    'type'         => $d['type'] ?? '',
                    'prio'         => $d['prio'] ?? '',
                    'dept'         => $d['dept'] ?? '',
                    'owner'        => $d['owner'] ?? '',
                    'products'     => $prods->map(fn ($p) => trim($p['name'] . ' ' . ($p['batch'] ?? '')))->implode('، '),
                    'product_list' => $prods->pluck('name')->map(fn ($n) => trim($n))->unique()->values()->all(),
                    'due'          => $due,
                    'closed_at'    => $closedAt,
                    'dev'          => ! empty($d['capa']) ? ($d['devNo'] ?? '') : '',
                    'sat'          => $d['sat'] ?? '',
                    'stage'        => $stage,
                    'portal'       => ($d['source'] ?? '') === 'portal',
                    'mobile'       => $d['portal']['mobile'] ?? ($d['phone'] ?? ''),
                    'late'         => (bool) $late,
                    'late_txt'     => $late,
                ];
            });
    }

    private function days(string $from, string $to): int
    {
        return (int) Carbon::parse($from)->diffInDays(Carbon::parse($to));
    }
}