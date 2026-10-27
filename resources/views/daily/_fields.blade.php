{{-- حقول المهمة اليومية (إضافة · تعديل) --}}
@php
  $sel = $t?->roles ?? [];
  $dd = str_split((string) ($t?->days ?? '012346'));
@endphp
<div class="tf">
  <div class="full"><label class="f req">المهمة</label><input type="text" name="title" value="{{ $t?->title }}" maxlength="160" required placeholder="مثال: نظافة منطقة الاستلام وتسجيلها"></div>
  <div><label class="f">الإجراء المرتبط</label><input type="text" name="sop" value="{{ $t?->sop }}" maxlength="20" class="ltr-in" placeholder="SH-003"></div>
  <div><label class="f req">موعد الإنجاز</label><input type="time" name="due_time" value="{{ $t ? $t->due() : '11:00' }}" required style="direction:ltr"></div>
  <div><label class="f">الترتيب</label><input type="number" name="sort" value="{{ $t?->sort ?? 0 }}" min="0" max="999"></div>
  <div class="full"><label class="f">المسؤولون عن التنفيذ <span style="font-weight:400">(بدون اختيار = مدير الجودة والمدير الصيدلي)</span></label>
    <div class="roles">@foreach ($roles as $r)<label><input type="checkbox" name="roles[]" value="{{ $r->key }}" {{ in_array($r->key, $sel, true) ? 'checked' : '' }}> {{ $r->name_ar }}</label>@endforeach</div></div>
  <div class="full"><label class="f req">أيام العمل</label>
    <div class="days">@foreach ($days as $k => $dn)<label><input type="checkbox" name="days[]" value="{{ $k }}" {{ in_array((string) $k, $dd, true) ? 'checked' : '' }}> {{ $dn }}</label>@endforeach</div></div>
</div>