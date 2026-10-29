@extends('layouts.app')

@section('title', 'سجل الوثائق')

@push('head')
<style>
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:14px}
  .stats{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:16px}
  .stat{background:#fff;border:1px solid var(--line);border-radius:10px;padding:12px 14px;text-decoration:none;color:inherit}
  .stat b{display:block;font-size:26px;line-height:1.2;direction:ltr;text-align:right}
  .stat span{font-size:13px;color:var(--muted)}
  .stat.bad b{color:var(--bad)} .stat.ok b{color:var(--ok)} .stat.warn b{color:var(--warn)}
  .code{font-weight:700;color:var(--brand);direction:ltr;display:inline-block;white-space:nowrap;font-size:13px}
  .filters{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:12px}
  .filters select,.filters input{width:auto}
  .add{display:grid;grid-template-columns:140px 170px 1fr 160px auto;gap:8px;align-items:end}
  @media (max-width:800px){.add{grid-template-columns:1fr}}
  .pill{display:inline-block;border-radius:99px;padding:2px 10px;font-size:12px;font-weight:700;white-space:nowrap}
  .p-effective{background:#e3f4ea;color:var(--ok)} .p-pending{background:#fdf1dc;color:#9a6300}
  .p-none{background:#eef0f0;color:var(--muted)} .p-review{background:#fde8e6;color:var(--bad)} .p-obsolete{background:#eef0f0;color:#999;text-decoration:line-through}
  .by{font-size:11.5px;color:var(--muted)}
  .err{color:var(--bad);font-size:13px;margin-top:6px}
  tr.off td{opacity:.55}
  .hint{font-size:12.5px;color:var(--muted);line-height:1.8}
  .libbox{border:1.5px solid #cfe3df;background:#f6fbfa;border-radius:12px;padding:14px 16px;margin-bottom:16px}
  .libbox h2{font-size:16px;margin:0 0 6px;color:var(--brand)}
  .steps3{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:10px}
  .steps3 form{margin:0;background:#fff;border:1px solid var(--line);border-radius:10px;padding:10px 12px;display:flex;flex-direction:column;gap:6px}
  .steps3 b{font-size:13.5px} .steps3 span{font-size:12.5px;color:var(--muted);flex:1}
  .steps3 .done{color:var(--ok);font-weight:700;font-size:12.5px}
  @media (max-width:800px){.steps3{grid-template-columns:1fr}}
  .pbar{display:flex;gap:8px;align-items:center;flex-wrap:wrap;background:#f6fbfa;border:1px solid #cfe3df;border-radius:10px;padding:8px 12px;margin-bottom:10px;font-size:13.5px}
  .pbar .n{font-weight:700;color:var(--brand)}
  .pbar label{display:flex;align-items:center;gap:5px;font-size:13px;margin:0}
  td.ck,th.ck{width:30px;text-align:center}
  td.ck input,th.ck input{width:17px;height:17px;accent-color:var(--brand)}
</style>
@endpush

@section('content')
  @php
    $stLabel = ['effective' => 'ساري', 'pending' => 'قيد الإعداد أو المراجعة', 'none' => 'بدون إصدار', 'review' => 'تجاوز موعد المراجعة', 'obsolete' => 'ملغاة'];
  @endphp
  <div class="head-actions">
    <div>
      <h1 class="page-title">سجل الوثائق</h1>
      <p class="page-sub" style="margin:0">{{ auth()->user()->tenant->name_ar }} · القائمة الرئيسية للوثائق المعتمدة · <span class="ltr">SH-014</span></p>
    </div>
    <div class="actions">
      <a href="{{ route('documents.queue') }}" class="btn">قائمة الانتظار @if ($waiting)<b>({{ $waiting }})</b>@endif</a>
    </div>
  </div>

  <div class="stats">
    <a class="stat" href="{{ route('documents.index') }}"><b>{{ $stats['total'] }}</b><span>وثيقة في السجل</span></a>
    <a class="stat ok" href="{{ route('documents.index', ['status' => 'effective']) }}"><b>{{ $stats['effective'] }}</b><span>سارية</span></a>
    <a class="stat {{ $stats['pending'] ? 'warn' : '' }}" href="{{ route('documents.index', ['status' => 'pending']) }}"><b>{{ $stats['pending'] }}</b><span>قيد الإعداد أو المراجعة</span></a>
    <a class="stat" href="{{ route('documents.index', ['status' => 'none']) }}"><b>{{ $stats['none'] }}</b><span>بدون إصدار بعد</span></a>
    <a class="stat {{ $stats['review'] ? 'bad' : '' }}" href="{{ route('documents.index', ['status' => 'review']) }}"><b>{{ $stats['review'] }}</b><span>تجاوزت موعد المراجعة (سنتان)</span></a>
  </div>

  @if ($canManage)
    <div class="libbox">
      <h2>📘 مكتبة إتقان: الوثائق جاهزة في النظام بدون رفع ملفات</h2>
      <div class="hint">٣٤ إجراء تشغيل قياسي بنماذجها · {{ \App\Support\JobLibrary::all()->count() }} وصفاً وظيفياً · {{ count(\App\Support\KpiSets::SETS) }} مجموعات مؤشرات أداء.
        تملأ فراغات كل إجراء (مدد الحفظ · الحدود · الأوقات)، وتُعتمد في النظام (الجودة ← الرئيس التنفيذي)، وتُطبع بشعار المخزن وأسماء المعتمدين.</div>
      <div class="steps3">
        <form method="POST" action="{{ route('documents.seed') }}">
          @csrf
          <b>١. إضافة وثائق المكتبة للسجل</b>
          <span>{{ $lib['missing'] ? $lib['missing'] . ' وثيقة من المكتبة غير موجودة في السجل.' : 'كل وثائق المكتبة في السجل.' }}</span>
          @if ($lib['missing'])<button type="submit" class="btn small">إضافة {{ $lib['missing'] }} وثيقة</button>@else<span class="done">✓ تم</span>@endif
        </form>
        <form method="POST" action="{{ route('documents.library.all') }}">
          @csrf
          <b>٢. إنشاء المسودات من نص المكتبة</b>
          <span>{{ $lib['empty'] ? $lib['empty'] . ' وثيقة ليس لها إصدار بعد.' : 'لا توجد وثائق بدون إصدار.' }} بعدها املأ فراغات كل إجراء من صفحته.</span>
          @if ($lib['empty'])<button type="submit" class="btn small">إنشاء {{ $lib['empty'] }} مسودة</button>@else<span class="done">✓ تم</span>@endif
        </form>
        <form method="POST" action="{{ route('documents.submit.all') }}" onsubmit="return confirm('إرسال كل مسوداتك من المكتبة لمدير إدارة الجودة؟')">
          @csrf
          <b>٣. الإرسال للمراجعة دفعة واحدة</b>
          <span>{{ $lib['drafts'] ? $lib['drafts'] . ' مسودة من المكتبة باسمك لم تُرسل.' : 'لا توجد مسودات باسمك.' }} يعتمدها مدير الجودة ثم الرئيس التنفيذي من قائمة الانتظار (اعتماد جماعي).</span>
          @if ($lib['drafts'])<button type="submit" class="btn small">إرسال {{ $lib['drafts'] }} مسودة</button>@else<span class="done">—</span>@endif
        </form>
      </div>
    </div>

    <div class="card">
      <h2 style="font-size:16px;margin:0 0 10px">إضافة وثيقة للسجل</h2>
      <form method="POST" action="{{ route('documents.store') }}" class="add">
        @csrf
        <div><label class="f req" for="code">الكود</label><input type="text" id="code" name="code" value="{{ old('code') }}" class="ltr-in" placeholder="SH-035" required></div>
        <div><label class="f req" for="type">النوع</label>
          <select id="type" name="type">@foreach ($types as $k => $t)<option value="{{ $k }}" {{ old('type') === $k ? 'selected' : '' }}>{{ $t }}</option>@endforeach</select></div>
        <div><label class="f req" for="title">العنوان</label><input type="text" id="title" name="title" value="{{ old('title') }}" required></div>
        <div><label class="f" for="dept">الإدارة / القسم</label><input type="text" id="dept" name="dept" value="{{ old('dept') }}"></div>
        <button type="submit" class="btn">إضافة</button>
      </form>
      @foreach (['code', 'type', 'title', 'dept'] as $k)@error($k)<div class="err">{{ $message }}</div>@enderror @endforeach
      <div class="hint" style="margin-top:8px">لوثيقة خاصة بالمخزن غير موجودة في المكتبة (سياسة · نموذج إضافي): أضفها هنا ثم ارفع ملفها من صفحتها.</div>
    </div>
  @endif

  <div class="card">
    <form method="GET" action="{{ route('documents.index') }}" class="filters">
      <select name="type" onchange="this.form.submit()">
        <option value="">كل الأنواع</option>
        @foreach ($types as $k => $t)<option value="{{ $k }}" {{ $f['type'] === $k ? 'selected' : '' }}>{{ $t }}</option>@endforeach
      </select>
      <select name="status" onchange="this.form.submit()">
        <option value="">كل الحالات</option>
        @foreach ($stLabel as $k => $t)<option value="{{ $k }}" {{ $f['status'] === $k ? 'selected' : '' }}>{{ $t }}</option>@endforeach
      </select>
      <input type="text" name="q" value="{{ $f['q'] }}" placeholder="بحث بالكود أو العنوان…">
      <button type="submit" class="btn ghost">بحث</button>
      @if ($f['type'] !== '' || $f['status'] !== '' || $f['q'] !== '')<a href="{{ route('documents.index') }}" class="btn ghost">مسح</a>@endif
    </form>

    <form method="GET" action="{{ route('documents.print.batch') }}" target="_blank" id="pb" class="pbar" onsubmit="return pbCheck()">
      <span>طباعة مجمعة:</span>
      <span class="n" id="pbN">٠ محددة</span>
      <button type="submit" class="btn small">طباعة المحدد</button>
      <button type="button" class="btn ghost small" onclick="pbAll()">طباعة كل النتائج ({{ $rows->count() }})</button>
      <label><input type="checkbox" name="forms" value="1"> مع نماذج الإجراءات</label>
      <span class="by">كل وثيقة في صفحة جديدة · فلتر النوع أو الحالة ثم حدد</span>
    </form>

    <div class="tbl-wrap">
      <table class="t">
        <thead><tr>
          <th class="ck"><input type="checkbox" id="pbHead" title="تحديد الكل" onclick="document.querySelectorAll('.pbx').forEach(c => c.checked = this.checked); pbCount()"></th>
          <th>الكود</th><th>العنوان</th><th>النوع</th><th>الإصدار الساري</th><th>تاريخ السريان</th><th>موعد المراجعة</th><th>الإصدار الجاري</th><th>الحالة</th>
        </tr></thead>
        <tbody>
          @forelse ($rows as $r)
            @php
              $d = $r['doc'];
            @endphp
            <tr class="{{ $d->is_active ? '' : 'off' }}">
              <td class="ck"><input type="checkbox" class="pbx" name="ids[]" value="{{ $d->id }}" form="pb" onchange="pbCount()"></td>
              <td><a href="{{ route('documents.show', $d) }}" class="code">{{ $d->code }}</a></td>
              <td><a href="{{ route('documents.show', $d) }}" style="color:inherit">{{ $d->title }}</a>@if ($d->dept)<div class="by">{{ $d->dept }}</div>@endif</td>
              <td class="by">{{ $d->typeLabel() }}</td>
              <td class="ltr">{{ $r['current']?->version ?? '—' }}</td>
              <td class="ltr">{{ $r['current']?->effective_date?->format('Y-m-d') ?? '—' }}</td>
              <td class="ltr" style="{{ $r['state'] === 'review' ? 'color:var(--bad);font-weight:700' : '' }}">{{ $r['current']?->review_due?->format('Y-m-d') ?? '—' }}</td>
              <td>
                @if ($r['working'])
                  <span class="ltr">{{ $r['working']->version }}</span> <span class="by">· {{ $r['working']->stageLabel() }}</span>
                @else — @endif
              </td>
              <td><span class="pill p-{{ $r['state'] }}">{{ $stLabel[$r['state']] }}</span>
                @if ($r['current'] && $r['current']->isLibrary())<a href="{{ route('documents.print', $d) }}" target="_blank" class="by" style="margin-inline-start:6px">طباعة</a>@endif</td>
            </tr>
          @empty
            <tr><td colspan="9" style="text-align:center;color:var(--muted);padding:24px">
              {{ ($f['type'] !== '' || $f['status'] !== '' || $f['q'] !== '') ? 'لا توجد نتائج.' : 'السجل فارغ. ابدأ بإضافة وثائق مكتبة إتقان (الخطوة ١ أعلاه).' }}
            </td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection

@push('scripts')
<script>
  function pbCount() {
    const n = document.querySelectorAll('.pbx:checked').length;
    document.getElementById('pbN').textContent = n.toLocaleString('ar-EG') + ' محددة';
    const h = document.getElementById('pbHead'), all = document.querySelectorAll('.pbx').length;
    if (h) { h.checked = n > 0 && n === all; h.indeterminate = n > 0 && n < all; }
  }
  function pbCheck() {
    const n = document.querySelectorAll('.pbx:checked').length;
    if (!n) { alert('حدد وثيقة واحدة على الأقل، أو اضغط «طباعة كل النتائج».'); return false; }
    if (n > 80) { alert('الحد الأقصى ٨٠ وثيقة في الطباعة الواحدة. ضيّق الفلتر.'); return false; }
    return true;
  }
  function pbAll() {
    document.querySelectorAll('.pbx').forEach(c => c.checked = true); pbCount();
    if (pbCheck()) document.getElementById('pb').submit();
  }
</script>
@endpush