<?php

namespace App\Http\Controllers;

use App\Models\FormRecord;
use App\Models\User;
use App\Support\FormRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * التدريب (SH-015): تقرير الدورات والمحاضرين · سجل تدريب الموظف (F05)
 * يقرأ محاضر التدريب SH-015-F03 المحفوظة في form_records (الشاشة training)
 */
class TrainingController extends Controller
{
    private const FORM = 'training';

    public const TYPES = [
        'induction' => 'تعريفي', 'ojt' => 'على رأس العمل', 'refresher' => 'تنشيطي',
        'corrective' => 'تصحيحي', 'newsop' => 'إجراء جديد / معدل', 'external' => 'خارجي / دورة',
    ];

    public const STAGES = ['draft' => 'مسودة', 'held' => 'بانتظار الاعتماد', 'approved' => 'متابعة الفاعلية', 'final' => 'مغلق'];
    public const EFF = ['effective' => 'فعّال', 'partial' => 'فعّال جزئياً', 'not' => 'غير فعّال'];

    /** كل محاضر التدريب بصيغة موحدة */
    private static function sessions(): Collection
    {
        $today = Carbon::today('Africa/Cairo')->toDateString();

        return FormRecord::query()->form(FormRegistry::get(self::FORM)['code'])
            ->orderByDesc('record_date')->orderByDesc('id')->get()
            ->map(function (FormRecord $r) use ($today) {
                $d = $r->data ?? [];
                $stage = $r->status === FormRecord::STATUS_FINAL ? 'final' : ($d['stage'] ?? 'draft');
                $att = collect($d['attendees'] ?? [])->filter(fn ($a) => trim((string) ($a['name'] ?? '')) !== '')->values();
                $present = $att->filter(fn ($a) => ! empty($a['present']));
                $hours = (float) ($d['hours'] ?? 0);

                return [
                    'id' => $r->id, 'number' => $r->number, 'date' => $r->record_date?->toDateString(),
                    'topic' => $d['topic'] ?? $r->title, 'type' => $d['type'] ?? '', 'ref' => $d['ref'] ?? '',
                    'trigger' => $d['trigger'] ?? '', 'triggerRef' => $d['triggerRef'] ?? '',
                    'trainer' => trim((string) ($d['trainer'] ?? '')), 'external' => ($d['trType'] ?? '') === 'external', 'trainerJob' => $d['trainerJob'] ?? '',
                    'hours' => $hours, 'stage' => $stage, 'held' => $stage !== 'draft',
                    'attendees' => $att, 'present' => $present->count(),
                    'pass' => $present->where('result', 'pass')->count(), 'fail' => $present->where('result', 'fail')->count(),
                    'manHours' => $hours * $present->count(),
                    'effDue' => $d['effDue'] ?? null, 'eff' => $d['effResult'] ?? '',
                    'effLate' => $stage === 'approved' && ! empty($d['effDue']) && $d['effDue'] < $today,
                ];
            });
    }

    /** تقرير الدورات والمحاضرين */
    public function index(Request $request): View
    {
        $all = self::sessions();
        $years = $all->map(fn ($s) => substr((string) $s['date'], 0, 4))->filter()->unique()->sortDesc()->values();
        $year = preg_match('/^\d{4}$/', (string) $request->query('year')) ? $request->query('year') : Carbon::today('Africa/Cairo')->format('Y');
        $type = (string) $request->query('type', '');
        $rows = $all->filter(fn ($s) => str_starts_with((string) $s['date'], $year) && ($type === '' || $s['type'] === $type))->values();
        $held = $rows->where('held', true);
        $present = $held->sum('present');

        $trainers = $held->groupBy(fn ($s) => $s['trainer'] ?: '—')->map(fn ($g, $name) => [
            'name' => $name, 'external' => $g->contains('external', true), 'job' => $g->first()['trainerJob'],
            'sessions' => $g->count(), 'hours' => $g->sum('hours'), 'trainees' => $g->sum('present'),
            'pass' => $g->sum('present') ? round($g->sum('pass') / $g->sum('present') * 100) : null,
            'notEff' => $g->whereIn('eff', ['not', 'partial'])->count(),
        ])->sortByDesc('sessions')->values();

        $byType = collect(self::TYPES)->map(fn ($label, $k) => ['label' => $label, 'n' => $held->where('type', $k)->count(), 'hours' => $held->where('type', $k)->sum('manHours')]);
        $months = collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => $held->filter(fn ($s) => (int) substr((string) $s['date'], 5, 2) === $m)->count()]);

        return view('training.index', [
            'rows' => $rows, 'year' => $year, 'years' => $years->contains($year) ? $years : $years->push($year)->sortDesc()->values(),
            'type' => $type, 'types' => self::TYPES, 'stages' => self::STAGES, 'effs' => self::EFF,
            'stats' => [
                'sessions' => $held->count(),
                'manHours' => $held->sum('manHours'),
                'trainees' => $held->flatMap(fn ($s) => $s['attendees']->filter(fn ($a) => ! empty($a['present']))->map(fn ($a) => $a['userId'] ?? $a['name']))->unique()->count(),
                'pass' => $present ? round($held->sum('pass') / $present * 100) : null,
                'fail' => $held->sum('fail'),
                'effLate' => $rows->where('effLate', true)->count(),
                'pending' => $rows->where('stage', 'held')->count(),
                'effective' => $rows->where('stage', 'final')->count() ? round($rows->where('eff', 'effective')->count() / $rows->where('stage', 'final')->count() * 100) : null,
            ],
            'trainers' => $trainers, 'byType' => $byType, 'months' => $months,
            'formUrl' => route('forms.page', self::FORM),
        ]);
    }

    /** سجل تدريب الموظف (SH-015-F05) */
    public function employee(Request $request): View
    {
        $user = $request->user();
        $users = User::query()->with('role')->where('tenant_id', $user->tenant_id)->get()->sortBy(fn ($x) => $x->displayName())->values();
        $emp = $users->firstWhere('id', (int) $request->query('user')) ?? null;
        $name = trim((string) $request->query('name', ''));

        $list = collect();
        if ($emp || $name !== '') {
            $list = self::sessions()->where('held', true)->map(function ($s) use ($emp, $name) {
                $a = $s['attendees']->first(fn ($a) => $emp ? ((int) ($a['userId'] ?? 0) === $emp->id || ($a['name'] ?? '') === $emp->displayName())
                    : mb_strtolower(trim($a['name'] ?? '')) === mb_strtolower($name));

                return $a ? $s + ['me' => $a] : null;
            })->filter()->values();
        }

        return view('training.employee', [
            'users' => $users, 'emp' => $emp, 'name' => $name, 'list' => $list, 'types' => self::TYPES,
            'stages' => self::STAGES, 'effs' => self::EFF, 'formUrl' => route('forms.page', self::FORM),
            'tenant' => $user->tenant,
            'totals' => ['n' => $list->count(), 'hours' => $list->sum(fn ($s) => ! empty($s['me']['present']) ? $s['hours'] : 0),
                'fail' => $list->filter(fn ($s) => ($s['me']['result'] ?? '') === 'fail')->count()],
        ]);
    }

    /** تنبيهات الرئيسية: [بانتظار اعتمادي, متابعة فاعلية متأخرة, متدربون لم يجتازوا] */
    public static function alerts(User $u): array
    {
        $f = FormRegistry::get(self::FORM);
        $canApprove = FormRegistry::stepRights($f, $u)['approve'] ?? false;
        $canFinal = FormRegistry::canFinalize($f, $u);
        if (! $u->tenant_id || (! $canApprove && ! $canFinal)) {
            return ['pending' => 0, 'effLate' => 0, 'fail' => 0];
        }
        $s = self::sessions();

        return [
            'pending' => $canApprove ? $s->where('stage', 'held')->count() : 0,
            'effLate' => $canFinal ? $s->where('effLate', true)->count() : 0,
            'fail' => $s->whereIn('stage', ['held', 'approved'])->sum('fail'),
        ];
    }

    /** أعداد للبطاقات: [تدريبات هذا العام, ساعات تدريب هذا العام] */
    public static function counts(): array
    {
        $y = Carbon::today('Africa/Cairo')->format('Y');
        $s = self::sessions()->filter(fn ($x) => $x['held'] && str_starts_with((string) $x['date'], $y));

        return [$s->count(), $s->sum('manHours'), $s->pluck('trainer')->filter()->unique()->count()];
    }
}