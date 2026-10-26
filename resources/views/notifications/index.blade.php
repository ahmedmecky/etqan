@extends('layouts.app')

@section('title', 'الإشعارات')

@push('head')
<style>
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:14px}
  .nb-tabs{display:flex;gap:6px;margin-bottom:12px}
  .nb-tabs a{padding:6px 14px;border-radius:99px;border:1px solid var(--line);text-decoration:none;font-size:13.5px;font-weight:600;color:var(--ink);background:#fff}
  .nb-tabs a.on{background:var(--brand);border-color:var(--brand);color:#fff}
  .nl{background:#fff;border:1px solid var(--line);border-radius:12px;overflow:hidden}
  .nl a.it{display:flex;gap:12px;padding:12px 14px;border-bottom:1px solid #eef1f0;text-decoration:none;color:var(--ink)}
  .nl a.it:last-child{border-bottom:0}
  .nl a.it:hover{background:#f7faf9}
  .nl a.it.unread{background:#f2f8f7}
  .nl .dot{width:9px;height:9px;border-radius:50%;margin-top:8px;flex:none;background:transparent}
  .nl .unread .dot{background:var(--brand)}
  .nl .unread.urg .dot{background:var(--bad)}
  .nl .tx{flex:1;min-width:0}
  .nl .tx b{display:block;font-size:14.5px}
  .nl .tx p{margin:2px 0 0;font-size:13px;color:var(--muted);white-space:pre-line;overflow-wrap:anywhere}
  .nl .mt{font-size:11.5px;color:var(--muted);white-space:nowrap;text-align:left}
  .nl .mt span{display:block;margin-top:3px;border-radius:99px;padding:0 8px;background:#eef0f0;color:#5f6f6c;font-weight:600}
  .nl .urg .mt span{background:#fbe7e5;color:#b3261e}
  .empty{padding:30px;text-align:center;color:var(--muted)}
  .devs{font-size:13.5px}
  .devs div{display:flex;justify-content:space-between;align-items:center;gap:10px;padding:8px 0;border-bottom:1px solid #eef1f0}
  .devs div:last-child{border-bottom:0}
  .devs small{color:var(--muted)}
  .push-state{font-size:13.5px;line-height:1.9}
  .lay{display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:16px;align-items:start}
  @media (max-width:900px){ .lay{grid-template-columns:1fr} }
</style>
@endpush

@section('content')
  <div class="head-actions">
    <div>
      <h1 class="page-title">الإشعارات</h1>
      <p class="page-sub" style="margin:0">{{ $unread ? $unread . ' غير مقروء' : 'لا يوجد إشعار غير مقروء' }}</p>
    </div>
    <div class="actions">
      @if ($unread)
        <form method="POST" action="{{ route('notifications.readAll') }}" style="margin:0">@csrf<button class="btn ghost" type="submit">تعليم الكل كمقروء</button></form>
      @endif
      @if (auth()->user()->isClientAdmin())
        <a href="{{ route('client.notify.index') }}" class="btn ghost">إعداد المستلمين</a>
      @endif
    </div>
  </div>

  <div class="lay">
    <div>
      <div class="nb-tabs">
        <a href="{{ route('notifications.index') }}" class="{{ $only ? '' : 'on' }}">الكل</a>
        <a href="{{ route('notifications.index', ['only' => 'unread']) }}" class="{{ $only ? 'on' : '' }}">غير المقروء</a>
      </div>
      <div class="nl">
        @forelse ($list as $n)
          <a class="it {{ $n->read_at ? '' : 'unread' }} {{ $n->urgent ? 'urg' : '' }}" href="{{ route('notifications.open', $n->id) }}">
            <span class="dot"></span>
            <span class="tx"><b>{{ $n->title }}</b>@if ($n->body)<p>{{ $n->body }}</p>@endif</span>
            <span class="mt">{{ $n->created_at?->timezone('Africa/Cairo')->format('Y-m-d H:i') }}<span>{{ \App\Support\Notifier::label($n->event) }}</span></span>
          </a>
        @empty
          <div class="empty">لا توجد إشعارات.</div>
        @endforelse
      </div>
      @if ($list->hasPages())
        <div class="actions" style="margin-top:12px;justify-content:space-between">
          @if ($list->previousPageUrl())<a class="btn ghost" href="{{ $list->previousPageUrl() }}">الأحدث</a>@else<span></span>@endif
          <small style="color:var(--muted)">صفحة {{ $list->currentPage() }} من {{ $list->lastPage() }}</small>
          @if ($list->nextPageUrl())<a class="btn ghost" href="{{ $list->nextPageUrl() }}">الأقدم</a>@else<span></span>@endif
        </div>
      @endif
    </div>

    <div class="card">
      <h2>{!! \App\Support\Icon::svg('bell', 18) !!} إشعارات الموبايل</h2>
      @unless ($ready)
        <div class="alert bad" style="margin-bottom:10px">إشعارات الموبايل غير مفعلة على الخادم بعد. تظهر الإشعارات هنا وفي الجرس فقط.</div>
      @endunless
      <div class="push-state" id="pushState">جارٍ التحقق من هذا الجهاز…</div>
      <div class="actions" style="margin:10px 0 14px">
        <button type="button" class="btn" data-push="on" hidden>تفعيل الإشعارات على هذا الجهاز</button>
        <button type="button" class="btn ghost" data-push="test" hidden>إشعار تجريبي</button>
        <button type="button" class="btn danger" data-push="off" hidden>إيقاف على هذا الجهاز</button>
      </div>
      <b style="font-size:14px">أجهزتي المفعّلة</b>
      <div class="devs">
        @forelse ($devices as $d)
          <div>
            <span>{{ $d->device ?: 'جهاز' }}<br><small>منذ {{ $d->created_at?->timezone('Africa/Cairo')->format('Y-m-d') }}{{ $d->last_ok_at ? ' · آخر إشعار ' . $d->last_ok_at->timezone('Africa/Cairo')->format('Y-m-d H:i') : '' }}</small></span>
            <form method="POST" action="{{ route('notifications.unsubscribe') }}" style="margin:0">@csrf<input type="hidden" name="id" value="{{ $d->id }}"><button class="btn danger" style="padding:4px 10px;font-size:12.5px" type="submit">إزالة</button></form>
          </div>
        @empty
          <div><small>لم تفعّل الإشعارات على أي جهاز بعد.</small></div>
        @endforelse
      </div>
    </div>
  </div>
@endsection