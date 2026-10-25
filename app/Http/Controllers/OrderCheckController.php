<?php

namespace App\Http\Controllers;

use App\Models\FormRecord;
use App\Models\Product;
use App\Support\FormRegistry;
use App\Support\Settings;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * فحص دقة التحضير العشوائي (SH-004-F04): قائمة الأصناف للشاشة · تقرير الزونات والمحضرين الشهري
 */
class OrderCheckController extends Controller
{
    private const FORM = 'order-check';

    public const TYPES = [
        'item'   => 'صنف خطأ',
        'short'  => 'صلاحية منتهية أو قصيرة',
        'qty'    => 'كمية خطأ',
        'batch'  => 'تشغيلة خطأ',
        'exp'    => 'صلاحية مختلفة',
        'damage' => 'عبوة تالفة',
    ];

    /** الأصناف بالموقع (للشاشة) */
    public function products(): JsonResponse
    {
        $list = Product::query()->orderBy('name')->get(['id', 'name', 'location'])
            ->map(fn ($p) => ['i' => $p->id, 'n' => $p->name, 'l' => $p->location])
            ->values();

        return response()->json(['products' => $list]);
    }

    /** التقرير الشهري: الزونات · المحضرون · المراجعون · أنواع الأخطاء */
    public function index(Request $request): View
    {
        $tenantId = $request->user()->tenant_id;
        $set   = Settings::forTenant($tenantId);
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->query('month')) ? $request->query('month') : Carbon::today('Africa/Cairo')->format('Y-m');
        $train = (float) $set['oc_train'];
        $disc  = (float) $set['oc_disc'];

        $records = FormRecord::query()
            ->form(FormRegistry::get(self::FORM)['code'])
            ->where('status', FormRecord::STATUS_FINAL)
            ->orderByDesc('record_date')
            ->get();

        $lines = $this->lines($records, $set);
        $inMonth = $lines->filter(fn ($l) => str_starts_with((string) $l['date'], $month));
        $checks  = $records->filter(fn ($r) => str_starts_with((string) $r->record_date?->toDateString(), $month));

        $zones = $this->group($inMonth, 'zone')->map(function ($z) use ($train, $disc) {
            $z['flag'] = $z['pts'] >= $disc ? 'disc' : ($z['pts'] >= $train ? 'train' : 'ok');
            return $z;
        });
        $pickers = $this->group($inMonth->filter(fn ($l) => $l['picker'] !== ''), 'picker')->map(function ($p) use ($train, $disc) {
            $p['flag'] = $p['pts'] >= $disc ? 'disc' : ($p['pts'] >= $train ? 'train' : 'ok');
            return $p;
        });
        $checkers = $this->group($inMonth->filter(fn ($l) => $l['checker'] !== ''), 'checker');

        $types = collect(self::TYPES)->map(fn ($t, $k) => [
            'label' => $t,
            'count' => $inMonth->filter(fn ($l) => in_array($k, $l['errs'], true))->count(),
        ])->filter(fn ($t) => $t['count'] > 0)->sortByDesc('count');

        $all = $this->summary($inMonth);
        $all['checks'] = $checks->count();
        $all['orders'] = $inMonth->pluck('order')->unique()->count();

        // الاتجاه: آخر ٦ أشهر
        $trend = collect(range(5, 0))->map(function ($i) use ($lines) {
            $m = Carbon::today('Africa/Cairo')->startOfMonth()->subMonths($i)->format('Y-m');
            $s = $this->summary($lines->filter(fn ($l) => str_starts_with((string) $l['date'], $m)));
            return ['month' => $m] + $s;
        });

        return view('orderchecks.index', [
            'month'    => $month,
            'all'      => $all,
            'zones'    => $zones,
            'pickers'  => $pickers,
            'checkers' => $checkers,
            'types'    => $types,
            'trend'    => $trend,
            'checks'   => $checks,
            'target'   => (float) $set['oc_target'],
            'train'    => $train,
            'disc'     => $disc,
            'formUrl'  => route('forms.page', self::FORM),
        ]);
    }

    /* ======================================================= */

    /** كل الأصناف المفحوصة من الفحوصات المعتمدة */
    private function lines(Collection $records, array $set): Collection
    {
        $defaults = [
            'item' => $set['oc_w_item'], 'short' => $set['oc_w_short'], 'qty' => $set['oc_w_qty'],
            'batch' => $set['oc_w_batch'], 'exp' => $set['oc_w_exp'], 'damage' => $set['oc_w_damage'],
        ];

        return $records->flatMap(function (FormRecord $r) use ($defaults) {
            $d = $r->data ?? [];
            $w = ($d['weights'] ?? []) + $defaults;          // أوزان وقت الفحص
            $maxw = max(array_map('floatval', $w)) ?: 5;
            $pickers = $d['pickers'] ?? [];
            $date = $r->record_date?->toDateString();

            return collect($d['orders'] ?? [])->flatMap(fn ($o) => collect($o['lines'] ?? [])
                ->filter(fn ($l) => ($l['name'] ?? '') !== '' && ($l['res'] ?? '') !== '')
                ->map(function ($l) use ($o, $w, $maxw, $pickers, $date, $r) {
                    $errs = ($l['res'] === 'nc') ? array_values($l['errs'] ?? []) : [];
                    $pts = array_sum(array_map(fn ($k) => (float) ($w[$k] ?? 0), $errs));
                    $zone = trim((string) ($l['zone'] ?? '')) ?: '—';
                    return [
                        'record'  => $r->number,
                        'date'    => $date,
                        'order'   => $r->number . '|' . ($o['invoice'] ?? ''),
                        'zone'    => $zone,
                        'picker'  => trim((string) ($pickers[$zone] ?? '')),
                        'checker' => trim((string) ($o['checker'] ?? '')),
                        'bad'     => $l['res'] === 'nc',
                        'errs'    => $errs,
                        'pts'     => $pts,
                        'capped'  => min($pts, $maxw),
                        'maxw'    => $maxw,
                    ];
                }));
        })->values();
    }

    private function summary(Collection $lines): array
    {
        $n = $lines->count();
        $bad = $lines->where('bad', true)->count();
        $den = $lines->sum('maxw');

        return [
            'lines'   => $n,
            'bad'     => $bad,
            'pts'     => $lines->sum('pts'),
            'lineAcc' => $n ? round(($n - $bad) / $n * 100, 1) : null,
            'wAcc'    => $den ? round(100 - $lines->sum('capped') / $den * 100, 1) : null,
        ];
    }

    private function group(Collection $lines, string $key): Collection
    {
        return $lines->groupBy($key)->map(function ($g, $k) use ($key) {
            return ['name' => $k]
                + $this->summary($g)
                + [
                    'zones'   => $key !== 'zone' ? $g->pluck('zone')->unique()->sort()->implode('، ') : '',
                    'pickers' => $key === 'zone' ? $g->pluck('picker')->filter()->unique()->implode('، ') : '',
                    'types'   => collect($g->flatMap(fn ($l) => $l['errs']))->countBy()->sortDesc()
                        ->map(fn ($n, $t) => (self::TYPES[$t] ?? $t) . ' ' . $n)->implode(' · '),
                ];
        })->sortByDesc('pts')->values();
    }
}