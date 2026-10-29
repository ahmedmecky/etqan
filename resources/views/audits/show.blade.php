@extends('layouts.app')

@section('title', $a->number . ' · ' . $a->title)

@push('head')
  @include('audits._style')
@endpush

@section('content')
@php
  $G = \App\Support\Audits::GRADES;
  $steps = ['plan', 'fieldwork', 'reported', 'response', 'closed'];
  $at = array_search($a->status, $steps, true);
  $acc = $a->auditor;
  $areaTab = fn ($ar) => route('audits.show', [$a, 'tab' => 'area-' . $ar->id]);
  $src = $a->type === 'authority' ? 'تفتيش هيئة الدواء' : 'تفتيش ذاتي (SH-012)';
@endphp
<div class="au">
  <div class="head-actions">
    <div>
      <p style="margin:0 0 4px"><a href="{{ route('audits.index') }}">← المراجعات الخارجية</a></p>
      <h1 class="page-title" style="margin-bottom:2px">{{ $a->title }} <span class="ltr" style="font-size:15px;color:var(--muted)">{{ $a->number }}</span></h1>
      <p class="page-sub" style="margin:0">{{ $a->typeLabel() }}@if ($a->company) · {{ $a->company }}@endif @if ($a->lead) · {{ $a->lead }}@endif
        · <span class="ltr">{{ $a->start_date->format('Y-m-d') }}</span> ← <span class="ltr">{{ $a->end_date->format('Y-m-d') }}</span></p>
    </div>
    <div class="actions">
      <a href="{{ route('audits.print', $a) }}" target="_blank" class="btn ghost">طباعة التقرير</a>
      @if ($a->file_path)<a href="{{ route('audits.file', $a) }}" target="_blank" class="btn ghost small">تقرير الجهة المرفق</a>@endif
    </div>
  </div>

  <div class="flow">
    @foreach (\App\Support\Audits::STATUSES as $k => $lbl)
      <span class="{{ $k === $a->status ? 'on' : (array_search($k, $steps, true) < $at ? 'done' : '') }}">{{ $lbl }}</span>
    @endforeach
  </div>

  @if ($isAuditor)
    <div class="alert" style="background:#eef6f5;border-color:#cfe5e1">مرحباً {{ auth()->user()->displayName() }}. حسابك للاطلاع على كل شاشات النظام وسجلاته، والكتابة في هذه المراجعة فقط، وينتهي في <b class="ltr">{{ auth()->user()->access_until?->format('Y-m-d') }}</b>.
      اختر الإدارة من القائمة، اكتب ما راجعته، وسجّل الملاحظات بدرجتها والدليل والمرجع. عند الانتهاء: «التقرير» ← إرسال.</div>
  @endif

  <div class="lay">
    {{-- ===================== القائمة ===================== --}}
    <nav class="nav">
      <a href="{{ route('audits.show', [$a, 'tab' => 'plan']) }}" class="{{ $tab === 'plan' ? 'on' : '' }}">الخطة والإدارات</a>
      <hr>
      @foreach ($a->areas as $ar)
        <a href="{{ $areaTab($ar) }}" class="{{ $tab === 'area' && $area->id === $ar->id ? 'on' : '' }}">@if ($ar->done)<span class="ok">✓</span>@endif {{ $ar->name }}<span class="c">{{ $ar->findings->count() }}</span></a>
      @endforeach
      @if ($a->areas->isEmpty())<span class="hint" style="padding:6px 10px;display:block">لم تُضف إدارات بعد</span>@endif
      <hr>
      <a href="{{ route('audits.show', [$a, 'tab' => 'report']) }}" class="{{ $tab === 'report' ? 'on' : '' }}">التقرير والخلاصة<span class="c">{{ $s['total'] }}</span></a>
      <a href="{{ route('audits.show', [$a, 'tab' => 'capa']) }}" class="{{ $tab === 'capa' ? 'on' : '' }}">الرد والإجراءات @if ($s['open'])<span class="c">{{ $s['open'] }}</span>@endif</a>
      @if ($manage && $a->auditor_user_id)<a href="{{ route('audits.show', [$a, 'tab' => 'log']) }}" class="{{ $tab === 'log' ? 'on' : '' }}">سجل دخول المراجع</a>@endif
    </nav>

    <div>
      {{-- ===================== الخطة ===================== --}}
      @if ($tab === 'plan')
        <div class="box">
          <h2>الخطة</h2>
          <div class="hint" style="margin-bottom:8px;white-space:pre-line">@if ($a->scope)<b>النطاق:</b> {{ $a->scope }}
@endif<b>المعايير:</b> {{ $a->criteria ?: '—' }}@if ($a->team)
<b>الفريق:</b> {{ $a->lead }} · {{ $a->team }}@endif</div>
          @if ($manage && $a->status !== 'closed')
            <details>
              <summary class="hint" style="cursor:pointer;font-weight:700">تعديل الخطة ▾</summary>
              <form method="POST" action="{{ route('audits.update', $a) }}" enctype="multipart/form-data" style="margin-top:8px">
                @csrf
                @method('PUT')
                @include('audits._plan', ['a' => $a])
                <div class="actions" style="margin-top:10px"><button type="submit" class="btn small">حفظ</button></div>
              </form>
            </details>
          @endif
        </div>

        @if ($manage)
          <div class="box">
            <h2>حساب المراجع الخارجي</h2>
            @if ($acc)
              <p style="margin:0 0 8px">{{ $acc->displayName() }} · <span class="ltr">{{ $acc->email }}</span> ·
                @if ($acc->is_active && $acc->access_until && $acc->access_until->format('Y-m-d') >= now('Africa/Cairo')->toDateString())
                  <span class="st ok">فعال حتى {{ $acc->access_until->format('Y-m-d') }}</span>
                @else
                  <span class="st bad">منتهي / موقوف</span>
                @endif
                @if ($acc->last_login_at) · آخر دخول <span class="ltr">{{ $acc->last_login_at->timezone('Africa/Cairo')->format('Y-m-d H:i') }}</span>@endif
              </p>
            @else
              <p class="hint" style="margin-top:0">حساب مؤقت: يرى كل شاشات النظام وسجلاته للاطلاع فقط (عدا تقييمات الأداء)، ويكتب ملاحظاته في هذه المراجعة فقط.
                كل صفحة يفتحها تُسجل، ومطبوعاته عليها علامة «نسخة للمراجع الخارجي»، وينتهي تلقائياً في التاريخ المحدد.</p>
            @endif
            <form method="POST" action="{{ route('audits.account', $a) }}">
              @csrf
              <div class="mf">
                @unless ($acc)
                  <label>اسم المراجع <input type="text" name="name" maxlength="120" value="{{ old('name', $a->lead) }}"></label>
                  <label>البريد (اسم الدخول) <input type="email" name="email" maxlength="160" class="ltr" value="{{ old('email') }}"></label>
                @endunless
                <label>{{ $acc ? 'كلمة مرور جديدة (اختياري)' : 'كلمة مرور مبدئية' }} <input type="text" name="password" class="ltr" autocomplete="off"></label>
                <label>الحساب صالح حتى <input type="date" name="access_until" required value="{{ old('access_until', $acc?->access_until?->format('Y-m-d') ?? $a->end_date->copy()->addDays(7)->format('Y-m-d')) }}"></label>
              </div>
              <div class="actions" style="margin-top:10px">
                <button type="submit" class="btn small">{{ $acc ? 'تحديث الحساب' : 'إنشاء الحساب' }}</button>
                @if ($acc)<button type="submit" name="do" value="stop" class="btn ghost small" formnovalidate>إيقاف الحساب الآن</button>@endif
              </div>
            </form>
          </div>
        @endif

        <div class="box">
          <h2>الإدارات ({{ $a->areas->count() }})</h2>
          @foreach ($a->areas as $ar)
            <p style="margin:0 0 6px"><a href="{{ $areaTab($ar) }}"><b>{{ $ar->name }}</b></a> <span class="hint ltr">{{ $ar->sops }}</span>
              · {{ $ar->findings->count() }} ملاحظة @if ($ar->done)<span class="st ok">رُوجعت</span>@endif</p>
          @endforeach
          @if ($write)
            @php
              $left = collect(\App\Support\Audits::AREAS)->reject(fn ($v, $k) => $a->areas->contains('name', $k));
            @endphp
            <form method="POST" action="{{ route('audits.areas.add', $a) }}" style="margin-top:10px;border-top:1px solid #eef1f0;padding-top:10px">
              @csrf
              <b style="font-size:13.5px">+ إضافة إدارة</b>
              @if ($left->isNotEmpty())
                <div class="grades" style="margin:8px 0">
                  @foreach ($left as $name => $sops)<label><input type="checkbox" name="preset[]" value="{{ $name }}"> {{ $name }}</label>@endforeach
                </div>
              @endif
              <div class="mf">
                <label class="two">أو إدارة / قسم آخر <input type="text" name="name" maxlength="160" placeholder="مثال: فرع سموحة · مخزن القطاعي"></label>
                <label>الإجراءات المرتبطة <input type="text" name="sops" maxlength="120" class="ltr" placeholder="SH-005 · SH-016"></label>
              </div>
              <div class="actions" style="margin-top:8px"><button type="submit" class="btn small">إضافة</button></div>
            </form>
          @endif
        </div>
      @endif

      {{-- ===================== شاشة الإدارة ===================== --}}
      @if ($tab === 'area')
        @php
          $ids = $a->areas->pluck('id')->values();
          $pos = $ids->search($area->id);
          $prev = $pos > 0 ? $a->areas[$pos - 1] : null;
          $next = $pos < $ids->count() - 1 ? $a->areas[$pos + 1] : null;
        @endphp
        <div class="box">
          <h2>{{ $area->name }} <span class="hint ltr">{{ $area->sops }}</span></h2>
          @if ($write)
            <form method="POST" action="{{ route('audits.areas.update', [$a, $area]) }}">
              @csrf
              @method('PUT')
              <div class="mf">
                <label>اسم الإدارة <input type="text" name="name" maxlength="160" required value="{{ $area->name }}"></label>
                <label>الإجراءات <input type="text" name="sops" maxlength="120" class="ltr" value="{{ $area->sops }}"></label>
                <label>تاريخ المراجعة <input type="date" name="audited_on" value="{{ $area->audited_on?->format('Y-m-d') ?? now('Africa/Cairo')->toDateString() }}"></label>
                <label class="full">من قابلهم المراجع <input type="text" name="auditee" maxlength="160" value="{{ $area->auditee }}" placeholder="الاسم والوظيفة"></label>
                <label class="full">ما تمت مراجعته (نص حر) <textarea name="notes" rows="5" maxlength="10000" placeholder="السجلات والعينات التي رُوجعت، الأماكن التي زيرت، الأسئلة، الانطباع العام…">{{ $area->notes }}</textarea></label>
              </div>
              <label class="chk" style="margin:8px 0 0;display:flex;gap:6px;align-items:center"><input type="checkbox" name="done" value="1" @checked($area->done)> انتهت مراجعة هذه الإدارة</label>
              <div class="actions" style="margin-top:8px">
                <button type="submit" class="btn small">حفظ</button>
                @if ($area->findings->isEmpty())<button type="submit" form="delArea" class="btn ghost small">حذف الإدارة</button>@endif
              </div>
            </form>
            <form method="POST" action="{{ route('audits.areas.destroy', [$a, $area]) }}" id="delArea" hidden>@csrf @method('DELETE')</form>
          @else
            <div class="hint" style="white-space:pre-line">@if ($area->auditee)<b>من قابلهم:</b> {{ $area->auditee }}
@endif{{ $area->notes ?: 'لا يوجد وصف.' }}</div>
          @endif
        </div>

        <h2 style="font-size:15.5px;margin:4px 0 8px">الملاحظات ({{ $area->findings->count() }})</h2>
        @include('audits._findings', ['list' => $area->findings])

        @if ($write)
          @include('audits._add', ['areaId' => $area->id])
        @endif

        <div class="actions" style="justify-content:space-between;margin-top:12px">
          @if ($prev)<a href="{{ $areaTab($prev) }}" class="btn ghost small">→ {{ $prev->name }}</a>@else<span></span>@endif
          @if ($next)<a href="{{ $areaTab($next) }}" class="btn ghost small">{{ $next->name }} ←</a>@else<a href="{{ route('audits.show', [$a, 'tab' => 'report']) }}" class="btn small">التقرير ←</a>@endif
        </div>
      @endif

      {{-- ===================== التقرير ===================== --}}
      @if ($tab === 'report')
        <div class="box">
          <h2>ملخص الملاحظات</h2>
          <div style="overflow-x:auto">
            <table class="mx">
              <tr><th>الإدارة</th>@foreach ($G as $k => $g)<th><span class="gr" style="background:{{ $g[1] }}">{{ $g[0] }}</span></th>@endforeach</tr>
              @foreach ($a->areas as $ar)
                <tr><td><a href="{{ $areaTab($ar) }}">{{ $ar->name }}</a></td>@foreach ($G as $k => $g)<td>{{ $ar->findings->where('grade', $k)->count() ?: '' }}</td>@endforeach</tr>
              @endforeach
              @php
                $general = $a->findings->whereNull('area_id');
              @endphp
              @if ($general->isNotEmpty())
                <tr><td>عامة</td>@foreach ($G as $k => $g)<td>{{ $general->where('grade', $k)->count() ?: '' }}</td>@endforeach</tr>
              @endif
              <tr><th>الإجمالي</th>@foreach ($G as $k => $g)<th>{{ $s['grades'][$k] }}</th>@endforeach</tr>
            </table>
          </div>
          <p class="hint" style="margin:8px 0 0">@foreach ($G as $k => $g)<span class="gr" style="background:{{ $g[1] }}">{{ $g[0] }}</span> {{ $g[3] }}@unless ($loop->last) · @endunless @endforeach</p>
        </div>

        @if ($general->isNotEmpty())
          <h2 style="font-size:15.5px;margin:4px 0 8px">ملاحظات عامة</h2>
          @include('audits._findings', ['list' => $general])
        @endif
        @if ($write)
          @include('audits._add', ['areaId' => null])
        @endif

        <div class="box">
          <h2>الخلاصة والتقييم العام</h2>
          @if ($write)
            <form method="POST" action="{{ route('audits.submit', $a) }}">
              @csrf
              <div class="grades" style="margin-bottom:10px">
                @foreach (\App\Support\Audits::RATINGS as $k => $lbl)<label><input type="radio" name="rating" value="{{ $k }}" @checked(old('rating', $a->rating) === $k)> {{ $lbl }}</label>@endforeach
              </div>
              <textarea name="conclusion" rows="6" maxlength="10000" style="width:100%" placeholder="خلاصة المراجع: مدى التزام المخزن بالمتطلبات، أهم نقاط القوة، أهم المخاطر، التوصيات وأولوياتها">{{ old('conclusion', $a->conclusion) }}</textarea>
              <div class="actions" style="margin-top:10px">
                <button type="submit" name="action" value="save" class="btn ghost small">حفظ</button>
                <button type="submit" name="action" value="submit" class="btn small" onclick="return confirm('إرسال التقرير؟ بعد الإرسال لا يمكن تعديل الملاحظات.')">إرسال التقرير لإدارة الجودة</button>
              </div>
            </form>
          @else
            <p style="margin:0 0 6px"><b>{{ \App\Support\Audits::RATINGS[$a->rating] ?? 'لم يُحدد التقييم بعد' }}</b>@if ($a->reported_at) · أُرسل <span class="ltr">{{ $a->reported_at->timezone('Africa/Cairo')->format('Y-m-d') }}</span>@endif</p>
            <div style="white-space:pre-line;font-size:14px;line-height:1.9">{{ $a->conclusion ?: '—' }}</div>
            @if ($manage && in_array($a->status, ['reported', 'response'], true))
              <form method="POST" action="{{ route('audits.reopen', $a) }}" style="margin-top:10px">@csrf<button type="submit" class="btn ghost small">إعادة للعمل الميداني (لتعديل الملاحظات)</button></form>
            @endif
          @endif
        </div>
      @endif

      {{-- ===================== الرد والإجراءات ===================== --}}
      @if ($tab === 'capa')
        @php
          $act = $a->findings->filter->needsAction();
        @endphp
        <div class="box">
          <h2>الرد على الملاحظات والإجراءات التصحيحية</h2>
          <p class="hint" style="margin:0">لكل ملاحظة: السبب الجذري، الإجراء التصحيحي، المسؤول والموعد، ورقم الانحراف (DEV) لمتابعته في سجل CAPA. الملاحظات الحرجة والكبرى تُفتح لها انحرافات.
            @if ($a->inField())<br><b>يُتاح الرد بعد إرسال المراجع للتقرير.</b>@endif</p>
          <p style="margin:8px 0 0"><span class="st {{ $s['open'] ? 'warn' : 'ok' }}">{{ $s['open'] }} مفتوحة</span> من {{ $s['action'] }} @if ($s['late'])<span class="st bad">{{ $s['late'] }} متأخرة</span>@endif @if ($s['noPlan'])<span class="st warn">{{ $s['noPlan'] }} بدون إجراء</span>@endif</p>
        </div>
        @forelse ($act as $f)
          @php
            $sop = preg_match('/SH-\d{3}/', (string) $f->ref, $mm) ? $mm[0] : '';
            $devUrl = route('forms.page', 'deviation') . '?' . http_build_query(['ref' => $a->number, 'source' => $src, 'sop' => $sop,
              'what' => 'ملاحظة مراجعة ' . $a->number . ' رقم ' . $f->no . ' (' . $f->gradeLabel() . '): ' . $f->text, 'back' => '/audits/' . $a->id . '?tab=capa']);
          @endphp
          <details class="fd" id="f{{ $f->id }}" style="border-inline-start-color:{{ $G[$f->grade][1] }}" {{ $f->status === 'open' && ! $f->action ? 'open' : '' }}>
            <summary>
              <span class="no">{{ $f->no }}</span>
              <span class="tx"><span class="gr" style="background:{{ $G[$f->grade][1] }}">{{ $f->gradeLabel() }}</span> {{ $f->text }}
                <small>{{ $f->area?->name ?? 'عامة' }}@if ($f->ref) · {{ $f->ref }}@endif @if ($f->owner) · المسؤول: {{ $f->owner->displayName() }}@endif @if ($f->due_date) · <span class="ltr">{{ $f->due_date->format('Y-m-d') }}</span>@endif @if ($f->dev_no) · <span class="ltr">{{ $f->dev_no }}</span>@endif</small></span>
              <span class="st {{ $f->status === 'open' ? 'warn' : 'ok' }}">{{ ['open' => 'مفتوحة', 'done' => 'نُفذ', 'verified' => 'تم التحقق'][$f->status] }}</span>
            </summary>
            <div class="body">
              @if ($f->evidence)<p class="hint" style="margin:0 0 8px"><b>الدليل:</b> {{ $f->evidence }}</p>@endif
              @if ($manage && ! $a->inField() && $a->status !== 'closed')
                <form method="POST" action="{{ route('audits.findings.update', [$a, $f]) }}">
                  @csrf
                  @method('PUT')
                  <div class="mf">
                    <label class="full">السبب الجذري <textarea name="cause" rows="2" maxlength="4000">{{ $f->cause }}</textarea></label>
                    <label class="full">الإجراء التصحيحي <textarea name="action" rows="2" maxlength="4000">{{ $f->action }}</textarea></label>
                    <label>المسؤول
                      <select name="owner_id"><option value="">—</option>@foreach ($staff as $x)<option value="{{ $x->id }}" @selected((int) $f->owner_id === $x->id)>{{ $x->displayName() }}</option>@endforeach</select>
                    </label>
                    <label>الموعد <input type="date" name="due_date" value="{{ $f->due_date?->format('Y-m-d') }}"></label>
                    <label>رقم الانحراف (DEV) <input type="text" name="dev_no" maxlength="30" class="ltr" value="{{ $f->dev_no }}"></label>
                    <label>الحالة
                      <select name="status">@foreach (['open' => 'مفتوحة', 'done' => 'نُفذ الإجراء', 'verified' => 'تم التحقق من الفاعلية'] as $k => $lbl)<option value="{{ $k }}" @selected($f->status === $k)>{{ $lbl }}</option>@endforeach</select>
                    </label>
                  </div>
                  <div class="actions" style="margin-top:8px">
                    <button type="submit" class="btn small">حفظ الرد</button>
                    @unless ($f->dev_no)<a href="{{ $devUrl }}" target="_blank" class="btn ghost small">+ تسجيل انحراف (DEV)</a>@endunless
                  </div>
                </form>
              @else
                <p style="margin:0;font-size:13.5px;line-height:1.9"><b>السبب:</b> {{ $f->cause ?: '—' }}<br><b>الإجراء:</b> {{ $f->action ?: '—' }}</p>
              @endif
            </div>
          </details>
        @empty
          <div class="empty">لا توجد ملاحظات تحتاج إجراء.</div>
        @endforelse

        @if ($manage && in_array($a->status, ['reported', 'response'], true))
          <form method="POST" action="{{ route('audits.close', $a) }}" class="box" style="margin-top:12px">
            @csrf
            <b>إغلاق المراجعة</b>
            <p class="hint" style="margin:4px 0 8px">@if ($s['open'])ما زالت {{ $s['open'] }} ملاحظة مفتوحة: يُفضل الإغلاق بعد تنفيذ الإجراءات أو تحويلها لانحرافات مفتوحة في سجل CAPA. @endif الإغلاق يُنهي حساب المراجع اليوم. ارفع نتائج المراجعة لاجتماع مراجعة الإدارة (SH-013).</p>
            <button type="submit" class="btn small" onclick="return confirm('إغلاق المراجعة {{ $a->number }}؟')">إغلاق المراجعة</button>
          </form>
        @endif
      @endif

      {{-- ===================== سجل دخول المراجع ===================== --}}
      @if ($tab === 'log' && $manage)
        <div class="box">
          <h2>سجل الصفحات التي فتحها المراجع ({{ $logs->count() }})</h2>
          @if ($logs->isEmpty())
            <p class="hint" style="margin:0">لم يفتح المراجع أي صفحة بعد.</p>
          @else
            <div style="overflow-x:auto">
              <table class="mx">
                <tr><th>الوقت</th><th>الصفحة</th><th>الطلب</th><th>IP</th></tr>
                @foreach ($logs as $l)
                  <tr><td class="ltr">{{ $l->at?->timezone('Africa/Cairo')->format('Y-m-d H:i') }}</td><td class="ltr" style="text-align:left">{{ $l->path }}</td><td class="ltr">{{ $l->method }}</td><td class="ltr">{{ $l->ip }}</td></tr>
                @endforeach
              </table>
            </div>
          @endif
        </div>
      @endif
    </div>
  </div>
</div>
@endsection