<?php

namespace App\Support;

use App\Models\Document;
use App\Models\DocumentVersion;

/**
 * مكتبة وثائق إتقان المدمجة في النظام (بدون رفع ملفات):
 *   إجراءات التشغيل القياسية ونماذجها (SopLibrary) · الأوصاف الوظيفية (JobLibrary) · مجموعات مؤشرات الأداء (KpiSets)
 * المخزن يحمّلها في سجل الوثائق، ويملأ الفراغات، ويعتمدها في النظام، ويطبعها بشعاره.
 */
class DocLibrary
{
    /** أعمدة جدول الاعتماد للمؤشرات (لا توجد في بياناتها) */
    public const KPI_APPROVERS = ['مدير الموارد البشرية', 'مدير إدارة الجودة', 'الرئيس التنفيذي'];

    /** كل وثائق المكتبة: [code => ['type', 'title', 'dept', 'version']] */
    public static function catalog(): array
    {
        $out = [];
        foreach (SopLibrary::all() as $code => $s) {
            $out[$code] = ['type' => 'sop', 'title' => $s['title'], 'dept' => null, 'version' => $s['version']];
        }
        foreach (JobLibrary::all() as $code => $j) {
            $out[$code] = ['type' => 'jd', 'title' => $j['title'], 'dept' => JobLibrary::dept($code) ?: null, 'version' => '01'];
        }
        foreach (KpiSets::SETS as $s) {
            $out[$s['code']] = ['type' => 'kpi', 'title' => 'مؤشرات أداء ' . $s['title'], 'dept' => null, 'version' => '01'];
        }

        return $out;
    }

    /** هل للوثيقة نص مدمج في المكتبة؟ */
    public static function has(Document $d): bool
    {
        return match ($d->type) {
            'sop'   => SopLibrary::get($d->code) !== null,
            'jd'    => JobLibrary::get($d->code) !== null,
            'kpi'   => self::kpi($d->code) !== null,
            default => false,
        };
    }

    /** رقم إصدار النص في المكتبة */
    public static function version(Document $d): string
    {
        return $d->type === 'sop' ? (SopLibrary::get($d->code)['version'] ?? '01') : '01';
    }

    public static function kpi(string $code): ?array
    {
        foreach (KpiSets::SETS as $s) {
            if ($s['code'] === $code) {
                return $s;
            }
        }

        return null;
    }

    /** للأنواع التي لها فراغات تُملأ في شاشة الوثيقة (الأوصاف الوظيفية تُملأ من شاشة الهيكل الوظيفي) */
    public static function hasBlanks(Document $d): bool
    {
        return $d->type === 'sop' && SopLibrary::get($d->code) && count(SopLibrary::placeholders(SopLibrary::get($d->code))) > 0;
    }

    /**
     * التوقيعات الإلكترونية لجدول الاعتماد: إعداد · مراجعة · اعتماد
     * ['prep' => [name, role, at], 'review' => [...], 'approve' => [...]] (null = لم يتم بعد)
     */
    public static function signatures(?DocumentVersion $v): array
    {
        if (! $v) {
            return ['prep' => null, 'review' => null, 'approve' => null];
        }
        $v->loadMissing('preparer.role');
        $a = collect($v->approvals ?? []);
        $pick = fn ($steps) => ($x = $a->whereIn('step', $steps)->values()) && $x->isNotEmpty()
            ? [$x->pluck('name')->implode(' · '), $x->pluck('role')->filter()->unique()->implode(' · '), (string) $x->last()['at']]
            : null;

        return [
            'prep'    => $v->preparer && $v->submitted_at
                ? [$v->preparer->displayName(), $v->preparer->role?->name_ar, $v->submitted_at->timezone('Africa/Cairo')->format('Y-m-d H:i')]
                : ($v->preparer ? [$v->preparer->displayName(), $v->preparer->role?->name_ar, null] : null),
            'review'  => $pick(['qa', 'hr']),
            'approve' => $pick(['ceo']),
        ];
    }
}