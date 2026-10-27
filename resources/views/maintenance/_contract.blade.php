{{-- حقول عقد الصيانة: $c (أو null للجديد) --}}
<div class="mf">
  <label>نوع العقد
    <select name="kind" required>
      @foreach (\App\Support\Maintenance::KINDS as $k => $lbl)
        <option value="{{ $k }}" @selected(old('kind', $c?->kind) === $k)>{{ $lbl }}</option>
      @endforeach
    </select>
  </label>
  <label>الشركة <input type="text" name="company" maxlength="160" required value="{{ old('company', $c?->company) }}" placeholder="اسم شركة الصيانة"></label>
  <label>رقم العقد <input type="text" name="contract_no" maxlength="60" value="{{ old('contract_no', $c?->contract_no) }}"></label>
  <label>المسؤول بالشركة <input type="text" name="contact" maxlength="120" value="{{ old('contact', $c?->contact) }}"></label>
  <label>التليفون <input type="text" name="phone" maxlength="40" class="ltr" value="{{ old('phone', $c?->phone) }}"></label>
  <label>الزيارات <input type="text" name="visits" maxlength="120" value="{{ old('visits', $c?->visits) }}" placeholder="مثال: زيارة كل ٣ شهور"></label>
  <label>بداية العقد <input type="date" name="start_date" value="{{ old('start_date', $c?->start_date?->format('Y-m-d')) }}"></label>
  <label>انتهاء العقد <input type="date" name="end_date" required value="{{ old('end_date', $c?->end_date?->format('Y-m-d')) }}"></label>
  <label>التنبيه قبل (يوم) <input type="number" name="alert_days" min="1" max="180" required value="{{ old('alert_days', $c?->alert_days ?? 30) }}"></label>
  <label class="full">نطاق العقد <textarea name="scope" rows="2" maxlength="2000" placeholder="ما يشمله العقد: الأجهزة، قطع الغيار، الاستجابة للأعطال…">{{ old('scope', $c?->scope) }}</textarea></label>
  <label class="full">صورة العقد (PDF أو صورة · اختياري) <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"></label>
</div>