<?php

namespace App\Http\Controllers;

use App\Models\FormRecord;
use App\Models\TempCheck;
use App\Models\TempPoint;
use App\Models\TempReading;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * التحكم في الحرارة والرطوبة (SH-030)
 *   جولة القراءات (الصباح / المساء) لكل النقاط · نقاط المراقبة وحدودها
 *   سجل نصف الشهر لكل نقطة (F01 · F02) · التقرير الشهري · المراجعة الأسبوعية · اختبار الإنذار
 */
class TemperatureController extends Controller
{
    public const F03 = 'SH-030-F03';
    private const TZ = 'Africa/Cairo';

    /* ===================== الصلاحيات ===================== */

    /** إعداد نقاط المراقبة: مدير المخزن + مدير إدارة الجودة */
    public static function canManage(User $u): bool
    {
        return $u->isClientAdmin() || $u->hasRole('quality_manager');
    }

    /** المراجعة الأسبوعية وتعديل قراءات الأيام السابقة: مدير المخزن + المدير الصيدلي + مدير الجودة */
    public static function canReview(User $u): bool
    {
        return $u->isClientAdmin() || $u->hasRole('pharmacist_manager') || $u->hasRole('quality_manager');
    }

    private static function today(): Carbon
    {
        return Carbon::today(self::TZ);
    }

    private static function points(bool $activeOnly = true): Collection
    {
        return TempPoint::query()->when($activeOnly, fn ($q) => $q->where('is_active', true))->orderBy('sort')->orderBy('name')->get();
    }

    /** تقارير الانحراف F03 مربوطة برقم القراءة: [readingId => ['id', 'no', 'final']] */
    private static function excursions(): Collection
    {
        return FormRecord::query()->form(self::F03)->get(['id', 'number', 'status', 'data', 'record_date'])
            ->mapWithKeys(fn ($r) => [(int) ($r->data['readingId'] ?? 0) => ['id' => $r->id, 'no' => $r->number, 'final' => $r->status === FormRecord::STATUS_FINAL]])
            ->forget(0);
    }

    /* ===================== جولة القراءات ===================== */

    public function round(Request $request): View
    {
        $today = self::today();
        $date = $this->dateParam($request->query('date')) ?? $today->copy();
        if ($date->gt($today)) {
            $date = $today->copy();
        }
        $shift = in_array($request->query('shift'), ['am', 'pm'], true) ? $request->query('shift')
            : (Carbon::now(self::TZ)->hour < 14 ? 'am' : 'pm');

        $points = self::points();
        $readings = TempReading::query()->with(['user', 'editor'])->whereDate('date', $date->toDateString())->get()
            ->groupBy('shift')->map(fn ($g) => $g->keyBy('point_id'));

        return view('temperature.round', [
            'points'   => $points,
            'date'     => $date,
            'shift'    => $shift,
            'cur'      => $readings->get($shift, collect()),
            'other'    => $readings->get($shift === 'am' ? 'pm' : 'am', collect()),
            'done'     => ['am' => $readings->get('am', collect())->count(), 'pm' => $readings->get('pm', collect())->count()],
            'isToday'  => $date->isSameDay($today),
            'canEdit'  => $date->isSameDay($today) || self::canReview($request->user()),
            'canManage'=> self::canManage($request->user()),
            'exc'      => self::excursions(),
            'f03Url'   => route('forms.page', 'temp-excursion'),
        ]);
    }

    public function saveRound(Request $request): RedirectResponse
    {
        $user = $request->user();
        $today = self::today();
        $data = $request->validate([
            'date'  => ['required', 'date_format:Y-m-d', 'before_or_equal:' . $today->toDateString()],
            'shift' => ['required', Rule::in(['am', 'pm'])],
            'time'  => ['nullable', 'date_format:H:i'],
            'r'     => ['array'],
            'r.*.cur'  => ['nullable', 'numeric', 'between:-40,60'],
            'r.*.high' => ['nullable', 'numeric', 'between:-40,60'],
            'r.*.low'  => ['nullable', 'numeric', 'between:-40,60'],
            'r.*.hum'  => ['nullable', 'integer', 'between:0,100'],
            'r.*.note' => ['nullable', 'string', 'max:500'],
        ], [
            'date.before_or_equal' => 'لا يمكن تسجيل قراءات لتاريخ لم يأتِ بعد.',
            'r.*.cur.numeric'  => 'القراءة يجب أن تكون رقماً.',
            'r.*.high.numeric' => 'القراءة يجب أن تكون رقماً.',
            'r.*.low.numeric'  => 'القراءة يجب أن تكون رقماً.',
            'r.*.cur.between'  => 'قراءة غير منطقية (بين -٤٠ و٦٠).',
            'r.*.hum.between'  => 'الرطوبة بين ٠ و١٠٠٪.',
        ]);
        $date = Carbon::parse($data['date'], self::TZ);
        abort_unless($date->isSameDay($today) || self::canReview($user), 403, 'تعديل قراءات الأيام السابقة للمدير الصيدلي أو مدير الجودة فقط.');

        $points = self::points()->keyBy('id');
        $existing = TempReading::query()->whereDate('date', $data['date'])->where('shift', $data['shift'])->get()->keyBy('point_id');
        $errors = [];
        $rows = [];
        foreach ($data['r'] ?? [] as $pid => $r) {
            $p = $points->get((int) $pid);
            if (! $p) {
                continue;
            }
            $v = fn ($k) => isset($r[$k]) && $r[$k] !== '' && $r[$k] !== null ? (float) $r[$k] : null;
            [$cur, $high, $low] = [$v('cur'), $v('high'), $v('low')];
            $hum = isset($r['hum']) && $r['hum'] !== '' && $r['hum'] !== null ? (int) $r['hum'] : null;
            $note = trim((string) ($r['note'] ?? ''));
            if ($cur === null && $high === null && $low === null && $hum === null) {
                continue;   // لم تُسجل: تظهر ناقصة
            }
            if ($cur === null) {
                $errors["r.{$pid}.cur"] = "{$p->name}: سجل القراءة الحالية.";
                continue;
            }
            if ($high !== null && $low !== null && $high < $low) {
                $errors["r.{$pid}.high"] = "{$p->name}: الأعلى أقل من الأدنى.";
                continue;
            }
            if (($high !== null && $cur > $high) || ($low !== null && $cur < $low)) {
                $errors["r.{$pid}.cur"] = "{$p->name}: القراءة الحالية يجب أن تكون بين الأدنى والأعلى.";
                continue;
            }
            if ($p->h_max && $hum === null) {
                $errors["r.{$pid}.hum"] = "{$p->name}: سجل الرطوبة.";
                continue;
            }
            [$status] = $p->evaluate($cur, $high, $low, $hum);
            if ($status === 'out' && $note === '') {
                $errors["r.{$pid}.note"] = "{$p->name}: القراءة خارج الحدود. اكتب الإجراء الفوري (التحقق بجهاز آخر، السبب، إبلاغ المدير الصيدلي).";
                continue;
            }
            $rows[$p->id] = compact('cur', 'high', 'low', 'hum', 'note', 'status');
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $out = 0;
        foreach ($rows as $pid => $x) {
            $attrs = [
                'time' => $data['time'] ?? Carbon::now(self::TZ)->format('H:i'),
                'cur' => $x['cur'], 't_high' => $x['high'], 't_low' => $x['low'], 'hum' => $x['hum'],
                'status' => $x['status'], 'note' => $x['note'] ?: null,
            ];
            $old = $existing->get($pid);
            if ($old) {
                $old->update($attrs + ['updated_by' => $user->id]);
            } else {
                TempReading::create($attrs + [
                    'tenant_id' => $user->tenant_id, 'point_id' => $pid, 'date' => $data['date'],
                    'shift' => $data['shift'], 'user_id' => $user->id,
                ]);
            }
            $out += $x['status'] === 'out' ? 1 : 0;
        }

        $missing = $points->count() - $existing->keys()->merge(array_keys($rows))->unique()->count();
        $msg = 'تم حفظ ' . count($rows) . ' قراءة.';
        if ($missing > 0) $msg .= " لم تُسجل {$missing} نقطة بعد.";
        if ($out) $msg .= " {$out} قراءة خارج الحدود: افتح تقرير انحراف الحرارة (F03) لكل منها.";

        return redirect()->route('temperature.round', ['date' => $data['date'], 'shift' => $data['shift']])->with('status', $msg);
    }

    /* ===================== نقاط المراقبة ===================== */

    public function pointsPage(Request $request): View
    {
        return view('temperature.points', [
            'points'    => self::points(false),
            'types'     => TempPoint::TYPES,
            'defaults'  => TempPoint::DEFAULTS,
            'canManage' => self::canManage($request->user()),
        ]);
    }

    public function storePoint(Request $request): RedirectResponse
    {
        abort_unless(self::canManage($request->user()), 403);
        $data = $this->validatePoint($request);
        TempPoint::create($data + ['tenant_id' => $request->user()->tenant_id]);

        return back()->with('status', 'تمت إضافة نقطة المراقبة.');
    }

    public function updatePoint(Request $request, TempPoint $point): RedirectResponse
    {
        abort_unless(self::canManage($request->user()), 403);
        $point->update($this->validatePoint($request, $point->id));

        return back()->with('status', "تم حفظ «{$point->name}».");
    }

    public function togglePoint(Request $request, TempPoint $point): RedirectResponse
    {
        abort_unless(self::canManage($request->user()), 403);
        $point->update(['is_active' => ! $point->is_active]);

        return back()->with('status', $point->is_active ? "أُعيد تفعيل «{$point->name}»." : "أُوقفت «{$point->name}» (تبقى قراءاتها في السجل).");
    }

    /** نقاط بداية: منطقة التخزين العامة + ثلاجة */
    public function seedPoints(Request $request): RedirectResponse
    {
        abort_unless(self::canManage($request->user()), 403);
        if (TempPoint::query()->exists()) {
            return back();
        }
        $t = $request->user()->tenant_id;
        foreach ([['منطقة التخزين العامة', 'room'], ['منطقة الاستلام والحجر', 'room'], ['الثلاجة ١', 'fridge']] as $i => [$name, $type]) {
            TempPoint::create(['tenant_id' => $t, 'name' => $name, 'type' => $type, 'sort' => $i + 1] + TempPoint::DEFAULTS[$type]);
        }

        return back()->with('status', 'تمت إضافة ٣ نقاط بداية. عدّل الأسماء وأرقام الأجهزة وأضف باقي المناطق والثلاجات.');
    }

    /** للشاشة F03 */
    public function pointsJson(): JsonResponse
    {
        return response()->json(['points' => self::points(false)->map->toClient()->values()]);
    }

    /** قراءة واحدة (لملء تقرير الانحراف) */
    public function readingJson(TempReading $reading): JsonResponse
    {
        $reading->load('point');

        return response()->json(['reading' => [
            'id' => $reading->id, 'pointId' => $reading->point_id, 'date' => $reading->date->toDateString(),
            'shift' => $reading->shift, 'time' => substr((string) $reading->time, 0, 5),
            'cur' => $reading->cur, 'high' => $reading->t_high, 'low' => $reading->t_low, 'hum' => $reading->hum,
            'status' => $reading->status, 'note' => $reading->note,
            'f03' => self::excursions()->get($reading->id)['id'] ?? null,   // تقرير موجود لنفس القراءة
        ]]);
    }

    /* ===================== سجل نصف الشهر (F01 · F02) ===================== */

    public function sheet(Request $request): View
    {
        $points = self::points(false);
        $point = $points->firstWhere('id', (int) $request->query('point')) ?? $points->first();
        $month = $this->monthParam($request->query('month'));
        $half = $request->query('half') === '2' ? 2 : ((string) $request->query('half') === '1' ? 1 : (self::today()->format('Y-m') === $month && self::today()->day > 15 ? 2 : 1));
        $start = Carbon::parse($month . '-01', self::TZ);
        $from = $half === 1 ? $start->copy() : $start->copy()->day(16);
        $to = $half === 1 ? $start->copy()->day(15) : $start->copy()->endOfMonth()->startOfDay();

        $readings = $point ? TempReading::query()->with('user')->where('point_id', $point->id)
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])->get()
            ->groupBy(fn ($r) => $r->date->toDateString())->map(fn ($g) => $g->keyBy('shift')) : collect();

        $reviews = TempCheck::query()->with('user')->where('kind', 'review')
            ->whereBetween('date', [$from->copy()->subDays(6)->toDateString(), $to->toDateString()])->orderBy('date')->get();
        $alarms = $point ? TempCheck::query()->with('user')->where('kind', 'alarm')->where('point_id', $point->id)
            ->where('date', 'like', $month . '%')->orderBy('date')->get() : collect();

        $days = [];
        for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
            $days[] = $d->copy();
        }

        return view('temperature.sheet', compact('points', 'point', 'month', 'half', 'from', 'to', 'readings', 'reviews', 'alarms', 'days') + [
            'exc' => self::excursions(),
            'tenant' => $request->user()->tenant,
        ]);
    }

    /* ===================== التقرير الشهري ===================== */

    public function report(Request $request): View
    {
        $month = $this->monthParam($request->query('month'));
        $today = self::today();
        $start = Carbon::parse($month . '-01', self::TZ);
        $end = $start->copy()->endOfMonth()->startOfDay();
        $points = self::points(false);
        $readings = TempReading::query()->with('point')->whereBetween('date', [$start->toDateString(), $end->toDateString()])->get();
        $exc = self::excursions();

        // الجولات المتوقعة: كل يوم عمل (عدا الجمعة) حتى أمس صباحاً ومساءً، واليوم حسب الوقت
        $hour = Carbon::now(self::TZ)->hour;
        $slots = [];
        for ($d = $start->copy(); $d->lte($end) && $d->lte($today); $d->addDay()) {
            if ($d->dayOfWeek === Carbon::FRIDAY) continue;
            $isToday = $d->isSameDay($today);
            if (! $isToday || $hour >= 12) $slots[] = [$d->toDateString(), 'am'];
            if (! $isToday || $hour >= 20) $slots[] = [$d->toDateString(), 'pm'];
        }
        $have = $readings->map(fn ($r) => $r->point_id . '|' . $r->date->toDateString() . '|' . $r->shift)->flip();

        $stats = $points->filter(fn ($p) => $p->is_active || $readings->where('point_id', $p->id)->isNotEmpty())->map(function ($p) use ($readings, $slots, $have) {
            $rs = $readings->where('point_id', $p->id);
            $miss = collect($slots)->reject(fn ($s) => $have->has($p->id . '|' . $s[0] . '|' . $s[1]))->values();
            $highs = $rs->map(fn ($r) => $r->t_high ?? $r->cur)->filter(fn ($v) => $v !== null);
            $lows = $rs->map(fn ($r) => $r->t_low ?? $r->cur)->filter(fn ($v) => $v !== null);

            return [
                'p' => $p, 'count' => $rs->count(), 'expected' => $p->is_active ? count($slots) : $rs->count(),
                'missing' => $p->is_active ? $miss : collect(),
                'max' => $highs->max(), 'min' => $lows->min(),
                'avg' => $rs->whereNotNull('cur')->avg('cur'),
                'humMax' => $rs->whereNotNull('hum')->max('hum'),
                'out' => $rs->where('status', 'out')->count(), 'warn' => $rs->where('status', 'warn')->count(),
            ];
        })->values();

        $outs = $readings->where('status', 'out')->sortByDesc(fn ($r) => $r->date->toDateString() . $r->shift)->values();

        // المراجعة الأسبوعية (الأسبوع يبدأ السبت)
        $reviews = TempCheck::query()->with('user')->where('kind', 'review')
            ->whereBetween('date', [$start->copy()->subDays(6)->toDateString(), $end->toDateString()])->get()
            ->keyBy(fn ($c) => $c->date->toDateString());
        $weeks = [];
        for ($w = $start->copy()->startOfWeek(Carbon::SATURDAY); $w->lte($end); $w->addWeek()) {
            $weeks[] = ['start' => $w->copy(), 'end' => $w->copy()->addDays(6), 'review' => $reviews->get($w->toDateString()),
                'due' => $w->copy()->addDays(6)->lt($today)];
        }

        // اختبار الإنذار الشهري للثلاجات وغرف التبريد
        $alarms = TempCheck::query()->with('user')->where('kind', 'alarm')->where('date', 'like', $month . '%')->orderByDesc('date')->get()->groupBy('point_id');

        $f03 = FormRecord::query()->form(self::F03)->where('record_date', 'like', $month . '%')->orderByDesc('record_date')->get();

        return view('temperature.report', [
            'month' => $month, 'stats' => $stats, 'outs' => $outs, 'exc' => $exc, 'weeks' => $weeks,
            'alarms' => $alarms, 'cold' => $points->filter(fn ($p) => $p->is_active && $p->isCold())->values(),
            'f03' => $f03, 'f03Url' => route('forms.page', 'temp-excursion'),
            'canReview' => self::canReview($request->user()), 'today' => $today,
        ]);
    }

    /** مراجعة أسبوعية (المدير الصيدلي) أو اختبار إنذار */
    public function storeCheck(Request $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validate([
            'kind'     => ['required', Rule::in(['review', 'alarm'])],
            'date'     => ['required', 'date_format:Y-m-d', 'before_or_equal:' . self::today()->toDateString()],
            'point_id' => ['required_if:kind,alarm', 'nullable', 'integer'],
            'result'   => ['required_if:kind,alarm', 'nullable', Rule::in(['ok', 'fail'])],
            'note'     => ['nullable', 'string', 'max:500'],
        ], ['result.required_if' => 'حدد نتيجة اختبار الإنذار.', 'date.before_or_equal' => 'التاريخ في المستقبل.']);

        if ($data['kind'] === 'review') {
            abort_unless(self::canReview($user), 403, 'المراجعة الأسبوعية للمدير الصيدلي.');
            $week = Carbon::parse($data['date'], self::TZ)->startOfWeek(Carbon::SATURDAY)->toDateString();
            TempCheck::updateOrCreate(['tenant_id' => $user->tenant_id, 'kind' => 'review', 'date' => $week, 'point_id' => null],
                ['note' => $data['note'] ?? null, 'user_id' => $user->id]);
            $msg = 'تم توقيع المراجعة الأسبوعية لبيانات أجهزة التسجيل.';
        } else {
            $point = TempPoint::query()->findOrFail($data['point_id']);
            if ($data['result'] === 'fail' && ! trim((string) ($data['note'] ?? ''))) {
                throw ValidationException::withMessages(['note' => 'الإنذار لا يعمل: اكتب الإجراء (إصلاح، إبلاغ، مراقبة بديلة).']);
            }
            TempCheck::create(['tenant_id' => $user->tenant_id, 'kind' => 'alarm', 'point_id' => $point->id, 'date' => $data['date'],
                'result' => $data['result'], 'note' => $data['note'] ?? null, 'user_id' => $user->id]);
            $msg = "تم تسجيل اختبار إنذار «{$point->name}»" . ($data['result'] === 'fail' ? ' (لا يعمل). سجل انحرافاً SH-008.' : '.');
        }

        return back()->with('status', $msg);
    }

    /* ===================== تنبيهات الصفحة الرئيسية ===================== */

    /** ['missing' => نص الجولة الناقصة اليوم أو null, 'open' => قراءات خارج الحدود بدون تقرير F03] */
    public static function homeAlerts(User $user): array
    {
        $res = ['missing' => null, 'open' => 0];
        if (! $user->tenant_id || ! self::canReview($user)) {
            return $res;
        }
        $count = TempPoint::query()->where('is_active', true)->count();
        if (! $count) {
            return $res;
        }
        $now = Carbon::now(self::TZ);
        if ($now->dayOfWeek !== Carbon::FRIDAY) {
            $done = TempReading::query()->whereDate('date', $now->toDateString())->get()->groupBy('shift')->map->count();
            if ($now->hour >= 12 && ($done['am'] ?? 0) < $count) $res['missing'] = 'الصباح';
            elseif ($now->hour >= 20 && ($done['pm'] ?? 0) < $count) $res['missing'] = 'المساء';
        }
        $linked = self::excursions()->keys();
        $res['open'] = TempReading::query()->where('status', 'out')->where('date', '>=', $now->copy()->subDays(60)->toDateString())
            ->whereNotIn('id', $linked->all() ?: [0])->count();

        return $res;
    }

    /* ======================================================= */

    private function validatePoint(Request $request, ?int $id = null): array
    {
        $data = $request->validate([
            'name'      => ['required', 'string', 'max:120', Rule::unique('temp_points', 'name')->where('tenant_id', $request->user()->tenant_id)->ignore($id)],
            'type'      => ['required', Rule::in(array_keys(TempPoint::TYPES))],
            'device_no' => ['nullable', 'string', 'max:60'],
            't_min'     => ['required', 'numeric', 'between:-40,60'],
            't_max'     => ['required', 'numeric', 'between:-40,60', 'gt:t_min'],
            'warn_low'  => ['nullable', 'numeric', 'gte:t_min', 'lte:t_max'],
            'warn_high' => ['nullable', 'numeric', 'gte:t_min', 'lte:t_max'],
            'h_max'     => ['nullable', 'integer', 'between:1,100'],
            'sort'      => ['nullable', 'integer', 'between:0,999'],
        ], [
            'name.required' => 'اكتب اسم المنطقة أو الثلاجة.',
            'name.unique'   => 'هذا الاسم مستخدم لنقطة أخرى.',
            't_max.gt'      => 'الحد الأعلى يجب أن يكون أكبر من الأدنى.',
            'warn_low.gte'  => 'حد الإنذار يجب أن يكون داخل الحدود.',
            'warn_low.lte'  => 'حد الإنذار يجب أن يكون داخل الحدود.',
            'warn_high.gte' => 'حد الإنذار يجب أن يكون داخل الحدود.',
            'warn_high.lte' => 'حد الإنذار يجب أن يكون داخل الحدود.',
        ]);
        $data['sort'] = $data['sort'] ?? 0;

        return $data;
    }

    private function dateParam($v): ?Carbon
    {
        return is_string($v) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? Carbon::parse($v, self::TZ)->startOfDay() : null;
    }

    private function monthParam($v): string
    {
        return is_string($v) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $v) ? $v : self::today()->format('Y-m');
    }
}