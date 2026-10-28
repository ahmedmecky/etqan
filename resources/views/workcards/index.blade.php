@extends('layouts.app')

@section('title', 'بطاقات العمل المبسطة')

@push('head')
<style>
  .wi{max-width:1000px;margin:0 auto}
  .mine{display:flex;align-items:center;gap:12px;flex-wrap:wrap;background:#0f5e5a;color:#fff;border-radius:14px;padding:14px 18px;margin-bottom:14px}
  .mine b{font-size:17px}
  .mine .btn{margin-inline-start:auto;background:#fff;color:#0f5e5a !important}
  .grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:10px}
  .cd{background:#fff;border:1px solid var(--line);border-radius:12px;padding:12px 14px;display:flex;flex-direction:column;gap:6px}
  .cd b{font-size:15px;line-height:1.45}
  .cd small{font-size:12px;color:var(--muted)}
  .cd .ac{display:flex;gap:6px;flex-wrap:wrap;margin-top:auto}
  .sp{background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px;margin:16px 0}
  .sp h2{font-size:15.5px;margin:0 0 8px}
  .sp form{display:flex;gap:8px;flex-wrap:wrap}
  .sp select{flex:1;min-width:240px}
  .hint{font-size:12.5px;color:var(--muted);line-height:1.8}
</style>
@endpush

@section('content')
<div class="wi">
  <h1 class="page-title">بطاقات العمل المبسطة</h1>
  <p class="page-sub">لكل وظيفة: ماذا يفعل بالضبط، خطوة خطوة، مأخوذة من الإجراءات المعتمدة. للعامل الذي لا يحتاج قراءة الإجراء كاملاً.
    الوصف الوظيفي الكامل والإجراء الكامل يبقيان المرجع.</p>

  @if ($mine)
    <div class="mine">
      <div>بطاقتي<br><b>{{ $mine['title'] }}</b></div>
      <a href="{{ route('workcards.mine') }}" target="_blank" class="btn">طباعة بطاقتي</a>
    </div>
  @endif

  <div class="sp">
    <h2>بطاقات إجراء واحد</h2>
    <p class="hint" style="margin-top:0">مثال: إجراء النظافة SH-003 ← بطاقة لعامل النظافة، وأخرى لأمين المخزن، وأخرى للمحضر: كلٌّ بخطواته في هذا الإجراء فقط.</p>
    <form method="GET" onsubmit="if(this.code.value){window.open('{{ url('/work-cards/sop') }}/' + this.code.value, '_blank');} return false;">
      <select name="code">
        <option value="">اختر الإجراء…</option>
        @foreach ($sops as $s)<option value="{{ $s['code'] }}">{{ $s['code'] }} · {{ $s['title'] }}</option>@endforeach
      </select>
      <button type="submit" class="btn">طباعة بطاقات الإجراء</button>
    </form>
  </div>

  <h2 style="font-size:16px;margin:16px 0 8px">بطاقة لكل وظيفة ({{ $cards->count() }})</h2>
  <div class="grid">
    @foreach ($cards as $c)
      <div class="cd">
        <b>{{ $c['title'] }}</b>
        <small><span class="ltr">{{ $c['jd'] ?? 'بدون وصف وظيفي' }}</span> · {{ $c['n'] }} خطوة</small>
        <div class="ac">
          <a href="{{ route('workcards.show', $c['key']) }}" target="_blank" class="btn small">طباعة البطاقة</a>
          @if ($c['jd'] && $manage)<a href="{{ route('jobs.print', $c['jd']) }}" target="_blank" class="btn ghost small">الوصف الكامل</a>@endif
        </div>
      </div>
    @endforeach
  </div>
</div>
@endsection