<?php

namespace App\Http\Controllers;

use App\Models\TenantJob;
use App\Models\User;
use App\Support\JobLibrary;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * الهيكل الوظيفي والأوصاف الوظيفية (SH-021)
 *   الهيكل: الوظائف الموجودة في المخزن وعددها والشاغر منها وتوزيع مهامه
 *   الوصف الوظيفي: فارغ للتعيين، أو باسم الموظف مع المهام الإضافية، وإقرار الاستلام
 */
class JobController extends Controller
{
    /** إدارة الهيكل وطباعة أوصاف الموظفين: مدير المخزن ومدير الموارد البشرية */
    public static function canManage(User $u): bool
    {
        return $u->isClientAdmin() || $u->hasRole('hr_manager', 'hr_officer');
    }

    private function guard(Request $r): void
    {
        abort_unless(self::canManage($r->user()), 403, 'الهيكل الوظيفي لمدير المخزن والموارد البشرية.');
    }

    /* ===================== الهيكل الوظيفي ===================== */

    public function index(Request $request): View
    {
        $this->guard($request);
        $tid = $request->user()->tenant_id;
        $settings = JobLibrary::settings();
        $count = JobLibrary::headcount($tid);
        $users = User::query()->where('tenant_id', $tid)->where('is_active', true)->get();

        $rows = JobLibrary::all()->map(function ($job, $code) use ($settings, $count, $users) {
            $t = $settings[$code] ?? null;
            $mode = $t->mode ?? 'staffed';
            $n = $count[$code] ?? 0;
            $groups = JobLibrary::groups($job);
            $needs = JobLibrary::needsCover($code, $settings, $count);
            $assigned = collect($groups)->filter(fn ($g) => ! empty($t?->reassign[$g['key']] ?? null))->count();

            return [
                'code' => $code, 'title' => $job['title'], 'dept' => JobLibrary::dept($code), 'mode' => $mode,
                'planned' => $t->planned ?? 0, 'count' => $n, 'needs' => $needs && $mode !== 'na',
                'groups' => count($groups), 'assigned' => $assigned,
                'people' => $users->filter(fn ($u) => in_array($code, $u->jobCodes(), true))->map->displayName()->values(),
            ];
        })->values();

        return view('jobs.index', [
            'rows' => $rows,
            'noJob' => $users->filter(fn ($u) => ! $u->job_code)->values(),
            'depts' => JobLibrary::DEPTS,
            'modes' => TenantJob::MODES,
        ]);
    }

    public function edit(Request $request, string $code): View
    {
        $this->guard($request);
        $job = JobLibrary::get($code) ?? abort(404);
        $settings = JobLibrary::settings();
        $count = JobLibrary::headcount($request->user()->tenant_id);
        $t = $settings[$code] ?? new TenantJob(['job_code' => $code, 'mode' => 'staffed', 'planned' => 0]);

        // الوظائف التي يمكن أن تستلم المهام: لها موظف فعال حالياً
        $targets = JobLibrary::all()->filter(fn ($j, $c) => $c !== $code && ($count[$c] ?? 0) > 0)
            ->map(fn ($j, $c) => $j['title'] . ' (' . ($count[$c] ?? 0) . ')');

        return view('jobs.edit', [
            'job' => $job, 'code' => $code, 't' => $t, 'count' => $count[$code] ?? 0,
            'needs' => JobLibrary::needsCover($code, $settings->put($code, $t), $count),
            'placeholders' => JobLibrary::placeholders($job), 'tenant' => $request->user()->tenant,
            'groups' => JobLibrary::groups($job), 'targets' => $targets, 'modes' => TenantJob::MODES,
        ]);
    }

    public function update(Request $request, string $code): RedirectResponse
    {
        $this->guard($request);
        $job = JobLibrary::get($code) ?? abort(404);
        $keys = collect(JobLibrary::groups($job))->pluck('key')->all();
        $data = $request->validate([
            'mode'       => ['required', Rule::in(array_keys(TenantJob::MODES))],
            'planned'    => ['nullable', 'integer', 'min:0', 'max:999'],
            'blanks'     => ['nullable', 'array'],
            'blanks.*'   => ['nullable', 'string', 'max:200'],
            'reassign'   => ['nullable', 'array'],
            'reassign.*' => ['nullable', Rule::in(JobLibrary::all()->keys()->all())],
            'note'       => ['nullable', 'string', 'max:1000'],
        ]);

        TenantJob::updateOrCreate(['tenant_id' => $request->user()->tenant_id, 'job_code' => $code], [
            'mode' => $data['mode'],
            'planned' => (int) ($data['planned'] ?? 0),
            'blanks' => array_map(fn ($v) => trim((string) $v), $data['blanks'] ?? []),
            'reassign' => collect($data['reassign'] ?? [])->only($keys)->filter()->all(),
            'note' => $data['note'] ?? null,
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('jobs.index')->with('status', "تم حفظ إعدادات «{$job['title']}».");
    }

    /* ===================== الطباعة ===================== */

    /** الوصف الوظيفي فارغاً (للتعيين أو الإعلان عن الوظيفة) */
    public function printJob(Request $request, string $code): View
    {
        $this->guard($request);

        return $this->render($request, JobLibrary::get($code) ?? abort(404), null);
    }

    /** الوصف الوظيفي باسم الموظف (مع وظائفه الإضافية والمهام المحولة له) */
    public function printUser(Request $request, User $user): View
    {
        abort_unless((int) $user->tenant_id === (int) $request->user()->tenant_id, 404);
        abort_unless(self::canManage($request->user()) || $user->id === $request->user()->id, 403);
        // مدير المخزن بدون وظيفة محددة: وصف مدير المخزن
        if (! $user->job_code && $user->isClientAdmin()) {
            $user->job_code = 'JD-MG-01';
        }
        $job = JobLibrary::get($user->job_code);
        if (! $job) {
            return view('jobs.none', ['user' => $user, 'canManage' => self::canManage($request->user())]);
        }

        return $this->render($request, $job, $user);
    }

    /** وصفي الوظيفي (للموظف نفسه) */
    public function mine(Request $request): View
    {
        return $this->printUser($request, $request->user());
    }

    /** تسجيل إقرار الموظف باستلام الوصف (توقيع ورقي) */
    public function acknowledge(Request $request, User $user): RedirectResponse
    {
        $this->guard($request);
        abort_unless((int) $user->tenant_id === (int) $request->user()->tenant_id, 404);
        $d = $request->validate(['date' => ['required', 'date', 'before_or_equal:today']], ['date.required' => 'اكتب تاريخ توقيع الموظف.']);
        $user->forceFill(['jd_ack_at' => $d['date']])->save();

        return back()->with('status', "تم تسجيل إقرار {$user->displayName()} باستلام الوصف الوظيفي.");
    }

    private function render(Request $request, array $job, ?User $user): View
    {
        $tenant = $request->user()->tenant;
        $settings = JobLibrary::settings();
        $count = JobLibrary::headcount($tenant->id);
        $blanks = fn ($c) => $settings[$c]->blanks ?? [];
        $main = JobLibrary::fill($job, $blanks($job['code']), $tenant);

        $extra = [];
        $inherited = [];
        if ($user) {
            foreach (array_diff($user->extra_jobs ?? [], [$user->job_code]) as $c) {
                if ($j = JobLibrary::get($c)) $extra[] = JobLibrary::fill($j, $blanks($c), $tenant);
            }
            foreach (JobLibrary::inherited($user->jobCodes(), $settings, $count) as $h) {
                $filled = JobLibrary::fill(JobLibrary::get($h['from']), $h['blanks'], $tenant);
                $g = collect(JobLibrary::groups($filled))->firstWhere('key', $h['group']['key']);
                $inherited[] = ['from' => $h['from'], 'fromTitle' => $h['fromTitle'], 'group' => $g];
            }
        }

        // التدريب الإلزامي: الوظيفة الأساسية + الإضافية (بدون تكرار)
        $training = collect([$main, ...$extra])->flatMap(function ($j) {
            $s = JobLibrary::section($j, 'التدريب');
            return collect($s['blocks'] ?? [])->where('type', 'table')->flatMap(fn ($b) => $b['rows']);
        })->unique(fn ($r) => $r[0] ?? '')->values()->all();

        return view('jobs.print', [
            'job' => $main, 'user' => $user, 'extra' => $extra, 'inherited' => $inherited, 'training' => $training,
            'tenant' => $tenant, 'note' => $settings[$job['code']]->note ?? null,
            'canManage' => self::canManage($request->user()),
            'today' => Carbon::today('Africa/Cairo')->toDateString(),
        ]);
    }
}