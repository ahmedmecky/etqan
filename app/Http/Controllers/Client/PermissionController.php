<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use App\Models\PermissionOverride;
use App\Models\Role;
use App\Models\User;
use App\Support\Access;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** مدير المخزن: صلاحيات الأدوار على الشاشات (الافتراضي من RACI) */
class PermissionController extends Controller
{
    public function index(Request $request): View
    {
        $tid = $request->user()->tenant_id;
        $roles = Role::assignableByClient()->get();
        $counts = User::query()->where('tenant_id', $tid)->where('is_active', true)->selectRaw('role_id, count(*) as n')->groupBy('role_id')->pluck('n', 'role_id');
        $exceptions = PermissionOverride::query()->whereNotNull('user_id')->with([])->get()->groupBy('user_id');
        $users = User::query()->whereIn('id', $exceptions->keys())->get()->keyBy('id');

        return view('client.permissions', [
            'roles' => $roles, 'counts' => $counts, 'modules' => Access::MODULES, 'levels' => Access::LEVELS,
            'tid' => $tid, 'exceptions' => $exceptions, 'users' => $users,
            'showAll' => $request->boolean('all'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tid = $request->user()->tenant_id;
        $data = $request->validate(['p' => ['array'], 'p.*' => ['array'], 'p.*.*' => ['in:none,view,use']]);
        $roles = Role::assignableByClient()->get()->keyBy('id');
        $n = 0;
        foreach ($data['p'] ?? [] as $roleId => $mods) {
            $role = $roles->get((int) $roleId);
            if (! $role) continue;
            foreach ($mods as $module => $level) {
                if (! isset(Access::MODULES[$module])) continue;
                $default = Access::default($role->key, $module);
                $q = PermissionOverride::query()->where('tenant_id', $tid)->where('role_id', $role->id)->whereNull('user_id')->where('module', $module);
                if ($level === $default) {
                    $n += $q->delete();
                } else {
                    PermissionOverride::updateOrCreate(['tenant_id' => $tid, 'role_id' => $role->id, 'user_id' => null, 'module' => $module],
                        ['level' => $level, 'updated_by' => $request->user()->id]);
                    $n++;
                }
            }
        }
        Access::forget();

        return back()->with('status', 'تم حفظ الصلاحيات.');
    }

    /** إعادة كل الأدوار للافتراضي من RACI (لا تمس استثناءات الأشخاص) */
    public function reset(Request $request): RedirectResponse
    {
        PermissionOverride::query()->whereNotNull('role_id')->whereNull('user_id')->delete();
        Access::forget();

        return back()->with('status', 'أُعيدت صلاحيات كل الأدوار للافتراضي من مصفوفة RACI.');
    }
}