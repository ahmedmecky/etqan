@extends('layouts.app')

@section('title', 'قراءات الحرارة')

@push('head')
<style>
  .tr-wrap{max-width:760px;margin:0 auto}
  .hero{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:12px}
  .hero h1{margin:0;font-size:20px}
  .code{font-size:12px;color:var(--muted);direction:ltr;display:inline-block}
  .pick{display:flex;gap:8px;align-items:center;flex-wrap:wrap;background:#fff;border:1px solid var(--line);border-radius:12px;padding:10px 12px;margin-bottom:12px}
  .pick input[type=date]{width:auto;direction:ltr}
  .seg{display:inline-flex;border:1px solid var(--line);border-radius:10px;overflow:hidden}
  .seg a{padding:9px 16px;font-weight:700;color:var(--muted);text-decoration:none;font-size:14px}
  .seg a+a{border-inline-start:1px solid var(--line)}
  .seg a.on{background:var(--brand);color:#fff}
  .seg a small{font-weight:400;font-size:11px;display:block;text-align:center}
  .pt{background:#fff;border:1px solid var(--line);border-radius:12px;padding:12px 14px;margin-bottom:10px;border-inline-start:5px solid var(--line)}
  .pt.ok{border-inline-start-color:var(--ok)} .pt.warn{border-inline-start-color:#d99a1e;background:#fffcf4} .pt.out{border-inline-start-color:var(--bad);background:#fff8f7}
  .pt-h{display:flex;justify-content:space-between;align-items:flex-start;gap:8px;margin-bottom:8px}
  .pt-h b{font-size:15.5px}
  .pt-h .lim{font-size:12px;color:var(--muted)}
  .pt-h .ico{font-size:18px}
  .vals{display:grid;grid-template-columns:1fr 1fr;gap:10px}
  .vals.nohum{grid-template-columns:1fr}
  .seg{flex-wrap:wrap}
  .others{display:flex;gap:6px;flex-wrap:wrap;margin-top:6px}
  .others span{font-size:12px;background:#f3f6f5;border-radius:99px;padding:1px 9px;color:var(--muted);direction:ltr}
  .others span.out{background:#fde8e6;color:var(--bad);font-weight:700}
  .vals label{display:block;font-size:12px;color:var(--muted);margin-bottom:3px;font-weight:600}
  .vals input{font-size:19px;padding:10px 8px;text-align:center;direction:ltr;font-weight:700;width:100%}
  .msg{font-size:12.5px;margin-top:6px;font-weight:600}
  .msg.out{color:var(--bad)} .msg.warn{color:#9a6300} .msg.ok{color:var(--ok)}
  .note{margin-top:8px}
  .note textarea{min-height:50px;font-size:14px}
  .prev{font-size:12px;color:var(--muted);margin-top:6px}
  .f03{display:inline-block;margin-top:8px;font-size:13px;font-weight:700;border-radius:8px;padding:6px 12px;text-decoration:none}
  .f03.open{background:var(--bad);color:#fff} .f03.done{background:#e3f4ea;color:var(--ok)} .f03.pend{background:#fdf1dc;color:#9a6300}
  .bar{position:sticky;bottom:0;background:#fff;border-top:1px solid var(--line);padding:10px 0;margin-top:10px;display:flex;gap:8px;align-items:center;z-index:5}
  .bar .btn{flex:1;font-size:16px;padding:12px}
  .grp{font-size:13px;font-weight:700;color:var(--brand);margin:14px 0 6px}
  .links{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}
  .err{color:var(--bad);font-size:12.5px;margin-top:4px;font-weight:600}
  .lock{background:#eef0f0;color:var(--muted);border-radius:10px;padding:10px 12px;font-size:13.5px;margin-bottom:10px}
  fieldset{border:0;padding:0;margin:0;min-width:0}
</style>
@endpush

@section('content')
<div class="tr-wrap">
  <div class="hero">
    <div>
      <h1>قراءات الحرارة والرطوبة</h1>
      <span class="code">SH-030-F01 · F02</span>
    </div>
    <a href="{{ route('temperature.report') }}" class="btn ghost small">التقرير الشهري</a>
  </div>

  <form method="GET" action="{{ route('temperature.round') }}" class="pick">
    <input type="date" name="date" value="{{ $date->toDateString() }}" max="{{ now('Africa/Cairo')->toDateString() }}" onchange="this.form.submit()">
    <input type="hidden" name="shift" value="{{ $shift }}">
    <span class="seg">
      @foreach ($rounds as $k => $tm)
        <a href="{{ route('temperature.round', ['date' => $date->toDateString(), 'shift' => $k]) }}" class="{{ $shift === (string) $k ? 'on' : '' }}">{{ \App\Support\TempRounds::label((string) $k, null, false) }}<small>{{ $tm }} · {{ $done[$k] ?? 0 }}/{{ $points->count() }}</small></a>
      @endforeach
    </span>
    @unless ($isToday)<span class="code" style="direction:rtl">يوم سابق</span>@endunless
  </form>

  @if ($points->isEmpty())
    <div class="card">
      <p style="margin:0 0 10px">لم تُحدد نقاط المراقبة بعد (مناطق التخزين والثلاجات).</p>
      @if ($canManage)<a href="{{ route('temperature.points') }}" class="btn">إعداد نقاط المراقبة</a>
      @else<span class="code" style="direction:rtl">يقوم بها مدير المخزن أو مدير إدارة الجودة.</span>@endif
    </div>
  @else
    @unless ($canEdit)
      <div class="lock">قراءات الأيام السابقة للعرض فقط. التعديل للمدير الصيدلي أو مدير إدارة الجودة.</div>
    @endunless

    <form method="POST" action="{{ route('temperature.save') }}" id="roundForm" autocomplete="off">
      @csrf
      <input type="hidden" name="date" value="{{ $date->toDateString() }}">
      <input type="hidden" name="shift" value="{{ $shift }}">
      <fieldset {{ $canEdit ? '' : 'disabled' }}>
      @foreach ($points as $p)
        @php
          if ($loop->first) { $lastType = null; }
          $r = $cur->get($p->id);
          $others = $all->except([$shift])->map(fn ($g) => $g->get($p->id))->filter()->sortKeys();
          $val = fn ($k, $col) => old("r.{$p->id}.{$k}", $r ? ($col === 'hum' ? $r->hum : \App\Models\TempPoint::n($r->$col)) : '');
          $x = $exc->get($r?->id);
          $grp = $p->isCold() ? 'cold' : 'room';
        @endphp
        @if ($grp !== $lastType)
          <div class="grp">{{ $grp === 'cold' ? '❄ الثلاجات وغرف التبريد' : '🌡 مناطق التخزين' }}</div>
          @php
            $lastType = $grp;
          @endphp
        @endif
        <div class="pt {{ $r?->status }}" data-pt="{{ $p->id }}" data-min="{{ $p->t_min }}" data-max="{{ $p->t_max }}"
             data-wl="{{ $p->warn_low }}" data-wh="{{ $p->warn_high }}" data-hmax="{{ $p->h_max }}">
          <div class="pt-h">
            <div><b>{{ $p->name }}</b>
              <div class="lim">{{ $p->limitsText() }}@if ($p->device_no) · جهاز <span class="code">{{ $p->device_no }}</span>@endif</div></div>
            <span class="ico" data-o="ico">{{ $r ? ($r->status === 'out' ? '⚠️' : ($r->status === 'warn' ? '⚡' : '✓')) : '' }}</span>
          </div>
          <div class="vals {{ $p->h_max ? '' : 'nohum' }}">
            <div><label>درجة الحرارة °م</label><input type="number" step="0.1" inputmode="decimal" name="r[{{ $p->id }}][cur]" value="{{ $val('cur', 'cur') }}"></div>
            @if ($p->h_max)
              <div><label>الرطوبة ٪</label><input type="number" step="1" inputmode="numeric" name="r[{{ $p->id }}][hum]" value="{{ $val('hum', 'hum') }}"></div>
            @endif
          </div>
          <div class="msg" data-o="msg"></div>
          @foreach (['cur', 'hum', 'note'] as $k)@error("r.{$p->id}.{$k}")<div class="err">{{ $message }}</div>@enderror @endforeach
          <div class="note" data-o="note" {{ ($r?->status === 'out' || old("r.{$p->id}.note") || $r?->note) ? '' : 'hidden' }}>
            <textarea name="r[{{ $p->id }}][note]" placeholder="ملاحظة أو إجراء فوري (اختياري): التحقق بجهاز آخر · السبب · ما تم">{{ old("r.{$p->id}.note", $r?->note) }}</textarea>
          </div>
          @if ($r)
            <div class="prev">سجلها {{ $r->user?->displayName() ?? '—' }} <span class="code">{{ substr((string) $r->time, 0, 5) }}</span>@if ($r->editor) · عدّلها {{ $r->editor->displayName() }}@endif</div>
            @if ($r->status === 'out')
              @if ($x)
                <a href="{{ $f03Url }}?open={{ $x['id'] }}" class="f03 {{ $x['final'] ? 'done' : 'pend' }}">تقرير الانحراف {{ $x['no'] }} {{ $x['final'] ? '✓' : '· مفتوح' }}</a>
              @else
                <a href="{{ $f03Url }}?reading={{ $r->id }}" class="f03 open">⚠ فتح تقرير انحراف الحرارة F03</a>
              @endif
            @endif
          @endif
          @if ($others->isNotEmpty())
            <div class="others">@foreach ($others as $ok => $o)<span class="{{ $o->status === 'out' ? 'out' : '' }}" title="{{ \App\Support\TempRounds::label((string) $ok) }}">{{ \App\Support\TempRounds::short((string) $ok) }}: {{ \App\Models\TempPoint::n($o->cur) }}°{{ $o->hum !== null ? ' · ' . $o->hum . '%' : '' }}</span>@endforeach</div>
          @endif
        </div>
      @endforeach
      </fieldset>

      @if ($canEdit)
        <div class="bar"><button type="submit" class="btn">حفظ {{ \App\Support\TempRounds::label($shift) }}</button></div>
      @endif
    </form>
  @endif

  <div class="links">
    <a href="{{ route('temperature.sheet') }}" class="btn ghost small">سجل نصف الشهر (طباعة)</a>
    <a href="{{ route('temperature.points') }}" class="btn ghost small">نقاط المراقبة</a>
    <a href="{{ $f03Url }}" class="btn ghost small">تقارير الانحراف F03</a>
  </div>
</div>
@endsection

@push('scripts')
<script>
(function () {
  const num = v => { const n = parseFloat(v); return isFinite(n) ? n : null; };
  const f = v => String(Math.round(v * 10) / 10);
  function check(card) {
    const d = card.dataset, lim = { min: num(d.min), max: num(d.max), wl: num(d.wl), wh: num(d.wh), h: num(d.hmax) };
    const get = k => { const i = card.querySelector(`[name$="[${k}]"]`); return i ? num(i.value) : null; };
    const vals = [['الحرارة', get('cur')]];
    const out = [], warn = [];
    vals.forEach(([l, v]) => {
      if (v === null) return;
      if (v > lim.max) out.push(`${l} أعلى من ${f(lim.max)}`);
      else if (v < lim.min) out.push(`${l} أقل من ${f(lim.min)}`);
      else if (lim.wh !== null && v > lim.wh) warn.push(`${l} فوق حد الإنذار ${f(lim.wh)}`);
      else if (lim.wl !== null && v < lim.wl) warn.push(`${l} تحت حد الإنذار ${f(lim.wl)}`);
    });
    const h = get('hum');
    if (lim.h && h !== null && h > lim.h) out.push(`الرطوبة أعلى من ${lim.h}٪`);
    const any = vals.some(x => x[1] !== null) || h !== null;
    const st = !any ? '' : out.length ? 'out' : warn.length ? 'warn' : 'ok';
    card.classList.remove('ok', 'warn', 'out'); if (st) card.classList.add(st);
    const m = card.querySelector('[data-o=msg]');
    m.className = 'msg ' + st;
    m.textContent = st === 'out' ? '⚠ خارج الحدود: ' + out.join(' · ') + ' — تُحفظ عادي، ويصل إشعار لمدير الجودة والمدير الصيدلي.'
      : st === 'warn' ? '⚡ إنذار مبكر: ' + warn.join(' · ') : st === 'ok' ? 'ضمن الحدود' : '';
    card.querySelector('[data-o=ico]').textContent = st === 'out' ? '⚠️' : st === 'warn' ? '⚡' : st === 'ok' ? '✓' : '';
    const note = card.querySelector('[data-o=note]');
    if (st === 'out' || st === 'warn') note.hidden = false;
  }
  document.querySelectorAll('[data-pt]').forEach(card => {
    card.addEventListener('input', () => check(card));
    if (card.querySelector('[name$="[cur]"]').value !== '' || (card.querySelector('[name$="[hum]"]') || {}).value) check(card);
  });
})();
</script>
@endpush