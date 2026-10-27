@extends('layouts.app')

@section('title', 'الصيانة والأجهزة')

@push('head')
<style>
  .mt{max-width:1100px;margin:0 auto}
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:12px}
  .sum{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px}
  .sum a{display:flex;align-items:center;gap:8px;background:#fff;border:1px solid var(--line);border-radius:12px;padding:9px 13px;color:inherit;text-decoration:none;font-size:13.5px}
  .sum a b{font-size:18px}
  .sum a.bad{border-color:#f0c4bf;background:#fff8f7} .sum a.bad b{color:var(--bad)}
  .sum a.warn{border-color:#f0dcae;background:#fffbf1} .sum a.warn b{color:#a86b00}
  .tabs{display:flex;gap:6px;border-bottom:1px solid var(--line);margin-bottom:14px;overflow-x:auto}
  .tabs a{padding:10px 14px;color:var(--muted);text-decoration:none;font-weight:700;border-bottom:3px solid transparent;white-space:nowrap}
  .tabs a.on{color:var(--brand);border-bottom-color:var(--brand)}
  .flt{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px}
  .flt input,.flt select{width:auto;min-width:130px;flex:1}
  .flt input[type=search]{min-width:200px;flex:2}
  .row{background:#fff;border:1px solid var(--line);border-inline-start:5px solid var(--line);border-radius:12px;margin-bottom:8px}
  .row.expired{border-inline-start-color:var(--bad)} .row.week{border-inline-start-color:#e07b00} .row.soon{border-inline-start-color:#d9b25a}
  .row.ok{border-inline-start-color:var(--ok)} .row.off{opacity:.65}
  .row > summary{list-style:none;cursor:pointer;display:grid;grid-template-columns:minmax(0,2.2fr) minmax(0,1.3fr) minmax(0,1.5fr) minmax(0,1.3fr) 18px;gap:10px;align-items:center;padding:11px 14px}
  .row > summary::-webkit-details-marker{display:none}
  .row summary b{display:block;font-size:14.5px;line-height:1.45}
  .row summary small{display:block;font-size:12px;color:var(--muted);line-height:1.6}
  .row .ar{color:var(--muted);transition:transform .15s}
  .row[open] .ar{transform:rotate(180deg)}
  .row .body{border-top:1px solid #eef1f0;padding:12px 14px}
  .ltr{direction:ltr;unicode-bidi:embed}
  .st{display:inline-block;font-size:11.5px;font-weight:700;border-radius:99px;padding:2px 9px;background:#f1f3f3;color:#5f6f6c;white-space:nowrap}
  .st.ok{background:#e5f4ea;color:#1e7a3c} .st.soon{background:#fdf3dc;color:#8a5a00} .st.week{background:#fde7cf;color:#a14f00}
  .st.expired,.st.missing{background:#fbe9e7;color:#b3261e} .st.off,.st.none{background:#f1f3f3;color:#6b7a77} .st.repair,.st.damaged{background:#fde7cf;color:#a14f00}
  .mf{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
  .mf label{display:flex;flex-direction:column;gap:4px;font-size:12.5px;font-weight:700;color:var(--muted)}
  .mf label input,.mf label select,.mf label textarea{font-weight:400;color:var(--ink,#1c2826)}
  .mf .full{grid-column:1 / -1}
  @media (max-width:760px){
    .mf{grid-template-columns:1fr 1fr}
    .row > summary{grid-template-columns:1fr auto;row-gap:4px}
    .row > summary > :nth-child(2),.row > summary > :nth-child(3){grid-column:1 / 2}
    .row > summary .ar{grid-row:1;grid-column:2}
  }
  @media (max-width:480px){ .mf{grid-template-columns:1fr} }
  .new{background:#fff;border:1px dashed #b9d8d3;border-radius:12px;margin-bottom:12px}
  .new > summary{list-style:none;cursor:pointer;padding:11px 14px;font-weight:700;color:var(--brand)}
  .new > summary::-webkit-details-marker{display:none}
  .new .body{padding:0 14px 14px}
  .kv{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:6px 14px;font-size:13.5px;margin-bottom:12px}
  .kv div span{display:block;font-size:12px;color:var(--muted)}
  .devs{font-size:13px;line-height:1.9;margin:8px 0 0;padding-inline-start:18px}
  .empty{background:#fff;border:1px dashed var(--line);border-radius:12px;padding:22px;text-align:center;color:var(--muted)}
  .hint{font-size:12.5px;color:var(--muted);line-height:1.8}
  .renew{background:#f3f9f8;border:1px solid #cfe5e1;border-radius:10px;padding:10px 12px;margin-top:12px}
  .renew .mf{grid-template-columns:repeat(4,minmax(0,1fr))}
  @media (max-width:760px){ .renew .mf{grid-template-columns:1fr 1fr} }
</style>
@endpush

@section('content')
<div class="mt">
  <div class="head-actions">
    <div>
      <h1 class="page-title">الصيانة والأجهزة</h1>
      <p class="page-sub" style="margin:0">عقود الصيانة · سجل الأجهزة بالرقم التسلسلي · الجرد الفعلي <span class="ltr">(SH-023 · SH-017)</span></p>
    </div>
    <div class="actions">
      <a href="{{ route('maint.count') }}" class="btn">جرد الأجهزة</a>
      <a href="{{ route('maint.print') }}" class="btn ghost" target="_blank">طباعة السجل</a>
    </div>
  </div>

  @php
    $S = $summary;
    $L = \App\Support\Maintenance::STATE_LABELS;
  @endphp
  <div class="sum">
    <a href="{{ route('maint.index', ['tab' => 'contracts', 'st' => 'expired']) }}" class="{{ $S['contracts']['expired'] ? 'bad' : '' }}"><b>{{ $S['contracts']['expired'] }}</b> عقد منتهي</a>
    <a href="{{ route('maint.index', ['tab' => 'contracts', 'st' => 'soon']) }}" class="{{ $S['contracts']['soon'] ? 'warn' : '' }}"><b>{{ $S['contracts']['soon'] }}</b> عقد يقترب انتهاؤه</a>
    <a href="{{ route('maint.index', ['tab' => 'assets', 'st' => 'expired']) }}" class="{{ $S['assets']['expired'] ? 'bad' : '' }}"><b>{{ $S['assets']['expired'] }}</b> جهاز تجاوز موعده</a>
    <a href="{{ route('maint.index', ['tab' => 'assets', 'st' => 'soon']) }}" class="{{ $S['assets']['soon'] ? 'warn' : '' }}"><b>{{ $S['assets']['soon'] }}</b> يقترب موعده</a>
    <a href="{{ route('maint.index', ['tab' => 'assets', 'st' => 'missing']) }}" class="{{ $S['missing'] ? 'bad' : '' }}"><b>{{ $S['missing'] }}</b> غير موجود في الجرد</a>
    <a href="{{ route('maint.index', ['tab' => 'assets', 'st' => 'repair']) }}" class="{{ $S['repair'] ? 'warn' : '' }}"><b>{{ $S['repair'] }}</b> تحت الإصلاح</a>
    <a href="{{ route('maint.index', ['tab' => 'assets', 'ct' => 'none']) }}"><b>{{ $S['nocontract'] }}</b> بدون عقد</a>
  </div>

  <div class="tabs">
    <a href="{{ route('maint.index', ['tab' => 'assets']) }}" class="{{ $tab === 'assets' ? 'on' : '' }}">الأجهزة ({{ $allCount }})</a>
    <a href="{{ route('maint.index', ['tab' => 'contracts']) }}" class="{{ $tab === 'contracts' ? 'on' : '' }}">عقود الصيانة ({{ $contracts->where('is_active', true)->count() }})</a>
    <a href="{{ route('maint.index', ['tab' => 'counts']) }}" class="{{ $tab === 'counts' ? 'on' : '' }}">سجل الجرد ({{ $counts->count() }})</a>
  </div>

  <datalist id="locList">@foreach ($locations as $l)<option value="{{ $l }}">@endforeach</datalist>

  {{-- ===================== الأجهزة ===================== --}}
  @if ($tab === 'assets')
    <form method="GET" action="{{ route('maint.index') }}" class="flt">
      <input type="hidden" name="tab" value="assets">
      <input type="search" name="q" value="{{ $f['q'] }}" placeholder="الرقم التسلسلي أو الاسم أو الموقع…">
      <select name="cat" onchange="this.form.submit()">
        <option value="">كل الفئات</option>
        @foreach (\App\Support\Maintenance::CATEGORIES as $k => $c)<option value="{{ $k }}" @selected($f['cat'] === $k)>{{ $c['label'] }}</option>@endforeach
      </select>
      <select name="ct" onchange="this.form.submit()">
        <option value="">كل العقود</option>
        <option value="none" @selected($f['ct'] === 'none')>بدون عقد</option>
        @foreach ($contracts as $c)<option value="{{ $c->id }}" @selected($f['ct'] === (string) $c->id)>{{ $c->company }}{{ $c->is_active ? '' : ' (منتهي/موقوف)' }}</option>@endforeach
      </select>
      <select name="loc" onchange="this.form.submit()">
        <option value="">كل المواقع</option>
        @foreach ($locations as $l)<option value="{{ $l }}" @selected($f['loc'] === $l)>{{ $l }}</option>@endforeach
      </select>
      <select name="st" onchange="this.form.submit()">
        <option value="">كل الحالات</option>
        <option value="expired" @selected($f['st'] === 'expired')>تجاوز موعده</option>
        <option value="soon" @selected($f['st'] === 'soon')>يقترب موعده</option>
        <option value="cexpired" @selected($f['st'] === 'cexpired')>عقده منتهي</option>
        <option value="missing" @selected($f['st'] === 'missing')>غير موجود في الجرد</option>
        <option value="repair" @selected($f['st'] === 'repair')>تحت الإصلاح</option>
      </select>
      <button type="submit" class="btn ghost small">بحث</button>
    </form>

    @if ($manage)
      <details class="new" {{ $allCount === 0 || $errors->any() && old('serial') && ! old('_asset') ? 'open' : '' }}>
        <summary>+ إضافة جهاز</summary>
        <div class="body">
          <form method="POST" action="{{ route('maint.assets.store') }}">
            @csrf
            @include('maintenance._asset', ['a' => null])
            <div class="actions" style="margin-top:10px"><button type="submit" class="btn small">إضافة</button></div>
          </form>
          <p class="hint" style="margin:8px 0 0">الطفايات: اكتب تاريخ انتهاء الطفاية (أو إعادة التعبئة) في حقل التاريخ، واربطها بعقد شركة الحريق: يصلك تنبيه بالتاريخين.
            الترمومترات وأجهزة القياس: تاريخ المعايرة القادمة، واربطها بعقد شركة المعايرة.</p>
        </div>
      </details>
    @endif

    @forelse ($assets as $a)
      @php
        $st = $a->state();
        $ct = $a->contract;
        $cst = $ct?->state();
      @endphp
      <details class="row {{ $st }}" @if ($f['q'] !== '' && $assets->count() === 1) open @endif>
        <summary>
          <div><b>{{ $a->name }}</b><small>{{ $a->categoryLabel() }}@if ($a->brand) · {{ $a->brand }} {{ $a->model }}@endif</small></div>
          <div><small>Serial</small><b class="ltr" style="font-size:13.5px">{{ $a->serial }}</b>@if ($a->tag_no)<small class="ltr">{{ $a->tag_no }}</small>@endif</div>
          <div>
            <small>{{ $a->location ?: 'بدون موقع' }}</small>
            @if ($ct)
              <small>{{ $ct->company }} <span class="st {{ $cst }}">{{ $cst === 'expired' ? 'العقد منتهي' : 'العقد ' . $L[$cst] }}</span></small>
            @else
              <small>بدون عقد صيانة</small>
            @endif
          </div>
          <div>
            @if ($a->due_date)
              <small>{{ $a->dueLabel() }}</small>
              <span class="ltr" style="font-size:13px;font-weight:700">{{ $a->due_date->format('Y-m-d') }}</span> <span class="st {{ $st }}">{{ $L[$st] }}</span>
            @else
              <span class="st none">بدون تاريخ</span>
            @endif
            @if ($a->status !== 'active')<span class="st {{ $a->status === 'repair' ? 'repair' : 'off' }}">{{ $a->statusLabel() }}</span>@endif
            @if ($a->count_result === 'missing')<span class="st missing">غير موجود</span>@endif
          </div>
          <span class="ar">▾</span>
        </summary>
        <div class="body">
          <div class="kv">
            <div><span>آخر صيانة / معايرة</span>{{ $a->last_service?->format('Y-m-d') ?? '—' }}</div>
            <div><span>آخر جرد</span>{{ $a->last_counted?->format('Y-m-d') ?? 'لم يُجرد' }}@if ($a->count_result) · {{ ['ok' => 'موجود', 'missing' => 'غير موجود', 'damaged' => 'تالف'][$a->count_result] ?? '' }}@endif</div>
            @if ($a->due_date)<div><span>{{ $a->dueLabel() }}</span>{{ \App\Support\Maintenance::leftText(\App\Support\Maintenance::daysLeft($a->due_date)) }}</div>@endif
            @if ($ct)<div><span>انتهاء العقد</span>{{ $ct->end_date->format('Y-m-d') }} · {{ \App\Support\Maintenance::leftText($ct->daysLeft()) }}</div>@endif
            @if ($a->notes)<div style="grid-column:1 / -1"><span>ملاحظات</span>{{ $a->notes }}</div>@endif
          </div>
          @if ($manage)
            <form method="POST" action="{{ route('maint.assets.update', $a) }}">
              @csrf
              @method('PUT')
              <input type="hidden" name="_asset" value="{{ $a->id }}">
              @include('maintenance._asset', ['a' => $a])
              <div class="actions" style="margin-top:10px">
                <button type="submit" class="btn small">حفظ</button>
                <button type="submit" form="del{{ $a->id }}" class="btn ghost small" onclick="return confirm('حذف الجهاز {{ $a->serial }} نهائياً؟')">حذف</button>
              </div>
            </form>
            <form method="POST" action="{{ route('maint.assets.destroy', $a) }}" id="del{{ $a->id }}" hidden>@csrf @method('DELETE')</form>
          @endif
        </div>
      </details>
    @empty
      <div class="empty">{{ $allCount ? 'لا توجد أجهزة بهذه الشروط.' : 'لم يُسجل أي جهاز بعد. سجّل الأجهزة الإلكترونية والطفايات وأجهزة القياس برقمها التسلسلي واربطها بعقد الصيانة.' }}</div>
    @endforelse
  @endif

  {{-- ===================== العقود ===================== --}}
  @if ($tab === 'contracts')
    @if ($manage)
      <details class="new" {{ $contracts->isEmpty() ? 'open' : '' }}>
        <summary>+ عقد صيانة جديد</summary>
        <div class="body">
          <form method="POST" action="{{ route('maint.contracts.store') }}" enctype="multipart/form-data">
            @csrf
            @include('maintenance._contract', ['c' => null])
            <div class="actions" style="margin-top:10px"><button type="submit" class="btn small">إضافة</button></div>
          </form>
          <p class="hint" style="margin:8px 0 0">عند اقتراب الانتهاء (حسب «التنبيه قبل»)، ثم قبله بأسبوع، ثم يوم الانتهاء وكل أسبوع بعده: يصل إشعار للمدير الصيدلي ومدير الجودة والمدير العام
            (أو من تختارهم في إعداد الإشعارات · «انتهاء عقود الصيانة والأجهزة»).</p>
        </div>
      </details>
    @endif

    @forelse ($shown as $c)
      @php
        $st = $c->state();
        $d = $c->daysLeft();
        $devs = $byId->where('contract_id', $c->id);
      @endphp
      <details class="row {{ $st }}" @if ($open === $c->id) open @endif id="c{{ $c->id }}">
        <summary>
          <div><b>{{ $c->company }}</b><small>{{ $c->kindLabel() }}@if ($c->contract_no) · <span class="ltr">{{ $c->contract_no }}</span>@endif</small></div>
          <div><small>ينتهي</small><b class="ltr" style="font-size:13.5px">{{ $c->end_date->format('Y-m-d') }}</b></div>
          <div><span class="st {{ $st }}">{{ $st === 'off' ? 'موقوف / مُجدد' : $L[$st] }}</span>@if ($c->is_active)<small>{{ \App\Support\Maintenance::leftText($d) }}</small>@endif</div>
          <div><small>الأجهزة المرتبطة</small><b>{{ $c->assets_count }}</b></div>
          <span class="ar">▾</span>
        </summary>
        <div class="body">
          <div class="kv">
            <div><span>البداية</span>{{ $c->start_date?->format('Y-m-d') ?? '—' }}</div>
            <div><span>التنبيه قبل</span>{{ $c->alert_days }} يوم</div>
            <div><span>المسؤول · التليفون</span>{{ $c->contact ?: '—' }} @if ($c->phone)· <a href="tel:{{ $c->phone }}" class="ltr">{{ $c->phone }}</a>@endif</div>
            <div><span>الزيارات</span>{{ $c->visits ?: '—' }}</div>
            <div><span>صورة العقد</span>@if ($c->file_path)<a href="{{ route('maint.contracts.file', $c) }}">{{ $c->file_name }}</a>@else — @endif</div>
            @if ($c->previous)<div><span>تجديد لعقد</span>انتهى {{ $c->previous->end_date->format('Y-m-d') }}</div>@endif
            @if ($c->scope)<div style="grid-column:1 / -1"><span>نطاق العقد</span>{{ $c->scope }}</div>@endif
          </div>
          @if ($devs->isNotEmpty())
            <b style="font-size:13.5px">الأجهزة</b>
            <ul class="devs">
              @foreach ($devs as $a)
                <li>{{ $a->name }} · <span class="ltr">{{ $a->serial }}</span>@if ($a->location) · {{ $a->location }}@endif
                  @if ($a->due_date) · {{ $a->dueLabel() }} <span class="ltr">{{ $a->due_date->format('Y-m-d') }}</span> <span class="st {{ $a->state() }}">{{ $L[$a->state()] }}</span>@endif</li>
              @endforeach
            </ul>
            <a href="{{ route('maint.index', ['tab' => 'assets', 'ct' => $c->id]) }}" class="hint">عرض الأجهزة في قائمة الأجهزة</a>
          @endif

          @if ($manage)
            @if ($c->is_active)
              <form method="POST" action="{{ route('maint.contracts.renew', $c) }}" enctype="multipart/form-data" class="renew">
                @csrf
                <b style="font-size:13.5px">تجديد العقد</b>
                <div class="mf" style="margin-top:6px">
                  <label>بداية الجديد <input type="date" name="start_date" value="{{ $c->end_date->copy()->addDay()->format('Y-m-d') }}"></label>
                  <label>انتهاء الجديد <input type="date" name="end_date" required value="{{ $c->end_date->copy()->addYear()->format('Y-m-d') }}"></label>
                  <label>رقم العقد <input type="text" name="contract_no" maxlength="60" value="{{ $c->contract_no }}"></label>
                  <label>صورة العقد الجديد <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"></label>
                </div>
                <div class="actions" style="margin-top:8px"><button type="submit" class="btn small">تجديد ونقل الأجهزة</button></div>
              </form>
            @endif

            <details style="margin-top:12px">
              <summary class="hint" style="cursor:pointer;font-weight:700">تعديل بيانات العقد ▾</summary>
              <form method="POST" action="{{ route('maint.contracts.update', $c) }}" enctype="multipart/form-data" style="margin-top:8px">
                @csrf
                @method('PUT')
                @include('maintenance._contract', ['c' => $c])
                <div class="actions" style="margin-top:10px">
                  <button type="submit" class="btn small">حفظ</button>
                  <button type="submit" form="tg{{ $c->id }}" class="btn ghost small">{{ $c->is_active ? 'إيقاف العقد' : 'إعادة تفعيل' }}</button>
                </div>
              </form>
            </details>
            <form method="POST" action="{{ route('maint.contracts.toggle', $c) }}" id="tg{{ $c->id }}" hidden>@csrf</form>
          @endif
        </div>
      </details>
    @empty
      <div class="empty">{{ $contracts->isEmpty() ? 'لا توجد عقود صيانة بعد. أضف عقود الطفايات وصيانة الكمبيوتر ومعايرة الترمومترات وغيرها.' : 'لا توجد عقود بهذه الحالة.' }}</div>
    @endforelse
  @endif

  {{-- ===================== سجل الجرد ===================== --}}
  @if ($tab === 'counts')
    @forelse ($counts as $cn)
      <details class="row {{ $cn->missing ? 'expired' : ($cn->damaged ? 'soon' : 'ok') }}">
        <summary>
          <div><b class="ltr" style="text-align:right">{{ $cn->date->format('Y-m-d') }}</b><small>{{ $cn->counter?->displayName() }}</small></div>
          <div><small>موجود</small><b>{{ $cn->found }}</b></div>
          <div><small>غير موجود</small><b style="{{ $cn->missing ? 'color:var(--bad)' : '' }}">{{ $cn->missing }}</b></div>
          <div><small>تالف</small><b>{{ $cn->damaged }}</b></div>
          <span class="ar">▾</span>
        </summary>
        <div class="body">
          @php
            $issues = collect($cn->results)->filter(fn ($r) => ($r['r'] ?? '') !== 'ok' || isset($r['loc']));
          @endphp
          @if ($issues->isEmpty())
            <p class="hint" style="margin:0">كل الأجهزة المجرودة موجودة في أماكنها.</p>
          @else
            <ul class="devs">
              @foreach ($issues as $id => $r)
                @php
                  $a = $byId->get((int) $id);
                @endphp
                <li>
                  <span class="st {{ $r['r'] }}">{{ ['ok' => 'موجود', 'missing' => 'غير موجود', 'damaged' => 'تالف'][$r['r']] ?? '' }}</span>
                  {{ $a?->name ?? 'جهاز محذوف' }} · <span class="ltr">{{ $a?->serial }}</span>
                  @if (isset($r['loc'])) · نُقل من «{{ $r['from'] ?: 'بدون موقع' }}» إلى «{{ $r['loc'] }}»@endif
                </li>
              @endforeach
            </ul>
          @endif
          @if ($cn->notes)<p class="hint" style="margin:8px 0 0">ملاحظات: {{ $cn->notes }}</p>@endif
        </div>
      </details>
    @empty
      <div class="empty">لم يُسجل جرد للأجهزة بعد. اضغط «جرد الأجهزة» وامشِ على المواقع من الموبايل.</div>
    @endforelse
  @endif
</div>
@endsection