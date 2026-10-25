<?php

namespace App\Http\Controllers;

use App\Models\FormRecord;
use App\Models\User;
use App\Support\FormRegistry;
use App\Support\KpiSets;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * تقييم الأداء الشهري: مجموعات المؤشرات · السجل الشهري · ما ينتظر توقيعي
 */
class KpiController extends Controller
{
    private const FORM = 'kpi';

    /** مجموعات المؤشرات والمقياس (للشاشة) */
    public function sets(): JsonResponse
    {
        return response()->json([
            'sets'  => KpiSets::SETS,
            'bands' => KpiSets::BANDS,
            'cap'   => KpiSets::GATE_CAP,
            'low'   => KpiSets::LOW_FLAG,
            'plan'  => KpiSets::PLAN_LIMIT,
        ]);
    }

    /** السجل الشهري */
    public function index(Request $request): View
    {
        $user  = $request->user();
        $form  = FormRegistry::get(self::FORM);
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->query('month')) ? $request->query('month') : Carbon::today('Africa/Cairo')->subMonthNoOverflow()->format('Y-m');
        $job   = (string) $request->query('job', '');
        $all   = $user->isClientAdmin() || FormRegistry::canFinalize($form, $user);

        $records = self::visible(FormRecord::query()->form($form['code']), $user, $all)
            ->whereBetween('record_date', [$month . '-01', Carbon::parse($month . '-01')->endOfMonth()->toDateString()])
            ->orderBy('title')
            ->get();

        $rows = $records->map(function (FormRecord $r) {
            $d = $r->data ?? [];
            $res = $d['result'] ?? null;
            $stage = $r->isFinal() ? 'final' : ($d['stage'] ?? 'draft');

            return [
                'number'   => $r->number,
                'emp'      => $d['empName'] ?? '',
                'code'     => $d['empCode'] ?? '',
                'job'      => $d['job'] ?? '',
                'jobTitle' => $d['jobTitle'] ?? (KpiSets::SETS[$d['job'] ?? '']['title'] ?? ''),
                'variant'  => KpiSets::SETS[$d['job'] ?? '']['variants'][$d['variant'] ?? '']['name'] ?? '',
                'evaluator'=> $d['evaluator'] ?? '',
                'score'    => $res['final'] ?? null,
                'raw'      => $res['raw'] ?? null,
                'rating'   => $res['rating'] ?? null,
                'low'      => $res['low'] ?? [],
                'gates'    => (int) ($res['gates'] ?? 0),
                'stage'    => $stage,
                'paper'    => ! empty($d['sign']['emp']['paper']),
            ];
        })->when($job !== '', fn ($c) => $c->where('job', $job))->values();

        $scored = $rows->whereNotNull('score');
        $bands = collect(KpiSets::BANDS)->map(fn ($b) => $b + [
            'count' => $scored->filter(fn ($r) => self::band($r['score'])['name'] === $b['name'])->count(),
        ]);
        $byJob = $scored->groupBy('job')->map(fn ($g, $k) => [
            'title' => KpiSets::SETS[$k]['title'] ?? $k,
            'count' => $g->count(),
            'avg'   => round($g->avg('score') * 100, 1),
            'min'   => round($g->min('score') * 100, 1),
        ])->sortBy('avg');

        return view('kpi.index', [
            'rows'    => $rows,
            'month'   => $month,
            'job'     => $job,
            'jobs'    => KpiSets::jobs(),
            'bands'   => $bands,
            'byJob'   => $byJob,
            'all'     => $all,
            'stats'   => [
                'count'   => $rows->count(),
                'final'   => $rows->where('stage', 'final')->count(),
                'pending' => $rows->where('stage', '!=', 'final')->count(),
                'avg'     => $scored->count() ? round($scored->avg('score') * 100, 1) : null,
                'weak'    => $scored->filter(fn ($r) => $r['score'] < KpiSets::PLAN_LIMIT || $r['gates'])->count(),
            ],
            'formUrl' => route('forms.page', self::FORM),
        ]);
    }

    /** تقييمات تنتظر المستخدم: توقيع الموظف · اعتماد الموارد البشرية */
    public static function pendingFor(User $user): array
    {
        if (! $user->tenant_id) {
            return ['emp' => 0, 'hr' => 0];
        }
        $form = FormRegistry::get(self::FORM);
        $q = fn () => FormRecord::query()->form($form['code'])->where('status', FormRecord::STATUS_DRAFT);

        return [
            'emp' => $q()->where('data->stage', 'mgr')->where('data->empId', $user->id)->count(),
            'hr'  => FormRegistry::canFinalize($form, $user) ? $q()->where('data->stage', 'emp')->count() : 0,
        ];
    }

    public static function band(?float $s): array
    {
        foreach (KpiSets::BANDS as $b) {
            if ($s !== null && $s >= $b['min']) {
                return $b;
            }
        }

        return ['name' => '—', 'cls' => 'b-draft', 'color' => '#888'];
    }

    private static function visible(Builder $q, User $user, bool $all): Builder
    {
        return $all ? $q : $q->where(fn ($w) => $w->where('created_by', $user->id)
            ->orWhere('data->empId', $user->id)
            ->orWhere('data->evaluatorId', $user->id));
    }
}