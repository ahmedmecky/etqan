@extends('layouts.app')

@section('title', 'مهام اليوم')

@push('head')
<style>
  .dt{max-width:820px;margin:0 auto}
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:12px}
  .prog{display:flex;align-items:center;gap:12px;background:#fff;border:1px solid var(--line);border-radius:14px;padding:12px 14px;margin-bottom:12px}
  .prog .bar{flex:1;height:10px;background:#e9eeec;border-radius:99px;overflow:hidden}
  .prog .bar i{display:block;height:100%;background:var(--ok)}
  .prog b{font-size:18px;direction:ltr}
  .prog .late{color:var(--bad);font-weight:700;font-size:13.5px}
  .it{display:flex;gap:12px;align-items:flex-start;background:#fff;border:1px solid var(--line);border-inline-start:5px solid var(--line);border-radius:12px;padding:12px 14px;margin-bottom:8px}
  .it.done{border-inline-start-color:var(--ok);background:#f8fcf9}
  .it.late{border-inline-start-color:var(--bad);background:#fffafa}
  .it.soon{border-inline-start-color:#d9b25a}
  .it .ck{width:34px;height:34px;border-radius:50%;border:2px solid var(--line);display:flex;align-items:center;justify-content:center;flex:none;font-size:18px;font-weight:700;color:#fff;background:#fff}
  .it.done .ck{background:var(--ok);border-color:var(--ok)}
  .it.late .ck{border-color:var(--bad);color:var(--bad);background:#fff}
  .it .tx{flex:1;min-width:0}
  .it .tx b{display:block;font-size:15.5px;line-height:1.5}
  .it .tx span{display:block;font-size:12.5px;color:var(--muted);margin-top:2px}
  .it .tm{font-size:12.5px;font-weight:700;direction:ltr;white-space:nowrap;text-align:left}
  .it .tm small{display:block;font-weight:600;font-size:11.5px;direction:rtl}
  .it.late .tm{color:var(--bad)} .it.done .tm{color:var(--ok)}
  .it form{margin:0}
  .it .go{display:flex;gap:6px;align-items:center;margin-top:8px;flex-wrap:wrap}
  .it .go input[type=text]{flex:1;min-width:160px;padding:8px 10px}
  .it .go .btn{padding:8px 16px}
  .empty{background:#fff;border:1px dashed var(--line);border-radius:12px;padding:22px;text-align:center;color:var(--muted)}
  .setup h2{font-size:16px;margin:0 0 10px}
  .row-t{border-bottom:1px solid #eef1f0;padding:8px 0}
  .row-t summary{cursor:pointer;display:flex;gap:10px;align-items:center;list-style:none}
  .row-t summary::-webkit-details-marker{display:none}
  .row-t summary b{flex:1;font-size:14.5px}
  .row-t summary .by{font-size:12px;color:var(--muted)}
  .row-t.off summary b{text-decoration:line-through;color:var(--muted)}
  .row-t .ar{transition:transform .15s;color:var(--muted)}
  .row-t[open] .ar{transform:rotate(180deg)}
  .tf{display:grid;grid-template-columns:1fr 120px 110px;gap:8px;margin-top:8px}
  .tf .full{grid-column:1 / -1}
  @media (max-width:640px){.tf{grid-template-columns:1fr 1fr}}
  .roles,.days{display:flex;flex-wrap:wrap;gap:6px}
  .roles label,.days label{display:flex;align-items:center;gap:5px;border:1px solid var(--line);border-radius:99px;padding:4px 10px;font-size:13px;cursor:pointer;background:#fff}
  .roles input,.days input{accent-color:var(--brand)}
  .hint{font-size:12.5px;color:var(--muted);line-height:1.8}
</style>
@endpush

@section('content')
<div class="dt">
  <div class="head-actions">
    <div>
      <h1 class="page-title">مهام اليوم</h1>
      <p class="page-sub" style="margin:0">{{ ['الأحد','الاثنين','الثلاثاء','الأربعاء','الخميس','الجمعة','السبت'][$date->dayOfWeek] }} <span class="ltr">{{ $date->format('Y-m-d') }}</span>
        @unless ($isToday) · <b>يوم سابق</b>@endunless</p>
    </div>
    <div class="actions" style="margin:0">
      <a href="{{ route('tasks.index') }}" class="btn ghost small">المهام المكلف بها</a>
    </div>
    @if ($manager)
      <form method="GET" action="{{ route('daily.index') }}" class="actions" style="margin:0">
        <input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ now('Africa/Cairo')->toDateString() }}" onchange="this.form.submit()" style="width:auto;direction:ltr">
      </form>
    @endif
  </div>

  @php
    $L = $list;
    $pct = $L['total'] ? round($L['done'] / $L['total'] * 100) : 0;
  @endphp

  @if ($L['total'])
    <div class="prog">
      <b>{{ $L['done'] }}/{{ $L['total'] }}</b>
      <div class="bar"><i style="width:{{ $pct }}%"></i></div>
      @if ($L['late'])<span class="late">{{ $L['late'] }} متأخرة</span>@else<span style="color:var(--ok);font-weight:700;font-size:13.5px">✓ في موعدها</span>@endif
    </div>
  @endif

  @forelse ($L['items'] as $it)
    <div class="it {{ $it['state'] }}">
      <span class="ck">{{ $it['state'] === 'done' ? '✓' : ($it['state'] === 'late' ? '!' : '') }}</span>
      <div class="tx">
        @if ($it['kind'] === 'temp' || $it['kind'] === 'job')
          <a href="{{ $it['url'] }}" style="color:inherit;text-decoration:none"><b>{{ $it['title'] }}</b></a>
        @else
          <b>{{ $it['title'] }}</b>
        @endif
        <span>{{ $it['sub'] }}@if ($it['sop']) · <span class="ltr" style="display:inline">{{ $it['sop'] }}</span>@endif</span>

        @if ($isToday && $it['kind'] === 'task' && $it['mine'])
          @if ($it['state'] === 'done')
            @if ($manager || (int) ($it['log']->done_by ?? 0) === auth()->id())
              <form method="POST" action="{{ route('daily.undo', $it['task']) }}" class="go">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn ghost small">إلغاء التنفيذ</button>
              </form>
            @endif
          @else
            <form method="POST" action="{{ route('daily.done', $it['task']) }}" class="go">
              @csrf
              <input type="text" name="note" maxlength="500" placeholder="ملاحظة (اختياري)">
              <button type="submit" class="btn">✓ تم</button>
            </form>
          @endif
        @elseif ($isToday && $it['kind'] === 'job')
          @if ($it['state'] === 'done')
            <form method="POST" action="{{ route('tasks.reopen', $it['task']) }}" class="go">
              @csrf
              <button type="submit" class="btn ghost small">إعادة فتح</button>
            </form>
          @else
            <form method="POST" action="{{ route('tasks.done', $it['task']) }}" class="go">
              @csrf
              <input type="text" name="note" maxlength="500" placeholder="ملاحظة (اختياري)">
              <button type="submit" class="btn">✓ تم</button>
              <a href="{{ $it['url'] }}" class="btn ghost small">التفاصيل</a>
            </form>
          @endif
        @elseif ($it['kind'] === 'temp' && $it['state'] !== 'done' && $isToday)
          <div class="go"><a href="{{ $it['url'] }}" class="btn small">تسجيل القراءة</a></div>
        @endif
      </div>
      <div class="tm">{{ $it['due'] }}<small>{{ $it['state'] === 'done' ? 'تمت' : ($it['state'] === 'late' ? 'متأخرة' : 'الموعد') }}</small></div>
    </div>
  @empty
    <div class="empty">لا توجد مهام يومية لك اليوم.@if ($setup) أضف المهام من الإعداد أسفل الصفحة.@endif</div>
  @endforelse

  @if ($setup)
    @php
      $adm = auth()->user()->isClientAdmin();
    @endphp
    <div class="card setup" style="margin-top:18px">
      <h2>إعداد المهام اليومية</h2>
      <p class="hint" style="margin-top:0">لكل مهمة: المسؤولون عنها (أدوار)، وموعد إنجازها، وأيام العمل. إذا فات الموعد ولم تُنجز يصل إشعار للمسؤولين عنها
        ولمستلمي «مهام يومية لم تُنجز» في {!! $adm ? '<a href="' . route('client.notify.index') . '">إعداد الإشعارات</a>' : 'إعداد الإشعارات' !!}
        (افتراضياً مدير الجودة والمدير الصيدلي). قراءات الحرارة تظهر تلقائياً حسب عدد القراءات في {!! $adm ? '<a href="' . route('client.settings.edit') . '">الإعدادات</a>' : 'الإعدادات' !!}.</p>

      @foreach ($tasks as $t)
        <details class="row-t {{ $t->is_active ? '' : 'off' }}">
          <summary><b>{{ $t->title }}</b><span class="by">{{ $t->due() }} · {{ \App\Support\DailyTasks::rolesText($t) }}</span><span class="ar">▾</span></summary>
          <form method="POST" action="{{ route('daily.tasks.update', $t) }}">
            @csrf
            @method('PUT')
            @include('daily._fields', ['t' => $t])
            <div class="actions" style="margin-top:8px">
              <button type="submit" class="btn small">حفظ</button>
              <button type="submit" form="tg{{ $t->id }}" class="btn ghost small">{{ $t->is_active ? 'إيقاف' : 'إعادة تفعيل' }}</button>
            </div>
          </form>
          <form method="POST" action="{{ route('daily.tasks.toggle', $t) }}" id="tg{{ $t->id }}" hidden>@csrf</form>
        </details>
      @endforeach

      <details class="row-t" {{ $tasks->isEmpty() ? 'open' : '' }}>
        <summary><b style="color:var(--brand)">+ مهمة يومية جديدة</b><span class="ar">▾</span></summary>
        <form method="POST" action="{{ route('daily.tasks.store') }}">
          @csrf
          @include('daily._fields', ['t' => null])
          <div class="actions" style="margin-top:8px"><button type="submit" class="btn small">إضافة</button></div>
        </form>
      </details>

      <form method="POST" action="{{ route('daily.tasks.seed') }}" style="margin-top:12px">
        @csrf
        <span class="hint">مهام مقترحة من الإجراءات ({{ count($suggest) }}): النظافة اليومية · منشورات هيئة الدواء · الحجر والمرتجعات · الثلاجات · خروج السيارات · جولة الأمن.</span>
        <button type="submit" class="btn ghost small">إضافة المهام المقترحة</button>
      </form>
    </div>
  @endif
</div>
@endsection