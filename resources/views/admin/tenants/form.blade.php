@extends('layouts.app')

@section('title', $tenant->exists ? 'تعديل عميل' : 'عميل جديد')

@push('head')
<style>
  .logo-box{display:flex;gap:16px;align-items:center;flex-wrap:wrap}
  .logo-prev{width:160px;height:100px;border:1px dashed var(--line);border-radius:10px;display:flex;align-items:center;justify-content:center;background:#fafbfb;overflow:hidden}
  .logo-prev img{max-width:100%;max-height:100%;object-fit:contain}
  .logo-prev span{font-size:12px;color:var(--muted)}
  .sec-h{display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap}
  details.edit-admin{margin-top:6px}
  details.edit-admin summary{cursor:pointer;color:var(--brand);font-weight:600;font-size:13px}
  details.edit-admin .inner{border:1px solid var(--line);border-radius:10px;padding:12px;margin-top:8px;background:#fbfcfc}
  .bag{background:var(--bad-soft);color:var(--bad);border-radius:8px;padding:8px 12px;font-size:13px;margin-bottom:10px}
  .bag ul{margin:4px 0 0;padding-inline-start:18px}
  .tags{display:flex;flex-wrap:wrap;gap:6px;align-items:center;border:1px solid var(--line);border-radius:8px;padding:6px 8px;background:#fff;min-height:42px;cursor:text}
  .tags:focus-within{border-color:var(--brand);box-shadow:0 0 0 3px rgba(15,94,90,.12)}
  .tag{display:inline-flex;align-items:center;gap:4px;background:var(--brand-soft, #e3f0ee);color:var(--brand);border-radius:99px;padding:3px 6px 3px 10px;font-size:13px;font-weight:600}
  .tag button{border:0;background:none;color:inherit;cursor:pointer;font-size:15px;line-height:1;padding:0 3px;opacity:.7}
  .tag button:hover{opacity:1}
  .tags input{border:0 !important;outline:0;box-shadow:none !important;flex:1;min-width:160px;padding:4px !important;background:none}
  .hint{font-size:12px;color:var(--muted);margin-top:4px}
</style>
@endpush

@section('content')
  <div class="actions" style="justify-content:space-between;margin-bottom:14px">
    <div>
      <h1 class="page-title">{{ $tenant->exists ? 'تعديل العميل: ' . $tenant->name_ar : 'عميل جديد' }}</h1>
      <p class="page-sub" style="margin:0">بيانات الشركة أو المخزن كما تظهر أعلى كل إجراء ونموذج ومؤشر أداء.</p>
    </div>
    <a href="{{ route('admin.tenants.index') }}" class="btn ghost">← العودة إلى العملاء</a>
  </div>

  {{-- بيانات العميل --}}
  <div class="card">
    <h2>بيانات العميل</h2>
    <form method="POST"
          action="{{ $tenant->exists ? route('admin.tenants.update', $tenant) : route('admin.tenants.store') }}"
          enctype="multipart/form-data" novalidate>
      @csrf
      @if ($tenant->exists) @method('PUT') @endif

      <div class="grid">
        <div class="field">
          <label class="f req" for="code">كود العميل</label>
          <input type="text" id="code" name="code" class="ltr" maxlength="10" style="text-transform:uppercase"
                 value="{{ old('code', $tenant->code) }}" placeholder="TGT">
        </div>
        <div class="field">
          <label class="f req" for="name_ar">الاسم بالعربية</label>
          <input type="text" id="name_ar" name="name_ar" value="{{ old('name_ar', $tenant->name_ar) }}">
        </div>
        <div class="field">
          <label class="f req" for="name_en">الاسم بالإنجليزية</label>
          <input type="text" id="name_en" name="name_en" class="ltr" value="{{ old('name_en', $tenant->name_en) }}">
        </div>
        <div class="field">
          <label class="f" for="phone">الهاتف</label>
          <input type="tel" id="phone" name="phone" class="ltr" value="{{ old('phone', $tenant->phone) }}">
        </div>
      </div>

      <div class="grid">
        <div class="field" style="grid-column:span 2">
          <label class="f" for="address">العنوان</label>
          <input type="text" id="address" name="address" value="{{ old('address', $tenant->address) }}">
        </div>
        <div class="field">
          <label class="f req" for="city">المدينة (المحافظة)</label>
          @php($curCity = old('city', $tenant->city))
          <select id="city" name="city">
            <option value="">— اختر —</option>
            @foreach ($cities as $c)
              <option value="{{ $c }}" {{ $curCity === $c ? 'selected' : '' }}>{{ $c }}</option>
            @endforeach
          </select>
        </div>
        <div class="field">
          <label class="f" for="gln">رقم GLN</label>
          <input type="text" id="gln" name="gln" class="ltr" inputmode="numeric" maxlength="17"
                 value="{{ old('gln', $tenant->gln) }}" placeholder="13 digits">
          <div class="hint" id="glnHint">رقم الموقع العالمي من GS1 مصر · ١٣ رقماً</div>
        </div>
      </div>

      <div class="field">
        <label class="f" for="zoneInput">مناطق التغطية</label>
        @php($zones = old('coverage_zones', $tenant->coverage_zones ?? []))
        <div class="tags" id="zones" onclick="document.getElementById('zoneInput').focus()">
          @foreach ($zones as $z)
            <span class="tag"><input type="hidden" name="coverage_zones[]" value="{{ $z }}">{{ $z }}<button type="button" title="حذف">×</button></span>
          @endforeach
          <input type="text" id="zoneInput" placeholder="اكتب اسم المنطقة ثم Enter" autocomplete="off">
        </div>
        <div class="hint">المناطق أو المحافظات التي يغطيها المخزن بالتوزيع. اضغط Enter أو فاصلة بعد كل منطقة.</div>
      </div>

      <div class="field">
        <label class="f" for="logo">الشعار (PNG · بحد أقصى ١ ميجابايت)</label>
        <div class="logo-box">
          <div class="logo-prev" id="logoPrev">
            @if ($tenant->logoUrl())
              <img src="{{ $tenant->logoUrl() }}" alt="">
            @else
              <span>لا يوجد شعار</span>
            @endif
          </div>
          <div>
            <input type="file" id="logo" name="logo" accept="image/png">
            @if ($tenant->logo_path)
              <label class="chk" style="margin-top:8px"><input type="checkbox" name="remove_logo" value="1"> حذف الشعار الحالي</label>
            @endif
          </div>
        </div>
      </div>

      <div class="field">
        <input type="hidden" name="is_active" value="0">
        <label class="chk"><input type="checkbox" name="is_active" value="1" {{ old('is_active', $tenant->is_active) ? 'checked' : '' }}> العميل فعال (يمكن لمستخدميه الدخول)</label>
      </div>

      <div class="actions">
        <button type="submit" class="btn">{{ $tenant->exists ? 'حفظ التعديلات' : 'إنشاء العميل' }}</button>
        <a href="{{ route('admin.tenants.index') }}" class="btn ghost">إلغاء</a>
      </div>
    </form>
  </div>

  @if ($tenant->exists)
    {{-- مديرو العميل --}}
    <div class="card">
      <div class="sec-h">
        <h2 style="margin:0">مدير العميل (مدير المخزن)</h2>
        <span class="page-sub" style="margin:0">ينشئ مستخدمي المخزن ويحدد أدوارهم.</span>
      </div>

      @if ($admins->isNotEmpty())
        <div class="tbl-wrap" style="margin-top:12px">
          <table class="t">
            <thead><tr><th>الاسم</th><th>البريد الإلكتروني</th><th>الهاتف</th><th>آخر دخول</th><th>الحالة</th><th></th></tr></thead>
            <tbody>
              @foreach ($admins as $u)
                @php($bag = $errors->getBag('admin_' . $u->id))
                <tr>
                  <td>
                    <b>{{ $u->displayName() }}</b>
                    @if ($u->name_ar) <div class="ltr" style="font-size:12.5px;color:var(--muted)">{{ $u->name }}</div> @endif
                    <details class="edit-admin" {{ $bag->any() ? 'open' : '' }}>
                      <summary>تعديل · تغيير كلمة المرور</summary>
                      <div class="inner">
                        @if ($bag->any())
                          <div class="bag"><ul>@foreach ($bag->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
                        @endif
                        <form method="POST" action="{{ route('admin.tenants.admins.update', [$tenant, $u]) }}" novalidate>
                          @csrf
                          @method('PUT')
                          <div class="grid">
                            <div class="field"><label class="f req">الاسم (إنجليزي)</label><input type="text" name="name" class="ltr" value="{{ $u->name }}"></div>
                            <div class="field"><label class="f">الاسم بالعربية</label><input type="text" name="name_ar" value="{{ $u->name_ar }}"></div>
                            <div class="field"><label class="f req">البريد الإلكتروني</label><input type="email" name="email" class="ltr" value="{{ $u->email }}"></div>
                            <div class="field"><label class="f">الهاتف</label><input type="tel" name="phone" class="ltr" value="{{ $u->phone }}"></div>
                            <div class="field"><label class="f">كلمة مرور جديدة (اتركها فارغة بدون تغيير)</label><input type="password" name="password" class="ltr" autocomplete="new-password"></div>
                          </div>
                          <button type="submit" class="btn">حفظ</button>
                        </form>
                      </div>
                    </details>
                  </td>
                  <td><span class="ltr">{{ $u->email }}</span></td>
                  <td><span class="ltr">{{ $u->phone }}</span></td>
                  <td><span class="ltr">{{ $u->last_login_at?->timezone('Africa/Cairo')->format('Y-m-d H:i') ?? '—' }}</span></td>
                  <td>
                    @if ($u->is_active)
                      <span class="badge b-ok">فعال</span>
                    @else
                      <span class="badge b-bad">موقوف</span>
                    @endif
                  </td>
                  <td>
                    <form method="POST" action="{{ route('admin.tenants.admins.toggle', [$tenant, $u]) }}" style="margin:0">
                      @csrf
                      @method('PATCH')
                      <button type="submit" class="btn {{ $u->is_active ? 'danger' : 'ghost' }}" style="padding:4px 12px;font-size:13px">{{ $u->is_active ? 'إيقاف' : 'تفعيل' }}</button>
                    </form>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @else
        <div class="alert info" style="margin-top:12px">لا يوجد مدير لهذا العميل بعد. أضفه من النموذج بالأسفل.</div>
      @endif

      {{-- إضافة مدير --}}
      @php($addBag = $errors->getBag('admin'))
      <h2 style="margin-top:18px">{{ $admins->isEmpty() ? 'إضافة مدير العميل' : 'إضافة مدير آخر' }}</h2>
      @if ($addBag->any())
        <div class="bag"><ul>@foreach ($addBag->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
      @endif
      <form method="POST" action="{{ route('admin.tenants.admins.store', $tenant) }}" novalidate>
        @csrf
        <div class="grid">
          <div class="field"><label class="f req" for="a_name">الاسم (إنجليزي)</label><input type="text" id="a_name" name="name" class="ltr" value="{{ $addBag->any() ? old('name') : '' }}"></div>
          <div class="field"><label class="f" for="a_name_ar">الاسم بالعربية</label><input type="text" id="a_name_ar" name="name_ar" value="{{ $addBag->any() ? old('name_ar') : '' }}"></div>
          <div class="field"><label class="f req" for="a_email">البريد الإلكتروني</label><input type="email" id="a_email" name="email" class="ltr" value="{{ $addBag->any() ? old('email') : '' }}"></div>
          <div class="field"><label class="f" for="a_phone">الهاتف</label><input type="tel" id="a_phone" name="phone" class="ltr" value="{{ $addBag->any() ? old('phone') : '' }}"></div>
          <div class="field"><label class="f req" for="a_password">كلمة مرور مبدئية</label><input type="text" id="a_password" name="password" class="ltr" autocomplete="off"></div>
        </div>
        <button type="submit" class="btn">إنشاء حساب المدير</button>
      </form>
    </div>

    {{-- إيقاف / تفعيل العميل --}}
    <div class="card">
      <h2>حالة العميل</h2>
      <p class="page-sub">إيقاف العميل يمنع كل مستخدميه من الدخول فوراً، دون حذف أي بيانات.</p>
      <form method="POST" action="{{ route('admin.tenants.toggle', $tenant) }}" style="margin:0">
        @csrf
        @method('PATCH')
        <button type="submit" class="btn {{ $tenant->is_active ? 'danger' : '' }}">{{ $tenant->is_active ? 'إيقاف العميل' : 'تفعيل العميل' }}</button>
      </form>
    </div>
  @endif
@endsection

@push('scripts')
<script>
  // معاينة الشعار قبل الرفع
  (function () {
    var input = document.getElementById('logo');
    var box = document.getElementById('logoPrev');
    if (!input || !box) return;
    input.addEventListener('change', function () {
      var f = input.files && input.files[0];
      if (!f) return;
      var url = URL.createObjectURL(f);
      box.innerHTML = '<img src="' + url + '" alt="">';
    });
  })();

  // مناطق التغطية: إضافة وحذف
  (function () {
    var box = document.getElementById('zones');
    var input = document.getElementById('zoneInput');
    if (!box || !input) return;

    function values() {
      return Array.prototype.map.call(box.querySelectorAll('input[type=hidden]'), function (h) { return h.value; });
    }
    function add(text) {
      text = (text || '').replace(/\s+/g, ' ').trim();
      if (!text || values().indexOf(text) !== -1) return;
      var tag = document.createElement('span');
      tag.className = 'tag';
      var h = document.createElement('input');
      h.type = 'hidden'; h.name = 'coverage_zones[]'; h.value = text;
      var b = document.createElement('button');
      b.type = 'button'; b.title = 'حذف'; b.textContent = '×';
      tag.appendChild(h);
      tag.appendChild(document.createTextNode(text));
      tag.appendChild(b);
      box.insertBefore(tag, input);
    }
    function flush() {
      input.value.split(/[,،]/).forEach(add);
      input.value = '';
    }

    input.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ',' || e.key === '،') { e.preventDefault(); flush(); }
      if (e.key === 'Backspace' && input.value === '') {
        var tags = box.querySelectorAll('.tag');
        if (tags.length) tags[tags.length - 1].remove();
      }
    });
    input.addEventListener('blur', flush);
    box.addEventListener('click', function (e) {
      if (e.target.matches('.tag button')) { e.stopPropagation(); e.target.closest('.tag').remove(); }
    });
    // ما لم يُضف بعد يُحفظ مع النموذج
    input.form.addEventListener('submit', flush);
  })();

  // GLN: التحقق من رقم التحقق أثناء الكتابة
  (function () {
    var input = document.getElementById('gln');
    var hint = document.getElementById('glnHint');
    if (!input || !hint) return;
    var base = hint.textContent;
    function check() {
      var v = input.value.replace(/\D+/g, '');
      if (!v) { hint.textContent = base; hint.style.color = ''; return; }
      if (v.length !== 13) { hint.textContent = 'عدد الأرقام ' + v.length + ' من ١٣'; hint.style.color = 'var(--warn)'; return; }
      var sum = 0;
      for (var i = 11, w = 3; i >= 0; i--, w = (w === 3 ? 1 : 3)) sum += Number(v[i]) * w;
      var ok = (10 - sum % 10) % 10 === Number(v[12]);
      hint.textContent = ok ? 'رقم GLN صحيح ✓' : 'رقم التحقق الأخير غير صحيح';
      hint.style.color = ok ? 'var(--ok)' : 'var(--bad)';
    }
    input.addEventListener('input', check);
    check();
  })();
</script>
@endpush