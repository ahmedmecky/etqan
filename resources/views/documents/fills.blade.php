@extends('layouts.app')

@section('title', 'الفراغات · ' . $doc->code)

@push('head')
<style>
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:14px}
  .code{font-weight:700;color:var(--brand);direction:ltr;display:inline-block;white-space:nowrap}
  .info{background:#eef6f5;border:1px solid #d3e8e3;border-radius:10px;padding:10px 14px;font-size:13.5px;line-height:1.9;margin-bottom:14px}
  .sec{margin-bottom:14px}
  .sec h2{font-size:15px;margin:0 0 8px;color:var(--brand)}
  .bl{display:grid;grid-template-columns:minmax(0,1fr) 260px;gap:10px;align-items:center;padding:8px 0;border-bottom:1px solid #eef1f0}
  .bl:last-child{border-bottom:0}
  .bl .cx{font-size:13.5px;line-height:1.8}
  .bl .cx mark{background:#fdf1dc;color:#7a4f00;border-radius:4px;padding:0 4px;font-weight:700}
  .bl .cx small{color:var(--muted)}
  .bl .in{display:flex;gap:6px;align-items:center}
  .bl .in input{padding:7px 9px}
  .bl .in span{font-size:12.5px;color:var(--muted);white-space:nowrap}
  .bl.empty input{border-color:#e9b8b3;background:#fffafa}
  .bar{position:sticky;bottom:0;background:#fff;border-top:1px solid var(--line);padding:10px 0;display:flex;gap:10px;align-items:center;flex-wrap:wrap;z-index:5}
  .bar .by{font-size:12.5px;color:var(--muted)}
  @media (max-width:700px){ .bl{grid-template-columns:1fr} }
</style>
@endpush

@section('content')
  <div class="head-actions">
    <div>
      <h1 class="page-title">فراغات <span class="code">{{ $doc->code }}</span> · {{ $doc->title }}</h1>
      <p class="page-sub" style="margin:0">الإصدار <span class="ltr">{{ $v->version }}</span> · {{ $v->stageLabel() }} · {{ $count }} فراغ</p>
    </div>
    <div class="actions">
      <a href="{{ route('documents.print', [$doc, 'v' => $v->id]) }}" class="btn ghost" target="_blank">معاينة الطباعة</a>
      <a href="{{ route('documents.show', $doc) }}" class="btn ghost">رجوع للوثيقة</a>
    </div>
  </div>

  <div class="info">
    نص الإجراء ثابت كما في مكتبة إتقان، وتملأ هنا ما يخص مخزنك فقط: مدد الحفظ، والحدود، والأوقات، والأسماء.
    القيمة المقترحة مكتوبة مسبقاً ويمكنك تغييرها. الخانة الفارغة تُطبع خطاً للكتابة باليد.
    إذا احتجت تعديلاً أكبر في النص، ارفع ملفاً معدلاً من صفحة الوثيقة (يصبح الإصدار ملفاً مرفوعاً).
  </div>

  <form method="POST" action="{{ route('documents.fills.save', [$doc, $v]) }}">
    @csrf
    @method('PUT')
    @foreach ($groups as $sec => $items)
      <div class="card sec">
        <h2>{{ $sec }}</h2>
        @foreach ($items as $p)
          <div class="bl {{ trim($p['value']) === '' ? 'empty' : '' }}">
            <div class="cx">{{ $p['ctx'] }} <mark>{{ $p['raw'] }}</mark> <small>{{ $p['after'] }}</small></div>
            <div class="in">
              <input type="text" name="f[{{ $p['i'] }}]" value="{{ $p['value'] }}" maxlength="300" {{ $editable ? '' : 'disabled' }} aria-label="{{ $p['ctx'] }}">
              @if ($p['unit'])<span>{{ $p['unit'] }}</span>@endif
            </div>
          </div>
        @endforeach
      </div>
    @endforeach

    <div class="bar">
      @if ($editable)
        <button type="submit" class="btn">حفظ الفراغات</button>
        <span class="by">الخانات المظللة بالأحمر فارغة وستُطبع خطاً للكتابة.</span>
      @else
        <span class="by">الإصدار في مرحلة {{ $v->stageLabel() }}، ولا يمكنك تعديله الآن.</span>
      @endif
    </div>
  </form>
@endsection