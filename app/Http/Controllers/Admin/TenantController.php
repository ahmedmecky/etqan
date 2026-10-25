<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\TenantRequest;
use App\Models\Role;
use App\Models\Tenant;
use App\Support\Egypt;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * مدير النظام: إدارة العملاء (الشركات / المخازن)
 */
class TenantController extends Controller
{
    public function index(Request $request): View
    {
        $q    = trim((string) $request->query('q', ''));
        $city = (string) $request->query('city', '');

        $tenants = Tenant::query()
            ->withCount('users')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('code', 'like', "%{$q}%")
                      ->orWhere('name_ar', 'like', "%{$q}%")
                      ->orWhere('name_en', 'like', "%{$q}%")
                      ->orWhere('gln', 'like', "%{$q}%")
                      ->orWhere('coverage_zones', 'like', "%{$q}%");
                });
            })
            ->when($city !== '', fn ($query) => $query->where('city', $city))
            ->orderBy('name_ar')
            ->get();

        return view('admin.tenants.index', [
            'tenants' => $tenants,
            'q'       => $q,
            'city'    => $city,
            'cities'  => Egypt::GOVERNORATES,
        ]);
    }

    public function create(): View
    {
        return view('admin.tenants.form', [
            'tenant' => new Tenant(['is_active' => true]),
            'admins' => collect(),
            'cities' => Egypt::GOVERNORATES,
        ]);
    }

    public function store(TenantRequest $request): RedirectResponse
    {
        $data = $this->payload($request);
        $tenant = Tenant::create($data);

        if ($request->hasFile('logo')) {
            $tenant->update(['logo_path' => $this->storeLogo($request->file('logo'), $tenant)]);
        }

        return redirect()->route('admin.tenants.edit', $tenant)
            ->with('status', "تم إنشاء العميل {$tenant->name_ar}. أضف الآن مدير العميل بالأسفل.");
    }

    public function edit(Tenant $tenant): View
    {
        $admins = $tenant->users()
            ->whereHas('role', fn ($q) => $q->where('level', Role::LEVEL_CLIENT_ADMIN))
            ->orderBy('name')
            ->get();

        return view('admin.tenants.form', [
            'tenant' => $tenant,
            'admins' => $admins,
            'cities' => Egypt::GOVERNORATES,
        ]);
    }

    public function update(TenantRequest $request, Tenant $tenant): RedirectResponse
    {
        $tenant->update($this->payload($request));

        if ($request->boolean('remove_logo') && $tenant->logo_path) {
            Storage::disk('public')->delete($tenant->logo_path);
            $tenant->update(['logo_path' => null]);
        }

        if ($request->hasFile('logo')) {
            if ($tenant->logo_path) {
                Storage::disk('public')->delete($tenant->logo_path);
            }
            $tenant->update(['logo_path' => $this->storeLogo($request->file('logo'), $tenant)]);
        }

        return redirect()->route('admin.tenants.edit', $tenant)->with('status', 'تم حفظ بيانات العميل.');
    }

    public function toggle(Tenant $tenant): RedirectResponse
    {
        $tenant->update(['is_active' => ! $tenant->is_active]);

        $msg = $tenant->is_active
            ? "تم تفعيل العميل {$tenant->name_ar}."
            : "تم إيقاف العميل {$tenant->name_ar}. لن يتمكن أي من مستخدميه من الدخول.";

        return back()->with('status', $msg);
    }

    /** البيانات المسموح بحفظها من النموذج */
    private function payload(TenantRequest $request): array
    {
        $v = $request->validated();

        return [
            'code'           => $v['code'],
            'name_ar'        => $v['name_ar'],
            'name_en'        => $v['name_en'],
            'phone'          => $v['phone'] ?? null,
            'address'        => $v['address'] ?? null,
            'city'           => $v['city'],
            'coverage_zones' => array_values($v['coverage_zones'] ?? []),
            'gln'            => $v['gln'] ?? null,
            'is_active'      => $request->boolean('is_active'),
        ];
    }

    private function storeLogo(UploadedFile $file, Tenant $tenant): string
    {
        return $file->storeAs('logos', $tenant->code . '-' . now()->format('YmdHis') . '.png', 'public');
    }
}