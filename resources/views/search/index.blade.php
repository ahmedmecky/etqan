@extends('layouts.app')

@section('title', 'البحث في النظام')

@push('head')
<style>
  .sr{max-width:860px;margin:0 auto}
  .sr form{display:flex;gap:8px;margin-bottom:14px}
  .sr form input{flex:1;padding:11px 14px;font-size:15px}
  .sr .grp{background:#fff;border:1px solid var(--line);border-radius:14px;margin-bottom:12px;overflow:hidden}
  .sr .grp h2{font-size:14px;margin:0;padding:10px 16px;background:#f7f9f9;border-bottom:1px solid var(--line);color:var(--muted)}
  .sr .grp a{display:flex;align-items:center;gap:12px;padding:10px 16px;color:inherit;text-decoration:none;border-top:1px solid #f1f3f3}
  .sr .grp h2 + a{border-top:0}
  .sr .grp a:hover{background:#f5f9f8}
  .sr .c{direction:ltr;font-weight:700;color:var(--brand);font-size:13px;white-space:nowrap;min-width:96px}
  .sr .t{flex:1;min-width:0}
  .sr .t b{display:block;font-size:14.5px;line-height:1.5}
  .sr .t small{display:block;font-size:12.5px;color:var(--muted)}
  .sr .s{font-size:11.5px;border-radius:99px;padding:2px 9px;background:#f1f3f3;color:#5f6f6c;white-space:nowrap}
  .sr .s.effective{background:#e5f4ea;color:#1e7a3c} .sr .s.pending{background:#fdf3dc;color:#8a5a00} .sr .s.obsolete{background:#fbe9e7;color:#b3261e}
  .sr .empty{background:#fff;border:1px dashed var(--line);border-radius:12px;padding:22px;text-align:center;color:var(--muted)}
  @media (max-width:560px){ .sr .c{min-width:0} .sr .grp a{flex-wrap:wrap} }
</style>
@endpush

@section('content')
<div class="sr">
  <h1 class="page-title">البحث في النظام</h1>

  <form method="GET" action="{{ route('search') }}">
    <input type="search" name="q" value="{{ $res['q'] }}" placeholder="إجراء، نموذج، رقم سجل، شاشة، موظف…" autofocus autocomplete="off" enterkeyhint="search">
    <button type="submit" class="btn">بحث</button>
  </form>

  @if (mb_strlen($res['q']) < 2)
    <div class="empty">اكتب حرفين على الأقل. أمثلة: «نظافة»، «SH-003»، «RCV-2026-004»، «حرارة»، «JD-WH-03».</div>
  @elseif (! $res['total'])
    <div class="empty">لا توجد نتائج لـ «{{ $res['q'] }}». جرّب كلمة أخرى أو الكود.</div>
  @else
    <p class="page-sub">{{ $res['total'] }} نتيجة لـ «{{ $res['q'] }}»</p>
    @foreach ($res['groups'] as $g)
      <div class="grp">
        <h2>{{ $g['title'] }} ({{ count($g['items']) }})</h2>
        @foreach ($g['items'] as $i)
          <a href="{{ $i['url'] }}">
            @if ($i['code'])<span class="c">{{ $i['code'] }}</span>@endif
            <span class="t"><b>{{ $i['title'] }}</b>@if ($i['sub'])<small>{{ $i['sub'] }}</small>@endif</span>
            @if ($i['badge'])<span class="s {{ $i['state'] ?? '' }}">{{ $i['badge'] }}</span>@endif
          </a>
        @endforeach
      </div>
    @endforeach
  @endif
</div>
@endsection