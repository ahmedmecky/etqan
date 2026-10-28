{{-- بيانات السحب (F01): $r أو null --}}
<div class="mf">
  <label>الصنف <input type="text" name="product" maxlength="200" required value="{{ old('product', $r?->product) }}" placeholder="الاسم التجاري والتركيز والشكل"></label>
  <label>رقم التشغيلة <input type="text" name="batch" maxlength="80" required class="ltr" value="{{ old('batch', $r?->batch) }}"></label>
  <label>تاريخ الانتهاء <input type="date" name="expiry" value="{{ old('expiry', $r?->expiry?->format('Y-m-d')) }}"></label>
  <label>مصدر المنشور
    <select name="source" required>
      @foreach (\App\Support\Recalls::SOURCES as $k => $lbl)<option value="{{ $k }}" @selected(old('source', $r?->source ?? 'eda') === $k)>{{ $lbl }}</option>@endforeach
    </select>
  </label>
  <label>رقم المنشور <input type="text" name="circular_no" maxlength="80" value="{{ old('circular_no', $r?->circular_no) }}"></label>
  <label>تاريخ الاستلام <input type="date" name="received_on" required value="{{ old('received_on', $r?->received_on?->format('Y-m-d') ?? now('Africa/Cairo')->toDateString()) }}"></label>
  <label>درجة الاستدعاء
    <select name="class" required>
      @foreach (\App\Support\Recalls::CLASSES as $k => $lbl)<option value="{{ $k }}" @selected((string) old('class', $r?->class ?? '2') === (string) $k)>{{ $lbl }}</option>@endforeach
    </select>
  </label>
  <label>آخر موعد لإبلاغ العملاء <input type="date" name="deadline" value="{{ old('deadline', $r?->deadline?->format('Y-m-d')) }}"></label>
  <label class="full">سبب السحب <textarea name="reason" rows="2" maxlength="2000" placeholder="كما ورد في المنشور">{{ old('reason', $r?->reason) }}</textarea></label>
</div>