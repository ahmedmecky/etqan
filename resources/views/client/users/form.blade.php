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

      <h3 style="font-size:15px;color:var(--brand);margin:18px 0 8px;border-top:1px solid var(--line);padding-top:14px">الوصف الوظيفي</h3>
      <div class="grid">
        <div class="field">
          <label class="f" for="job_code">الوظيفة (من مكتبة الأوصاف الوظيفية)</label>
          <select id="job_code" name="job_code">
            <option value="">— لم تُحدد —</option>
            @foreach ($jobGroups as $dept => $list)
              <optgroup label="{{ $dept }}">
                @foreach ($list as $code => $title)
                  <option value="{{ $code }}" {{ old('job_code', $user->job_code) === $code ? 'selected' : '' }}>{{ $title }} · {{ $code }}</option>
                @endforeach
              </optgroup>
            @endforeach
          </select>
        </div>
        <div class="field">
          <label class="f" for="hire_date">تاريخ التعيين</label>
          <input type="date" id="hire_date" name="hire_date" class="ltr" value="{{ old('hire_date', $user->hire_date?->format('Y-m-d')) }}">
        </div>
      </div>
      <details class="field" {{ count(old('extra_jobs', $user->extra_jobs ?? [])) ? 'open' : '' }}>
        <summary style="cursor:pointer;font-weight:600">وظائف إضافية يشغلها نفس الموظف (للمخازن الصغيرة)</summary>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:4px 14px;margin-top:8px;font-size:13.5px">
          @foreach ($jobGroups as $dept => $list)
            @foreach ($list as $code => $title)
              <label class="chk" style="margin:0"><input type="checkbox" name="extra_jobs[]" value="{{ $code }}" {{ in_array($code, old('extra_jobs', $user->extra_jobs ?? []), true) ? 'checked' : '' }}> {{ $title }}</label>
            @endforeach
          @endforeach
        </div>
      </details>
      @if ($user->exists && $user->job_code)
        <p class="page-sub"><a href="{{ route('jobs.user', $user) }}" target="_blank">طباعة الوصف الوظيفي لهذا الموظف ←</a>
          @if ($user->jd_ack_at) · <span style="color:var(--ok)">وقّع بالاستلام {{ $user->jd_ack_at->format('Y-m-d') }}</span>@endif</p>
      @endif

      <details class="field" style="border-top:1px solid var(--line);padding-top:12px">
        <summary style="cursor:pointer;font-weight:700;color:var(--brand)">استثناءات الصلاحيات لهذا الشخص (اختياري)</summary>
        <p class="page-sub" style="margin:6px 0">اترك «حسب الدور» إلا إذا احتاج هذا الشخص صلاحية مختلفة عن زملائه في نفس الدور (مثل: أمين مخزن مفوض بالتفتيش). صلاحيات الأدوار: <a href="{{ route('client.permissions.index') }}" target="_blank">الصلاحيات</a>.</p>
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(250px,1fr));gap:6px 16px;font-size:13.5px">
          @foreach (\App\Support\Access::MODULES as $mk => $m)
            @php
              $ov = $user->exists ? \App\Support\Access::userOverride($user, $mk) : null;
            @endphp
            <label style="display:flex;justify-content:space-between;align-items:center;gap:8px">{{ $m[0] }}
              <select name="perm[{{ $mk }}]" style="width:auto">
                <option value="">حسب الدور</option>
                @foreach (\App\Support\Access::LEVELS as $lv => $lt)<option value="{{ $lv }}" {{ old('perm.' . $mk, $ov) === $lv ? 'selected' : '' }}>{{ $lt }}</option>@endforeach
              </select>
            </label>
          @endforeach
        </div>
      </details>

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

@push('scripts')
<script>
  // اقتراح الوظيفة حسب الدور إذا لم تُحدد
  (function () {
    const map = @json($roleJobs);
    const role = document.getElementById('role_id'), job = document.getElementById('job_code');
    if (!role || !job) return;
    role.addEventListener('change', () => { if (!job.value && map[role.value]) job.value = map[role.value]; });
  })();
</script>
@endpush