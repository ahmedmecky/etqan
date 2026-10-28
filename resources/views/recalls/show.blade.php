@extends('layouts.app')

@section('title', $r->number . ' · سحب ' . $r->product)

@push('head')
  @include('recalls._style')
@endpush

@section('content')
<div class="rc">
  <div class="head-actions">
    <div>
      <p style="margin:0 0 4px"><a href="{{ route('recalls.index') }}">← سجل السحب</a></p>
      <h1 class="page-title" style="margin-bottom:2px">{{ $r->product }} <span class="ltr" style="font-size:15px;color:var(--muted)">{{ $r->number }}</span></h1>
      <p class="page-sub" style="margin:0">تشغيلة <b class="ltr">{{ $r->batch }}</b>@if ($r->expiry) · انتهاء <span class="ltr">{{ $r->expiry->format('Y-m-d') }}</span>@endif
        · <span class="st {{ $r->class === '1' ? 'c1' : '' }}">{{ $r->classLabel() }}</span>
        · {{ $r->isOpen() ? 'مفتوح' : 'مغلق ' . $r->closed_at?->timezone('Africa/Cairo')->format('Y-m-d') }}</p>
    </div>
    <div class="actions">
      <a href="{{ route('recalls.declarations', $r) }}" target="_blank" class="btn">طباعة الإقرارات ({{ $s['customers'] - $s['declared'] }})</a>
      <a href="{{ route('recalls.declarations', [$r, 'all' => 1]) }}" target="_blank" class="btn ghost small">كل الإقرارات</a>
      <a href="{{ route('recalls.declarations', [$r, 'blank' => 1]) }}" target="_blank" class="btn ghost small">إقرار فارغ</a>
      <a href="{{ route('recalls.report', $r) }}" target="_blank" class="btn ghost small">طباعة المتابعة والتسوية</a>
    </div>
  </div>

  <div class="chips">
    <div><b>{{ $s['customers'] }}</b>عميل</div>
    <div class="{{ $s['late'] ? 'bad' : '' }}"><b>{{ $s['notified'] }}</b>تم إبلاغه{{ $r->deadline ? ' · ' . $s['inTime'] . ' في الموعد' : '' }}</div>
    <div class="{{ $s['declared'] === $s['customers'] && $s['customers'] ? 'ok' : '' }}"><b>{{ $s['declared'] }}</b>إقرار وصل ({{ $s['photos'] }} بصورة)</div>
    <div><b>{{ $s['none'] }}</b>لا يوجد لديه</div>
    <div><b>{{ $s['returned'] }}@if ($s['sold']) / {{ $s['sold'] }}@endif</b>كمية مسترجعة / مبيعة</div>
    @if ($s['success'] !== null)<div class="{{ $s['success'] >= 100 ? 'ok' : '' }}"><b>{{ $s['success'] }}٪</b>نسبة نجاح السحب</div>@endif
  </div>

  @if ($s['late'])
    <div class="alert bad">تجاوز موعد إبلاغ العملاء ({{ $r->deadline->format('Y-m-d') }}) وما زال {{ $s['customers'] - $s['notified'] }} عميل لم يُبلغ. أبلغ المدير الصيدلي بأي عميل لم يتم الوصول إليه.</div>
  @endif

  {{-- ===================== بيانات السحب ===================== --}}
  <details class="new">
    <summary>بيانات المنشور والسحب ▾</summary>
    <div class="body">
      <div class="kv" style="margin-bottom:10px">
        <div><span>المصدر</span>{{ $r->sourceLabel() }}</div>
        <div><span>رقم المنشور</span>{{ $r->circular_no ?: '—' }}</div>
        <div><span>تاريخ الاستلام</span>{{ $r->received_on->format('Y-m-d') }}</div>
        <div><span>آخر موعد للإبلاغ</span>{{ $r->deadline?->format('Y-m-d') ?? '—' }}</div>
        <div><span>فتحه</span>{{ $r->creator?->displayName() }}</div>
        @if ($r->reason)<div style="grid-column:1 / -1"><span>السبب</span>{{ $r->reason }}</div>@endif
      </div>
      @if ($manage && $r->isOpen())
        <form method="POST" action="{{ route('recalls.update', $r) }}">
          @csrf
          @method('PUT')
          @include('recalls._fields', ['r' => $r])
          <div class="actions" style="margin-top:10px"><button type="submit" class="btn small">حفظ</button></div>
        </form>
      @endif
    </div>
  </details>

  {{-- ===================== إضافة العملاء ===================== --}}
  @if ($r->isOpen())
    <details class="new" {{ $s['customers'] === 0 ? 'open' : '' }}>
      <summary>+ إضافة العملاء الذين اشتروا التشغيلة</summary>
      <div class="body">
        <form method="POST" action="{{ route('recalls.customers.add', $r) }}">
          @csrf
          <textarea name="lines" rows="6" style="width:100%" class="ltr" placeholder="P-1023, 4&#10;P-0871, 2&#10;صيدلية الشفاء, 1"></textarea>
          <p class="hint" style="margin:6px 0 8px">سطر لكل عميل: <b>كود الصيدلية، الكمية المبيعة</b> (ويمكن إضافة التليفون بعدهما). يمكن النسخ من Excel مباشرة (عمودان).
            الكود يُكمل الاسم والعنوان من <b>قائمة الصيدليات</b> ({{ $pharmacies->count() }} صيدلية). العميل غير المسجل: اكتب اسمه بدل الكود.</p>
          <button type="submit" class="btn small">إضافة</button>
        </form>
      </div>
    </details>
  @endif

  {{-- ===================== العملاء ===================== --}}
  <h2 style="font-size:16px;margin:16px 0 8px">العملاء ({{ $s['customers'] }})</h2>
  @forelse ($r->customers as $c)
    @php
      $late = $r->isOpen() && ! $c->notified_at && $r->deadline && $r->deadline->format('Y-m-d') < now('Africa/Cairo')->toDateString();
    @endphp
    <details class="row {{ $late ? 'late' : $c->declaration }}" id="c{{ $c->id }}">
      <summary>
        <div><b>{{ $c->name }}</b><small>@if ($c->code)<span class="ltr">{{ $c->code }}</span> · @endif{{ $c->address }}</small></div>
        <div><small>المبيع</small><b>{{ $c->qty_sold ?? '—' }}</b></div>
        <div>
          @if ($c->notified_at)
            <span class="st ok">أُبلغ</span><small>{{ $c->notified_at->timezone('Africa/Cairo')->format('m-d H:i') }} · {{ $c->methodLabel() }}</small>
          @else
            <span class="st {{ $late ? 'bad' : 'warn' }}">لم يُبلغ</span>
          @endif
        </div>
        <div>
          <span class="st {{ $c->declaration === 'pending' ? 'warn' : 'ok' }}">{{ $c->declarationLabel() }}</span>
          @if ($c->declaration === 'has')<small>استُرجع {{ $c->qty_returned }}@if ($c->returned_on) في {{ $c->returned_on->format('m-d') }}@endif</small>@endif
          @if ($c->file_path)<small>📎 صورة الإقرار</small>@endif
        </div>
        <span class="ar">▾</span>
      </summary>
      <div class="body">
        <div class="quick">
          @if (! $c->notified_at && $r->isOpen())
            <span class="hint">تسجيل الإبلاغ الآن:</span>
            @foreach (\App\Support\Recalls::METHODS as $k => $lbl)
              <form method="POST" action="{{ route('recalls.customers.notified', [$r, $c]) }}" style="margin:0">
                @csrf
                <input type="hidden" name="method" value="{{ $k }}">
                <button type="submit" class="btn ghost small">{{ $lbl }}</button>
              </form>
            @endforeach
          @endif
          <a href="{{ route('recalls.declarations', [$r, 'c' => $c->id]) }}" target="_blank" class="btn ghost small">طباعة إقرار {{ $c->name }}</a>
          @if ($c->file_path)<a href="{{ route('recalls.customers.file', [$r, $c]) }}" target="_blank" class="btn ghost small">عرض صورة الإقرار</a>@endif
          @if ($c->phone)<a href="tel:{{ $c->phone }}" class="btn ghost small ltr">{{ $c->phone }}</a>@endif
        </div>

        <form method="POST" action="{{ route('recalls.customers.update', [$r, $c]) }}" enctype="multipart/form-data">
          @csrf
          @method('PUT')
          <label style="display:block;font-size:12.5px;font-weight:700;color:var(--muted);margin-bottom:4px">نتيجة الإقرار</label>
          <div class="dec" style="margin-bottom:10px">
            <label><input type="radio" name="declaration" value="pending" @checked($c->declaration === 'pending')>لم يصل</label>
            <label><input type="radio" name="declaration" value="none" @checked($c->declaration === 'none')>✓ لا يوجد لديه</label>
            <label><input type="radio" name="declaration" value="has" @checked($c->declaration === 'has')>↩ يوجد وتم الاسترجاع</label>
          </div>
          <div class="mf">
            <label>الكمية المسترجعة <input type="number" name="qty_returned" min="0" value="{{ $c->qty_returned }}"></label>
            <label>تاريخ الاسترجاع <input type="date" name="returned_on" value="{{ $c->returned_on?->format('Y-m-d') }}"></label>
            <label>اسم الصيدلي المُقر <input type="text" name="pharmacist" maxlength="120" value="{{ $c->pharmacist }}"></label>
            <label class="full">صورة الإقرار الموقّع والمختوم <input type="file" name="file" accept="image/*,.pdf" capture="environment"></label>
          </div>
          <details style="margin-top:10px">
            <summary class="hint" style="cursor:pointer;font-weight:700">بيانات العميل والإبلاغ ▾</summary>
            <div class="mf" style="margin-top:8px">
              <label>الاسم <input type="text" name="name" maxlength="200" required value="{{ $c->name }}"></label>
              <label>الكود <input type="text" name="code" maxlength="40" class="ltr" value="{{ $c->code }}"></label>
              <label>التليفون <input type="text" name="phone" maxlength="40" class="ltr" value="{{ $c->phone }}"></label>
              <label class="full">العنوان <input type="text" name="address" maxlength="255" value="{{ $c->address }}"></label>
              <label>الكمية المبيعة <input type="number" name="qty_sold" min="0" value="{{ $c->qty_sold }}"></label>
              <label>تاريخ الإبلاغ <input type="date" name="notified" value="{{ $c->notified_at?->timezone('Africa/Cairo')->format('Y-m-d') }}"></label>
              <label>وسيلة الإبلاغ
                <select name="method">
                  <option value="">—</option>
                  @foreach (\App\Support\Recalls::METHODS as $k => $lbl)<option value="{{ $k }}" @selected($c->method === $k)>{{ $lbl }}</option>@endforeach
                </select>
              </label>
              <label class="full">ملاحظة <input type="text" name="note" maxlength="500" value="{{ $c->note }}"></label>
            </div>
          </details>
          <div class="actions" style="margin-top:10px">
            <button type="submit" class="btn small">حفظ</button>
            @if ($manage)<button type="submit" form="dc{{ $c->id }}" class="btn ghost small">حذف العميل</button>@endif
          </div>
          @if ($c->editor)<p class="hint" style="margin:6px 0 0">آخر تعديل: {{ $c->editor->displayName() }} · {{ $c->updated_at?->timezone('Africa/Cairo')->format('Y-m-d H:i') }}</p>@endif
        </form>
        @if ($manage)
          <form method="POST" action="{{ route('recalls.customers.destroy', [$r, $c]) }}" id="dc{{ $c->id }}" hidden onsubmit="return confirm('حذف {{ $c->name }} من السحب؟')">@csrf @method('DELETE')</form>
        @endif
      </div>
    </details>
  @empty
    <div class="empty">لم يُضف عملاء بعد. استخرج من النظام كل العملاء الذين اشتروا التشغيلة بالكميات ثم الصقهم في «إضافة العملاء».</div>
  @endforelse

  {{-- ===================== التسوية والإغلاق ===================== --}}
  @if ($manage)
    <div class="box" style="margin-top:16px">
      <h2>التسوية والإغلاق <span class="ltr" style="font-size:12.5px;color:var(--muted)">SH-010-F03</span></h2>
      @if ($r->isOpen())
        <form method="POST" action="{{ route('recalls.close', $r) }}">
          @csrf
          <div class="mf">
            <label>(أ) الكمية المستلمة من التشغيلة <input type="number" name="qty_received" min="0" value="{{ old('qty_received', $r->qty_received) }}"></label>
            <label>(ج) الموجود بالمخزن والمنقول للمسحوبات <input type="number" name="qty_in_stock" min="0" value="{{ old('qty_in_stock', $r->qty_in_stock) }}"></label>
            <label>(ب) المبيع · (د) المسترجع <input type="text" disabled value="{{ $s['sold'] }} · {{ $s['returned'] }}"></label>
            <label>التصرف في المسحوب
              <select name="disposal">
                <option value="">—</option>
                @foreach (\App\Support\Recalls::DISPOSALS as $k => $lbl)<option value="{{ $k }}" @selected(old('disposal', $r->disposal) === $k)>{{ $lbl }}</option>@endforeach
              </select>
            </label>
            <label>رقم المستند / المحضر <input type="text" name="disposal_ref" maxlength="80" value="{{ old('disposal_ref', $r->disposal_ref) }}"></label>
            <label>رقم الإجراء التصحيحي (DEV) <input type="text" name="dev_no" maxlength="30" class="ltr" value="{{ old('dev_no', $r->dev_no) }}"></label>
            <label>إبلاغ هيئة الدواء بتاريخ <input type="date" name="eda_notified_on" value="{{ old('eda_notified_on', $r->eda_notified_on?->format('Y-m-d')) }}"></label>
            <label>إبلاغ المصنع / المورد بتاريخ <input type="date" name="supplier_notified_on" value="{{ old('supplier_notified_on', $r->supplier_notified_on?->format('Y-m-d')) }}"></label>
            <label class="full">ملاحظات <textarea name="notes" rows="2" maxlength="2000">{{ old('notes', $r->notes) }}</textarea></label>
          </div>
          @if ($s['received'] !== null)
            <p class="hint" style="margin:8px 0 0">(هـ) غير المسترد = أ − ج − د = <b>{{ $s['unrecovered'] }}</b> · نسبة نجاح السحب = <b>{{ $s['success'] ?? '—' }}٪</b> · الإبلاغ في الموعد = <b>{{ $s['notifiedPct'] ?? '—' }}٪</b></p>
          @endif
          @if ($s['declared'] < $s['customers'])
            <p class="hint" style="margin:6px 0 0;color:#8a5a00">لم يصل إقرار {{ $s['customers'] - $s['declared'] }} عميل. يُفضل استكمال الإقرارات قبل الإغلاق.</p>
          @endif
          <div class="actions" style="margin-top:10px">
            <button type="submit" name="action" value="save" class="btn ghost small">حفظ التسوية</button>
            <button type="submit" name="action" value="close" class="btn small">حفظ وإغلاق السحب</button>
          </div>
        </form>
      @else
        <div class="kv">
          <div><span>(أ) المستلم</span>{{ $r->qty_received }}</div>
          <div><span>(ج) بالمخزن</span>{{ $r->qty_in_stock }}</div>
          <div><span>(د) المسترجع</span>{{ $s['returned'] }}</div>
          <div><span>(هـ) غير المسترد</span>{{ $s['unrecovered'] }}</div>
          <div><span>نسبة النجاح</span>{{ $s['success'] ?? '—' }}٪</div>
          <div><span>التصرف</span>{{ \App\Support\Recalls::DISPOSALS[$r->disposal] ?? '—' }} {{ $r->disposal_ref }}</div>
          <div><span>أغلقه</span>{{ $r->closer?->displayName() }} · {{ $r->closed_at?->timezone('Africa/Cairo')->format('Y-m-d') }}</div>
        </div>
        <form method="POST" action="{{ route('recalls.reopen', $r) }}" style="margin-top:10px">@csrf<button type="submit" class="btn ghost small">إعادة فتح</button></form>
      @endif
    </div>
  @endif
</div>
@endsection