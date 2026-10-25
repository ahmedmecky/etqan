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
</style>
@endpush

@section('content')
  @php
    $stLabel = ['effective' => 'ساري', 'pending' => 'قيد المراجعة', 'none' => 'لم يُرفع ملف', 'review' => 'تجاوز موعد المراجعة', 'obsolete' => 'ملغاة'];
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
    <a class="stat" href="{{ route('documents.index', ['status' => 'none']) }}"><b>{{ $stats['none'] }}</b><span>لم يُرفع لها ملف</span></a>
    <a class="stat {{ $stats['review'] ? 'bad' : '' }}" href="{{ route('documents.index', ['status' => 'review']) }}"><b>{{ $stats['review'] }}</b><span>تجاوزت موعد المراجعة (سنتان)</span></a>
  </div>

  @if ($canManage)
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
      <form method="POST" action="{{ route('documents.seed') }}" style="margin-top:12px">
        @csrf
        <span class="hint">أول مرة؟ حمّل قائمة إجراءات التشغيل القياسية الـ ٣٤ ومجموعات مؤشرات الأداء الـ ٩ في السجل دفعة واحدة، ثم ارفع ملف كل منها.</span>
        <button type="submit" class="btn ghost small">تحميل قائمة الإجراءات والمؤشرات</button>
      </form>
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

    <div class="tbl-wrap">
      <table class="t">
        <thead><tr>
          <th>الكود</th><th>العنوان</th><th>النوع</th><th>الإصدار الساري</th><th>تاريخ السريان</th><th>موعد المراجعة</th><th>الإصدار الجاري</th><th>الحالة</th>
        </tr></thead>
        <tbody>
          @forelse ($rows as $r)
            @php($d = $r['doc'])
            <tr class="{{ $d->is_active ? '' : 'off' }}">
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
              <td><span class="pill p-{{ $r['state'] }}">{{ $stLabel[$r['state']] }}</span></td>
            </tr>
          @empty
            <tr><td colspan="8" style="text-align:center;color:var(--muted);padding:24px">
              {{ ($f['type'] !== '' || $f['status'] !== '' || $f['q'] !== '') ? 'لا توجد نتائج.' : 'السجل فارغ. أضف وثيقة أو حمّل قائمة الإجراءات.' }}
            </td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection