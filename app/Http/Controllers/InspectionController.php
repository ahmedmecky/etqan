<?php

namespace App\Http\Controllers;

use App\Models\FormRecord;
use App\Support\FormRegistry;
use App\Support\InspectionBank;
use App\Support\QuickChecklist;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * التفتيش الذاتي (SH-012): بنك بنود الفحص · سجل التفتيش والتغطية السنوية
 */
class InspectionController extends Controller
{
    private const FORM = 'inspection';

    /** قائمة الإجراءات المتاحة للتفتيش */
    public function processes(): JsonResponse
    {
        return response()->json(['processes' => InspectionBank::processes()]);
    }

    /** بنود فحص إجراء واحد */
    public function bank(string $code): JsonResponse
    {
        abort_unless(isset(InspectionBank::BANK[$code]), 404);

        return response()->json(['items' => InspectionBank::BANK[$code]['items']]);
    }

    /** مناطق التفتيش السريع (موبايل) */
    public function quickAreas(): JsonResponse
    {
        $areas = array_map(fn ($a) => $a + ['sop_title' => InspectionBank::BANK[$a['sop']]['title'] ?? ''], QuickChecklist::areas());

        return response()->json(['areas' => $areas]);
    }

    /** بنود منطقة واحدة: [المتطلب، التصنيف الثابت] */
    public function quickItems(string $area): JsonResponse
    {
        abort_unless(isset(QuickChecklist::AREAS[$area]), 404);

        return response()->json(['items' => QuickChecklist::items($area)]);
    }

    /** سجل التفتيش والتغطية */
    public function index(Request $request): View
    {
        $today = Carbon::today('Africa/Cairo');
        $year  = (int) $request->query('year', $today->year);

        $records = FormRecord::query()
            ->form(FormRegistry::get(self::FORM)['code'])
            ->orderByDesc('record_date')
            ->orderByDesc('id')
            ->get();

        // حالة الانحرافات المرتبطة (لمعرفة حالات عدم المطابقة المفتوحة)
        $devCode = FormRegistry::get('deviation')['code'];
        $devs = FormRecord::query()->form($devCode)->get(['id', 'number', 'status', 'data'])
            ->keyBy(fn ($d) => strtoupper($d->number));

        $rows = $records->map(function (FormRecord $r) use ($devs) {
            $d = $r->data ?? [];
            $t = $d['totals'] ?? [];
            $nc = collect($d['items'] ?? [])->where('res', 'nc');
            $open = $nc->filter(function ($it) use ($devs) {
                $dev = $devs->get(strtoupper(trim((string) ($it['devNo'] ?? ''))));
                return ! $dev || ! $dev->isFinal();
            })->count();

            return [
                'id'      => $r->id,
                'number'  => $r->number,
                'date'    => $r->record_date?->toDateString(),
                'final'   => $r->isFinal(),
                'process' => $d['process'] ?? '',
                'title'   => $d['processTitle'] ?? '',
                'type'    => $d['type'] ?? 'planned',
                'dept'    => $d['dept'] ?? '',
                'lead'    => $d['lead'] ?? '',
                'score'   => $t['score'] ?? null,
                'crit'    => (int) ($t['crit'] ?? 0),
                'maj'     => (int) ($t['maj'] ?? 0),
                'min'     => (int) ($t['min'] ?? 0),
                'opp'     => (int) ($t['opp'] ?? 0),
                'open'    => $open,
                'follow'  => $d['followDate'] ?? null,
                'devs'    => $nc->pluck('devNo')->filter()->values()->all(),
            ];
        });

        // التغطية: آخر تفتيش معتمد لكل إجراء وموعد التفتيش التالي
        $final = $rows->where('final', true);
        $coverage = collect(InspectionBank::processes())->map(function ($title, $code) use ($final, $today, $year) {
            $mine  = $final->where('process', $code)->sortByDesc('date');
            $last  = $mine->first();
            $months = in_array($code, InspectionBank::HIGH_RISK, true) ? 6 : 12;
            $next  = $last ? Carbon::parse($last['date'])->addMonths($months)->toDateString() : null;
            $inYear = $mine->filter(fn ($r) => str_starts_with((string) $r['date'], (string) $year))
                ->map(fn ($r) => (int) substr($r['date'], 5, 2))->unique()->values()->all();

            return [
                'code'    => $code,
                'title'   => $title,
                'high'    => $months === 6,
                'last'    => $last['date'] ?? null,
                'score'   => $last['score'] ?? null,
                'number'  => $last['number'] ?? null,
                'next'    => $next,
                'overdue' => ! $last || $next < $today->toDateString(),
                'months'  => $inYear,
            ];
        })->values();

        $yearRows = $final->filter(fn ($r) => str_starts_with((string) $r['date'], (string) $year));
        $scored   = $yearRows->whereNotNull('score');

        $stats = [
            'count'    => $yearRows->count(),
            'avg'      => $scored->count() ? round($scored->avg('score')) : null,
            'crit'     => $yearRows->sum('crit'),
            'open'     => $final->sum('open'),
            'covered'  => $coverage->where('overdue', false)->count(),
            'total'    => $coverage->count(),
            'drafts'   => $rows->where('final', false)->count(),
        ];

        // تكرار نفس البند غير المطابق في أكثر من تفتيش لنفس الإجراء
        $repeats = $records->filter->isFinal()
            ->flatMap(fn ($r) => collect($r->data['items'] ?? [])->where('res', 'nc')
                ->map(fn ($it) => ($r->data['process'] ?? '') . ' · ' . mb_substr((string) ($it['q'] ?? ''), 0, 90)))
            ->countBy()->filter(fn ($n) => $n > 1)->sortDesc()->take(10);

        return view('inspections.index', [
            'rows'     => $rows,
            'coverage' => $coverage,
            'stats'    => $stats,
            'repeats'  => $repeats,
            'year'     => $year,
            'years'    => $rows->map(fn ($r) => (int) substr((string) $r['date'], 0, 4))->push($today->year)->filter()->unique()->sortDesc()->values(),
            'formUrl'  => route('forms.page', self::FORM),
            'devUrl'   => route('forms.page', 'deviation'),
            'today'    => $today->toDateString(),
        ]);
    }
}