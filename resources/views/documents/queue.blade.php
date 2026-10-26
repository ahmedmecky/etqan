@extends('layouts.app')

@section('title', 'قائمة انتظار الوثائق')

@push('head')
<style>
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:14px}
  .code{font-weight:700;color:var(--brand);direction:ltr;display:inline-block;white-space:nowrap;font-size:13px}
  .by{font-size:11.5px;color:var(--muted)}
  .q{display:flex;gap:12px;align-items:flex-start;justify-content:space-between;border:1px solid var(--line);border-radius:10px;padding:12px 14px;margin-bottom:10px;background:#fff;flex-wrap:wrap}
  .q h3{margin:0 0 4px;font-size:15px}
  .q .note{background:#fde8e6;color:var(--bad);border-radius:8px;padding:6px 10px;font-size:13px;margin-top:6px}
  .q .age{font-size:12px;font-weight:700}
  .age.late{color:var(--bad)}
  .empty{color:var(--muted);padding:10px 0}
  .tag{display:inline-block;background:#e8f0f0;color:var(--brand);border-radius:99px;padding:2px 10px;font-size:12px;font-weight:700}
  .q .ck{width:19px;height:19px;accent-color:var(--brand);margin-top:3px;flex:none}
  .q .main{display:flex;gap:10px;align-items:flex-start;flex:1;min-width:0}
  .bulk{display:flex;gap:8px;align-items:center;flex-wrap:wrap;background:#f3f9f8;border:1px solid #cfe3df;border-radius:10px;padding:10px 12px;margin-bottom:10px;position:sticky;top:0;z-index:4}
  .bulk input[type=text]{flex:1;min-width:200px}
</style>
@endpush

@section('content')
  <div class="head-actions">
    <div>
      <h1 class="page-title">قائمة الانتظار</h1>
      <p class="page-sub" style="margin:0">
        @if ($stages)
          أنت تراجع بصفة: @foreach ($stages as $s)<span class="tag">{{ $labels[$s] }}</span> @endforeach
        @else
          الوثائق التي أعددتها وما أُعيد لك منها
        @endif
      </p>
    </div>
    <div class="actions"><a href="{{ route('documents.index') }}" class="btn ghost">سجل الوثائق</a></div>
  </div>

  @if ($stages)
    <div class="card">
      <h2 style="font-size:16px;margin:0 0 10px">بانتظار مراجعتي أو اعتمادي ({{ $mine->count() }})</h2>
      @if ($mine->count() > 1)
        <form method="POST" action="{{ route('documents.approve.bulk') }}" id="bulkForm" onsubmit="return document.querySelectorAll('.ck:checked').length ? confirm('اعتماد ' + document.querySelectorAll('.ck:checked').length + ' وثيقة؟ راجعت محتواها؟') : (alert('اختر الوثائق أولاً'), false)">
          @csrf
          <div class="bulk">
            <label class="chk" style="margin:0"><input type="checkbox" onchange="document.querySelectorAll('.ck').forEach(c => c.checked = this.checked)"> تحديد الكل</label>
            <input type="text" name="comment" placeholder="تعليق على الاعتماد (اختياري)" maxlength="1000">
            <button type="submit" class="btn">اعتماد المحدد</button>
          </div>
        </form>
      @endif
      @forelse ($mine as $v)
        @php
          $days = $v->submitted_at ? (int) $v->submitted_at->diffInDays(now()) : 0;
          $nx = $nextOf($v);
        @endphp
        <div class="q">
          <div class="main">
            @if ($mine->count() > 1)<input type="checkbox" class="ck" name="ids[]" value="{{ $v->id }}" form="bulkForm" aria-label="{{ $v->document->code }}">@endif
            <div>
            <h3><span class="code">{{ $v->document->code }}</span> · {{ $v->document->title }}</h3>
            <div class="by">الإصدار <span class="ltr">{{ $v->version }}</span> · {{ $v->document->typeLabel() }} · أعده {{ $v->preparer?->displayName() ?? '—' }}
              · المرحلة: <b>{{ $labels[$v->stage] ?? $v->stageLabel() }}</b></div>
            <div class="by" style="margin-top:4px">{{ \Illuminate\Support\Str::limit($v->change_summary, 160) }}</div>
            <div class="by" style="margin-top:2px">{{ $v->isLibrary() ? '📘 نص مكتبة إتقان' : '📄 ملف مرفوع' }} · بعد اعتمادك: {{ $nx ? $labels[$nx] : 'ساري' }}</div>
            </div>
          </div>
          <div style="text-align:left">
            <div class="age {{ $days > 5 ? 'late' : '' }}">منذ {{ $days }} يوم</div>
            <div class="actions" style="gap:4px;margin-top:6px;justify-content:flex-end">
              @if ($v->isLibrary())<a href="{{ route('documents.print', [$v->document, 'v' => $v->id]) }}" class="btn small ghost" target="_blank">قراءة</a>@endif
              <a href="{{ route('documents.show', $v->document) }}" class="btn small">مراجعة</a>
            </div>
          </div>
        </div>
      @empty
        <div class="empty">لا يوجد ما ينتظرك. ✓</div>
      @endforelse
    </div>
  @endif

  <div class="card">
    <h2 style="font-size:16px;margin:0 0 10px">أُعيدت لي للتعديل ({{ $returned->count() }})</h2>
    @forelse ($returned as $v)
      <div class="q">
        <div>
          <h3><span class="code">{{ $v->document->code }}</span> · {{ $v->document->title }}</h3>
          <div class="by">الإصدار <span class="ltr">{{ $v->version }}</span></div>
          @if ($v->return_note)<div class="note">سبب الإعادة: {{ $v->return_note }}</div>@endif
        </div>
        <a href="{{ route('documents.show', $v->document) }}" class="btn small">تعديل وإعادة الإرسال</a>
      </div>
    @empty
      <div class="empty">لا يوجد.</div>
    @endforelse
  </div>

  <div class="card">
    <h2 style="font-size:16px;margin:0 0 10px">مسوداتي التي لم تُرسل ({{ $drafts->count() }})</h2>
    @forelse ($drafts as $v)
      <div class="q">
        <div>
          <h3><span class="code">{{ $v->document->code }}</span> · {{ $v->document->title }}</h3>
          <div class="by">الإصدار <span class="ltr">{{ $v->version }}</span> · آخر تعديل <span class="ltr">{{ $v->updated_at->timezone('Africa/Cairo')->format('Y-m-d') }}</span></div>
        </div>
        <a href="{{ route('documents.show', $v->document) }}" class="btn small ghost">فتح</a>
      </div>
    @empty
      <div class="empty">لا يوجد.</div>
    @endforelse
  </div>
@endsection