<?php

namespace App\Support;

use App\Models\Document;
use App\Models\FormRecord;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * البحث العام في إتقان + دليل الوثائق (زر المعلومات في الشريط العلوي)
 *   - الوثائق: الإجراءات SH-xxx · مؤشرات الأداء KPI-xx-xx · الأوصاف الوظيفية JD-xx-xx (+ وثائق السجل المضافة يدوياً)
 *   - نماذج الإجراءات SH-xxx-Fxx
 *   - السجلات: رقم السجل أو عنوانه (استلام، انحراف، شكوى، تفتيش، تدريب…) حسب صلاحيات المستخدم
 *   - الشاشات: كل شاشات النظام بكلمات بحث عربية
 *   - المستخدمون (لمدير المخزن والموارد البشرية)
 */
class GlobalSearch
{
    public const TYPES = ['sop' => 'إجراء', 'kpi' => 'مؤشرات أداء', 'jd' => 'وصف وظيفي', 'form' => 'نموذج', 'policy' => 'سياسة', 'other' => 'وثيقة'];

    /** حالة الوثيقة في سجل المخزن */
    public const STATES = ['effective' => 'سارية', 'pending' => 'قيد المراجعة', 'draft' => 'لم تُعتمد', 'none' => 'غير مسجلة', 'obsolete' => 'ملغاة'];

    /** تطبيع النص العربي للبحث */
    public static function norm(?string $s): string
    {
        $s = mb_strtolower((string) $s);
        $s = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $s);   // التشكيل والتطويل

        return strtr($s, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ٱ' => 'ا', 'ة' => 'ه', 'ى' => 'ي', 'ؤ' => 'و', 'ئ' => 'ي']);
    }

    /** كل كلمات البحث موجودة في النص */
    private static function hit(string $hay, array $words): bool
    {
        $hay = self::norm($hay);
        foreach ($words as $w) {
            if (! str_contains($hay, $w)) {
                return false;
            }
        }

        return true;
    }

    /* ===================== دليل الوثائق ===================== */

    /**
     * دليل الوثائق للمستخدم: ['sop' => [...], 'kpi' => [...], 'jd' => [...]]
     * كل عنصر: code, title, url, state, stateLabel
     */
    public static function library(User $u): array
    {
        $docs = Document::query()->with(['current', 'versions'])->get()->keyBy('code');
        $out = ['sop' => [], 'kpi' => [], 'jd' => []];

        foreach (DocLibrary::catalog() as $code => $c) {
            $out[$c['type']][] = self::docItem($code, $c['title'], $c['type'], $docs->get($code));
        }
        // وثائق أضافها المخزن يدوياً من نفس الأنواع
        foreach ($docs as $code => $d) {
            if (isset($out[$d->type]) && ! collect($out[$d->type])->contains('code', $code)) {
                $out[$d->type][] = self::docItem($code, $d->title, $d->type, $d);
            }
        }
        foreach ($out as &$list) {
            usort($list, fn ($a, $b) => strnatcmp($a['code'], $b['code']));
        }

        return $out;
    }

    private static function docItem(string $code, string $title, string $type, ?Document $d): array
    {
        $state = 'none';
        if ($d) {
            $state = ! $d->is_active ? 'obsolete' : ($d->current ? 'effective' : ($d->versions->isNotEmpty() ? 'pending' : 'draft'));
        }

        return [
            'code'  => $code, 'title' => $title, 'type' => $type, 'typeLabel' => self::TYPES[$type] ?? '',
            'state' => $state, 'stateLabel' => self::STATES[$state],
            'url'   => $d ? route('documents.show', $d) : route('documents.index', ['q' => $code]),
        ];
    }

    /* ===================== البحث العام ===================== */

    /**
     * ['q', 'groups' => [['key', 'title', 'items' => [[title, sub, code, url, badge]]]], 'total']
     */
    public static function run(User $u, string $q): array
    {
        $q = trim(mb_substr($q, 0, 80));
        $words = array_values(array_filter(preg_split('/\s+/u', self::norm($q))));
        if (! $words || mb_strlen($q) < 2) {
            return ['q' => $q, 'groups' => [], 'total' => 0];
        }

        $groups = [
            ['key' => 'pages',   'title' => 'الشاشات',              'items' => self::pages($u, $words)],
            ['key' => 'records', 'title' => 'السجلات',              'items' => self::records($u, $q, $words)],
            ['key' => 'docs',    'title' => 'الإجراءات والوثائق',    'items' => self::docs($u, $words)],
            ['key' => 'forms',   'title' => 'نماذج الإجراءات',       'items' => self::sopForms($u, $words)],
            ['key' => 'users',   'title' => 'المستخدمون',           'items' => self::users($u, $words)],
        ];
        $groups = array_values(array_filter($groups, fn ($g) => $g['items']));

        return ['q' => $q, 'groups' => $groups, 'total' => array_sum(array_map(fn ($g) => count($g['items']), $groups))];
    }

    /** الوثائق (إجراءات · مؤشرات · أوصاف) */
    private static function docs(User $u, array $words): array
    {
        $out = [];
        foreach (self::library($u) as $list) {
            foreach ($list as $d) {
                if (self::hit($d['code'] . ' ' . $d['title'] . ' ' . $d['typeLabel'], $words)) {
                    $out[] = ['title' => $d['title'], 'code' => $d['code'], 'sub' => $d['typeLabel'], 'badge' => $d['stateLabel'], 'state' => $d['state'], 'url' => $d['url']];
                }
            }
        }

        return array_slice($out, 0, 12);
    }

    /** نماذج الإجراءات SH-xxx-Fxx: تُفتح للطباعة فارغة من سجل الوثائق */
    private static function sopForms(User $u, array $words): array
    {
        $docs = Document::query()->where('type', 'sop')->pluck('id', 'code');
        $out = [];
        foreach (SopLibrary::all() as $code => $s) {
            foreach ($s['forms'] ?? [] as $f) {
                if (! self::hit(($f['code'] ?? '') . ' ' . ($f['title'] ?? '') . ' ' . $s['title'], $words)) {
                    continue;
                }
                $out[] = [
                    'title' => $f['title'] ?? '', 'code' => $f['code'] ?? '', 'sub' => $code . ' · ' . $s['title'], 'badge' => 'طباعة',
                    'url'   => isset($docs[$code]) ? route('documents.print', ['document' => $docs[$code], 'form' => $f['code']]) : route('documents.index', ['q' => $code]),
                ];
                if (count($out) >= 10) {
                    return $out;
                }
            }
        }

        return $out;
    }

    /** سجلات الشاشات: بالرقم أو العنوان */
    private static function records(User $u, string $q, array $words): array
    {
        // كود النموذج في السجل ← مفتاح الشاشة (أول شاشة بنفس الكود)
        $byCode = [];
        foreach (FormRegistry::FORMS as $key => $f) {
            $byCode[$f['code']] ??= $key;
        }

        $like = '%' . str_replace(['%', '_'], ['\%', '\_'], $q) . '%';
        $rows = FormRecord::query()
            ->where(fn ($w) => $w->where('number', 'like', $like)->orWhere('title', 'like', $like))
            ->latest('id')->limit(60)->get(['id', 'form_code', 'number', 'title', 'record_date', 'status', 'data', 'created_by']);

        $out = [];
        foreach ($rows as $r) {
            $key = $byCode[$r->form_code] ?? null;
            if (! $key) {
                continue;
            }
            $f = FormRegistry::FORMS[$key] + ['key' => $key];
            $module = Access::FORM_MODULE[$key] ?? null;
            if (! empty($f['private'])) {
                $mine = (int) $r->created_by === $u->id || (int) ($r->data['empId'] ?? 0) === $u->id || (int) ($r->data['evaluatorId'] ?? 0) === $u->id;
                if (! $mine && ! $u->isClientAdmin() && ! FormRegistry::canFinalize($f, $u)) {
                    continue;
                }
            } elseif ($module && ! Access::can($u, $module, 'view')) {
                continue;
            }
            $out[] = [
                'title' => $r->title ?: $f['title'], 'code' => $r->number, 'sub' => $f['title'] . ($r->record_date ? ' · ' . $r->record_date->format('Y-m-d') : ''),
                'badge' => $r->status === FormRecord::STATUS_FINAL ? 'معتمد' : 'جارٍ', 'state' => $r->status === FormRecord::STATUS_FINAL ? 'effective' : 'pending',
                'url'   => route('forms.page', $key) . '?open=' . urlencode((string) $r->number),
            ];
            if (count($out) >= 10) {
                break;
            }
        }

        return $out;
    }

    /** المستخدمون: لمدير المخزن والموارد البشرية (يفتح الوصف الوظيفي للموظف) */
    private static function users(User $u, array $words): array
    {
        if (! $u->isClientAdmin() && ! $u->hasRole('hr_manager', 'hr_officer')) {
            return [];
        }
        $out = [];
        foreach (User::query()->with('role')->where('tenant_id', $u->tenant_id)->orderBy('name')->get() as $x) {
            if (self::hit($x->displayName() . ' ' . $x->name . ' ' . ($x->email ?? '') . ' ' . ($x->role?->name_ar ?? '') . ' ' . ($x->job_code ?? ''), $words)) {
                $out[] = [
                    'title' => $x->displayName(), 'code' => $x->job_code ?? '', 'sub' => $x->role?->name_ar ?? '',
                    'badge' => $x->is_active ? '' : 'موقوف', 'state' => $x->is_active ? '' : 'obsolete',
                    'url'   => $u->isClientAdmin() ? route('client.users.edit', $x) : route('jobs.user', $x),
                ];
                if (count($out) >= 8) {
                    break;
                }
            }
        }

        return $out;
    }

    /** شاشات النظام: [عنوان, وصف, مسار, وسيط, كلمات, وحدة الصلاحية, مدير فقط] */
    private static function screens(): array
    {
        return [
            ['مهام اليوم', 'قائمة المهام اليومية وقراءات الحرارة', 'daily.index', [], 'مهام يوميه تودو متاخر', null, false],
            ['خريطة الجودة', 'خريطة منظومة الجودة ومصفوفة المسؤوليات RACI', 'qms.map', [], 'منظومه راسي مسؤوليات اجراءات', null, false],
            ['مخططات سير العمل', 'مخطط الاستلام SH-001 ومخطط تحضير الطلبيات وتوزيعها SH-004', 'qms.flows', [], 'فلو شارت مخطط استلام توزيع تحضير', null, false],
            ['سجل الوثائق', 'التحكم في الوثائق: الإجراءات والأوصاف والمؤشرات', 'documents.index', [], 'وثائق مستندات SOP اجراءات SH-014', null, false],
            ['وثائق بانتظاري', 'الوثائق التي تنتظر مراجعتك أو اعتمادك', 'documents.queue', [], 'مراجعه اعتماد وثائق', null, false],
            ['استلام الشحنات', 'فحص الشحنات الواردة وقرار الإفراج', 'forms.page', ['receiving'], 'استلام وارد شحنه فحص امر شراء SH-001', 'receiving', false],
            ['قراءة الحرارة والرطوبة', 'تسجيل جولة الحرارة اليومية', 'temperature.round', [], 'حراره رطوبه ثلاجه جوله SH-030', 'temperature', false],
            ['تقرير الحرارة الشهري', 'إحصاءات الحرارة والانحرافات والمراجعات الأسبوعية', 'temperature.report', [], 'حراره تقرير شهري', 'temperature', false],
            ['سجل الحرارة للطباعة', 'سجل نصف شهري F01 · F02', 'temperature.sheet', [], 'حراره طباعه سجل', 'temperature', false],
            ['نقاط قياس الحرارة', 'الغرف والثلاجات وحدودها', 'temperature.points', [], 'حراره نقاط ثلاجات حدود', 'temperature', false],
            ['تقرير انحراف الحرارة', 'SH-030-F03', 'forms.page', ['temp-excursion'], 'انحراف حراره F03', 'temperature', false],
            ['الانحرافات والإجراءات التصحيحية', 'تسجيل انحراف جديد', 'forms.page', ['deviation'], 'انحراف CAPA تصحيحي وقائي SH-008', 'deviation', false],
            ['سجل الانحرافات', 'متابعة الانحرافات SH-008-F04', 'deviations.index', [], 'انحرافات سجل متابعه CAPA', 'deviation', false],
            ['شكاوى العملاء', 'تسجيل شكوى جديدة', 'forms.page', ['complaint'], 'شكوي شكاوي عميل صيدليه SH-007', 'complaint', false],
            ['سجل الشكاوى', 'سجل الشكاوى SH-007-F03', 'complaints.index', [], 'شكاوي سجل', 'complaint', false],
            ['التفتيش الذاتي', 'تفتيش كامل SH-012', 'forms.page', ['inspection'], 'تفتيش ذاتي مراجعه SH-012', 'inspection', false],
            ['تفتيش سريع', 'تفتيش من الموبايل بند بند', 'inspections.quick', [], 'تفتيش سريع موبايل', 'inspection', false],
            ['سجل التفتيش والتغطية', 'تقارير التفتيش ومصفوفة التغطية', 'inspections.index', [], 'تفتيش سجل تغطيه', 'inspection', false],
            ['بنك بنود الفحص', 'بنود التفتيش لكل إجراء', 'inspections.bank', [], 'بنود فحص بنك تفتيش', 'inspection', false],
            ['التفتيش الميداني', 'مراجعة الطلبيات عند الصيدليات بالمندوبين', 'field.index', [], 'ميداني مندوب صيدليه تشغيله SH-012-F04', 'inspection', false],
            ['فحص دقة التحضير', 'فحص عشوائي للطلبيات SH-004-F04', 'forms.page', ['order-check'], 'دقه تحضير طلبيات محضر SH-004', 'ordercheck', false],
            ['تقرير دقة التحضير', 'الزونات والمحضرون والأخطاء', 'orderchecks.index', [], 'دقه تحضير تقرير زون', 'ordercheck', false],
            ['محاضر الجرد', 'الجرد الدوري والفروقات SH-016', 'forms.page', ['inventory-count'], 'جرد فروقات مخزون SH-016', 'inventory', false],
            ['محاضر الاجتماعات', 'محضر اجتماع SH-034-F02', 'forms.page', ['meeting-minutes'], 'اجتماع محضر قرارات', 'meetings', false],
            ['متابعة القرارات', 'قرارات الاجتماعات SH-034-F03', 'decisions.index', [], 'قرارات متابعه اجتماع', 'meetings', false],
            ['محضر التدريب', 'تسجيل تدريب SH-015-F03', 'forms.page', ['training'], 'تدريب محاضره SH-015', 'training', false],
            ['تقرير التدريب', 'الدورات والمدربون والفاعلية', 'training.index', [], 'تدريب تقرير دورات', 'training', false],
            ['سجل تدريبي', 'سجل تدريب الموظف F05', 'training.employee', [], 'تدريب سجل موظف', null, false],
            ['تقييم الأداء الشهري', 'تقييم موظف بالمؤشرات', 'forms.page', ['kpi'], 'KPI مؤشرات تقييم اداء', 'kpi', false],
            ['سجل تقييم الأداء', 'نتائج التقييم الشهري', 'kpi.index', [], 'KPI سجل تقييم اداء', 'kpi', false],
            ['مجموعات المؤشرات', 'مؤشرات الأداء لكل وظيفة', 'kpi.sets', [], 'KPI مؤشرات مجموعات', 'kpi', false],
            ['وصفي الوظيفي', 'الوصف الوظيفي الخاص بك', 'jobs.mine', [], 'وصف وظيفي JD', null, false],
            ['الهيكل الوظيفي', 'الوظائف والأوصاف الوظيفية للمخزن', 'jobs.index', [], 'هيكل وظيفي اوصاف JD', null, true],
            ['الإشعارات', 'صندوق الإشعارات والأجهزة', 'notifications.index', [], 'اشعارات تنبيهات جرس', null, false],
            ['المستخدمون', 'إضافة وتعديل المستخدمين', 'client.users.index', [], 'مستخدمين موظفين حسابات', null, true],
            ['الصلاحيات', 'الأدوار × الشاشات', 'client.permissions.index', [], 'صلاحيات ادوار', null, true],
            ['إعداد الإشعارات', 'مستلمو كل حدث', 'client.notify.index', [], 'اشعارات اعداد مستلمين', null, true],
            ['إعدادات المخزن', 'الحرارة والجرد والاستلام ورابط الصيدليات', 'client.settings.edit', [], 'اعدادات ضبط', null, true],
            ['الأصناف', 'قائمة الأصناف وأماكنها', 'client.data.index', ['products'], 'اصناف منتجات ادويه اماكن لوكيشن', null, true],
            ['الصيدليات', 'قائمة الصيدليات وأكوادها', 'client.data.index', ['pharmacies'], 'صيدليات عملاء اكواد', null, true],
        ];
    }

    private static function pages(User $u, array $words): array
    {
        $hr = $u->isClientAdmin() || $u->hasRole('hr_manager', 'hr_officer');
        $out = [];
        foreach (self::screens() as [$title, $desc, $route, $params, $kw, $module, $admin]) {
            if (! Route::has($route)) {
                continue;
            }
            if ($admin && ! ($route === 'jobs.index' ? $hr : $u->isClientAdmin())) {
                continue;
            }
            if ($module && ! Access::can($u, $module, 'view')) {
                continue;
            }
            if (self::hit($title . ' ' . $desc . ' ' . $kw, $words)) {
                $out[] = ['title' => $title, 'code' => '', 'sub' => $desc, 'badge' => '', 'url' => route($route, $params)];
            }
        }

        return array_slice($out, 0, 8);
    }
}