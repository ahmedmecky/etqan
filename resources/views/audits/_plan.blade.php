{{-- بيانات خطة المراجعة: $a أو null --}}
<div class="mf">
  <label>نوع المراجعة
    <select name="type" required>
      @foreach (\App\Support\Audits::TYPES as $k => $lbl)<option value="{{ $k }}" @selected(old('type', $a?->type ?? 'external') === $k)>{{ $lbl }}</option>@endforeach
    </select>
  </label>
  <label class="two">العنوان <input type="text" name="title" maxlength="200" required value="{{ old('title', $a?->title) }}" placeholder="مثال: مراجعة GDP السنوية لكل أنشطة المخزن"></label>
  <label>الجهة / المكتب الاستشاري <input type="text" name="company" maxlength="160" value="{{ old('company', $a?->company) }}"></label>
  <label>رئيس فريق المراجعة <input type="text" name="lead" maxlength="120" value="{{ old('lead', $a?->lead) }}"></label>
  <label>باقي الفريق <input type="text" name="team" maxlength="255" value="{{ old('team', $a?->team) }}"></label>
  <label>من <input type="date" name="start_date" required value="{{ old('start_date', $a?->start_date?->format('Y-m-d') ?? now('Africa/Cairo')->toDateString()) }}"></label>
  <label>إلى <input type="date" name="end_date" required value="{{ old('end_date', $a?->end_date?->format('Y-m-d') ?? now('Africa/Cairo')->toDateString()) }}"></label>
  <label>تقرير الجهة (PDF · اختياري) <input type="file" name="file" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png"></label>
  <label class="full">النطاق <textarea name="scope" rows="2" maxlength="4000" placeholder="الأنشطة والمواقع والفترة التي تشملها المراجعة">{{ old('scope', $a?->scope) }}</textarea></label>
  <label class="full">المعايير <textarea name="criteria" rows="2" maxlength="2000">{{ old('criteria', $a?->criteria ?? 'ممارسات التخزين والتوزيع الجيد الصادرة عن هيئة الدواء المصرية · إجراءات التشغيل القياسية المعتمدة بالمخزن (SH-001 … SH-034)') }}</textarea></label>
</div>