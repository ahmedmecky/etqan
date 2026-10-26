@extends('layouts.app')

@section('title', 'الصلاحيات')

@push('head')
<style>
  main.wrap{max-width:1560px !important}
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:14px}
  .info{background:#eef6f5;border:1px solid #d3e8e3;border-radius:10px;padding:10px 14px;font-size:13.5px;line-height:1.9;margin-bottom:14px}
  .pm{border-collapse:separate;border-spacing:0;font-size:13px;min-width:1100px;width:100%}
  .pm th,.pm td{border-bottom:1px solid var(--line);border-inline-start:1px solid var(--line);padding:5px 6px;text-align:center;background:#fff}
  .pm thead th{position:sticky;top:0;background:#f6f8f7;font-size:12px;vertical-align:bottom;z-index:2;line-height:1.35}
  .pm thead th span{display:block;font-weight:400;color:var(--muted);direction:ltr;font-size:11px}
  .pm th.r,.pm td.r{position:sticky;right:0;text-align:start;min-width:200px;z-index:1;border-inline-start:0}
  .pm thead th.r{z-index:3}
  .pm td.r b{display:block}
  .pm td.r span{font-size:11.5px;color:var(--muted)}
  .pm select{width:auto;padding:3px 4px;font-size:12.5px;border-radius:6px;border:1px solid var(--line);min-width:78px}
  .pm select.l-none{background:#f3f4f4;color:#8a9895}
  .pm select.l-view{background:#e9f1fb;color:#1d4f86}
  .pm select.l-use{background:#e4f4ea;color:#1e7a46;font-weight:700}
  .pm td.chg{background:#fff8e6}
  .pm .lt{display:inline-block;min-width:14px;font-size:10.5px;font-weight:700;color:var(--muted);direction:ltr}
  .tw{overflow:auto;max-height:72vh;border:1px solid var(--line);border-radius:10px}
  .legend{display:flex;gap:14px;flex-wrap:wrap;font-size:13px;margin-bottom:10px;align-items:center}
  .ex{font-size:13.5px}
  .ex div{padding:6px 0;border-bottom:1px solid #eef1f0}
</style>
@endpush

@section('content')
  <div class="head-actions">
    <div>
      <h1 class="page-title">الصلاحيات</h1>
      <p class="page-sub" style="margin:0">ما يراه ويستخدمه كل دور · الافتراضي من مصفوفة المسؤوليات RACI</p>
    </div>
    <div class="actions">
      <a href="{{ route('qms.map') }}" class="btn ghost">مصفوفة RACI</a>
      <a href="{{ route('client.users.index') }}" class="btn ghost">المستخدمون</a>
    </div>
  </div>

  <div class="info">
    <b>استخدام</b> = يسجل ويعدل · <b>عرض</b> = يقرأ فقط · <b>مخفية</b> = لا تظهر له ولا يستطيع فتحها.
    الافتراضي محسوب من مصفوفة RACI: قسم الدور ينفذ أو يعتمد (R / A) ← استخدام · يُستشار أو يُبلغ (C / I) ← عرض · لا علاقة ← مخفية.
    الخلايا المظللة غيّرتها أنت عن الافتراضي. مدير المخزن ومدير إدارة الجودة لهما صلاحية كاملة دائماً، وقراءة الإجراءات السارية ووصف الموظف الوظيفي وتقييمه وسجل تدريبه متاحة لكل موظف.
    صلاحيات الاعتماد (من يعتمد ويغلق) لا تتغير من هنا.
  </div>

  <form method="POST" action="{{ route('client.permissions.update') }}">
    @csrf
    @method('PUT')
    <div class="legend">
      <label class="chk" style="margin:0"><input type="checkbox" onchange="location.href='{{ route('client.permissions.index') }}' + (this.checked ? '?all=1' : '')" {{ $showAll ? 'checked' : '' }}> عرض كل الأدوار (وليس الأدوار التي لها مستخدمون فقط)</label>
      <span style="flex:1"></span>
      <button type="submit" class="btn">حفظ الصلاحيات</button>
    </div>
    <div class="tw">
      <table class="pm">
        <thead><tr><th class="r">الدور</th>
          @foreach ($modules as $k => [$title, $sop])<th>{{ $title }}<span>{{ $sop }}</span></th>@endforeach</tr></thead>
        <tbody>
          @foreach ($roles as $r)
            @continue(! $showAll && ! ($counts[$r->id] ?? 0) && ! in_array($r->key, ['quality_manager'], true))
            @if ($r->key === 'quality_manager')
              <tr><td class="r"><b>{{ $r->name_ar }}</b><span>{{ $counts[$r->id] ?? 0 }} مستخدم</span></td><td colspan="{{ count($modules) }}" style="color:var(--ok);font-weight:700">صلاحية كاملة على كل الشاشات</td></tr>
              @continue
            @endif
            <tr>
              <td class="r"><b>{{ $r->name_ar }}</b><span>{{ $counts[$r->id] ?? 0 }} مستخدم · {{ \App\Support\Access::ROLE_DEPT[$r->key] ?? '—' }}</span></td>
              @foreach ($modules as $k => $m)
                @php
                  $def = \App\Support\Access::default($r->key, $k);
                  $cur = \App\Support\Access::roleLevel($tid, $r->id, $r->key, $k);
                @endphp
                <td class="{{ $cur !== $def ? 'chg' : '' }}" title="الافتراضي: {{ $levels[$def] }}">
                  <span class="lt">{{ \App\Support\Access::letter($r->key, $k) }}</span>
                  <select name="p[{{ $r->id }}][{{ $k }}]" class="l-{{ $cur }}" onchange="this.className='l-'+this.value;this.parentNode.classList.toggle('chg', this.value !== '{{ $def }}')">
                    @foreach ($levels as $lv => $lt)<option value="{{ $lv }}" {{ $cur === $lv ? 'selected' : '' }}>{{ $lt }}{{ $lv === $def ? ' ·' : '' }}</option>@endforeach
                  </select>
                </td>
              @endforeach
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    <div class="actions" style="margin-top:12px">
      <button type="submit" class="btn">حفظ الصلاحيات</button>
    </div>
  </form>
  <form method="POST" action="{{ route('client.permissions.reset') }}" onsubmit="return confirm('إعادة كل الأدوار للافتراضي من RACI؟')" style="margin-top:8px">
    @csrf
    <button type="submit" class="btn ghost small">إعادة كل الأدوار للافتراضي</button>
  </form>

  <div class="card" style="margin-top:16px">
    <h2 style="font-size:16px;margin:0 0 6px">استثناءات لأشخاص</h2>
    <p class="page-sub">تُضبط من صفحة تعديل المستخدم. تتقدم على صلاحية الدور.</p>
    <div class="ex">
      @forelse ($exceptions as $uid => $list)
        @if ($users->get($uid))
          <div><a href="{{ route('client.users.edit', $users[$uid]) }}"><b>{{ $users[$uid]->displayName() }}</b></a> ·
            {{ $list->map(fn ($o) => ($modules[$o->module][0] ?? $o->module) . ': ' . ($levels[$o->level] ?? $o->level))->implode(' · ') }}</div>
        @endif
      @empty
        <div style="color:var(--muted)">لا توجد استثناءات.</div>
      @endforelse
    </div>
  </div>
@endsection