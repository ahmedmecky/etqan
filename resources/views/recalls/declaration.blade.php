@extends('layouts.app')

@section('title', 'إقرارات السحب · ' . $r->number)

@push('head')
  @include('documents._print-style')
  <style>
    .dc{max-width:820px;margin:0 auto 18px;background:#fff;border:1px solid var(--line);border-radius:12px;padding:26px 30px;font-size:15px;line-height:2.1;break-after:page;page-break-after:always}
    .dc:last-child{break-after:auto;page-break-after:auto}
    .dc .hd{display:flex;justify-content:space-between;align-items:center;gap:12px;border-bottom:3px solid #c08a1e;padding-bottom:8px;margin-bottom:14px}
    .dc .hd .tn{display:flex;align-items:center;gap:10px}
    .dc .hd img{max-height:56px;max-width:96px;object-fit:contain}
    .dc .hd b{display:block;font-size:15px;color:#0f5e5a;line-height:1.35}
    .dc .hd small{display:block;font-size:11px;color:#5f6f6c;line-height:1.5}
    .dc .hd .no{text-align:left;font-size:11.5px;line-height:1.6;direction:ltr}
    .dc h1{text-align:center;font-size:21px;margin:4px 0 2px;color:#1c2826}
    .dc .sub{text-align:center;font-size:12.5px;color:#5f6f6c;margin-bottom:12px}
    .dc table.it{width:100%;border-collapse:collapse;font-size:13.5px;margin:6px 0 14px;line-height:1.7}
    .dc .it td{border:1px solid #9fb3b0;padding:5px 9px}
    .dc .it td.k{background:#e3f0ee;color:#0f5e5a;font-weight:700;width:22%}
    .dc .fill{display:inline-block;min-width:220px;border-bottom:1px dotted #333;text-align:center;font-weight:700;line-height:1.5}
    .dc .fill.s{min-width:110px}
    .dc .op{display:flex;gap:10px;align-items:flex-start;margin:8px 0;padding:8px 12px;border:1px solid #c9d3d1;border-radius:8px}
    .dc .op i{width:22px;height:22px;border:2px solid #1c2826;border-radius:4px;flex:none;margin-top:8px}
    .dc .ack{font-weight:700;margin-top:14px}
    .dc .sig{display:grid;grid-template-columns:1.25fr 1fr;gap:16px;margin-top:18px}
    .dc .sig .s{border:1px solid #9fb3b0;border-radius:8px;padding:8px 12px;font-size:13.5px;line-height:2.3}
    .dc .sig .s b{color:#0f5e5a;display:block;line-height:1.6;margin-bottom:2px}
    .dc .stamp{border:2px dashed #9fb3b0;border-radius:50%;width:150px;height:150px;margin:6px auto 0;display:flex;align-items:center;justify-content:center;color:#9fb3b0;font-size:13px;text-align:center}
    .dc .rcv{margin-top:14px;border-top:1px dashed #9fb3b0;padding-top:8px;font-size:12.5px;line-height:2.2;color:#33403d}
    .dc .ft{margin-top:12px;font-size:10.5px;color:#5f6f6c;text-align:center;border-top:1px solid #0f5e5a;padding-top:5px}
    @media print{
      .dc{border:0;border-radius:0;padding:0;margin:0;max-width:none;font-size:14px}
      header.top,footer.foot,.ptools{display:none !important}
    }
  </style>
@endpush

@section('content')
  @php
    $logo = $tenant->logoUrl();
    $rows = $blank ? collect([null]) : $list;
  @endphp

  <div class="ptools">
    <div class="fl"><a href="{{ route('recalls.show', $r) }}" style="border:0;padding:0;font-size:13.5px">← {{ $r->number }}</a>
      <span>{{ $blank ? 'إقرار فارغ' : $list->count() . ' إقرار' }} · ورقة لكل صيدلية</span></div>
    <button type="button" class="btn gold" onclick="window.print()">طباعة</button>
  </div>

  @if ($rows->isEmpty())
    <div class="empty" style="max-width:820px;margin:0 auto">كل الإقرارات وصلت. لطباعة الكل: <a href="{{ route('recalls.declarations', [$r, 'all' => 1]) }}">كل الإقرارات</a>.</div>
  @endif

  @foreach ($rows as $c)
    <div class="dc">
      <div class="hd">
        <div class="tn">
          @if ($logo)<img src="{{ $logo }}" alt="">@endif
          <div><b>{{ $tenant->name_ar }}</b><small>{{ $tenant->name_en }}</small><small>نظام إدارة الجودة · إتقان</small></div>
        </div>
        <div class="no">
          SH-010-F05<br>{{ $r->number }}@if ($c)-{{ $c->id }}@endif
        </div>
      </div>

      <h1>إقرار بشأن سحب صنف من السوق</h1>
      <div class="sub">يُعاد هذا الإقرار موقعاً ومختوماً بختم الصيدلية مع مندوب الشركة</div>

      <table class="it">
        <tr><td class="k">الصنف</td><td><b>{{ $r->product }}</b></td><td class="k">رقم التشغيلة</td><td class="ltr" style="text-align:right"><b>{{ $r->batch }}</b></td></tr>
        <tr><td class="k">تاريخ الانتهاء</td><td class="ltr" style="text-align:right">{{ $r->expiry?->format('Y-m-d') ?? '……………' }}</td><td class="k">رقم المنشور</td><td>{{ $r->circular_no ?: '……………' }} · {{ $r->sourceLabel() }}</td></tr>
        @if ($r->reason)<tr><td class="k">سبب السحب</td><td colspan="3">{{ $r->reason }}</td></tr>@endif
        @if ($c && $c->qty_sold)<tr><td class="k">الكمية المبيعة لكم</td><td colspan="3">{{ $c->qty_sold }} عبوة (حسب فواتير الشركة)</td></tr>@endif
      </table>

      <p>
        أقر أنا الدكتور/ <span class="fill">{{ $c?->pharmacist ?? '' }}</span>
        مدير صيدلية/ <span class="fill">{{ $c?->name ?? '' }}</span>
        @if ($c && $c->code)(كود <span class="ltr">{{ $c->code }}</span>)@endif
        <br>والكائنة في/ <span class="fill" style="min-width:420px">{{ $c?->address ?? '' }}</span>
        <br>بأنني قد أُبلغت بقرار سحب الصنف المذكور أعلاه من التشغيلة رقم <b class="ltr">{{ $r->batch }}</b>، وأقر بأن:
      </p>

      <div class="op"><i></i><div><b>لا يوجد لدى الصيدلية</b> أي كمية من الصنف <b>{{ $r->product }}</b> من التشغيلة المذكورة.</div></div>
      <div class="op"><i></i><div><b>يوجد لدى الصيدلية</b> كمية ( <span class="fill s"></span> ) عبوة من التشغيلة المذكورة، وتم تسليمها لمندوب الشركة بتاريخ <span class="fill s"></span> ولم يُصرف منها شيء بعد الإبلاغ.</div></div>

      <p class="ack">وهذا إقرار مني بذلك، وأتحمل المسؤولية عن صحة ما ورد به.</p>

      <div class="sig">
        <div class="s">
          <b>الصيدلي المُقر</b>
          الاسم: ………………………………………………<br>
          رقم القيد بالنقابة: ……………………………<br>
          التوقيع: ………………………………………………<br>
          التاريخ: ……… / ……… / ………………
        </div>
        <div class="s" style="text-align:center">
          <b>ختم الصيدلية</b>
          <div class="stamp">مكان الختم</div>
        </div>
      </div>

      <div class="rcv">
        <b>خاص بالشركة:</b> استلم الإقرار<span>@if ($c && $c->qty_sold) والكمية @endif</span> المندوب/ ………………………………… التوقيع: …………………… التاريخ: ……… / ……… / ………
        <br>المراجعة: المدير الصيدلي ………………………………… التوقيع: ……………………
      </div>

      <div class="ft">{{ $tenant->name_ar }} · إجراء الاستدعاء والسحب من السوق (SH-010) · {{ $r->number }}@if ($c) · {{ $c->name }}@endif</div>
    </div>
  @endforeach
@endsection