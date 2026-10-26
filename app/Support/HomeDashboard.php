<?php

namespace App\Support;

use App\Http\Controllers\KpiController;
use App\Http\Controllers\TemperatureController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\FieldInspectionController;
use App\Models\DecisionStatus;
use App\Models\Document;
use App\Models\DocumentLog;
use App\Models\DocumentVersion;
use App\Models\FormRecord;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Models\TempPoint;
use App\Models\TempReading;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * بيانات الصفحة الرئيسية (بوابة الجودة والامتثال)
 *   المؤشرات الأربعة · أقسام البطاقات بأعدادها · «يتطلب انتباهك» · «آخر الأنشطة»
 */
class HomeDashboard
{
    private const TZ = 'Africa/Cairo';

    /** الشاشات التي تفتح السجل برقمه من الرابط (?open=NUMBER) */
    private const OPEN_BY_NUMBER = ['receiving', 'inventory-count', 'meeting-minutes', 'deviation', 'training'];

    private User $u;
    private Carbon $today;

    public static function for(User $user): array
    {
        return (new self($user))->build();
    }

    private function __construct(User $user)
    {
        $this->u = $user;
        $this->today = Carbon::today(self::TZ);
    }

    private function code(string $key): string
    {
        return FormRegistry::get($key)['code'];
    }

    private function records(string $key)
    {
        return FormRecord::query()->form($this->code($key));
    }

    private function build(): array
    {
        $y = $this->today->format('Y');
        $m = $this->today->format('Y-m');

        /* ---------- الأعداد ---------- */
        $devs = $this->records('deviation')->get(['id', 'status', 'data', 'record_date']);
        $devOpen = $devs->where('status', FormRecord::STATUS_DRAFT);
        $devLate = $devOpen->filter(fn ($r) => $this->deviationLate($r->data ?? []))->count();

        $cmpOpen = $this->records('complaint')->where('status', FormRecord::STATUS_DRAFT)->count();
        $cmpYear = $this->records('complaint')->whereYear('record_date', $y)->count();
        $siYear  = $this->records('inspection')->whereYear('record_date', $y)->count();
        $rcvMon  = $this->records('receiving')->where('record_date', 'like', $m . '%')->count();
        $invAll  = $this->records('inventory-count')->count();
        $mmAll   = $this->records('meeting-minutes')->count();
        $ocMon   = $this->records('order-check')->where('record_date', 'like', $m . '%')->count();
        $kpiMon  = $this->records('kpi')->where('record_date', 'like', $m . '%')->count();
        $teAll   = $this->records('temp-excursion')->count();
        $teOpen  = $this->records('temp-excursion')->where('status', FormRecord::STATUS_DRAFT)->count();
        $points  = TempPoint::query()->where('is_active', true)->count();

        $docs        = Document::query()->where('is_active', true)->get(['id', 'type', 'current_version_id']);
        $docEff      = $docs->whereNotNull('current_version_id')->count();
        $sopEff      = $docs->where('type', 'sop')->whereNotNull('current_version_id')->count();
        $docPending  = DocumentVersion::query()->whereIn('stage', ['qa', 'hr', 'ceo'])->count();

        [$decOpen, $decLate] = $this->decisions();
        [$trN, $trH, $trT] = TrainingController::counts();
        $trAl = TrainingController::alerts($this->u);

        /* ---------- يتطلب انتباهك ---------- */
        $alerts = $this->alerts($devLate, $decLate, $trAl);
        $tasks = $alerts->sum('n');

        /* ---------- نسبة الالتزام ---------- */
        $parts = array_filter([
            'الانحرافات في موعدها'     => $devOpen->count() ? (1 - $devLate / $devOpen->count()) * 100 : null,
            'قراءات الحرارة المسجلة'   => $this->tempCompletion(),
            'مطابقة التفتيش الذاتي'    => $this->inspectionConformity($y),
        ], fn ($v) => $v !== null);
        $compliance = $parts ? (int) round(array_sum($parts) / count($parts)) : null;

        $n = fn ($v) => number_format((int) $v);

        /* ---------- الأقسام والبطاقات ---------- */
        $sections = [
            [
                'key' => 'quality', 'title' => 'إدارة الجودة والامتثال', 'icon' => 'shield', 'cols' => 4,
                'cards' => [
                    ['title' => 'الإجراءات التصحيحية والوقائية', 'desc' => 'متابعة وتنفيذ الانحرافات والإجراءات التصحيحية والوقائية', 'icon' => 'shield', 'tone' => 'teal',
                        'm' => 'deviation', 'url' => route('forms.page', 'deviation'), 'count' => $n($devOpen->count()) . ' مفتوح', 'warn' => $devLate ? $devLate . ' متأخر' : null,
                        'links' => [['سجل الانحرافات', route('deviations.index')]], 'code' => 'SH-008'],
                    ['title' => 'التفتيش الذاتي', 'desc' => 'قوائم التفتيش وتقارير المراجعة الذاتية للمخزن', 'icon' => 'search', 'tone' => 'blue',
                        'm' => 'inspection', 'url' => route('forms.page', 'inspection'), 'count' => $n($siYear) . ' تقرير هذا العام',
                        'links' => [['تفتيش سريع', route('forms.page', 'inspection-quick')], ['التفتيش الميداني', route('field.index')], ['التغطية', route('inspections.index')]], 'code' => 'SH-012'],
                    ['title' => 'مراجعة الوثائق', 'desc' => 'مراجعة واعتماد الإجراءات والنماذج والأوصاف الوظيفية', 'icon' => 'file', 'tone' => 'blue',
                        'm' => 'documents', 'url' => route('documents.queue'), 'count' => $n($docPending) . ' قيد المراجعة', 'code' => 'SH-014'],
                    ['title' => 'سجل الوثائق', 'desc' => 'أرشيف الوثائق المعتمدة والسارية وإصداراتها', 'icon' => 'folder', 'tone' => 'green',
                        'url' => route('documents.index'), 'count' => $n($docEff) . ' وثيقة سارية', 'code' => 'SH-014'],
                ],
            ],
            [
                'key' => 'ops', 'title' => 'العمليات والمخازن', 'icon' => 'box', 'cols' => 4,
                'cards' => [
                    ['title' => 'استلام الشحنات', 'desc' => 'فحص الشحنات الواردة ومطابقتها مع المستندات وقرار الإفراج', 'icon' => 'truck', 'tone' => 'teal',
                        'm' => 'receiving', 'url' => route('forms.page', 'receiving'), 'count' => $n($rcvMon) . ' شحنة هذا الشهر', 'code' => 'SH-001'],
                    ['title' => 'قراءات الحرارة', 'desc' => 'متابعة درجات الحرارة والرطوبة في المخازن والثلاجات', 'icon' => 'thermo', 'tone' => 'blue',
                        'm' => 'temperature', 'url' => route('temperature.round'), 'count' => $n($points) . ' موقع مراقبة',
                        'links' => [['التقرير الشهري', route('temperature.report')]], 'code' => 'SH-030'],
                    ['title' => 'تقرير انحراف الحرارة', 'desc' => 'تسجيل ومراجعة انحرافات درجات الحرارة والأصناف المتأثرة', 'icon' => 'alert', 'tone' => 'red',
                        'm' => 'temperature', 'url' => route('forms.page', 'temp-excursion'), 'count' => $n($teAll) . ' تقرير', 'warn' => $teOpen ? $teOpen . ' مفتوح' : null, 'code' => 'SH-030-F03'],
                    ['title' => 'محاضر الجرد', 'desc' => 'محاضر الجرد الدوري والعشوائي والسنوي للمخزون', 'icon' => 'clipboard', 'tone' => 'green',
                        'm' => 'inventory', 'url' => route('forms.page', 'inventory-count'), 'count' => $n($invAll) . ' محضر', 'code' => 'SH-016'],
                ],
            ],
            [
                'key' => 'perf', 'title' => 'الأداء والتحسين', 'icon' => 'chart', 'cols' => 4,
                'cards' => [
                    ['title' => 'متابعة القرارات', 'desc' => 'متابعة تنفيذ قرارات الاجتماعات المعتمدة', 'icon' => 'gear', 'tone' => 'teal',
                        'm' => 'meetings', 'url' => route('decisions.index'), 'count' => $n($decOpen) . ' قرار مفتوح', 'warn' => $decLate ? $decLate . ' متأخر' : null, 'code' => 'SH-034'],
                    ['title' => 'شكاوى العملاء', 'desc' => 'استلام الشكاوى وتصنيفها والتحقيق والرد عليها', 'icon' => 'users', 'tone' => 'purple',
                        'm' => 'complaint', 'url' => route('forms.page', 'complaint'), 'count' => $n($cmpOpen) . ' شكوى مفتوحة',
                        'links' => [['سجل الشكاوى · ' . $n($cmpYear) . ' هذا العام', route('complaints.index')]], 'code' => 'SH-007'],
                    ['title' => 'فحص دقة التحضير', 'desc' => 'عينة عشوائية من الطلبيات قبل الخروج · الدقة حسب الزون والمحضر', 'icon' => 'check', 'tone' => 'orange',
                        'm' => 'ordercheck', 'url' => route('forms.page', 'order-check'), 'count' => $n($ocMon) . ' فحص هذا الشهر',
                        'links' => [['تقرير الزونات', route('orderchecks.index')]], 'code' => 'SH-004'],
                    ['title' => 'تقييم الأداء الشهري', 'desc' => 'تقييم أداء الموظفين شهرياً بمؤشرات كل وظيفة', 'icon' => 'target', 'tone' => 'green',
                        'm' => 'kpi', 'url' => route('forms.page', 'kpi'), 'count' => $n($kpiMon) . ' تقييم هذا الشهر',
                        'links' => [['سجل التقييمات', route('kpi.index')]], 'code' => 'KPI'],
                ],
            ],
            [
                'key' => 'people', 'title' => 'الموارد والتدريب', 'icon' => 'cap', 'cols' => \App\Http\Controllers\JobController::canManage($this->u) ? 4 : 3,
                'cards' => [
                    ['title' => 'تدريب الموظفين', 'desc' => 'محضر التدريب: المتدربون بالاسم · التقييم · متابعة الفاعلية', 'icon' => 'user', 'tone' => 'blue',
                        'm' => 'training', 'url' => route('forms.page', 'training'), 'count' => $n($trN) . ' تدريب هذا العام', 'warn' => $trAl['fail'] ? $trAl['fail'] . ' لم يجتز' : null,
                        'links' => [['سجل تدريب موظف', route('training.employee')]], 'code' => 'SH-015-F03'],
                    ['title' => 'تقرير الدورات والمحاضرين', 'desc' => 'الساعات ونسبة الاجتياز والفاعلية لكل مدرب ونوع', 'icon' => 'calendar', 'tone' => 'blue',
                        'm' => 'training', 'url' => route('training.index'), 'count' => $n($trH) . ' ساعة تدريب · ' . $n($trT) . ' مدرب', 'code' => 'SH-015'],
                    ['title' => 'محاضر الاجتماعات', 'desc' => 'محاضر اجتماعات الإدارة واللجان والأقسام', 'icon' => 'people', 'tone' => 'green',
                        'm' => 'meetings', 'url' => route('forms.page', 'meeting-minutes'), 'count' => $n($mmAll) . ' محضر', 'code' => 'SH-034'],
                    ...(\App\Http\Controllers\JobController::canManage($this->u) ? [$this->jobsCard()] : []),
                ],
            ],
        ];

        if ($this->u->isClientAdmin()) {
            $sections[] = [
                'key' => 'admin', 'title' => 'إدارة المخزن', 'icon' => 'settings', 'cols' => 4,
                'cards' => [
                    ['title' => 'المستخدمون', 'desc' => 'حسابات الموظفين وأدوارهم وحالتهم', 'icon' => 'users', 'tone' => 'teal',
                        'url' => route('client.users.index'), 'count' => $n(User::query()->where('tenant_id', $this->u->tenant_id)->where('is_active', true)->count()) . ' مستخدم فعال',
                        'links' => [['+ مستخدم جديد', route('client.users.create')]]],
                    ['title' => 'إعدادات المخزن', 'desc' => 'رابط شكاوى الصيدليات · حدود ومدد النماذج', 'icon' => 'settings', 'tone' => 'orange',
                        'url' => route('client.settings.edit'), 'count' => 'الإعدادات',
                        'links' => [['نقاط مراقبة الحرارة', route('temperature.points')]]],
                    ['title' => 'الأصناف', 'desc' => 'قائمة الأصناف من Excel · الاسم والسعر والموقع', 'icon' => 'box', 'tone' => 'blue',
                        'url' => route('client.data.index', 'products'), 'count' => $n(Product::query()->count()) . ' صنف'],
                    ['title' => 'الصيدليات', 'desc' => 'قائمة العملاء من Excel · الكود والاسم والعنوان', 'icon' => 'store', 'tone' => 'green',
                        'url' => route('client.data.index', 'pharmacies'), 'count' => $n(Pharmacy::query()->count()) . ' صيدلية'],
                ],
            ];
        }

        // الصلاحيات: إخفاء ما لا يراه المستخدم
        $sections = collect($sections)->map(function ($sec) {
            $sec['cards'] = array_values(array_filter($sec['cards'], fn ($c) => empty($c['m']) || Access::can($this->u, $c['m'], 'view')));

            return $sec;
        })->filter(fn ($sec) => count($sec['cards']))->values()->all();

        return [
            'kpis' => [
                ['label' => 'إجراءات نشطة', 'value' => $n($sopEff), 'icon' => 'clipcheck', 'tone' => 'blue', 'url' => route('documents.index', ['type' => 'sop', 'status' => 'effective']),
                    'hint' => $sopEff ? 'إجراء تشغيل قياسي ساري' : 'لم يُعتمد إجراء بعد'],
                ['label' => 'مهام تحتاج متابعة', 'value' => $n($tasks), 'icon' => 'tasks', 'tone' => 'orange', 'url' => '#attention', 'hint' => 'تنتظرك أو متأخرة'],
                Access::can($this->u, 'complaint', 'view')
                    ? ['label' => 'شكاوى مفتوحة', 'value' => $n($cmpOpen), 'icon' => 'alert', 'tone' => 'red', 'url' => route('complaints.index'), 'hint' => 'لم تُغلق بعد']
                    : ['label' => 'وثائق سارية', 'value' => $n($docEff), 'icon' => 'folder', 'tone' => 'green', 'url' => route('documents.index'), 'hint' => 'إجراءات ونماذج معتمدة'],
            ],
            'compliance' => ['value' => $compliance, 'parts' => array_map(fn ($v) => (int) round($v), $parts)],
            'sections' => $sections,
            'alerts' => $alerts->values()->all(),
            'activity' => $this->activity(),
        ];
    }

    /* ======================================================= */

    /** بطاقة الهيكل الوظيفي: الوظائف بدون شاغل ومهامها لم تُوزع · مستخدمون بدون وصف */
    private function jobsCard(): array
    {
        $settings = JobLibrary::settings();
        $count = JobLibrary::headcount($this->u->tenant_id);
        $open = JobLibrary::all()->filter(function ($j, $c) use ($settings, $count) {
            if (($settings[$c]->mode ?? 'staffed') === 'na' || ! JobLibrary::needsCover($c, $settings, $count)) return false;
            $re = $settings[$c]->reassign ?? [];

            return collect(JobLibrary::groups($j))->contains(fn ($g) => empty($re[$g['key']]));
        })->count();
        $noJob = User::query()->where('tenant_id', $this->u->tenant_id)->where('is_active', true)->whereNull('job_code')
            ->whereHas('role', fn ($q) => $q->where('level', 3))->count();

        return ['title' => 'الهيكل الوظيفي والأوصاف', 'desc' => 'الوظائف في المخزن والشاغر منها · توزيع مهامه · طباعة وصف كل موظف', 'icon' => 'users', 'tone' => 'purple',
            'url' => route('jobs.index'), 'count' => number_format(count($count)) . ' وظيفة مشغولة',
            'warn' => $open ? $open . ' مهامها لم تُوزع' : ($noJob ? $noJob . ' بدون وصف' : null),
            'links' => [['المستخدمون', route('client.users.index')]], 'code' => 'SH-021'];
    }

    /** نفس منطق سجل الانحرافات: خطة متأخرة أو إجراء متأخر أو تحقق متأخر */
    private function deviationLate(array $d): bool
    {
        $t = $this->today->toDateString();
        $stage = $d['stage'] ?? 'draft';
        if (in_array($stage, ['reported', 'open'], true) && ! empty($d['planDue']) && $d['planDue'] < $t) {
            return true;
        }
        if ($stage === 'implementing') {
            $round = (int) ($d['round'] ?? 1);
            foreach ($d['actions'] ?? [] as $a) {
                if ((int) ($a['round'] ?? 1) === $round && trim((string) ($a['text'] ?? '')) !== ''
                    && empty($a['done']) && ! empty($a['due']) && $a['due'] < $t) {
                    return true;
                }
            }
            if (! empty($d['verifyDue']) && empty($d['verifyDate']) && $d['verifyDue'] < $t) {
                return true;
            }
        }

        return false;
    }

    /** قرارات المحاضر المعتمدة: [المفتوح, المتأخر] */
    private function decisions(): array
    {
        $records = $this->records('meeting-minutes')->where('status', FormRecord::STATUS_FINAL)->get(['id', 'data']);
        $closed = DecisionStatus::query()->whereIn('form_record_id', $records->pluck('id'))
            ->where('status', '!=', DecisionStatus::OPEN)->get(['form_record_id', 'decision_key'])
            ->map(fn ($s) => $s->form_record_id . '|' . $s->decision_key)->flip();
        $t = $this->today->toDateString();
        $open = 0;
        $late = 0;
        foreach ($records as $r) {
            foreach (array_values($r->data['decisions'] ?? []) as $i => $d) {
                if (trim((string) ($d['text'] ?? '')) === '') continue;
                if ($closed->has($r->id . '|' . (string) ($d['id'] ?? $i))) continue;
                $open++;
                if (! empty($d['due']) && $d['due'] < $t) $late++;
            }
        }

        return [$open, $late];
    }

    /** نسبة قراءات الحرارة المسجلة هذا الشهر (عدا الجمعة) بعدد القراءات اليومية في الإعدادات */
    private function tempCompletion(): ?float
    {
        $points = TempPoint::query()->where('is_active', true)->pluck('id');
        if ($points->isEmpty()) return null;
        $slots = 0;
        for ($d = $this->today->copy()->startOfMonth(); $d->lte($this->today); $d->addDay()) {
            $slots += count(TempRounds::dueOn($d->copy(), $this->u->tenant_id));
        }
        $expected = $slots * $points->count();
        if (! $expected) return null;
        $done = TempReading::query()->whereIn('point_id', $points)
            ->whereBetween('date', [$this->today->copy()->startOfMonth()->toDateString(), $this->today->toDateString()])->count();

        return min(100, $done / $expected * 100);
    }

    /** نسبة بنود التفتيش الذاتي المطابقة هذا العام */
    private function inspectionConformity(string $year): ?float
    {
        $ok = 0;
        $nc = 0;
        foreach ($this->records('inspection')->whereYear('record_date', $year)->get(['data']) as $r) {
            foreach ($r->data['items'] ?? [] as $it) {
                if (($it['res'] ?? '') === 'ok') $ok++;
                if (($it['res'] ?? '') === 'nc') $nc++;
            }
        }

        return $ok + $nc ? $ok / ($ok + $nc) * 100 : null;
    }

    /** «يتطلب انتباهك»: كل ما ينتظر المستخدم أو تأخر */
    private function alerts(int $devLate, int $decLate, array $tr = []): Collection
    {
        $u = $this->u;
        $out = collect();
        $manager = $u->isClientAdmin() || $u->hasRole('quality_manager') || $u->hasRole('pharmacist_manager');

        $portal = PortalStats::newComplaints($u);
        if ($portal && Access::can($u, 'complaint', 'view')) $out->push(['n' => $portal, 'tone' => 'warn', 'icon' => 'alert', 'title' => 'شكوى جديدة بانتظار التصنيف',
            'sub' => "{$portal} من رابط الصيدليات", 'url' => route('complaints.index', ['tab' => 'open', 'src' => 'portal'])]);

        $temp = TemperatureController::homeAlerts($u);
        if ($temp['open']) $out->push(['n' => $temp['open'], 'tone' => 'bad', 'icon' => 'thermo', 'title' => 'انحراف حرارة يحتاج تقرير',
            'sub' => "{$temp['open']} قراءة خارج الحدود بدون F03", 'url' => route('temperature.report')]);
        if ($temp['missing']) $out->push(['n' => 1, 'tone' => 'warn', 'icon' => 'thermo', 'title' => 'قراءات الحرارة لم تكتمل',
            'sub' => "جولة {$temp['missing']} اليوم", 'url' => route('temperature.round')]);

        $docs = DocFlow::waiting($u);
        if ($docs) $out->push(['n' => $docs, 'tone' => 'info', 'icon' => 'file', 'title' => 'وثائق تنتظر مراجعتك',
            'sub' => "{$docs} للمراجعة أو الاعتماد أو التعديل", 'url' => route('documents.queue')]);

        $kpi = KpiController::pendingFor($u);
        if ($kpi['emp']) $out->push(['n' => $kpi['emp'], 'tone' => 'info', 'icon' => 'target', 'title' => 'تقييم أداء ينتظر توقيعك',
            'sub' => "{$kpi['emp']} تقييم للتوقيع بالعلم", 'url' => route('kpi.index')]);
        if ($kpi['hr']) $out->push(['n' => $kpi['hr'], 'tone' => 'info', 'icon' => 'target', 'title' => 'تقييمات بانتظار الموارد البشرية',
            'sub' => "{$kpi['hr']} تقييم للاعتماد", 'url' => route('kpi.index')]);

        if ($manager && $devLate) $out->push(['n' => $devLate, 'tone' => 'bad', 'icon' => 'shield', 'title' => 'إجراءات تصحيحية متأخرة',
            'sub' => "{$devLate} انحراف تجاوز موعده", 'url' => route('deviations.index', ['tab' => 'late'])]);
        if ($manager && $decLate) $out->push(['n' => $decLate, 'tone' => 'warn', 'icon' => 'gear', 'title' => 'قرارات اجتماعات متأخرة',
            'sub' => "{$decLate} قرار تجاوز موعده", 'url' => route('decisions.index', ['status' => 'overdue'])]);

        $field = FieldInspectionController::pending($u);
        if ($field) $out->push(['n' => $field, 'tone' => 'bad', 'icon' => 'search', 'title' => 'تفتيش ميداني به عدم مطابقة',
            'sub' => "{$field} فحص بانتظار مراجعتك", 'url' => route('field.index', ['only' => 'open'])]);
        if (! empty($tr['pending'])) $out->push(['n' => $tr['pending'], 'tone' => 'info', 'icon' => 'user', 'title' => 'تدريبات بانتظار اعتمادك',
            'sub' => "{$tr['pending']} محضر تدريب", 'url' => route('training.index')]);
        if (! empty($tr['effLate'])) $out->push(['n' => $tr['effLate'], 'tone' => 'warn', 'icon' => 'user', 'title' => 'متابعة فاعلية تدريب متأخرة',
            'sub' => "{$tr['effLate']} تدريب تجاوز موعد المتابعة", 'url' => route('training.index')]);

        return $out;
    }

    /** «آخر الأنشطة»: آخر الحركات على النماذج والوثائق */
    private function activity(): array
    {
        $byCode = collect(FormRegistry::FORMS)->reverse()->mapWithKeys(fn ($f, $k) => [$f['code'] => $f + ['key' => $k]])
            ->reject(fn ($f) => ! empty($f['private']));   // تقييمات الأداء خاصة
        $icons = ['deviation' => 'shield', 'receiving' => 'truck', 'complaint' => 'users', 'inspection' => 'search', 'inspection-quick' => 'search',
            'inventory-count' => 'clipboard', 'meeting-minutes' => 'people', 'training' => 'user', 'order-check' => 'check', 'temp-excursion' => 'thermo'];

        $byCode = $byCode->filter(fn ($f) => Access::can($this->u, Access::FORM_MODULE[$f['key']] ?? '-', 'view'));
        $items = FormRecord::query()->whereIn('form_code', $byCode->keys())->orderByDesc('updated_at')->take(15)
            ->with('logs.user')->get()
            ->flatMap(function ($r) use ($byCode, $icons) {
                $f = $byCode->get($r->form_code);
                $url = in_array($f['key'], self::OPEN_BY_NUMBER, true) ? route('forms.page', $f['key']) . '?open=' . urlencode($r->number)
                    : ($f['key'] === 'temp-excursion' ? route('forms.page', $f['key']) . '?open=' . $r->id : route('forms.page', $f['key']));

                return $r->logs->map(fn ($l) => [
                    'at' => $l->created_at, 'icon' => $icons[$f['key']] ?? 'file',
                    'title' => $l->action . ' · ' . $f['title'], 'sub' => $r->number . ($l->user ? ' · ' . $l->user->displayName() : ''), 'url' => $url,
                ]);
            });

        $docs = ! Access::can($this->u, 'documents', 'view') ? collect() : DocumentLog::query()->with(['user', 'version'])->orderByDesc('id')->take(8)->get()
            ->map(function ($l) {
                $doc = Document::query()->find($l->document_id);

                return $doc ? ['at' => $l->created_at, 'icon' => 'file', 'title' => $l->action . ' · وثيقة',
                    'sub' => $doc->code . ' ' . $doc->title . ($l->user ? ' · ' . $l->user->displayName() : ''), 'url' => route('documents.show', $doc)] : null;
            })->filter();

        return $items->merge($docs)->filter(fn ($a) => $a['at'])->sortByDesc(fn ($a) => $a['at']->timestamp)->take(7)
            ->map(fn ($a) => $a + ['ago' => $a['at']->copy()->locale('ar')->diffForHumans()])
            ->values()->all();
    }
}