@extends('layouts.app')

@section('title', 'إعداد الإشعارات')

@push('head')
<style>
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:14px}
  .info{background:#eef6f5;border:1px solid #d3e8e3;border-radius:10px;padding:10px 14px;font-size:13.5px;line-height:1.9;margin-bottom:14px}
  .ntf{border-collapse:separate;border-spacing:0;font-size:13.5px;width:100%;min-width:760px}
  .ntf th,.ntf td{border-bottom:1px solid var(--line);border-inline-start:1px solid var(--line);padding:7px 8px;text-align:center;background:#fff}
  .ntf thead th{position:sticky;top:0;background:#f6f8f7;font-size:12.5px;vertical-align:bottom;z-index:2;line-height:1.4;min-width:120px}
  .ntf thead th small{display:block;font-weight:400;color:var(--muted);font-size:11px}
  .ntf th.r,.ntf td.r{position:sticky;right:0;text-align:start;min-width:220px;z-index:1;border-inline-start:0}
  .ntf thead th.r{z-index:3}
  .ntf td.r b{display:block}
  .ntf td.r span{font-size:12px;color:var(--muted)}
  .ntf td.on{background:#e4f4ea}
  .ntf td.def{background:#f5f9f8}
  .ntf input[type=checkbox]{width:19px;height:19px;accent-color:var(--brand);cursor:pointer}
  .ntf .na{font-size:11px;color:#8a9895}
  .ntf tfoot td{background:#fafbfb;font-size:12px;color:var(--muted);text-align:start;line-height:1.7}
  .tw{overflow:auto;max-height:70vh;border:1px solid var(--line);border-radius:10px}
  .dev{display:inline-flex;align-items:center;gap:4px;font-size:11.5px;border-radius:99px;padding:1px 8px;margin-top:3px}
  .dev.y{background:#e4f4ea;color:#1e7a46}
  .dev.n{background:#f3f4f4;color:#8a9895}
  .tst{background:none;border:0;color:var(--brand);font:inherit;font-size:12px;cursor:pointer;padding:0;text-decoration:underline}
  .warnbox{background:#fdf1dc;border:1px solid #f0d9a8;color:#7a4f00;border-radius:10px;padding:10px 14px;font-size:13.5px;margin-bottom:14px}
</style>
@endpush

@section('content')
  <div class="head-actions">
    <div>
      <h1 class="page-title">إعداد الإشعارات</h1>
      <p class="page-sub" style="margin:0">من يستلم تنبيه الموبايل (بصوت) عند كل حدث</p>
    </div>
    <div class="actions">
      <a href="{{ route('notifications.index') }}" class="btn ghost">إشعاراتي</a>
      <a href="{{ route('client.permissions.index') }}" class="btn ghost">الصلاحيات</a>
    </div>
  </div>

  @unless ($ready)
    <div class="warnbox"><b>إشعارات الموبايل غير مفعلة على الخادم بعد.</b> شغّل مرة واحدة: <span class="ltr">php artisan etqan:push-keys</span> ثم <span class="ltr">php artisan optimize:clear</span>. حتى ذلك الحين تظهر الإشعارات في الجرس فقط.</div>
  @endunless

  <div class="info">
    اختر المستلمين لكل حدث. إذا لم تختر أحداً لحدث يستلمه <b>مدير الجودة والمدير الصيدلي</b> تلقائياً (الخلايا المظللة الفاتحة).
    <b>الاعتمادات</b> (استلام بانتظار القرار · انحراف بانتظار التصنيف · تقرير حرارة · محضر تدريب · وثيقة للمراجعة) تذهب تلقائياً لصاحب الاعتماد حسب دوره ولا تحتاج إعداداً.
    لا يستلم الشخص إشعار شاشة ليس له صلاحية عرضها. ليصل الإشعار للموبايل يفتح كل مستلم النظام من موبايله مرة ويضغط
    <b>«تفعيل الإشعارات»</b> من قائمة اسمه (أعلى الشاشة)، وعلى الآيفون يضيفه أولاً للشاشة الرئيسية.
  </div>

  <form method="POST" action="{{ route('client.notify.update') }}">
    @csrf
    @method('PUT')
    <div class="tw">
      <table class="ntf">
        <thead>
          <tr>
            <th class="r">المستخدم</th>
            @foreach ($events as $e)
              <th>{!! \App\Support\Icon::svg($e['icon'], 18) !!}<br>{{ $e['label'] }}<small>{{ $e['hint'] }}</small></th>
            @endforeach
          </tr>
        </thead>
        <tbody>
          @foreach ($users as $u)
            @php
              $n = (int) ($devices[$u->id] ?? 0);
            @endphp
            <tr>
              <td class="r">
                <b>{{ $u->displayName() }}</b>
                <span>{{ $u->role?->name_ar }}</span><br>
                <span class="dev {{ $n ? 'y' : 'n' }}">{!! \App\Support\Icon::svg('bell', 12) !!} {{ $n ? $n . ' جهاز مفعّل' : 'لم يفعّل على جهاز' }}</span>
                @if ($n)
                  · <button type="submit" class="tst" form="test-{{ $u->id }}">إشعار تجريبي</button>
                @endif
              </td>
              @foreach ($events as $e)
                @php
                  $can = $access($u, $e['module']);
                  $on = in_array($u->id, $e['chosen'], true);
                  $def = ! $e['chosen'] && in_array($u->displayName(), $e['defaults'], true);
                @endphp
                <td class="{{ $on ? 'on' : ($def ? 'def' : '') }}">
                  @if ($can)
                    <input type="checkbox" name="r[{{ $e['key'] }}][]" value="{{ $u->id }}" {{ $on ? 'checked' : '' }} title="{{ $e['label'] }}">
                  @else
                    <span class="na">بدون صلاحية</span>
                  @endif
                </td>
              @endforeach
            </tr>
          @endforeach
        </tbody>
        <tfoot>
          <tr>
            <td class="r">الحالي</td>
            @foreach ($events as $e)
              <td>{{ $e['chosen'] ? count($e['chosen']) . ' مستلم' : 'افتراضي: ' . (implode('، ', $e['defaults']) ?: '—') }}</td>
            @endforeach
          </tr>
        </tfoot>
      </table>
    </div>
    <div class="actions" style="margin-top:14px">
      <button type="submit" class="btn">حفظ المستلمين</button>
    </div>
  </form>

  @foreach ($users as $u)
    @if ((int) ($devices[$u->id] ?? 0))
      <form id="test-{{ $u->id }}" method="POST" action="{{ route('client.notify.test', $u) }}" hidden>@csrf</form>
    @endif
  @endforeach
@endsection