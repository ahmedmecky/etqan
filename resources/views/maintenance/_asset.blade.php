{{-- حقول الجهاز: $a (أو null للجديد) · $contracts · $locations --}}
<div class="mf">
  <label>الفئة
    <select name="category" required>
      @foreach (\App\Support\Maintenance::CATEGORIES as $k => $cat)
        <option value="{{ $k }}" @selected(old('category', $a?->category) === $k)>{{ $cat['label'] }}</option>
      @endforeach
    </select>
  </label>
  <label>اسم الجهاز <input type="text" name="name" maxlength="160" required value="{{ old('name', $a?->name) }}" placeholder="مثال: طفاية بودرة ٦ كجم"></label>
  <label>الرقم التسلسلي (Serial) <input type="text" name="serial" maxlength="80" required class="ltr" value="{{ old('serial', $a?->serial) }}"></label>
  <label>الكود الداخلي <input type="text" name="tag_no" maxlength="40" class="ltr" value="{{ old('tag_no', $a?->tag_no) }}" placeholder="رقم الملصق"></label>
  <label>الماركة <input type="text" name="brand" maxlength="80" value="{{ old('brand', $a?->brand) }}"></label>
  <label>الموديل <input type="text" name="model" maxlength="80" value="{{ old('model', $a?->model) }}"></label>
  <label>الموقع <input type="text" name="location" maxlength="120" list="locList" value="{{ old('location', $a?->location) }}" placeholder="مثال: مخزن الجملة · الثلاجة ٢"></label>
  <label>عقد الصيانة
    <select name="contract_id">
      <option value="">بدون عقد</option>
      @foreach ($contracts->where('is_active', true) as $c)
        <option value="{{ $c->id }}" @selected((string) old('contract_id', $a?->contract_id) === (string) $c->id)>{{ $c->company }} · {{ $c->kindLabel() }}</option>
      @endforeach
    </select>
  </label>
  <label>الحالة
    <select name="status" required>
      @foreach (\App\Support\Maintenance::STATUSES as $k => $lbl)
        <option value="{{ $k }}" @selected(old('status', $a?->status ?? 'active') === $k)>{{ $lbl }}</option>
      @endforeach
    </select>
  </label>
  <label><span class="dlbl">{{ $a ? $a->dueLabel() : 'انتهاء الطفاية / المعايرة / الصيانة القادمة' }}</span> <input type="date" name="due_date" value="{{ old('due_date', $a?->due_date?->format('Y-m-d')) }}"></label>
  <label>آخر صيانة / معايرة <input type="date" name="last_service" value="{{ old('last_service', $a?->last_service?->format('Y-m-d')) }}"></label>
  <label class="full">ملاحظات <input type="text" name="notes" maxlength="1000" value="{{ old('notes', $a?->notes) }}"></label>
</div>