<?php

namespace App\Http\Middleware;

use App\Models\DocumentVersion;
use App\Support\Access;
use App\Support\FormRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * بوابة الصلاحيات: تحدد شاشة كل مسار وما يحتاجه (عرض للقراءة · استخدام للحفظ)
 * المسارات غير المذكورة هنا متاحة لكل مستخدمي المخزن (الرئيسية · الخريطة · وصفي الوظيفي · قراءة الوثائق السارية)
 */
class ModuleGate
{
    /** اسم المسار ← [الشاشة, المطلوب] (null = حسب نوع الطلب: GET عرض · غيره استخدام) */
    private const ROUTES = [
        'decisions.index' => ['meetings', 'view'], 'decisions.update' => ['meetings', 'use'],
        'deviations.index' => ['deviation', 'view'],
        'complaints.index' => ['complaint', 'view'],
        'inspections.index' => ['inspection', 'view'], 'inspections.bank' => ['inspection', 'view'], 'inspections.bank.items' => ['inspection', 'view'],
        'inspections.quick' => ['inspection', 'view'], 'inspections.quick.items' => ['inspection', 'view'],
        'field.index' => ['inspection', 'view'], 'field.show' => ['inspection', 'view'], 'field.review' => ['inspection', 'use'],
        'field.delegates.store' => ['inspection', 'use'], 'field.delegates.toggle' => ['inspection', 'use'], 'field.delegates.renew' => ['inspection', 'use'],
        'orderchecks.index' => ['ordercheck', 'view'],
        'kpi.index' => ['kpi', 'view'],
        'documents.store' => ['documents', 'use'], 'documents.seed' => ['documents', 'use'], 'documents.update' => ['documents', 'use'],
        'documents.toggle' => ['documents', 'use'], 'documents.version.start' => ['documents', 'use'], 'documents.upload' => ['documents', 'use'],
        'documents.submit' => ['documents', 'use'], 'documents.version.destroy' => ['documents', 'use'],
        'temperature.round' => ['temperature', 'view'], 'temperature.save' => ['temperature', 'use'],
        'temperature.points' => ['temperature', 'view'], 'temperature.points.store' => ['temperature', 'use'], 'temperature.points.seed' => ['temperature', 'use'],
        'temperature.points.json' => ['temperature', 'view'], 'temperature.points.update' => ['temperature', 'use'], 'temperature.points.toggle' => ['temperature', 'use'],
        'temperature.reading' => ['temperature', 'view'], 'temperature.sheet' => ['temperature', 'view'], 'temperature.report' => ['temperature', 'view'],
        'temperature.checks' => ['temperature', 'use'],
        'training.index' => ['training', 'view'],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $route = $request->route();
        if (! $user || ! $user->tenant_id || ! $route || Access::full($user)) {
            return $next($request);
        }
        $name = (string) $route->getName();

        // شاشات النماذج
        if (str_starts_with($name, 'forms.')) {
            $form = (string) $route->parameter('form');
            $module = Access::FORM_MODULE[$form] ?? null;
            if ($module) {
                $private = ! empty(FormRegistry::FORMS[$form]['private'] ?? false);
                $write = ! $request->isMethod('GET');
                // النماذج الخاصة (تقييم الأداء): القراءة والتوقيع حسب صاحب السجل · الإنشاء والحذف للمصرح لهم
                if ($private) {
                    $need = in_array($name, ['forms.records.store', 'forms.records.destroy'], true) ? 'use' : null;
                } else {
                    $need = $write ? 'use' : 'view';
                }
                // من له صلاحية اعتماد النموذج (أو مرحلة منه) يستطيع الحفظ لاعتماده
                if ($need === 'use' && ! Access::can($user, $module, 'use') && $this->approver($user, $form)) {
                    $need = 'view';
                }
                if ($need && ! Access::can($user, $module, $need)) {
                    return $this->deny($request, $module, $need);
                }
            }

            return $next($request);
        }

        // سجل تدريب الموظف: كل موظف يرى سجله
        if ($name === 'training.employee') {
            if ((int) $request->query('user') === $user->id || Access::can($user, 'training', 'view')) return $next($request);

            return $this->deny($request, 'training', 'view');
        }

        // تنزيل ملف وثيقة: الساري متاح للجميع · غير الساري لمن يعمل على الوثائق أو يراجعها
        if ($name === 'documents.download') {
            $v = $route->parameter('version');
            $v = $v instanceof DocumentVersion ? $v : DocumentVersion::find($v);
            $reviewer = \App\Support\DocFlow::myStages($user) !== [];
            if ($v && $v->stage !== 'effective' && (int) $v->prepared_by !== $user->id && ! $reviewer && ! Access::can($user, 'documents', 'view')) {
                return $this->deny($request, 'documents', 'view');
            }

            return $next($request);
        }

        if (isset(self::ROUTES[$name])) {
            [$module, $need] = self::ROUTES[$name];
            if (! Access::can($user, $module, $need)) {
                return $this->deny($request, $module, $need);
            }
        }

        return $next($request);
    }

    private function approver($user, string $form): bool
    {
        $f = FormRegistry::FORMS[$form] ?? null;
        if (! $f || empty($f['final_roles'])) return false;
        $f += ['key' => $form];

        return FormRegistry::canFinalize($f, $user) || in_array(true, FormRegistry::stepRights($f, $user), true);
    }

    private function deny(Request $request, string $module, string $need): Response
    {
        $title = Access::MODULES[$module][0] ?? $module;
        $msg = $need === 'use'
            ? "صلاحيتك على «{$title}» للعرض فقط. اطلب من مدير المخزن صلاحية الاستخدام."
            : "ليست لديك صلاحية على «{$title}». اطلب الصلاحية من مدير المخزن.";

        if ($request->expectsJson()) {
            return response()->json(['message' => $msg], 403);
        }

        return response()->view('errors.no-access', ['message' => $msg], 403);
    }
}