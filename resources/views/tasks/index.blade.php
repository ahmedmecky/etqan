@extends('layouts.app')

@section('title', 'المهام')

@push('head')
<style>
  .tk{max-width:860px;margin:0 auto}
  .add{background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px;margin-bottom:14px}
  .add h2{font-size:15.5px;margin:0 0 10px}
  .af{display:grid;grid-template-columns:1fr 1fr;gap:10px}
  .af label{display:flex;flex-direction:column;gap:4px;font-size:12.5px;font-weight:700;color:var(--muted)}
  .af label input,.af label select,.af label textarea{font-weight:400;color:var(--ink,#1c2826);font-size:15px}
  .af .full{grid-column:1 / -1}
  .when{display:flex;gap:8px}
  .when input[type=date]{flex:2} .when input[type=time]{flex:1}
  .pr{display:flex;gap:6px}
  .pr label{flex:1;flex-direction:row;justify-content:center;align-items:center;border:1px solid var(--line);border-radius:10px;padding:10px 6px;cursor:pointer;font-size:14px;color:var(--ink,#1c2826);background:#fff}
  .pr input{position:absolute;opacity:0;pointer-events:none}
  .pr label:has(input[value=urgent]:checked){background:#c0362c;border-color:#c0362c;color:#fff}
  .pr label:has(input[value=important]:checked){background:#d98a00;border-color:#d98a00;color:#fff}
  .pr label:has(input[value=normal]:checked){background:#0f6e64;border-color:#0f6e64;color:#fff}
  .quick{display:flex;gap:6px;flex-wrap:wrap;margin-top:4px}
  .quick button{background:#f3f6f5;border:1px solid var(--line);border-radius:99px;padding:4px 10px;font:inherit;font-size:12.5px;cursor:pointer}
  @media (max-width:600px){ .af{grid-template-columns:1fr} }
  .tabs{display:flex;gap:6px;border-bottom:1px solid var(--line);margin-bottom:10px;overflow-x:auto}
  .tabs a{padding:10px 14px;color:var(--muted);text-decoration:none;font-weight:700;border-bottom:3px solid transparent;white-space:nowrap}
  .tabs a.on{color:var(--brand);border-bottom-color:var(--brand)}
  .tabs a i{font-style:normal;background:#fbe4e2;color:#c0362c;border-radius:99px;padding:0 7px;font-size:12px;margin-inline-start:4px}
  .sw{display:flex;gap:6px;margin-bottom:12px}
  .sw a{font-size:13px;padding:5px 12px;border-radius:99px;border:1px solid var(--line);color:var(--muted);text-decoration:none;background:#fff}
  .sw a.on{background:var(--brand);border-color:var(--brand);color:#fff}
  .t{background:#fff;border:1px solid var(--line);border-inline-start:5px solid #c9d3d0;border-radius:12px;margin-bottom:8px}
  .t.p-urgent{border-inline-start-color:#c0362c} .t.p-important{border-inline-start-color:#d98a00}
  .t.late{background:#fffafa} .t.done,.t.cancelled{opacity:.75}
  .t > summary{list-style:none;cursor:pointer;display:flex;gap:12px;align-items:flex-start;padding:12px 14px}
  .t > summary::-webkit-details-marker{display:none}
  .t .ck{width:30px;height:30px;border-radius:50%;border:2px solid #d5dedb;display:flex;align-items:center;justify-content:center;flex:none;font-weight:700;color:#fff;font-size:15px}
  .t.done .ck{background:var(--ok);border-color:var(--ok)} .t.late .ck{border-color:#c0362c;color:#c0362c}
  .t .tx{flex:1;min-width:0}
  .t .tx b{display:block;font-size:15.5px;line-height:1.5}
  .t.done .tx b{text-decoration:line-through;color:var(--muted)}
  .t .tx span{display:block;font-size:12.5px;color:var(--muted);margin-top:2px}
  .t .tm{text-align:left;white-space:nowrap;font-size:12.5px;font-weight:700}
  .t.late .tm{color:#c0362c}
  .tag{display:inline-block;border-radius:99px;padding:1px 8px;font-size:11.5px;font-weight:700;margin-inline-end:4px}
  .tag.urgent{background:#fbe4e2;color:#c0362c} .tag.important{background:#fdf0d8;color:#8a5a00} .tag.normal{background:#eef3f2;color:#4d5f5b}
  .t .body{border-top:1px solid #eef1f0;padding:12px 14px}
  .t .det{white-space:pre-line;font-size:14px;line-height:1.8;margin:0 0 10px}
  .t .go{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
  .t .go input[type=text]{flex:1;min-width:180px;padding:9px 10px}
  .meta{font-size:12.5px;color:var(--muted);line-height:1.9;margin-bottom:10px}
  .empty{background:#fff;border:1px dashed var(--line);border-radius:12px;padding:22px;text-align:center;color:var(--muted)}
</style>
@endpush

@section('content')
<div class="tk">
  <h1 class="page-title">المهام</h1>
  <p class="page-sub">
    @if ($counts['open'])
      لديك {{ $counts['open'] }} مهمة مفتوحة @if ($counts['late']) · <b style="color:#c0362c">{{ $counts['late'] }} متأخرة</b>@endif
      @if ($counts['today']) · {{ $counts['today'] }} موعدها اليوم @endif
    @else
      لا توجد مهام مفتوحة عليك.
    @endif
    المهام اليومية الثابتة في <a href="{{ route('daily.index') }}">مهام اليوم</a>.
  </p>

  {{-- ===================== مهمة جديدة ===================== --}}
  <form method="POST" action="{{ route('tasks.store') }}" class="add">
    @csrf
    <h2>{{ $canAssign ? '+ تكليف بمهمة' : '+ مهمة جديدة لنفسي' }}</h2>
    <div class="af">
      @if ($canAssign)
        <label>لمن
          <select name="assigned_to" required>
            @foreach ($assignees as $x)
              <option value="{{ $x->id }}" @selected((int) old('assigned_to', auth()->id()) === $x->id)>{{ $x->id === auth()->id() ? 'لنفسي (' . $x->displayName() . ')' : $x->displayName() . ($x->role ? ' · ' . $x->role->name_ar : '') }}</option>
            @endforeach
          </select>
        </label>
      @else
        <input type="hidden" name="assigned_to" value="{{ auth()->id() }}">
      @endif
      <label class="{{ $canAssign ? '' : 'full' }}">المهمة
        <input type="text" name="title" maxlength="200" required value="{{ old('title') }}" placeholder="مثال: تنظيف ثلاجة ٢ وتسجيلها">
      </label>
      <label class="full">ماذا يفعل بالضبط (اختياري)
        <textarea name="details" rows="2" maxlength="2000" placeholder="الخطوات أو التفاصيل: أين، ماذا، وما المطلوب تسليمه">{{ old('details') }}</textarea>
      </label>
      <label>النوع
        <span class="pr">
          @foreach (\App\Support\Tasks::PRIORITIES as $k => $lbl)
            <label><input type="radio" name="priority" value="{{ $k }}" @checked(old('priority', 'important') === $k)>{{ $k === 'urgent' ? '⚡ ' : '' }}{{ $lbl }}</label>
          @endforeach
        </span>
      </label>
      <label>الموعد
        <span class="when">
          <input type="date" name="due_date" id="tDate" required value="{{ old('due_date', $today) }}" min="{{ $today }}">
          <input type="time" name="due_time" value="{{ old('due_time') }}" title="الساعة (اختياري)">
        </span>
        <span class="quick" id="tQuick">
          <button type="button" data-d="0">اليوم</button><button type="button" data-d="1">غداً</button><button type="button" data-d="3">بعد ٣ أيام</button><button type="button" data-d="7">بعد أسبوع</button>
        </span>
      </label>
    </div>
    <div class="actions" style="margin-top:12px"><button type="submit" class="btn">{{ $canAssign ? 'تكليف وإرسال إشعار' : 'إضافة' }}</button></div>
  </form>

  {{-- ===================== القوائم ===================== --}}
  <div class="tabs">
    <a href="{{ route('tasks.index', ['tab' => 'mine']) }}" class="{{ $tab === 'mine' ? 'on' : '' }}">مهامي @if ($counts['late'])<i>{{ $counts['late'] }}</i>@endif</a>
    <a href="{{ route('tasks.index', ['tab' => 'given']) }}" class="{{ $tab === 'given' ? 'on' : '' }}">كلفت بها ({{ $givenOpen }})</a>
    @if (in_array('all', $tabs, true))
      <a href="{{ route('tasks.index', ['tab' => 'all']) }}" class="{{ $tab === 'all' ? 'on' : '' }}">كل المهام</a>
    @endif
  </div>
  <div class="sw">
    <a href="{{ route('tasks.index', ['tab' => $tab]) }}" class="{{ $show === 'open' ? 'on' : '' }}">مفتوحة</a>
    <a href="{{ route('tasks.index', ['tab' => $tab, 'show' => 'closed']) }}" class="{{ $show === 'closed' ? 'on' : '' }}">منتهية وملغاة (آخر ٦٠ يوم)</a>
  </div>

  @forelse ($tasks as $t)
    @php
      $st = $t->state();
      $canDo = \App\Support\Tasks::canDo(auth()->user(), $t);
      $canEdit = \App\Support\Tasks::canEdit(auth()->user(), $t);
    @endphp
    <details class="t p-{{ $t->priority }} {{ $st }}" id="t{{ $t->id }}" @if ($open === $t->id) open @endif>
      <summary>
        <span class="ck">{{ $st === 'done' ? '✓' : ($st === 'late' ? '!' : '') }}</span>
        <span class="tx">
          <b>{{ $t->title }}</b>
          <span><span class="tag {{ $t->priority }}">{{ $t->priorityLabel() }}</span>
            @if ($t->isSelf()) مهمة شخصية
            @elseif ((int) $t->assigned_to === auth()->id()) من {{ $t->creator?->displayName() }}
            @else إلى {{ $t->assignee?->displayName() }}@if ($tab === 'all') · من {{ $t->creator?->displayName() }}@endif
            @endif
            @if ($t->sop) · <span class="ltr">{{ $t->sop }}</span>@endif
          </span>
        </span>
        <span class="tm">{{ $st === 'done' ? 'تمت ' . $t->done_at?->timezone('Africa/Cairo')->format('m-d H:i') : ($st === 'cancelled' ? 'ملغاة' : $t->dueText()) }}</span>
      </summary>
      <div class="body">
        @if ($t->details)<p class="det">{{ $t->details }}</p>@endif
        <div class="meta">
          الموعد: <span class="ltr">{{ $t->due_date->format('Y-m-d') }}{{ $t->time() ? ' ' . $t->time() : '' }}</span>
          · كلّف بها: {{ $t->creator?->displayName() }} · المكلف: {{ $t->assignee?->displayName() }}
          · أُنشئت <span class="ltr">{{ $t->created_at?->timezone('Africa/Cairo')->format('Y-m-d H:i') }}</span>
          @if ($t->status === 'done')<br>✓ أنجزها {{ $t->doer?->displayName() }} <span class="ltr">{{ $t->done_at?->timezone('Africa/Cairo')->format('Y-m-d H:i') }}</span>@if ($t->done_note) · {{ $t->done_note }}@endif @endif
        </div>

        @if ($t->status === 'open' && $canDo)
          <form method="POST" action="{{ route('tasks.done', $t) }}" class="go">
            @csrf
            <input type="text" name="note" maxlength="500" placeholder="ملاحظة عند الإنجاز (اختياري)">
            <button type="submit" class="btn">✓ تم</button>
          </form>
        @elseif ($t->status !== 'open' && $canDo)
          <form method="POST" action="{{ route('tasks.reopen', $t) }}" class="go">
            @csrf
            <button type="submit" class="btn ghost small">إعادة فتح</button>
          </form>
        @endif

        @if ($t->status === 'open' && $canEdit)
          <details style="margin-top:12px">
            <summary style="cursor:pointer;font-size:13px;font-weight:700;color:var(--muted)">تعديل · إلغاء ▾</summary>
            <form method="POST" action="{{ route('tasks.update', $t) }}" style="margin-top:8px">
              @csrf
              @method('PUT')
              <div class="af">
                <label>لمن
                  <select name="assigned_to" required>
                    @foreach ($assignees as $x)<option value="{{ $x->id }}" @selected((int) $t->assigned_to === $x->id)>{{ $x->displayName() }}</option>@endforeach
                  </select>
                </label>
                <label>المهمة <input type="text" name="title" maxlength="200" required value="{{ $t->title }}"></label>
                <label class="full">ماذا يفعل بالضبط <textarea name="details" rows="2" maxlength="2000">{{ $t->details }}</textarea></label>
                <label>النوع
                  <select name="priority">
                    @foreach (\App\Support\Tasks::PRIORITIES as $k => $lbl)<option value="{{ $k }}" @selected($t->priority === $k)>{{ $lbl }}</option>@endforeach
                  </select>
                </label>
                <label>الموعد
                  <span class="when"><input type="date" name="due_date" required value="{{ $t->due_date->format('Y-m-d') }}"><input type="time" name="due_time" value="{{ $t->time() }}"></span>
                </label>
                <label>كود الإجراء (اختياري) <input type="text" name="sop" maxlength="20" class="ltr" value="{{ $t->sop }}" placeholder="SH-003"></label>
              </div>
              <div class="actions" style="margin-top:10px">
                <button type="submit" class="btn small">حفظ</button>
                <button type="submit" form="cx{{ $t->id }}" class="btn ghost small">إلغاء المهمة</button>
              </div>
            </form>
            <form method="POST" action="{{ route('tasks.cancel', $t) }}" id="cx{{ $t->id }}" hidden>@csrf</form>
          </details>
        @endif
      </div>
    </details>
  @empty
    <div class="empty">{{ $show === 'open' ? 'لا توجد مهام مفتوحة هنا.' : 'لا توجد مهام منتهية في آخر ٦٠ يوماً.' }}</div>
  @endforelse
</div>
@endsection

@push('scripts')
<script>
(function () {
  const d = document.getElementById('tDate'), q = document.getElementById('tQuick');
  if (!d || !q) return;
  q.addEventListener('click', e => {
    const b = e.target.closest('button'); if (!b) return;
    const t = new Date(); t.setDate(t.getDate() + (+b.dataset.d));
    d.value = t.getFullYear() + '-' + String(t.getMonth() + 1).padStart(2, '0') + '-' + String(t.getDate()).padStart(2, '0');
  });
  const o = document.querySelector('details.t[open]');
  if (o) o.scrollIntoView({ block: 'center' });
})();
</script>
@endpush