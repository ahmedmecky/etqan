@extends('layouts.app')

@section('title', 'إعدادات المخزن')

@push('head')
<style>
  .set-group{margin-bottom:18px}
  .set-group h2{font-size:16px;margin:0 0 4px;display:flex;align-items:center;gap:8px}
  .set-group h2 .code{font-size:12px;color:var(--muted);direction:ltr;font-weight:600}
  .set-row{display:grid;grid-template-columns:1fr 220px;gap:16px;align-items:center;padding:14px 0;border-top:1px solid var(--line)}
  .set-row:first-of-type{border-top:0}
  .set-row b{display:block;font-size:14.5px;margin-bottom:3px}
  .set-row p{margin:0;font-size:13px;color:var(--muted);line-height:1.7}
  .set-ctl{display:flex;justify-content:flex-end;align-items:center;gap:8px}
  .set-ctl input[type=number]{width:110px;text-align:center}
  .switch{position:relative;display:inline-block;width:52px;height:28px;flex:none}
  .switch input{opacity:0;width:0;height:0}
  .switch span{position:absolute;inset:0;background:#c9d3d1;border-radius:99px;cursor:pointer;transition:.2s}
  .switch span:before{content:"";position:absolute;width:22px;height:22px;top:3px;inset-inline-start:3px;background:#fff;border-radius:50%;transition:.2s;box-shadow:0 1px 2px rgba(0,0,0,.2)}
  .switch input:checked + span{background:var(--brand)}
  .switch input:checked + span:before{inset-inline-start:27px}
  .switch input:focus-visible + span{outline:2px solid var(--gold);outline-offset:2px}
  .state{font-size:13px;font-weight:600;min-width:70px;text-align:start}
  .err{color:var(--bad);font-size:12.5px;margin-top:4px}
  @media (max-width:640px){ .set-row{grid-template-columns:1fr} .set-ctl{justify-content:flex-start} }
  .portal-url{display:flex;gap:8px;align-items:center;margin:10px 0}
  .portal-url input{flex:1;direction:ltr;text-align:left;font-family:monospace;font-size:13px;background:#f6f8f7}
  .portal-meta{display:flex;gap:18px;flex-wrap:wrap;font-size:13px;color:var(--muted);margin-top:8px}
  .portal-meta b{color:var(--ink)}
  .pill{display:inline-block;border-radius:99px;padding:2px 12px;font-size:12.5px;font-weight:700}
  .pill.on{background:#e4f4ea;color:#1e7a46} .pill.off{background:#fbe7e5;color:#b3261e}
</style>
@endpush

@section('content')
  <h1 class="page-title">إعدادات المخزن</h1>
  <p class="page-sub">{{ $tenant->name_ar }} · تسري على كل مستخدمي المخزن</p>

  {{-- رابط شكاوى الصيدليات --}}
  <div class="card set-group">
    <h2>رابط شكاوى الصيدليات <span class="code">SH-007</span>
      <span class="pill {{ $tenant->complaint_portal ? 'on' : 'off' }}">{{ $tenant->complaint_portal ? 'يعمل' : 'متوقف' }}</span></h2>
    <p class="page-sub" style="margin:0">أرسل الرابط للصيدليات (واتساب أو على الفاتورة). الصيدلي يكتب كود الصيدلية ويختار الصنف أو الخدمة، والشكوى تصل لنظام الشكاوى بانتظار تصنيف المدير الصيدلي.</p>
    <div class="portal-url">
      <input type="text" id="portalUrl" value="{{ $portalUrl }}" readonly onclick="this.select()">
      <button type="button" class="btn" onclick="navigator.clipboard.writeText(document.getElementById('portalUrl').value).then(() => { this.textContent = 'تم النسخ ✓'; setTimeout(() => this.textContent = 'نسخ الرابط', 1500); })">نسخ الرابط</button>
      <a href="{{ $portalUrl }}" target="_blank" class="btn ghost">فتح</a>
    </div>
    <div class="portal-meta">
      <span>الأصناف: <b>{{ number_format($products) }}</b> · <a href="{{ route('client.data.index', 'products') }}">إدارة الأصناف</a></span>
      <span>الصيدليات: <b>{{ number_format($pharmacies) }}</b> · <a href="{{ route('client.data.index', 'pharmacies') }}">إدارة الصيدليات</a></span>
    </div>
    @if (! $pharmacies)
      <div class="alert info" style="margin-top:10px">ارفع قائمة الصيدليات أولاً: الصيدلي لا يستطيع الإرسال إلا بكود مسجل.</div>
    @endif
    <div class="actions" style="margin-top:12px">
      <form method="POST" action="{{ route('client.settings.portal.toggle') }}" style="margin:0">
        @csrf
        @method('PATCH')
        <button type="submit" class="btn {{ $tenant->complaint_portal ? 'danger' : '' }}">{{ $tenant->complaint_portal ? 'إيقاف الرابط' : 'تشغيل الرابط' }}</button>
      </form>
      <form method="POST" action="{{ route('client.settings.portal.regenerate') }}" style="margin:0"
            onsubmit="return confirm('سيتوقف الرابط الحالي فوراً ويجب إرسال الرابط الجديد للصيدليات. متابعة؟')">
        @csrf
        <button type="submit" class="btn ghost">إنشاء رابط جديد</button>
      </form>
    </div>
  </div>

  <form method="POST" action="{{ route('client.settings.update') }}">
    @csrf
    @method('PUT')

    @foreach ($groups as $g => $group)
      <div class="card set-group">
        <h2>{{ $group['title'] }} <span class="code">{{ $group['code'] }}</span></h2>

        @foreach ($defs as $key => $def)
          @continue($def['group'] !== $g)
          <div class="set-row">
            <div>
              <b>{{ $def['label'] }}</b>
              <p>{{ $def['help'] }}</p>
              @error($key)<div class="err">{{ $message }}</div>@enderror
            </div>
            <div class="set-ctl">
              @if ($def['type'] === 'bool')
                @php($on = (bool) old($key, $values[$key]))
                <input type="hidden" name="{{ $key }}" value="0">
                <span class="state" id="st_{{ $key }}">{{ $on ? 'مفعّل' : 'غير مفعّل' }}</span>
                <label class="switch">
                  <input type="checkbox" name="{{ $key }}" value="1" {{ $on ? 'checked' : '' }}
                         onchange="document.getElementById('st_{{ $key }}').textContent = this.checked ? 'مفعّل' : 'غير مفعّل'">
                  <span></span>
                </label>
              @elseif ($def['type'] === 'number')
                <input type="number" name="{{ $key }}" value="{{ old($key, $values[$key]) }}"
                       min="{{ $def['min'] }}" max="{{ $def['max'] }}" step="{{ $def['step'] ?? 'any' }}">
                @isset($def['suffix'])<span>{{ $def['suffix'] }}</span>@endisset
              @else
                <input type="text" name="{{ $key }}" value="{{ old($key, $values[$key]) }}" maxlength="500" placeholder="{{ $def['placeholder'] ?? '' }}" class="ltr-in" style="min-width:220px">
              @endif
            </div>
          </div>
        @endforeach
      </div>
    @endforeach

    <div class="actions">
      <button type="submit" class="btn">حفظ الإعدادات</button>
      <a href="{{ route('home') }}" class="btn ghost">رجوع</a>
    </div>
  </form>
@endsection