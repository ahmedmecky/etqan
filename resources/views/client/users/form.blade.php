@extends('layouts.app')

@section('title', $user->exists ? 'تعديل مستخدم' : 'مستخدم جديد')

@section('content')
  <div class="actions" style="justify-content:space-between;margin-bottom:14px">
    <div>
      <h1 class="page-title">{{ $user->exists ? 'تعديل المستخدم: ' . $user->displayName() : 'مستخدم جديد' }}</h1>
      <p class="page-sub" style="margin:0">الدور الوظيفي يحدد الشاشات التي يراها المستخدم وصلاحياته في الاعتماد.</p>
    </div>
    <a href="{{ route('client.users.index') }}" class="btn ghost">← العودة إلى المستخدمين</a>
  </div>

  <div class="card" style="max-width:900px">
    <form method="POST"
          action="{{ $user->exists ? route('client.users.update', $user) : route('client.users.store') }}"
          novalidate>
      @csrf
      @if ($user->exists) @method('PUT') @endif

      <div class="grid">
        <div class="field">
          <label class="f req" for="name">الاسم (إنجليزي)</label>
          <input type="text" id="name" name="name" class="ltr" value="{{ old('name', $user->name) }}">
        </div>
        <div class="field">
          <label class="f" for="name_ar">الاسم بالعربية</label>
          <input type="text" id="name_ar" name="name_ar" value="{{ old('name_ar', $user->name_ar) }}">
        </div>
        <div class="field">
          <label class="f req" for="email">البريد الإلكتروني (اسم الدخول)</label>
          <input type="email" id="email" name="email" class="ltr" value="{{ old('email', $user->email) }}">
        </div>
        <div class="field">
          <label class="f" for="phone">الهاتف</label>
          <input type="tel" id="phone" name="phone" class="ltr" value="{{ old('phone', $user->phone) }}">
        </div>
        <div class="field">
          <label class="f req" for="role_id">الدور الوظيفي</label>
          <select id="role_id" name="role_id">
            <option value="">— اختر —</option>
            @foreach ($roles as $r)
              <option value="{{ $r->id }}" {{ (string) old('role_id', $user->role_id) === (string) $r->id ? 'selected' : '' }}>{{ $r->name_ar }}</option>
            @endforeach
          </select>
        </div>
        <div class="field">
          <label class="f {{ $user->exists ? '' : 'req' }}" for="password">
            {{ $user->exists ? 'كلمة مرور جديدة (اتركها فارغة بدون تغيير)' : 'كلمة مرور مبدئية' }}
          </label>
          <input type="text" id="password" name="password" class="ltr" autocomplete="off">
        </div>
      </div>

      <div class="field">
        <input type="hidden" name="is_active" value="0">
        <label class="chk"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }}> الحساب فعال (يمكنه الدخول)</label>
      </div>

      <p class="page-sub">كلمة المرور: ٨ أحرف على الأقل، وتحتوي على حروف إنجليزية وأرقام. اطلب من المستخدم تغييرها بعد أول دخول.</p>

      <div class="actions">
        <button type="submit" class="btn">{{ $user->exists ? 'حفظ التعديلات' : 'إنشاء المستخدم' }}</button>
        <a href="{{ route('client.users.index') }}" class="btn ghost">إلغاء</a>
      </div>
    </form>
  </div>
@endsection