<?php

namespace App\Support;

use App\Models\Document;
use App\Models\DocumentVersion;
use App\Models\FormRecord;
use App\Models\TempPoint;
use App\Models\TempReading;
use App\Models\User;

/**
 * ربط الإشعارات بالأحداث بدون تعديل الشاشات:
 * تُراقب السجلات عند الحفظ (FormRecord · TempReading · DocumentVersion) وترسل الإشعار المناسب.
 * يُسجَّل مرة واحدة في AppServiceProvider::boot()
 */
class NotifyHooks
{
    /**
     * مراحل «بانتظار الاعتماد» لكل نموذج: المرحلة ← [المرحلة الوسيطة أو null للاعتماد النهائي, العنوان]
     */
    private const WAITING = [
        'receiving'      => ['submitted' => [null, 'استلام شحنة بانتظار قرارك']],
        'deviation'      => ['reported'  => [null, 'انحراف بانتظار التصنيف']],
        'temp-excursion' => ['submitted' => ['evaluate', 'تقرير انحراف حرارة بانتظار تقييمك'],
                             'evaluated' => [null, 'تقرير انحراف حرارة بانتظار اعتمادك']],
        'training'       => ['held'      => ['approve', 'محضر تدريب بانتظار اعتمادك']],
    ];

    /** فتح السجل من الإشعار: بالرقم أو بالمعرف */
    private const OPEN_BY_ID = ['temp-excursion'];

    /** أنواع الشكوى العاجلة */
    private const URGENT_COMPLAINT = ['جودة منتج', 'اشتباه غش', 'أثر جانبي'];

    public static function register(): void
    {
        FormRecord::created(fn (FormRecord $r) => self::safe(fn () => self::record($r, null)));
        FormRecord::updated(function (FormRecord $r) {
            $old = $r->getOriginal('data');
            self::safe(fn () => self::record($r, is_array($old) ? ($old['stage'] ?? null) : null));
        });

        TempReading::saved(fn (TempReading $t) => self::safe(fn () => self::reading($t)));

        DocumentVersion::updated(function (DocumentVersion $v) {
            if ($v->wasChanged('stage')) {
                self::safe(fn () => self::document($v));
            }
        });
    }

    /** الإشعار لا يوقف الحفظ أبداً */
    private static function safe(callable $fn): void
    {
        try {
            $fn();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /* ===================== النماذج ===================== */

    private static function record(FormRecord $r, ?string $oldStage): void
    {
        $d = is_array($r->data) ? $r->data : [];
        $stage = $d['stage'] ?? null;
        $tenant = (int) $r->tenant_id;

        // التفتيش الميداني من رابط المفوض
        if ($r->form_code === FieldCheck::CODE) {
            if (! $r->wasRecentlyCreated) {
                return;
            }
            $bad = (int) ($d['bad'] ?? 0);
            Notifier::event($tenant, 'field',
                $bad ? "تفتيش ميداني: {$bad} عدم مطابقة" : 'تفتيش ميداني جديد',
                trim(($d['inspector'] ?? '') . ' · فاتورة ' . ($d['invoice'] ?? '') . ' · ' . ($d['pharmacy'] ?? '') . ' · ' . count($d['items'] ?? []) . ' صنف', ' ·'),
                route('field.show', $r->id), 'field-' . $r->id, $bad > 0);

            return;
        }

        if ($stage === null || $stage === $oldStage || $r->isFinal()) {
            return;
        }
        $key = self::formKey($r->form_code);
        if (! $key) {
            return;
        }
        $form = FormRegistry::get($key);
        $url = route('forms.page', $key) . '?open=' . urlencode(in_array($key, self::OPEN_BY_ID, true) ? (string) $r->id : $r->number);

        // الشكوى: عند وصولها للتصنيف (من الرابط أو من الشاشة)
        if ($key === 'complaint' && $stage === 'reported') {
            $type = (string) ($d['type'] ?? '');
            $portal = ($d['source'] ?? '') === 'portal';
            Notifier::event($tenant, 'complaint',
                'شكوى جديدة' . ($type ? ': ' . $type : '') . ($portal ? ' (رابط الصيدليات)' : ''),
                trim($r->number . ' · ' . ($d['customer'] ?? '') . ' · ' . mb_substr(str_replace("\n", ' · ', (string) ($d['details'] ?? '')), 0, 160), ' ·'),
                $url, 'cmp-' . $r->id, in_array($type, self::URGENT_COMPLAINT, true) || ($d['prio'] ?? '') === 'critical');

            return;
        }

        // بانتظار الاعتماد: لصاحب المرحلة
        if (isset(self::WAITING[$key][$stage])) {
            [$step, $title] = self::WAITING[$key][$stage];
            $body = match ($key) {
                'receiving'      => $r->number . ' · ' . ($d['supplier'] ?? '') . (! empty($d['invoice']) ? ' · فاتورة ' . $d['invoice'] : ''),
                'deviation'      => $r->number . ' · ' . mb_substr((string) ($d['what'] ?? ''), 0, 160),
                'temp-excursion' => $r->number . ' · ' . ($d['pointName'] ?? '') . (! empty($d['maxRead']) ? ' · أعلى قراءة ' . $d['maxRead'] . '°' : ''),
                'training'       => $r->number . ' · ' . ($d['topic'] ?? ''),
                default          => $r->number,
            };
            Notifier::toUsers(Notifier::approvers($tenant, $form, $step), $tenant, 'approval', Access::FORM_MODULE[$key] ?? null,
                $title, trim($body, ' ·'), $url, 'apv-' . $r->id, $key === 'temp-excursion');
        }
    }

    private static function formKey(string $code): ?string
    {
        foreach (FormRegistry::FORMS as $k => $f) {
            if ($f['code'] === $code) {
                return $k;
            }
        }

        return null;
    }

    /* ===================== الحرارة ===================== */

    private static function reading(TempReading $t): void
    {
        if ($t->status !== 'out' || (! $t->wasRecentlyCreated && ! $t->wasChanged('status'))) {
            return;
        }
        $tenant = (int) $t->tenant_id;
        $p = TempPoint::withoutGlobalScope('tenant')->find($t->point_id);
        $date = $t->date?->format('Y-m-d');
        $tempOut = $p && $t->cur !== null && ($t->cur > $p->t_max || $t->cur < $p->t_min);
        $humOut = $p && $p->h_max && $t->hum !== null && $t->hum > $p->h_max;
        $what = $tempOut && $humOut ? 'انحراف حرارة ورطوبة' : ($humOut ? 'انحراف رطوبة' : 'انحراف حرارة');

        $parts = [];
        if ($t->cur !== null) {
            $parts[] = TempPoint::n($t->cur) . '°' . ($p ? ' (الحد ' . TempPoint::n($p->t_min) . '–' . TempPoint::n($p->t_max) . '°)' : '');
        }
        if ($t->hum !== null && $p?->h_max) {
            $parts[] = 'رطوبة ' . $t->hum . '٪ (الحد ' . $p->h_max . '٪)';
        }

        Notifier::event($tenant, 'temp',
            $what . ' · ' . TempRounds::label($t->shift, $tenant),
            ($p?->name ?? 'نقطة') . ': ' . implode(' · ', $parts) . ($t->note ? ' · ' . mb_substr($t->note, 0, 100) : ''),
            route('forms.page', 'temp-excursion') . '?reading=' . $t->id,
            'temp-' . $date . '-' . $t->shift, true);
    }

    /* ===================== الوثائق ===================== */

    private static function document(DocumentVersion $v): void
    {
        $doc = Document::withoutGlobalScope('tenant')->find($v->document_id);
        if (! $doc) {
            return;
        }
        $tenant = (int) $v->tenant_id;
        $what = $doc->code . ' · ' . $doc->title . ' · إصدار ' . $v->version;
        $url = route('documents.show', $doc->id);

        if (isset(DocFlow::ROLES[$v->stage])) {
            $users = Notifier::users($tenant)
                ->filter(fn ($u) => DocFlow::canAct($u, $v->stage))
                ->reject(fn ($u) => $u->id === auth()->id());
            // الإرسال أو الاعتماد الجماعي: إشعار واحد مجمع لكل مرحلة في اليوم، والرابط لقائمة الانتظار
            Notifier::toUsers($users, $tenant, 'approval', 'documents', 'وثيقة تنتظر ' . ($v->stage === 'qa' ? 'مراجعتك' : 'اعتمادك'),
                $what, route('documents.queue'), 'doc-' . $v->stage . '-' . now('Africa/Cairo')->format('Ymd'));

            return;
        }

        if ($v->stage === 'returned' && $v->prepared_by && (int) $v->prepared_by !== (int) auth()->id()) {
            $u = User::with('role')->find($v->prepared_by);
            Notifier::toUsers($u ? [$u] : [], $tenant, 'approval', null, 'أُعيدت وثيقتك للتعديل',
                $what . ($v->return_note ? ' · ' . mb_substr($v->return_note, 0, 160) : ''), $url, 'doc-' . $v->id);
        }
    }
}