@extends('layouts.app')

@section('title', 'تغيير كلمة المرور')

@section('content')
  <h1 class="page-title">تغيير كلمة المرور</h1>
  <p class="page-sub">كلمة المرور الجديدة: ٨ أحرف على الأقل، وتحتوي على حروف وأرقام.</p>

  <div class="card" style="max-width:520px">
    <form method="POST" action="{{ route('password.update') }}" novalidate>
      @csrf
      @method('PUT')

      <div class="field">
        <label class="f req" for="current_password">كلمة المرور الحالية</label>
        <input type="password" id="current_password" name="current_password" class="ltr" autocomplete="current-password" required>
      </div>

      <div class="field">
        <label class="f req" for="password">كلمة المرور الجديدة</label>
        <input type="password" id="password" name="password" class="ltr" autocomplete="new-password" required>
      </div>

      <div class="field">
        <label class="f req" for="password_confirmation">تأكيد كلمة المرور الجديدة</label>
        <input type="password" id="password_confirmation" name="password_confirmation" class="ltr" autocomplete="new-password" required>
      </div>

      <div class="actions">
        <button type="submit" class="btn">حفظ كلمة المرور</button>
        <a href="{{ route('home') }}" class="btn ghost">إلغاء</a>
      </div>
    </form>
  </div>
@endsection