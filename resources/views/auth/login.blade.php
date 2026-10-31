<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $admin ? 'دخول إدارة النظام' : 'تسجيل الدخول' }} · إتقان</title>
@if ($admin)<meta name="robots" content="noindex, nofollow">@endif
<link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 110'%3E%3Cpath d='M50 4 L90 18 V52 C90 78 72 96 50 106 C28 96 10 78 10 52 V18 Z' fill='%230f5e5a'/%3E%3Cpath d='M34 24 H66 V34 H44 V41 H60 V50 H44 V58 H66 V68 H34 Z' fill='white'/%3E%3Cpolyline points='43,79 48,84 57,75' fill='none' stroke='%23e0b24a' stroke-width='6' stroke-linecap='round' stroke-linejoin='round'/%3E%3C/svg%3E">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700&family=Reem+Kufi:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ asset('css/etqan.css') }}">
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-brand">
      <svg width="64" height="70" viewBox="0 0 100 110" aria-hidden="true">
        <path d="M50 4 L90 18 V52 C90 78 72 96 50 106 C28 96 10 78 10 52 V18 Z" fill="#0f5e5a"></path>
        <path d="M50 14 L81 25 V52 C81 72 67 86 50 95 C33 86 19 72 19 52 V25 Z" fill="none" stroke="#c08a1e" stroke-width="1.6"></path>
        <path d="M34 24 H66 V34 H44 V41 H60 V50 H44 V58 H66 V68 H34 Z" fill="#ffffff"></path>
        <polyline points="43,79 48,84 57,75" fill="none" stroke="#e0b24a" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"></polyline>
      </svg>
      <div class="ar">إتقان</div>
      <div class="en">ETQAN</div>
      <div class="sl">إتقان في كل خطوة</div>
    </div>

    <h1>{{ $admin ? 'دخول إدارة النظام' : 'تسجيل الدخول' }}</h1>
    @if ($admin)
      <p style="text-align:center;font-size:13px;color:#8a5a00;margin:-6px 0 12px">لمدير النظام فقط · العملاء يدخلون من صفحة الدخول المعتادة</p>
    @endif

    @if (session('status'))
      <div class="alert ok">{{ session('status') }}</div>
    @endif

    <form method="POST" action="{{ $admin ? route('admin.login.attempt') : route('login.attempt') }}" novalidate>
      @csrf

      <div class="field">
        <label class="f req" for="email">البريد الإلكتروني</label>
        <input type="email" id="email" name="email" class="ltr" value="{{ old('email') }}" autocomplete="username" autofocus required>
        @error('email') <div class="err">{{ $message }}</div> @enderror
      </div>

      <div class="field">
        <label class="f req" for="password">كلمة المرور</label>
        <input type="password" id="password" name="password" class="ltr" autocomplete="current-password" required>
        @error('password') <div class="err">{{ $message }}</div> @enderror
      </div>

      <div class="field">
        <label class="chk"><input type="checkbox" name="remember" value="1" {{ old('remember') ? 'checked' : '' }}> تذكرني على هذا الجهاز</label>
      </div>

      <button type="submit" class="btn block">دخول</button>
    </form>
  </div>
</div>
<footer class="foot">إتقان · نظام إدارة الجودة لتوزيع الأدوية</footer>
</body>
</html>