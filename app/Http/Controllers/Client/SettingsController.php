<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\Pharmacy;
use App\Models\Product;
use App\Support\Settings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * إعدادات المخزن · مدير العميل فقط
 */
class SettingsController extends Controller
{
    public function edit(Request $request): View
    {
        $tenant = $request->user()->tenant;

        return view('client.settings', [
            'tenant'     => $tenant,
            'groups'     => Settings::GROUPS,
            'defs'       => Settings::DEFINITIONS,
            'values'     => Settings::forTenant($tenant->id),
            'portalUrl'  => $tenant->complaintUrl(),
            'products'   => Product::count(),
            'pharmacies' => Pharmacy::count(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate(Settings::rules(), [
            'numeric' => 'القيمة يجب أن تكون رقماً.',
            'min'     => 'القيمة أقل من المسموح.',
            'max'     => 'القيمة أكبر من المسموح.',
        ]);

        $changed = Settings::save($request->user()->tenant_id, $data, $request->user()->id);

        return redirect()->route('client.settings.edit')->with('status', $changed
            ? 'تم حفظ الإعدادات: ' . implode('، ', $changed) . '.'
            : 'لا توجد تغييرات.');
    }

    /** تشغيل / إيقاف رابط شكاوى الصيدليات */
    public function togglePortal(Request $request): RedirectResponse
    {
        $tenant = $request->user()->tenant;
        $tenant->update(['complaint_portal' => ! $tenant->complaint_portal]);

        return redirect()->route('client.settings.edit')->with('status', $tenant->complaint_portal
            ? 'تم تشغيل رابط شكاوى الصيدليات.'
            : 'تم إيقاف رابط شكاوى الصيدليات. من يفتح الرابط يرى رسالة أنه متوقف.');
    }

    /** رابط جديد · الرابط القديم يتوقف فوراً */
    public function regeneratePortal(Request $request): RedirectResponse
    {
        $request->user()->tenant->regenerateComplaintToken();

        return redirect()->route('client.settings.edit')->with('status', 'تم إنشاء رابط جديد. الرابط القديم لم يعد يعمل؛ أرسل الرابط الجديد للصيدليات.');
    }
}