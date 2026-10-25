<?php

namespace App\Http\Controllers;

use App\Models\FormRecord;
use App\Support\FormRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * سجل الانحرافات والإجراءات التصحيحية والوقائية (SH-008-F04)
 * يقرأ الانحرافات المحفوظة للمخزن ويحسب المتأخر منها
 */
class DeviationController extends Controller
{
    private const FORM = 'deviation';

    public const STAGES = [
        'draft'        => 'مسودة',
        'reported'     => 'بانتظار التصنيف',
        'open'         => 'التحقيق والخطة',
        'implementing' => 'قيد التنفيذ',
        'closed'       => 'مغلق',
    ];

    public const CLASSES = ['critical' => 'حرج', 'major' => 'رئيسي', 'minor' => 'ثانوي'];

    public function index(Request $request): View
    {
        $all = $this->rows();

        $tab    = (string) $request->query('tab', 'open');
        $cls    = (string) $request->query('cls', '');
        $dept   = (string) $request->query('dept', '');
        $source = (string) $request->query('source', '');
        $year   = (string) $request->query('year', '');
        $q      = trim((string) $request->query('q', ''));

        $rows = $all->filter(function ($r) use ($tab, $cls, $dept, $source, $year, $q) {
            if ($tab === 'open' && $r['stage'] === 'closed') return false;
            if ($tab === 'late' && ! $r['late']) return false;
            if ($tab === 'closed' && $r['stage'] !== 'closed') return false;
            if ($cls !== '' && $r['cls'] !== $cls) return false;
            if ($dept !== '' && $r['dept'] !== $dept) return false;
            if ($source !== '' && $r['source'] !== $source) return false;
            if ($year !== '' && substr((string) $r['date'], 0, 4) !== $year) return false;
            if ($q !== '' && mb_stripos($r['number'] . ' ' . $r['what'] . ' ' . $r['ref'] . ' ' . $r['root'], $q) === false) return false;
            return true;
        })->values();

        $open   = $all->where('stage', '!=', 'closed');
        $closed = $all->where('stage', 'closed');
        $acts   = $all->flatMap(fn ($r) => $r['acts_done_dates']);

        $stats = [
            'open'          => $open->count(),
            'critical_open' => $open->where('cls', 'critical')->count(),
            'late'          => $all->where('late', true)->count(),
            'closed'        => $closed->count(),
            'acts_on_time'  => $acts->count() ? round($acts->where('on_time', true)->count() / $acts->count() * 100) : null,
            'avg_close'     => $closed->whereNotNull('days_to_close')->count() ? round($closed->avg('days_to_close')) : null,
        ];

        // الأسباب المتكررة: نفس القسم ونفس المصدر أكثر من مرة خلال آخر ١٢ شهراً
        $since   = Carbon::today('Africa/Cairo')->subYear()->toDateString();
        $repeats = $all->filter(fn ($r) => $r['date'] >= $since && $r['dept'] && $r['source'])
            ->groupBy(fn ($r) => $r['dept'] . ' · ' . $r['source'])
            ->filter(fn ($g) => $g->count() > 1)
            ->map(fn ($g) => $g->count())
            ->sortDesc();

        return view('deviations.index', [
            'rows'    => $rows,
            'stats'   => $stats,
            'repeats' => $repeats,
            'stages'  => self::STAGES,
            'classes' => self::CLASSES,
            'depts'   => $all->pluck('dept')->filter()->unique()->sort()->values(),
            'sources' => $all->pluck('source')->filter()->unique()->sort()->values(),
            'years'   => $all->map(fn ($r) => substr((string) $r['date'], 0, 4))->filter()->unique()->sortDesc()->values(),
            'f'       => compact('tab', 'cls', 'dept', 'source', 'year', 'q'),
            'formUrl' => route('forms.page', self::FORM),
        ]);
    }

    /** الانحرافات المرتبطة بمستند (رقم الاستلام أو الجرد أو المحضر…) للشاشات */
    public function links(Request $request): JsonResponse
    {
        $ref = trim((string) $request->query('ref', ''));
        if ($ref === '') {
            return response()->json(['links' => []]);
        }
        $links = $this->rows()
            ->filter(fn ($r) => $r['ref'] !== '' && mb_strtolower(trim($r['ref'])) === mb_strtolower($ref))
            ->map(fn ($r) => [
                'number' => $r['number'],
                'stage'  => $r['stage'],
                'label'  => self::STAGES[$r['stage']] ?? $r['stage'],
                'cls'    => self::CLASSES[$r['cls']] ?? '',
                'late'   => $r['late'],
                'what'   => mb_substr($r['what'], 0, 120),
                'date'   => $r['date'],
            ])
            ->values();

        return response()->json(['links' => $links]);
    }

    /** كل الانحرافات مع المواعيد والمتأخر */
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
                $round = (int) ($d['round'] ?? 1);
                $acts  = collect($d['actions'] ?? [])
                    ->filter(fn ($a) => (int) ($a['round'] ?? 1) === $round && trim((string) ($a['text'] ?? '')) !== '')
                    ->values();

                $late = [];
                $planDue = $d['planDue'] ?? null;
                if (in_array($stage, ['reported', 'open'], true) && $planDue && $planDue < $today) {
                    $late[] = 'الخطة متأخرة ' . $this->days($planDue, $today) . ' يوم';
                }
                if ($stage === 'draft' && ($r->record_date?->toDateString() ?? $today) < $today) {
                    $late[] = 'لم يُرسل لإدارة الجودة';
                }
                $nextDue = null;
                if ($stage === 'implementing') {
                    foreach ($acts as $a) {
                        if (empty($a['done']) && ! empty($a['due'])) {
                            $nextDue = $nextDue === null || $a['due'] < $nextDue ? $a['due'] : $nextDue;
                            if ($a['due'] < $today) {
                                $late[] = 'إجراء متأخر ' . $this->days($a['due'], $today) . ' يوم';
                            }
                        }
                    }
                    $vd = $d['verifyDue'] ?? null;
                    if ($vd && empty($d['verifyDate']) && $vd < $today) {
                        $late[] = 'التحقق متأخر ' . $this->days($vd, $today) . ' يوم';
                    }
                }

                $closedAt = $r->finalized_at?->timezone('Africa/Cairo')->toDateString();
                $date     = $r->record_date?->toDateString();

                return [
                    'id'       => $r->id,
                    'number'   => $r->number,
                    'date'     => $date,
                    'dept'     => $d['dept'] ?? '',
                    'source'   => $d['source'] ?? '',
                    'ref'      => $d['ref'] ?? '',
                    'what'     => trim((string) ($d['what'] ?? '')),
                    'root'     => trim((string) ($d['rootCause'] ?? '')),
                    'cls'      => $d['cls'] ?? '',
                    'stage'    => $stage,
                    'round'    => $round,
                    'plan_due' => $planDue,
                    'next_due' => $nextDue,
                    'verify'   => $d['verifyDue'] ?? null,
                    'acts'     => $acts->count(),
                    'acts_done'=> $acts->filter(fn ($a) => ! empty($a['done']))->count(),
                    'acts_done_dates' => $acts->filter(fn ($a) => ! empty($a['done']) && ! empty($a['due']))
                        ->map(fn ($a) => ['on_time' => $a['done'] <= $a['due']])->values(),
                    'late'     => (bool) $late,
                    'late_txt' => $late,
                    'closed_at'=> $stage === 'closed' ? $closedAt : null,
                    'days_to_close' => $stage === 'closed' && $date && $closedAt ? $this->days($date, $closedAt) : null,
                ];
            });
    }

    private function days(string $from, string $to): int
    {
        return (int) Carbon::parse($from)->diffInDays(Carbon::parse($to));
    }
}