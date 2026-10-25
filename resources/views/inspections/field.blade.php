@extends('layouts.app')

@section('title', 'التفتيش الميداني')

@push('head')
<style>
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:14px}
  .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:16px}
  .stat{background:#fff;border:1px solid var(--line);border-radius:10px;padding:12px 14px}
  .stat b{display:block;font-size:26px;line-height:1.2;direction:ltr;text-align:right}
  .stat span{font-size:13px;color:var(--muted)}
  .stat.bad b{color:var(--bad)} .stat.ok b{color:var(--ok)} .stat.warn b{color:var(--warn)}
  .two{display:grid;grid-template-columns:1.5fr 1fr;gap:16px}
  @media (max-width:900px){.two{grid-template-columns:1fr}}
  .two .card{margin:0 0 16px}
  .v{direction:ltr;font-variant-numeric:tabular-nums;white-space:nowrap}
  .by{font-size:11.5px;color:var(--muted)}
  .pill{display:inline-block;border-radius:99px;padding:2px 10px;font-size:12px;font-weight:700;white-space:nowrap}
  .p-ok{background:#e3f4ea;color:var(--ok)} .p-bad{background:#fde8e6;color:var(--bad)} .p-open{background:#fdf1dc;color:#9a6300}
  .dl{display:flex;align-items:center;gap:8px;flex-wrap:wrap;padding:9px 0;border-bottom:1px solid var(--line)}
  .dl:last-child{border:0}
  .dl .nm{flex:1;min-width:140px}
  .dl input.lnk{width:100%;direction:ltr;font-size:12px;padding:5px 8px;background:#f6f8f7;border:1px solid var(--line);border-radius:6px}
  .dl.off{opacity:.55}
  .dl.new{background:#eef8f2;border-radius:8px;padding:9px}
  .add{display:grid;grid-template-columns:1fr 1fr auto;gap:8px;align-items:end;margin-top:10px}
  @media (max-width:600px){.add{grid-template-columns:1fr}}
  .filters{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px}
  .filters select,.filters input{width:auto}
</style>
@endpush

@section('content')
  <div class="head-actions">
    <div>
      <h1 class="page-title">التفتيش الميداني بالتفويض</h1>
      <p class="page-sub" style="margin:0">مطابقة التشغيلة والصلاحية بين الفاتورة والفعلي · روابط شخصية للمفوضين · <span class="ltr">SH-012-F04</span></p>
    </div>
    <div class="actions"><a href="{{ route('inspections.index') }}" class="btn ghost">سجل التفتيش الذاتي</a></div>
  </div>

  <div class="stats">
    <div class="stat"><b>{{ $stats['checks'] }}</b><span>فحص في {{ $month }}</span></div>
    <div class="stat"><b>{{ $stats['items'] }}</b><span>صنف تم فحصه</span></div>
    <div class="stat {{ $stats['bad'] ? 'bad' : 'ok' }}"><b>{{ $stats['bad'] }}</b><span>صنف غير مطابق</span></div>
    <div class="stat {{ $stats['accuracy'] !== null && $stats['accuracy'] < 98 ? 'warn' : 'ok' }}"><b>{{ $stats['accuracy'] === null ? '—' : $stats['accuracy'] . '%' }}</b><span>نسبة المطابقة</span></div>
    <div class="stat {{ $stats['open'] ? 'warn' : '' }}"><b>{{ $stats['open'] }}</b><span>بانتظار المراجعة</span></div>
  </div>

  <div class="two">
    <div class="card">
      <form method="GET" action="{{ route('field.index') }}" class="filters">
        <input type="month" name="month" value="{{ $month }}" onchange="this.form.submit()" style="direction:ltr">
        <select name="who" onchange="this.form.submit()">
          <option value="">كل المفوضين</option>
          @foreach ($delegates as $dl)<option value="{{ $dl->id }}" {{ $who === $dl->id ? 'selected' : '' }}>{{ $dl->name }}</option>@endforeach
        </select>
        <select name="only" onchange="this.form.submit()">
          <option value="">الكل</option>
          <option value="bad" {{ $only === 'bad' ? 'selected' : '' }}>بها عدم مطابقة</option>
          <option value="open" {{ $only === 'open' ? 'selected' : '' }}>لم تُراجع</option>
        </select>
      </form>
      <div class="tbl-wrap">
        <table class="t">
          <thead><tr><th>الرقم</th><th>التاريخ</th><th>الفاتورة · الصيدلية</th><th>المفتش</th><th>أصناف</th><th>النتيجة</th><th>المراجعة</th></tr></thead>
          <tbody>
            @forelse ($rows as $r)
              @php
                $d = $r->data ?? [];
                $bad = (int) ($d['bad'] ?? 0);
              @endphp
              <tr>
                <td><a href="{{ route('field.show', $r) }}" class="v"><b>{{ $r->number }}</b></a></td>
                <td class="v">{{ $r->record_date?->format('Y-m-d') }} <span class="by">{{ $d['time'] ?? '' }}</span></td>
                <td><span class="v">{{ $d['invoice'] ?? '' }}</span><div class="by">{{ $d['pharmacy'] ?? '' }}</div></td>
                <td>{{ $d['inspector'] ?? '—' }}</td>
                <td class="v">{{ count($d['items'] ?? []) }}</td>
                <td>@if ($bad)<span class="pill p-bad">{{ $bad }} غير مطابق</span>@else<span class="pill p-ok">مطابق</span>@endif</td>
                <td>@if ($r->status === 'final')<span class="pill p-ok">✓ {{ $d['review']['by'] ?? '' }}</span>@else<a href="{{ route('field.show', $r) }}" class="pill p-open">مراجعة ←</a>@endif</td>
              </tr>
            @empty
              <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:20px">لا توجد فحوصات في {{ $month }}.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div>
      <div class="card">
        <h2 style="font-size:16px;margin:0 0 4px">المفوضون وروابطهم</h2>
        <div class="by" style="margin-bottom:6px">لكل مفوض رابط شخصي يفتح من الموبايل بدون تسجيل دخول. أوقف الرابط عند انتهاء التفويض، أو جدده إذا وصل لغير صاحبه.</div>
        @forelse ($delegates as $dl)
          <div class="dl {{ $dl->is_active ? '' : 'off' }} {{ session('newLink') === $dl->id ? 'new' : '' }}">
            <div class="nm"><b>{{ $dl->name }}</b> <span class="by">{{ $dl->job }}</span>
              <div class="by">{{ $dl->is_active ? 'فعال' : 'موقوف' }}{{ $dl->last_used_at ? ' · آخر استخدام ' . $dl->last_used_at->timezone('Africa/Cairo')->format('Y-m-d H:i') : ' · لم يُستخدم بعد' }}</div>
              @if ($dl->is_active && $canManage)<input class="lnk" readonly value="{{ $dl->url() }}" onclick="this.select()">@endif
            </div>
            @if ($canManage)
              <div class="actions" style="gap:4px">
                @if ($dl->is_active)
                  <button type="button" class="btn ghost small" onclick="navigator.clipboard.writeText('{{ $dl->url() }}');this.textContent='تم النسخ ✓'">نسخ</button>
                  <a class="btn ghost small" target="_blank" rel="noopener" href="https://wa.me/?text={{ urlencode('رابط التفتيش الميداني (' . $dl->name . '): ' . $dl->url()) }}">واتساب</a>
                @endif
                <form method="POST" action="{{ route('field.delegates.renew', $dl) }}" style="margin:0" onsubmit="return confirm('رابط جديد؟ الرابط القديم سيتوقف.')">@csrf<button class="btn ghost small">تجديد</button></form>
                <form method="POST" action="{{ route('field.delegates.toggle', $dl) }}" style="margin:0">@csrf<button class="btn {{ $dl->is_active ? 'danger' : 'ghost' }} small">{{ $dl->is_active ? 'إيقاف' : 'تفعيل' }}</button></form>
              </div>
            @endif
          </div>
        @empty
          <div class="by" style="padding:8px 0">لا يوجد مفوضون بعد.</div>
        @endforelse
        @if ($canManage)
          <form method="POST" action="{{ route('field.delegates.store') }}" class="add">
            @csrf
            <div><label class="f req">الاسم</label><input type="text" name="name" required></div>
            <div><label class="f">الوظيفة</label><input type="text" name="job" placeholder="أمين مخزن"></div>
            <button type="submit" class="btn">+ رابط جديد</button>
          </form>
        @endif
      </div>

      <div class="card">
        <h2 style="font-size:16px;margin:0 0 8px">عدم المطابقة حسب الزون</h2>
        <table class="t">
          <thead><tr><th>الزون</th><th>أصناف</th><th>غير مطابق</th></tr></thead>
          <tbody>
            @forelse ($zones as $z)
              <tr><td>{{ $z['zone'] }}</td><td class="v">{{ $z['n'] }}</td><td class="v" style="{{ $z['bad'] ? 'color:var(--bad);font-weight:700' : '' }}">{{ $z['bad'] }}</td></tr>
            @empty
              <tr><td colspan="3" class="by" style="padding:12px">لا توجد بيانات.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
@endsection