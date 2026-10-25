@extends('layouts.app')

@section('title', 'شكاوى العملاء')

@push('head')
<style>
@verbatim  :root{
    --bg:#f4f6f5; --card:#ffffff; --ink:#1c2826; --muted:#5f6f6c; --line:#dde3e1;
    --brand:#0f5e5a; --brand-soft:#e3f0ee; --gold:#c08a1e; --gold-soft:#f8efdc;
    --ok:#1e7a46; --ok-soft:#e4f4ea; --warn:#9a6300; --warn-soft:#fdf1dc;
    --bad:#b3261e; --bad-soft:#fbe7e5; --radius:10px;
  }
  .badge{display:inline-block;padding:4px 14px;border-radius:999px;font-size:13px;font-weight:700;white-space:nowrap}
  .b-draft{background:#e9eeec;color:#44524f}
  .b-ok{background:var(--ok-soft);color:var(--ok)}
  .b-warn{background:var(--warn-soft);color:var(--warn)}
  .b-bad{background:var(--bad-soft);color:var(--bad)}

  .scr{display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start}
  @media (max-width:1050px){.scr{grid-template-columns:1fr}}
  .scr-main{min-width:0}
  .scr-head{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px}
  .scr-head h1{margin:0;font-size:20px}
  .scr-head .code{font-size:12px;color:var(--muted);direction:ltr}
  .busy{opacity:.6;pointer-events:none}
  fieldset{border:0;padding:0;margin:0;min-width:0}

  .card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:18px 20px;margin-bottom:16px}
  .card h2{font-size:17px;margin:0 0 14px;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
  .card h3{font-size:14px;margin:16px 0 8px;color:var(--brand)}
  .step{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:var(--brand-soft);color:var(--brand);font-size:14px;font-weight:700}
  .sub{font-size:13px;color:var(--muted);margin:-8px 0 12px}
  [hidden]{display:none !important}
  .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px}
  .grid3{display:grid;grid-template-columns:2fr 1fr 1fr;gap:14px}
  @media (max-width:700px){.grid3{grid-template-columns:1fr}}
  label.f{display:block;font-size:13px;color:var(--muted);margin-bottom:4px;font-weight:600}
  .req::after{content:" *";color:var(--bad)}
  input[type=text],input[type=date],input[type=time],input[type=number],select,textarea{width:100%;padding:7px 9px;border:1px solid var(--line);border-radius:8px;font:inherit;background:#fff;color:var(--ink)}
  input[type=date],input[type=time],input[type=number],.ltr-in{direction:ltr;text-align:right}
  input[readonly]{background:#f0f3f2;color:var(--muted)}
  input:focus,select:focus,textarea:focus{outline:2px solid var(--brand-soft);border-color:var(--brand)}
  textarea{min-height:56px;resize:vertical}
  fieldset:disabled input,fieldset:disabled select,fieldset:disabled textarea{background:#f6f8f7;color:#44524f}
  .chk{display:flex;gap:8px;align-items:center;font-size:14px;margin-top:6px}
  .chk input{width:17px;height:17px;accent-color:var(--brand)}

  .btn{background:var(--brand);color:#fff;border:1px solid var(--brand);border-radius:8px;padding:8px 18px;font:inherit;font-weight:600;cursor:pointer}
  .btn.ghost{background:#fff;color:var(--brand)}
  .btn.gold{background:var(--gold);border-color:var(--gold)}
  .btn.okb{background:var(--ok);border-color:var(--ok)}
  .btn.danger{background:#fff;color:var(--bad);border-color:#e3b0ac}
  .btn.small{padding:3px 10px;font-size:13px}
  .actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  .x{background:none;border:0;color:var(--bad);font-size:20px;line-height:1;cursor:pointer;padding:2px 6px}
  fieldset:disabled .x,fieldset:disabled .add{display:none}

  .seg{display:inline-flex;border:1px solid var(--line);border-radius:8px;overflow:hidden}
  .seg label{padding:6px 14px;cursor:pointer;font-weight:600;color:var(--muted);font-size:13.5px}
  .seg label+label{border-inline-start:1px solid var(--line)}
  .seg input{display:none}
  .seg label:has(input:checked){background:var(--brand);color:#fff}

  table.t{width:100%;border-collapse:collapse;font-size:13.5px}
  table.t th{background:#fafbfb;color:var(--muted);font-weight:600;padding:8px 5px;text-align:start;border-bottom:1px solid var(--line);white-space:nowrap;font-size:12.5px}
  table.t td{padding:5px 4px;border-bottom:1px solid var(--line);vertical-align:top}
  table.t td input,table.t td select{padding:5px 6px;font-size:13px}
  table.t td.n{width:32px;text-align:center;color:var(--muted);font-weight:700;padding-top:10px}
  table.t td.c{text-align:center;white-space:nowrap;padding-top:10px;font-weight:700;direction:ltr;font-variant-numeric:tabular-nums}
  table.t tr.diff td{background:#fff7f6}
  table.t tr.sub td{background:#fbfcfc;border-bottom:2px solid var(--line)}
  .tbl-wrap{overflow-x:auto}
  .items{min-width:960px}
  .items td input{min-width:0}
  .c-ok{color:var(--ok)} .c-bad{color:var(--bad)} .c-warn{color:var(--warn)}

  .tpl{background:#fdfcf8;border:1px solid #eadcb9;border-radius:10px;padding:16px 18px;line-height:2.1;font-size:15px}
  .tpl b{color:var(--brand)}
  .tpl .blank{color:var(--bad);font-weight:600}

  .res{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:10px}
  .res div{border:1px solid var(--line);border-radius:10px;padding:10px 12px;background:#fbfcfc}
  .res span{display:block;font-size:12.5px;color:var(--muted)}
  .res b{font-size:20px;direction:ltr;display:block;text-align:right;font-variant-numeric:tabular-nums}
  .verdict{margin-top:12px;border-radius:8px;padding:10px 12px;font-size:14px;font-weight:700}
  .verdict.ok{background:var(--ok-soft);color:var(--ok)}
  .verdict.bad{background:var(--bad-soft);color:var(--bad)}

  .errors{background:var(--bad-soft);color:var(--bad);border-radius:8px;padding:10px 12px;font-size:13px;margin-top:12px}
  .errors ul{margin:6px 0 0;padding-inline-start:18px}
  .okmsg{background:var(--ok-soft);color:var(--ok);border-radius:8px;padding:8px 12px;font-size:13px;margin-top:12px;font-weight:600}
  .info{background:var(--brand-soft);border:1px solid #c6dfdb;color:#24514d;border-radius:var(--radius);padding:10px 14px;font-size:13px}

  aside{position:sticky;top:16px}
  @media (max-width:1050px){aside{position:static}}
  .kv{display:flex;justify-content:space-between;gap:8px;font-size:14px;padding:5px 0;border-bottom:1px dashed var(--line)}
  .kv:last-child{border-bottom:0}
  .kv b{font-weight:700;direction:ltr}
  .small{font-size:13px;color:var(--muted)}
  .saved{max-height:340px;overflow:auto}
  .saved .row{display:flex;justify-content:space-between;align-items:center;gap:6px;padding:7px 4px;border-bottom:1px solid var(--line);font-size:13px}
  .saved .row b{direction:ltr;display:inline-block}
  .saved .row.cur{background:var(--brand-soft);border-radius:6px}
  table.audit{width:100%;border-collapse:collapse;font-size:12.5px}
  table.audit td{padding:5px 6px;border-bottom:1px solid var(--line);vertical-align:top}
  td.tm{direction:ltr;text-align:right;white-space:nowrap;color:var(--muted)}
  .audit-wrap{max-height:220px;overflow:auto}
  .dt{direction:ltr;unicode-bidi:isolate;white-space:nowrap;display:inline-block}

  /* ===== نسخة الطباعة ===== */
  #doc{display:none}
  @page{size:A4;margin:13mm 13mm 15mm}
  @media print{
    body{background:#fff;font-size:12px}
    header.top,footer.foot,.scr,.scr-head,.track,main.wrap>.alert{display:none !important}
    main.wrap{padding:0;max-width:none}
    #doc{display:block}
  }
  .d-head{display:grid;grid-template-columns:1.2fr 1.6fr 1fr;align-items:center;border-bottom:3px solid var(--gold);padding-bottom:8px;margin-bottom:10px}
  .d-brand{display:flex;align-items:center;gap:8px}
  .d-brand .ar{font-family:"Reem Kufi","Cairo",sans-serif;font-size:22px;font-weight:700;color:var(--brand);line-height:1}
  .d-brand .sl{font-size:10.5px;color:var(--gold);font-weight:700}
  .d-tenant{display:flex;align-items:center;gap:8px;justify-content:flex-end}
  .d-tenant img{height:42px;width:auto;max-width:120px;object-fit:contain}
  .d-tenant b{display:block;font-size:13px;color:#24514d}
  .d-tenant span{font-size:10.5px;color:var(--muted);direction:ltr;display:block}
  .d-title{text-align:center}
  .d-title .t1{font-size:19px;font-weight:700;color:var(--brand)}
  .d-title .t2{font-size:11px;color:var(--muted)}
  .d-code{font-size:11px;line-height:1.7}
  .d-code b{direction:ltr;display:inline-block}
  .d-h2{font-size:14px;font-weight:700;color:var(--brand);border-bottom:1.5px solid var(--brand);padding-bottom:2px;margin:14px 0 6px}
  table.d{width:100%;border-collapse:collapse;font-size:11.5px}
  table.d th,table.d td{border:1px solid #9fb3b0;padding:4px 6px;text-align:start;vertical-align:top}
  table.d th{background:#e3f0ee;color:var(--brand);font-weight:700}
  table.d td.k{background:#f2f7f6;font-weight:700;color:#24514d;width:18%}
  table.d td.num{direction:ltr;text-align:center;white-space:nowrap}
  table.d tr.diff td{background:#fdf1ef}
  .d-p{line-height:2;text-align:justify;font-size:13px}
  .d-signs{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-top:26px;break-inside:avoid}
  .d-signs div{border-top:1px solid #555;padding-top:6px;text-align:center;font-size:12px}
  .d-foot{margin-top:16px;border-top:1px solid #9fb3b0;padding-top:4px;font-size:10px;color:var(--muted);text-align:center}
  .keep{break-inside:avoid}

  .q{display:grid;grid-template-columns:1fr auto;gap:6px 14px;align-items:center;padding:9px 0;border-bottom:1px solid var(--line)}
  .q:last-child{border-bottom:0}
  .q .qt{font-size:14px}
  .q textarea{grid-column:1 / -1;display:none;min-height:44px}
  .q.nc{background:#fff7f6;margin:0 -10px;padding:9px 10px;border-radius:8px}
  .q.nc .qt{color:var(--bad);font-weight:600}
  .q.nc textarea{display:block}
  .yn{display:inline-flex;border:1px solid var(--line);border-radius:8px;overflow:hidden}
  .yn button{background:#fff;border:0;padding:6px 14px;font:inherit;font-size:13px;font-weight:600;color:var(--muted);cursor:pointer}
  .yn button+button{border-inline-start:1px solid var(--line)}
  .yn button.on-ok{background:var(--ok);color:#fff}
  .yn button.on-bad{background:var(--bad);color:#fff}
  .yn button.on-na{background:#8a9895;color:#fff}
  fieldset:disabled .yn button{cursor:default}
  .temp{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px;align-items:end}
  .tmsg{font-size:12.5px;margin-top:4px;color:var(--muted)}
  .tmsg.ok{color:var(--ok)} .tmsg.bad{color:var(--bad);font-weight:700}
  .flag{display:inline-block;border-radius:99px;padding:1px 8px;font-size:11px;font-weight:700;margin:2px 2px 0 0;white-space:nowrap}
  .flag.bad{background:var(--bad-soft);color:var(--bad)} .flag.warn{background:var(--warn-soft);color:var(--warn)}
  .decs{display:grid;grid-template-columns:repeat(auto-fit,minmax(170px,1fr));gap:10px;margin-bottom:14px}
  .dec{border:2px solid var(--line);border-radius:10px;padding:10px 12px;cursor:pointer;display:block}
  .dec input{display:none}
  .dec b{display:block;font-size:15px}
  .dec small{color:var(--muted);font-size:12px}
  .dec.release:has(input:checked){border-color:var(--ok);background:var(--ok-soft)}
  .dec.partial:has(input:checked){border-color:var(--warn);background:var(--warn-soft)}
  .dec.reject:has(input:checked){border-color:var(--bad);background:var(--bad-soft)}
  .dec.hold:has(input:checked){border-color:var(--gold);background:var(--gold-soft)}
  fieldset:disabled .dec{cursor:default}
  .signed{background:var(--brand-soft);color:#24514d;border-radius:8px;padding:8px 12px;font-size:13px;font-weight:600;margin-top:10px}
  .suggest{border-radius:8px;padding:9px 12px;font-weight:700;font-size:14px}
  .nc-list{margin:6px 0 0;padding-inline-start:18px;font-size:13px;color:var(--bad)}
  .nc-list li{margin-bottom:3px}

  .track{display:flex;gap:6px;list-style:none;padding:0;margin:0 0 16px;flex-wrap:wrap}
  .track li{flex:1;min-width:150px;background:#fff;border:1px solid var(--line);border-radius:8px;padding:8px 10px;font-size:13px;font-weight:600;color:var(--muted);text-align:center}
  .track li.done{background:var(--ok-soft);color:var(--ok);border-color:#bfe3cc}
  .track li.cur{background:var(--brand);color:#fff;border-color:var(--brand)}
  .card.dim{opacity:.55}
  .late{color:var(--bad);font-weight:700;font-size:12px}
  .decs .dec small{line-height:1.5}
  .chips{display:flex;flex-wrap:wrap;gap:8px}
  .chip{display:inline-flex;align-items:center;gap:6px;border:1px solid var(--line);border-radius:99px;padding:5px 12px;font-size:13px;cursor:pointer;background:#fff}
  .chip:has(input:checked){background:var(--brand-soft);border-color:var(--brand);color:var(--brand);font-weight:600}
  .chip input{accent-color:var(--brand)}
@endverbatim
</style>
@endpush

@section('content')
@verbatim
<div class="scr-head">
  <h1>شكاوى العملاء</h1>
  <span class="code">SH-007-F01 · F02 · v02</span>
  <span id="badge" class="badge b-draft">مسودة</span>
</div>
<ol class="track" id="track">
  <li data-s="draft">١. استلام الشكوى</li>
  <li data-s="reported">٢. التصنيف والأولوية</li>
  <li data-s="investigating">٣. التحقيق والرد</li>
  <li data-s="closed">٤. الإغلاق</li>
</ol>
<div class="scr">
<div class="scr-main">

<fieldset id="fsA">
  <!-- ١. الاستلام -->
  <section class="card">
    <h2><span class="step">١</span> استلام الشكوى <span class="small" style="font-weight:400">(SH-007-F01 · الجزء أ)</span></h2>
    <div class="info" id="portalInfo" hidden style="margin-bottom:14px"></div>
    <div class="grid">
      <div><label class="f req" for="no">رقم الشكوى</label><input type="text" id="no" class="ltr-in" autocomplete="off"></div>
      <div><label class="f req" for="date">تاريخ الاستلام</label><input type="date" id="date"></div>
      <div><label class="f req" for="time">الوقت</label><input type="time" id="time"></div>
      <div><label class="f req" for="channel">وسيلة الاستلام</label><select id="channel"></select></div>
      <div><label class="f req" for="receiver">متلقي الشكوى</label><input type="text" id="receiver" list="peopleList"></div>
    </div>
    <h3>العميل</h3>
    <div class="grid">
      <div style="grid-column:span 2"><label class="f req" for="customer">اسم العميل (الصيدلية / الشركة)</label><input type="text" id="customer"></div>
      <div><label class="f" for="custCode">كود العميل</label><input type="text" id="custCode" class="ltr-in"></div>
      <div><label class="f req" for="custType">نوع العميل</label><select id="custType"><option value="">— اختر —</option><option>قطاعي</option><option>جملة</option></select></div>
      <div><label class="f" for="phone">رقم الهاتف</label><input type="text" id="phone" class="ltr-in"></div>
      <div><label class="f" for="invoice">رقم فاتورة البيع</label><input type="text" id="invoice" class="ltr-in"></div>
    </div>
    <div style="margin-top:14px"><label class="f req" for="details">تفاصيل الشكوى (بكلمات العميل)</label><textarea id="details" style="min-height:80px"></textarea></div>

    <h3 style="display:flex;align-items:center;gap:10px">بيانات المنتج <span class="small" style="font-weight:400">(لشكاوى المنتج)</span><span style="flex:1"></span><button type="button" class="btn ghost small add" data-act="add-prod">+ إضافة صنف</button></h3>
    <div class="tbl-wrap"><table class="t" style="min-width:820px">
      <thead><tr><th>م</th><th style="min-width:180px">الصنف والتركيز</th><th style="width:100px">التشغيلة</th><th style="width:120px">الصلاحية</th><th style="width:80px">الكمية</th><th style="width:110px">العينة متاحة</th><th style="min-width:140px">ملاحظات</th><th></th></tr></thead>
      <tbody id="prodRows"></tbody>
    </table></div>
    <p class="small" style="margin:8px 0 0">لا تجادل العميل ولا تعد بتعويض. في شكاوى المنتج اطلب الاحتفاظ بالعينة أو استلامها بإشعار مرتجع (SH-002).</p>
    <div class="signed" id="aSigned" hidden></div>
  </section>
</fieldset>

<fieldset id="fsB">
  <!-- ٢. التصنيف -->
  <section class="card" id="cardB">
    <h2><span class="step">٢</span> التصنيف والأولوية <span class="small" style="font-weight:400">(الجزء ب · الجودة والمدير الصيدلي)</span></h2>
    <div class="grid">
      <div><label class="f req" for="type">نوع الشكوى</label><select id="type"></select></div>
      <div><label class="f req" for="dept">القسم المعني</label><select id="dept"></select></div>
      <div><label class="f req" for="owner">المسؤول عن الشكوى</label><input type="text" id="owner" list="peopleList" placeholder="رئيس القسم المعني"></div>
    </div>
    <h3>الأولوية</h3>
    <div class="decs">
      <label class="dec reject"><input type="radio" name="prio" value="critical"><b>حرجة</b><small>خطر محتمل على المريض: غش، تلوث، صنف أو تركيز خطأ وصل للمريض، ثلاجة تعرضت للحرارة</small></label>
      <label class="dec partial"><input type="radio" name="prio" value="major"><b>رئيسية</b><small>تؤثر على الجودة أو الخدمة دون خطر مباشر</small></label>
      <label class="dec release"><input type="radio" name="prio" value="minor"><b>ثانوية</b><small>لا تؤثر على الجودة</small></label>
    </div>
    <div class="grid">
      <div><label class="f req" for="closeDue">موعد الإغلاق</label><input type="date" id="closeDue"><div class="tmsg" id="closeDueMsg"></div></div>
    </div>
    <div id="prodBox" hidden>
      <h3>الإجراءات الفورية لشكوى المنتج</h3>
      <div class="grid">
        <div><label class="f req">حجز باقي التشغيلة وإيقافها على النظام؟</label>
          <span class="yn" id="holdYN"><button type="button" data-v="Y">نعم</button><button type="button" data-v="N">لا</button></span></div>
        <div style="grid-column:span 2"><label class="f" id="immLbl" for="immediate">الإجراء الفوري المتخذ</label><textarea id="immediate" placeholder="مراجعة المخزون · الحجز · إبلاغ المورد · SH-011 / SH-010"></textarea></div>
        <div><label class="f" for="immAt">وقت الإجراء الفوري</label><input type="datetime-local" id="immAt" style="direction:ltr"><div class="tmsg" id="immMsg"></div></div>
      </div>
      <div class="info" id="routeInfo" hidden style="margin-top:10px"></div>
    </div>
    <div class="signed" id="bSigned" hidden></div>
  </section>
</fieldset>

<fieldset id="fsC">
  <!-- ٣. التحقيق والرد -->
  <section class="card" id="cardC">
    <h2><span class="step">٣</span> التحقيق والإجراء التصحيحي والرد <span class="small" style="font-weight:400">(SH-007-F02)</span></h2>
    <div class="grid">
      <div><label class="f req" for="ackDate">تاريخ علم المسؤول بالشكوى</label><input type="date" id="ackDate"></div>
    </div>
    <h3>المستندات التي تمت مراجعتها</h3>
    <div class="chips" id="docs"></div>
    <div style="margin-top:12px"><label class="f req" for="rootCause">السبب الجذري</label><textarea id="rootCause"></textarea></div>

    <h3 style="display:flex;align-items:center;gap:10px">الإجراءات التصحيحية والوقائية <span style="flex:1"></span><button type="button" class="btn ghost small add" data-act="add-action">+ إضافة إجراء</button></h3>
    <div class="tbl-wrap"><table class="t" style="min-width:960px">
      <thead><tr><th>م</th><th style="min-width:220px">الإجراء</th><th style="width:100px">النوع</th><th style="min-width:140px">المسؤول</th><th style="width:130px">الموعد</th><th style="width:130px">تاريخ التنفيذ</th><th style="width:110px">التحقق</th><th></th></tr></thead>
      <tbody id="actRows"></tbody>
    </table></div>

    <div class="grid" style="margin-top:14px">
      <div><label class="chk"><input type="checkbox" id="capa"> يتطلب إجراءً تصحيحياً رسمياً (SH-008)</label><div class="small" style="margin-top:4px">عند تكرار الشكوى أو وجود سبب في النظام.</div></div>
      <div id="capaBox"><label class="f req" for="devNo">رقم الانحراف</label><div style="display:flex;gap:6px"><input type="text" id="devNo" class="ltr-in" placeholder="DEV-…"><a class="btn ghost small" id="devLink" target="_blank" style="white-space:nowrap;text-decoration:none;padding-top:6px">فتح انحراف</a></div></div>
    </div>

    <h3>الإبلاغات</h3>
    <div class="grid">
      <div><label class="f" for="notifSup">الإبلاغ للمورد / المصنع</label><input type="date" id="notifSup"></div>
      <div><label class="f" for="notifEda" id="edaLbl">الإبلاغ لهيئة الدواء</label><input type="date" id="notifEda"></div>
    </div>

    <h3>الرد على العميل</h3>
    <div class="grid">
      <div style="grid-column:span 2"><label class="f req" for="reply">الرد والإجراء المتخذ</label><textarea id="reply"></textarea></div>
      <div><label class="f req" for="replyDate">تاريخ الرد</label><input type="date" id="replyDate"></div>
      <div><label class="f" for="commercial">التصحيح التجاري</label><select id="commercial"><option value="">لا يوجد</option><option>استبدال</option><option>إشعار خصم بعد مرتجع (SH-002)</option><option>اعتذار فقط</option></select></div>
      <div><label class="f req">رضا العميل</label><span class="yn" id="satYN"><button type="button" data-v="Y">راضٍ</button><button type="button" data-v="N">غير راضٍ</button></span></div>
    </div>
    <div id="escBox" hidden style="margin-top:12px"><label class="f req" for="escalation">قرار المدير التجاري بعد التصعيد</label><textarea id="escalation"></textarea></div>
    <div class="signed" id="cSigned" hidden></div>
  </section>
</fieldset>

  <section class="card">
    <div class="actions">
      <button type="button" class="btn" data-act="save" id="btnSave">حفظ</button>
      <button type="button" class="btn gold" data-act="report" id="btnReport">تحويل لإدارة الجودة</button>
      <button type="button" class="btn gold" data-act="classify" id="btnClassify" hidden>اعتماد التصنيف</button>
      <button type="button" class="btn okb" data-act="close" id="btnClose" hidden>إغلاق الشكوى</button>
      <button type="button" class="btn ghost" data-act="reopen" id="btnReopen" hidden>إعادة فتح</button>
      <button type="button" class="btn ghost" data-act="print">طباعة</button>
      <span style="flex:1"></span>
      <button type="button" class="btn ghost" data-act="new">شكوى جديدة</button>
    </div>
    <div id="msg"></div>
  </section>

  <section class="card">
    <h2>سجل التعديلات</h2>
    <div class="audit-wrap"><table class="audit"><tbody id="audit"></tbody></table></div>
  </section>
</div>

<aside>
  <section class="card">
    <h2 style="font-size:15px">ملخص</h2>
    <div class="kv"><span>رقم الشكوى</span><b id="sNo">—</b></div>
    <div class="kv"><span>العميل</span><span id="sCust">—</span></div>
    <div class="kv"><span>الأولوية</span><span id="sPrio">—</span></div>
    <div class="kv"><span>المرحلة</span><span id="sStage">—</span></div>
    <div class="kv"><span>موعد الإغلاق</span><span id="sDue">—</span></div>
    <div class="kv"><span>المطلوب الآن</span><span id="sNext" style="text-align:end">—</span></div>
    <div id="sLate"></div>
  </section>
  <section class="card">
    <h2 style="font-size:15px">الشكاوى المحفوظة</h2>
    <a id="regLink" class="btn ghost small" style="margin-bottom:8px;display:inline-block;text-decoration:none">سجل الشكاوى والتحليل (F03)</a>
    <div class="saved" id="saved"></div>
  </section>
</aside>
</div>

<div id="doc"></div>
<datalist id="peopleList"></datalist>
@endverbatim
@endsection

@push('scripts')
<script>
const BOOT = {!! json_encode($boot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!};
</script>
<script>
@verbatim
/* =========================================================
   الإعدادات
   ========================================================= */
const FORM_CODE = 'SH-007-F01 · F02';
const API = BOOT.urls.records;
const SET = BOOT.settings || {};
const QA = !!BOOT.can.finalize;                   // الجودة · المدير الصيدلي · مدير المخزن
const CLOSE_DAYS = {                              // مدد الإغلاق بأيام العمل (SH-007)
  critical: Number(SET.cmp_days_critical ?? 5),
  major:    Number(SET.cmp_days_major ?? 3),
  minor:    Number(SET.cmp_days_minor ?? 7),
};
const PRIO = { critical: ['حرجة', 'b-bad'], major: ['رئيسية', 'b-warn'], minor: ['ثانوية', 'b-draft'] };
const STAGES = {
  draft:         ['مسودة', 'b-draft', 'استكمال بيانات الشكوى وتحويلها لإدارة الجودة في نفس اليوم'],
  reported:      ['بانتظار التصنيف', 'b-warn', 'الجودة والمدير الصيدلي: النوع والأولوية والمسؤول'],
  investigating: ['قيد التحقيق', 'b-warn', 'السبب الجذري · الإجراءات · الرد على العميل'],
  closed:        ['مغلقة', 'b-ok', '—'],
};
const CHANNELS = ['تليفون', 'مكتوب', 'إلكتروني (واتساب / بريد)', 'المندوب', 'رابط الصيدليات'];
const TYPES = ['جودة منتج', 'اشتباه غش', 'أثر جانبي', 'خدمة توزيع (تأخير · تلف · خطأ تحضير)', 'تجارية (سعر · فاتورة · تحصيل)', 'سلوك أحد العاملين'];
const PRODUCT_TYPES = ['جودة منتج', 'اشتباه غش', 'أثر جانبي'];
const DEPTS = ['المخزن', 'الاستلام', 'التوزيع والحركة', 'المبيعات', 'التجاري والمشتريات', 'إدخال البيانات', 'الحسابات', 'إدارة الجودة', 'المدير الصيدلي', 'الموارد البشرية', 'المورد / المصنع'];
const DOCS = ['فاتورة البيع', 'بيان الحركة', 'تقرير أخطاء التحضير', 'سجلات الحرارة', 'سجل الاستلام', 'سجل المرتجعات', 'العينة'];

/* =========================================================
   أدوات
   ========================================================= */
const $ = s => document.querySelector(s);
const pad = (n, l = 2) => String(n).padStart(l, '0');
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const iso = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const today = () => iso(new Date());
const nowTime = () => { const d = new Date(); return `${pad(d.getHours())}:${pad(d.getMinutes())}`; };
const nowStr = () => `${today()} ${nowTime()}`;
const fmtDate = s => { if (!s) return ''; const [y, m, d] = s.split('-'); return `<span class="dt">${d}/${m}/${y}</span>`; };
const fmtMonth = s => { if (!s) return ''; const [y, m] = s.split('-'); return `${m}/${y}`; };
const uid = () => Math.random().toString(36).slice(2, 10);
const parse = s => new Date(s + 'T00:00:00');
const daysBetween = (a, b) => Math.round((parse(b) - parse(a)) / 86400000);
function addWorkDays(from, n) { const d = parse(from); let left = n; while (left > 0) { d.setDate(d.getDate() + 1); if (d.getDay() !== 5) left--; } return iso(d); }
const isProduct = () => PRODUCT_TYPES.includes(C.type);

/* =========================================================
   الخادم
   ========================================================= */
let LIST = [];
let LOGS = [];
const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

async function api(method, url, body) {
  const res = await fetch(url, {
    method,
    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
    credentials: 'same-origin',
    body: body ? JSON.stringify(body) : undefined
  });
  let json = {};
  try { json = await res.json(); } catch (e) {}
  if (!res.ok) {
    if (res.status === 419) throw ['انتهت الجلسة. حدّث الصفحة وسجّل الدخول مرة أخرى.'];
    if (res.status === 401) throw ['انتهت الجلسة. سجّل الدخول مرة أخرى.'];
    if (json.errors) throw Object.values(json.errors).flat();
    throw [json.message || ('خطأ من الخادم (' + res.status + ')')];
  }
  return json;
}
async function refreshList() {
  try { LIST = (await api('GET', API)).records || []; } catch (e) { LIST = []; showMsg(e); }
  renderSaved();
}
function busy(on) { document.querySelector('.scr').classList.toggle('busy', !!on); }

/* =========================================================
   الحالة
   ========================================================= */
let C = null;
let pendingDelete = null;

function nextNo(date) {
  const y = (date || today()).slice(0, 4);
  const prefix = `CMP-${y}-`;
  const nums = LIST.filter(m => m.number && m.number.startsWith(prefix)).map(m => parseInt(m.number.slice(prefix.length), 10) || 0);
  return prefix + pad((nums.length ? Math.max(...nums) : 0) + 1, 3);
}

function newProd() { return { id: uid(), name: '', batch: '', exp: '', qty: '', sample: '', note: '' }; }
function newAction() { return { id: uid(), text: '', type: 'تصحيحي', owner: '', due: '', done: '', ok: '' }; }

function blankCmp() {
  return {
    id: null, no: nextNo(today()), autoNo: true, date: today(), time: nowTime(), channel: '', receiver: BOOT.me.name,
    customer: '', custCode: '', custType: '', phone: '', invoice: '', details: '', prods: [newProd()],
    type: '', dept: '', owner: '', prio: '', closeDue: '', closeDueAuto: true, hold: '', immediate: '', immAt: '',
    ackDate: '', docs: [], rootCause: '', actions: [newAction()], capa: false, devNo: '',
    notifSup: '', notifEda: '', reply: '', replyDate: '', commercial: '', sat: '', escalation: '',
    stage: 'draft', sign: {}, status: 'draft'
  };
}

/* =========================================================
   الحسابات
   ========================================================= */
function activeActions() { return C.actions.filter(a => a.text.trim()); }

function autoDates() {
  if (C.closeDueAuto && C.prio) C.closeDue = addWorkDays(C.date || today(), CLOSE_DAYS[C.prio]);
}

function immHours() {
  if (!C.immAt || !C.date) return null;
  const rec = new Date(`${C.date}T${C.time || '00:00'}`);
  return (new Date(C.immAt) - rec) / 3600000;
}

function lateInfo() {
  const t = today(), out = [];
  const st = C.status === 'final' ? 'closed' : C.stage;
  if (st === 'draft' && C.date && C.date < t) out.push('لم تُحوّل لإدارة الجودة في نفس اليوم');
  if (st !== 'closed' && C.closeDue && C.closeDue < t) out.push(`تجاوزت موعد الإغلاق بـ ${daysBetween(C.closeDue, t)} يوم: تُصعّد للمدير العام`);
  if (C.prio === 'critical') {
    const h = immHours();
    if (h === null && st !== 'draft') out.push('شكوى حرجة: سجل الإجراء الفوري (خلال ٢٤ ساعة)');
    else if (h !== null && h > 24) out.push(`الإجراء الفوري بعد ${Math.round(h)} ساعة (المطلوب ٢٤ ساعة)`);
  }
  activeActions().forEach((a, i) => { if (!a.done && a.due && a.due < t && st !== 'closed') out.push(`الإجراء ${i + 1} متأخر ${daysBetween(a.due, t)} يوم`); });
  return out;
}

/* =========================================================
   الرسم
   ========================================================= */
const FIELDS = ['no', 'date', 'time', 'channel', 'receiver', 'customer', 'custCode', 'custType', 'phone', 'invoice', 'details',
  'type', 'dept', 'owner', 'closeDue', 'immediate', 'immAt', 'ackDate', 'rootCause', 'devNo', 'notifSup', 'notifEda',
  'reply', 'replyDate', 'commercial', 'escalation'];
const YN = { holdYN: 'hold', satYN: 'sat' };

function fillSelect(id, list, empty) {
  $('#' + id).innerHTML = (empty ? `<option value="">${empty}</option>` : '') + list.map(x => `<option>${esc(x)}</option>`).join('');
}

function render() {
  FIELDS.forEach(k => { $('#' + k).value = C[k] ?? ''; });
  document.querySelectorAll('input[name=prio]').forEach(r => { r.checked = r.value === C.prio; });
  $('#capa').checked = !!C.capa;
  const P = C.source === 'portal' ? (C.portal || {}) : null;
  $('#portalInfo').hidden = !P;
  $('#portalInfo').innerHTML = P ? `<b>من رابط الصيدليات</b> · ${esc(P.pharmacy)} (<span class="dt">${esc(P.code)}</span>)${P.address ? ' · ' + esc(P.address) : ''}<br>
    الصيدلي: <b>${esc(P.pharmacist)}</b> · موبايل: <a href="tel:${esc(P.mobile)}" class="dt">${esc(P.mobile)}</a> · <a href="https://wa.me/2${esc(P.mobile)}" target="_blank">واتساب</a> · أُرسلت <span class="dt">${esc(P.at)}</span>` : '';
  $('#docs').innerHTML = DOCS.map(d => `<label class="chip"><input type="checkbox" value="${esc(d)}" ${C.docs.includes(d) ? 'checked' : ''}> ${esc(d)}</label>`).join('');
  renderProds(); renderActions(); applyState(); refresh(); renderSaved(); renderAudit();
}

function applyState() {
  const final = C.status === 'final';
  const st = final ? 'closed' : C.stage;
  $('#fsA').disabled = final || !(st === 'draft' || QA);
  $('#fsB').disabled = final || !QA || st === 'draft';
  $('#fsC').disabled = final || st !== 'investigating';

  $('#cardB').classList.toggle('dim', st === 'draft');
  $('#cardC').classList.toggle('dim', ['draft', 'reported'].includes(st));

  const can = (stage, qaOnly) => !final && st === stage && (!qaOnly || QA);
  $('#btnSave').hidden = final || (st === 'reported' && !QA);
  $('#btnReport').hidden = !can('draft');
  $('#btnClassify').hidden = !can('reported', true);
  $('#btnClose').hidden = !can('investigating', true);
  $('#btnReopen').hidden = !final || !BOOT.can.reopen;

  const [label, cls] = STAGES[st];
  $('#badge').className = 'badge ' + cls; $('#badge').textContent = label;
  const order = Object.keys(STAGES);
  document.querySelectorAll('#track li').forEach(li => {
    const i = order.indexOf(li.dataset.s), cur = order.indexOf(st);
    li.className = i < cur ? 'done' : i === cur ? 'cur' : '';
  });
  const sg = (id, key) => { const s = C.sign[key]; $(id).hidden = !s; $(id).textContent = s ? `${s.label}: ${s.by} في ${s.at}` : ''; };
  sg('#aSigned', 'report'); sg('#bSigned', 'classify'); sg('#cSigned', 'close');
}

function renderProds() {
  $('#prodRows').innerHTML = C.prods.map((p, i) => `
    <tr data-id="${p.id}">
      <td class="n">${i + 1}</td>
      <td><input type="text" data-f="name" value="${esc(p.name)}"></td>
      <td><input type="text" data-f="batch" value="${esc(p.batch)}" class="ltr-in"></td>
      <td><input type="month" data-f="exp" value="${esc(p.exp)}" style="width:100%;padding:5px 6px;border:1px solid var(--line);border-radius:8px;font:inherit;font-size:13px;direction:ltr"></td>
      <td><input type="number" data-f="qty" value="${esc(p.qty)}" min="0" step="any"></td>
      <td><select data-f="sample"><option value="">—</option>${['نعم · لدى العميل', 'نعم · استُلمت', 'لا'].map(x => `<option ${x === p.sample ? 'selected' : ''}>${x}</option>`).join('')}</select></td>
      <td><input type="text" data-f="note" value="${esc(p.note)}"></td>
      <td><button type="button" class="x" data-act="del-prod" title="حذف">×</button></td>
    </tr>`).join('');
}

function renderActions() {
  const t = today();
  $('#actRows').innerHTML = C.actions.map((a, i) => `
    <tr data-id="${a.id}" class="${!a.done && a.due && a.due < t ? 'diff' : ''}">
      <td class="n">${i + 1}</td>
      <td><input type="text" data-f="text" value="${esc(a.text)}"></td>
      <td><select data-f="type">${['تصحيحي', 'وقائي'].map(x => `<option ${x === a.type ? 'selected' : ''}>${x}</option>`).join('')}</select></td>
      <td><input type="text" data-f="owner" value="${esc(a.owner)}" list="peopleList"></td>
      <td><input type="date" data-f="due" value="${esc(a.due)}"></td>
      <td><input type="date" data-f="done" value="${esc(a.done)}"></td>
      <td><select data-f="ok"><option value="">—</option>${['تم', 'لم يتم'].map(x => `<option ${x === a.ok ? 'selected' : ''}>${x}</option>`).join('')}</select></td>
      <td><button type="button" class="x" data-act="del-action" title="حذف">×</button></td>
    </tr>`).join('');
}

function paintYN(box, v) {
  box.querySelectorAll('button').forEach(b => { b.className = b.dataset.v !== v ? '' : v === 'Y' ? 'on-ok' : 'on-bad'; });
}

function refresh() {
  const prod = isProduct();
  $('#prodBox').hidden = !prod;
  $('#capaBox').hidden = !C.capa;
  $('#escBox').hidden = C.sat !== 'N';
  paintYN($('#holdYN'), C.hold); paintYN($('#satYN'), C.sat);
  $('#immLbl').className = 'f' + (C.prio === 'critical' ? ' req' : '');
  $('#edaLbl').textContent = ['اشتباه غش', 'أثر جانبي'].includes(C.type) ? 'الإبلاغ لهيئة الدواء (مطلوب)' : 'الإبلاغ لهيئة الدواء';
  const route = { 'اشتباه غش': 'اشتباه غش: حجز التشغيلة والتعامل طبقاً لإجراء SH-011، وإبلاغ المورد / المصنع وهيئة الدواء.',
    'أثر جانبي': 'أثر جانبي: إبلاغ الشركة المصنعة والمركز المصري لليقظة الدوائية بهيئة الدواء، وتسجيل الإبلاغ.',
    'جودة منتج': 'جودة منتج: مراجعة باقي التشغيلة بالمخزن وحجزها عند وجود خطر، وتقييم الحاجة للاستدعاء (SH-010)، وإبلاغ المورد.' }[C.type];
  $('#routeInfo').hidden = !route; $('#routeInfo').textContent = route || '';
  const h = immHours();
  $('#immMsg').textContent = h === null ? 'للحرجة: خلال ٢٤ ساعة من الاستلام' : `بعد ${Math.max(0, Math.round(h))} ساعة من الاستلام`;
  $('#immMsg').className = 'tmsg ' + (h === null ? '' : h <= 24 ? 'ok' : 'bad');
  $('#closeDueMsg').textContent = C.prio ? (C.closeDueAuto ? `تلقائي: ${CLOSE_DAYS[C.prio]} أيام عمل من الاستلام` : 'تم تعديله يدوياً') : 'يُحسب بعد تحديد الأولوية';
  $('#closeDue').value = C.closeDue || '';
  const q = new URLSearchParams({ ref: C.no, source: 'شكوى (SH-007)', sop: 'SH-007 الشكاوى' });
  $('#devLink').href = (BOOT.urls.deviation || '#') + '?' + q.toString();
  $('#regLink').href = BOOT.urls.register || '#';

  const st = C.status === 'final' ? 'closed' : C.stage;
  $('#sNo').textContent = C.no || '—';
  $('#sCust').textContent = C.customer || '—';
  $('#sPrio').innerHTML = C.prio ? `<span class="badge ${PRIO[C.prio][1]}" style="padding:1px 10px">${PRIO[C.prio][0]}</span>` : '—';
  $('#sStage').textContent = STAGES[st][0];
  $('#sDue').innerHTML = C.closeDue ? fmtDate(C.closeDue) : '—';
  $('#sNext').textContent = STAGES[st][2];
  const late = lateInfo();
  $('#sLate').innerHTML = late.length ? `<ul class="nc-list">${late.map(x => `<li>${esc(x)}</li>`).join('')}</ul>` : '';
}

/* =========================================================
   الأحداث
   ========================================================= */
function bind() {
  FIELDS.forEach(k => $('#' + k).addEventListener('input', e => {
    C[k] = e.target.value;
    if (k === 'no') C.autoNo = false;
    if (k === 'date') { if (C.autoNo) { C.no = nextNo(C.date); $('#no').value = C.no; } autoDates(); }
    if (k === 'closeDue') C.closeDueAuto = !C.closeDue;
    if (k === 'type' && ['اشتباه غش', 'أثر جانبي'].includes(C.type) && !C.prio) {   // خطر محتمل على المريض
      C.prio = 'critical'; document.querySelectorAll('input[name=prio]').forEach(r => { r.checked = r.value === 'critical'; }); autoDates();
    }
    refresh();
  }));
  document.querySelectorAll('input[name=prio]').forEach(r => r.addEventListener('change', e => { C.prio = e.target.value; autoDates(); refresh(); }));
  $('#capa').addEventListener('change', e => { C.capa = e.target.checked; refresh(); });
  $('#docs').addEventListener('change', () => { C.docs = [...document.querySelectorAll('#docs input:checked')].map(x => x.value); });
  Object.keys(YN).forEach(id => $('#' + id).addEventListener('click', e => {
    const b = e.target.closest('button[data-v]'); if (!b || b.closest('fieldset').disabled) return;
    C[YN[id]] = b.dataset.v; refresh();
  }));
  const rowEdit = (sel, arr) => {
    $(sel).addEventListener('input', e => {
      const host = e.target.closest('[data-id]'); const f = e.target.dataset.f; if (!host || !f) return;
      const it = C[arr].find(x => x.id === host.dataset.id); if (!it) return;
      it[f] = e.target.value; refresh();
    });
    $(sel).addEventListener('change', e => { if (e.target.matches('select')) e.target.dispatchEvent(new Event('input', { bubbles: true })); });
  };
  rowEdit('#prodRows', 'prods'); rowEdit('#actRows', 'actions');
}

/* =========================================================
   التحقق
   ========================================================= */
function vBasic() {
  const e = [];
  if (!C.no.trim()) e.push('رقم الشكوى مطلوب');
  if (LIST.some(m => m.number === C.no && m.id !== C.id)) e.push(`رقم الشكوى ${C.no} مستخدم في شكوى أخرى`);
  if (!C.date) e.push('تاريخ الاستلام مطلوب');
  return e;
}
function vReport() {
  const e = vBasic();
  if (C.date > today()) e.push('تاريخ الاستلام في المستقبل');
  if (!C.time) e.push('وقت الاستلام مطلوب');
  if (!C.channel) e.push('اختر وسيلة الاستلام');
  if (!C.receiver.trim()) e.push('اسم متلقي الشكوى مطلوب');
  if (!C.customer.trim()) e.push('اسم العميل مطلوب');
  if (!C.custType) e.push('اختر نوع العميل');
  if (!C.details.trim()) e.push('اكتب تفاصيل الشكوى');
  C.prods.filter(p => p.name.trim()).forEach(p => { if (!p.batch.trim()) e.push(`التشغيلة مطلوبة للصنف «${p.name}» (أو اطلبها من العميل)`); });
  return e;
}
function vClassify() {
  const e = vReport();
  if (!C.type) e.push('اختر نوع الشكوى');
  if (!C.dept) e.push('اختر القسم المعني');
  if (!C.owner.trim()) e.push('حدد المسؤول عن الشكوى');
  if (!C.prio) e.push('حدد الأولوية');
  if (!C.closeDue) e.push('حدد موعد الإغلاق');
  if (isProduct()) {
    if (!C.prods.some(p => p.name.trim())) e.push('شكوى منتج: أدخل الصنف والتشغيلة');
    if (!C.hold) e.push('حدد هل تم حجز باقي التشغيلة');
  }
  if (C.prio === 'critical' && !C.immediate.trim()) e.push('شكوى حرجة: اكتب الإجراء الفوري المتخذ');
  if (C.prio === 'critical' && !C.immAt) e.push('شكوى حرجة: سجل وقت الإجراء الفوري');
  return e;
}
function vClose() {
  const e = vClassify();
  if (!C.ackDate) e.push('سجل تاريخ علم المسؤول بالشكوى');
  if (!C.docs.length) e.push('حدد المستندات التي تمت مراجعتها');
  if (!C.rootCause.trim()) e.push('اكتب السبب الجذري');
  const acts = activeActions();
  if (!acts.length) e.push('أضف إجراءً تصحيحياً واحداً على الأقل');
  acts.forEach((a, i) => {
    if (!a.owner.trim() || !a.due) e.push(`حدد المسؤول والموعد للإجراء ${i + 1}`);
    if (!a.done) e.push(`سجل تاريخ تنفيذ الإجراء ${i + 1}`);
    if (a.ok !== 'تم') e.push(`تحقق من تنفيذ الإجراء ${i + 1}`);
  });
  if (C.capa && !C.devNo.trim()) e.push('اكتب رقم الانحراف (SH-008)');
  if (['اشتباه غش', 'أثر جانبي'].includes(C.type) && !C.notifEda) e.push('سجل تاريخ الإبلاغ لهيئة الدواء');
  if (['اشتباه غش', 'جودة منتج'].includes(C.type) && !C.notifSup) e.push('سجل تاريخ الإبلاغ للمورد / المصنع');
  if (!C.reply.trim()) e.push('اكتب الرد على العميل');
  if (!C.replyDate) e.push('سجل تاريخ الرد');
  if (!C.sat) e.push('حدد رضا العميل');
  if (C.sat === 'N' && !C.escalation.trim()) e.push('العميل غير راضٍ: سجل قرار المدير التجاري بعد التصعيد');
  return e;
}

function showMsg(errs, ok) {
  $('#msg').innerHTML = errs && errs.length ? `<div class="errors">لم يتم الحفظ:<ul>${errs.map(x => `<li>${esc(x)}</li>`).join('')}</ul></div>`
    : ok ? `<div class="okmsg">${esc(ok)}</div>` : '';
}

function recordTitle() {
  const d = C.details.trim().replace(/\s+/g, ' ');
  return [C.customer, d.length > 60 ? d.slice(0, 60) + '…' : d].filter(Boolean).join(' · ');
}

async function persist(status) {
  const body = { number: C.no.trim(), title: recordTitle(), record_date: C.date || null, status, data: Object.assign({}, C, { status }) };
  const res = C.id ? await api('PUT', `${API}/${C.id}`, body) : await api('POST', API, body);
  loadRecord(res.record);
  await refreshList();
}

function loadRecord(rec) {
  C = Object.assign(blankCmp(), rec.data || {});
  C.id = rec.id; C.no = rec.number; C.status = rec.status; C.autoNo = false;
  if (C.status !== 'final' && C.stage === 'closed') { C.stage = 'investigating'; C.sign = Object.assign({}, C.sign); delete C.sign.close; }
  LOGS = rec.logs || [];
  render();
}

async function move(stage, signKey, signLabel, status, okText) {
  const snap = JSON.stringify(C);
  C.stage = stage;
  if (signKey) C.sign = Object.assign({}, C.sign, { [signKey]: { label: signLabel, by: BOOT.me.name, at: nowStr() } });
  busy(true);
  try { await persist(status); showMsg(null, okText); }
  catch (err) { C = JSON.parse(snap); showMsg(err); }
  finally { busy(false); }
}

document.addEventListener('click', async e => {
  const b = e.target.closest('[data-act]'); if (!b) return;
  const a = b.dataset.act;

  if (a === 'add-prod') { C.prods.push(newProd()); renderProds(); }
  if (a === 'del-prod') { const id = b.closest('[data-id]').dataset.id; C.prods = C.prods.filter(x => x.id !== id); if (!C.prods.length) C.prods.push(newProd()); renderProds(); }
  if (a === 'add-action') { C.actions.push(newAction()); renderActions(); }
  if (a === 'del-action') { const id = b.closest('[data-id]').dataset.id; C.actions = C.actions.filter(x => x.id !== id); if (!C.actions.length) C.actions.push(newAction()); renderActions(); refresh(); }

  if (a === 'save') {
    const errs = vBasic(); if (errs.length) return showMsg(errs);
    busy(true);
    try { await persist('draft'); showMsg(null, `تم حفظ الشكوى ${C.no}`); } catch (err) { showMsg(err); } finally { busy(false); }
  }
  if (a === 'report') { const errs = vReport(); if (errs.length) return showMsg(errs); move('reported', 'report', 'سجلها وحوّلها', 'draft', 'تم تحويل الشكوى لإدارة الجودة.'); }
  if (a === 'classify') { const errs = vClassify(); if (errs.length) return showMsg(errs); move('investigating', 'classify', 'التصنيف', 'draft', `تم اعتماد التصنيف. موعد الإغلاق ${C.closeDue}.`); }
  if (a === 'close') { const errs = vClose(); if (errs.length) return showMsg(errs); move('closed', 'close', 'الإغلاق', 'final', `تم إغلاق الشكوى ${C.no}.`); }
  if (a === 'reopen') {
    busy(true);
    try { const res = await api('POST', `${API}/${C.id}/reopen`); loadRecord(res.record); await refreshList(); showMsg(null, 'أُعيد فتح الشكوى.'); }
    catch (err) { showMsg(err); } finally { busy(false); }
  }
  if (a === 'print') { buildDoc(); window.print(); }
  if (a === 'new') { C = blankCmp(); LOGS = []; render(); showMsg(); window.scrollTo(0, 0); }
  if (a === 'open') {
    busy(true);
    try { const res = await api('GET', `${API}/${b.dataset.id}`); loadRecord(res.record); showMsg(); window.scrollTo(0, 0); }
    catch (err) { showMsg(err); } finally { busy(false); }
  }
  if (a === 'del') {
    const id = Number(b.dataset.id);
    if (pendingDelete !== id) { pendingDelete = id; renderSaved(); return; }
    pendingDelete = null; busy(true);
    try { await api('DELETE', `${API}/${id}`); if (C.id === id) { C = blankCmp(); LOGS = []; } await refreshList(); render(); showMsg(null, 'تم حذف المسودة.'); }
    catch (err) { showMsg(err); renderSaved(); } finally { busy(false); }
  }
  if (a === 'cancel-del') { pendingDelete = null; renderSaved(); }
});

function renderSaved() {
  $('#saved').innerHTML = LIST.length ? LIST.map(m => `
    <div class="row ${m.id === C.id ? 'cur' : ''}">
      <div><b>${esc(m.number)}</b> <span class="badge ${m.status === 'final' ? 'b-ok' : 'b-draft'}" style="padding:1px 8px;font-size:11px">${m.status === 'final' ? 'مغلقة' : 'مفتوحة'}</span><div class="small">${esc(m.title || '')} · ${fmtDate(m.record_date)}</div></div>
      <div class="actions" style="gap:4px">
        ${pendingDelete === m.id
          ? `<button type="button" class="btn danger small" data-act="del" data-id="${m.id}">تأكيد الحذف</button><button type="button" class="btn ghost small" data-act="cancel-del">إلغاء</button>`
          : `<button type="button" class="btn ghost small" data-act="open" data-id="${m.id}">فتح</button>${m.status !== 'final' ? `<button type="button" class="x" data-act="del" data-id="${m.id}" title="حذف">×</button>` : ''}`}
      </div>
    </div>`).join('') : '<div class="small">لا توجد شكاوى محفوظة.</div>';
}

function renderAudit() {
  $('#audit').innerHTML = LOGS.length
    ? LOGS.map(x => `<tr><td class="tm">${esc(x.t)}</td><td>${esc(x.user)}</td><td><b>${esc(x.action)}</b></td><td>${esc(x.detail || '')}</td></tr>`).join('')
    : '<tr><td class="small" style="padding:10px">لم تُحفظ الشكوى بعد.</td></tr>';
}

/* =========================================================
   نسخة الطباعة
   ========================================================= */
function buildDoc() {
  const logo = document.querySelector('header .brand svg').outerHTML.replace('width="34" height="37"', 'width="38" height="42"').replace('fill="#ffffff"', 'fill="#0f5e5a"');
  const st = C.status === 'final' ? 'closed' : C.stage;
  const nl = s => esc(s).replace(/\n/g, '<br>');
  const prods = C.prods.filter(p => p.name.trim());
  const acts = activeActions();
  const sig = k => C.sign[k] ? `${esc(C.sign[k].by)}<br><span class="dt" style="font-size:10px">${esc(C.sign[k].at)}</span>` : '';
  $('#doc').innerHTML = `
    <div class="d-head">
      <div class="d-brand">${logo}<div><div class="ar">إتقان</div><div class="sl">إتقان في كل خطوة</div></div></div>
      <div class="d-title"><div class="t1">شكوى عميل</div><div class="t2">Customer Complaint</div></div>
      <div class="d-tenant">${BOOT.tenant.logo ? `<img src="${esc(BOOT.tenant.logo)}" alt="">` : ''}<div><b>${esc(BOOT.tenant.name_ar)}</b><span>${esc(BOOT.tenant.name_en)}</span></div></div>
    </div>
    <div class="d-code" style="display:flex;gap:18px;justify-content:space-between;margin:-4px 0 8px">
      <span>رقم الشكوى: <b>${esc(C.no)}</b></span><span>النموذج: <b>${FORM_CODE}</b></span><span>الحالة: ${STAGES[st][0]}</span>
    </div>

    <div class="d-h2">الجزء أ · استلام الشكوى · SH-007-F01</div>
    <table class="d">
      <tr><td class="k">تاريخ ووقت الاستلام</td><td>${fmtDate(C.date)} <span class="dt">${esc(C.time)}</span></td><td class="k">وسيلة الاستلام</td><td>${esc(C.channel)}</td></tr>
      <tr><td class="k">العميل</td><td>${esc(C.customer)}${C.custCode ? ' · <span class="dt">' + esc(C.custCode) + '</span>' : ''} (${esc(C.custType)})</td><td class="k">الهاتف</td><td class="num">${esc(C.phone) || '—'}</td></tr>
      <tr><td class="k">متلقي الشكوى</td><td>${esc(C.receiver)}</td><td class="k">فاتورة البيع</td><td class="num">${esc(C.invoice) || '—'}</td></tr>
      <tr><td class="k">تفاصيل الشكوى</td><td colspan="3">${nl(C.details)}</td></tr>
    </table>
    ${prods.length ? `<table class="d" style="margin-top:6px"><tr><th style="width:4%">م</th><th>الصنف والتركيز</th><th>التشغيلة</th><th>الصلاحية</th><th>الكمية</th><th>العينة</th><th>ملاحظات</th></tr>
      ${prods.map((p, i) => `<tr><td>${i + 1}</td><td>${esc(p.name)}</td><td class="num">${esc(p.batch)}</td><td class="num">${fmtMonth(p.exp)}</td><td class="num">${esc(p.qty)}</td><td>${esc(p.sample)}</td><td>${esc(p.note)}</td></tr>`).join('')}</table>` : ''}

    ${C.prio ? `<div class="keep"><div class="d-h2">الجزء ب · التصنيف والأولوية</div>
    <table class="d">
      <tr><td class="k">النوع</td><td>${esc(C.type)}</td><td class="k">الأولوية</td><td><b>${PRIO[C.prio][0]}</b></td></tr>
      <tr><td class="k">القسم / المسؤول</td><td>${esc(C.dept)} · ${esc(C.owner)}</td><td class="k">موعد الإغلاق</td><td>${fmtDate(C.closeDue)}</td></tr>
      ${isProduct() ? `<tr><td class="k">حجز التشغيلة</td><td>${C.hold === 'Y' ? 'نعم' : C.hold === 'N' ? 'لا' : '—'}</td><td class="k">وقت الإجراء الفوري</td><td class="num">${esc((C.immAt || '').replace('T', ' ')) || '—'}</td></tr>
      <tr><td class="k">الإجراء الفوري</td><td colspan="3">${nl(C.immediate) || '—'}</td></tr>` : ''}
    </table></div>` : ''}

    ${C.rootCause.trim() ? `<div class="keep"><div class="d-h2">التحقيق والإجراء التصحيحي · SH-007-F02</div>
    <table class="d">
      <tr><td class="k">علم المسؤول</td><td>${fmtDate(C.ackDate)}</td><td class="k">المستندات</td><td>${C.docs.map(esc).join('، ')}</td></tr>
      <tr><td class="k">السبب الجذري</td><td colspan="3">${nl(C.rootCause)}</td></tr>
    </table></div>` : ''}
    ${acts.length && C.rootCause.trim() ? `<table class="d" style="margin-top:6px"><tr><th style="width:4%">م</th><th>الإجراء</th><th>النوع</th><th>المسؤول</th><th>الموعد</th><th>التنفيذ</th><th>التحقق</th></tr>
      ${acts.map((a, i) => `<tr><td>${i + 1}</td><td>${esc(a.text)}</td><td>${esc(a.type)}</td><td>${esc(a.owner)}</td><td class="num">${fmtDate(a.due)}</td><td class="num">${fmtDate(a.done)}</td><td>${esc(a.ok)}</td></tr>`).join('')}</table>` : ''}

    ${C.reply.trim() ? `<div class="keep"><div class="d-h2">الإبلاغات والرد على العميل</div>
    <table class="d">
      <tr><td class="k">إبلاغ المورد / المصنع</td><td>${fmtDate(C.notifSup) || 'لا'}</td><td class="k">إبلاغ هيئة الدواء</td><td>${fmtDate(C.notifEda) || 'لا'}</td></tr>
      <tr><td class="k">الرد على العميل</td><td colspan="3">${nl(C.reply)} · ${fmtDate(C.replyDate)}</td></tr>
      <tr><td class="k">التصحيح التجاري</td><td>${esc(C.commercial) || 'لا يوجد'}</td><td class="k">رضا العميل</td><td>${C.sat === 'Y' ? 'راضٍ' : C.sat === 'N' ? 'غير راضٍ (تصعيد)' : '—'}</td></tr>
      ${C.sat === 'N' ? `<tr><td class="k">قرار المدير التجاري</td><td colspan="3">${nl(C.escalation)}</td></tr>` : ''}
      <tr><td class="k">إجراء تصحيحي رسمي</td><td colspan="3">${C.capa ? 'نعم · <span class="dt">' + esc(C.devNo) + '</span>' : 'لا'}</td></tr>
    </table></div>` : ''}

    <div class="d-signs">
      <div>متلقي الشكوى<br>${sig('report')}</div>
      <div>التصنيف (الجودة / المدير الصيدلي)<br>${sig('classify')}</div>
      <div>الإغلاق (مدير إدارة الجودة)<br>${sig('close')}</div>
    </div>
    <div class="d-foot">${esc(BOOT.tenant.name_ar)} · إتقان · نظام إدارة الجودة · ${FORM_CODE} · طبعه ${esc(BOOT.me.name)} في ${nowStr()}</div>`;
}

/* =========================================================
   البدء
   ========================================================= */
fillSelect('channel', CHANNELS, '— اختر —');
fillSelect('type', TYPES, '— اختر —');
fillSelect('dept', DEPTS, '— اختر —');
C = blankCmp();
bind();
render();
refreshList().then(async () => {
  const open = new URLSearchParams(location.search).get('open');
  if (open) {
    const m = LIST.find(x => x.number === open);
    if (m) { try { const res = await api('GET', `${API}/${m.id}`); loadRecord(res.record); return; } catch (e) { showMsg(e); } }
  }
  if (!C.id && C.autoNo) { C.no = nextNo(C.date); $('#no').value = C.no; refresh(); }
});

document.getElementById('peopleList').innerHTML = BOOT.users.map(u => `<option value="${esc(u.name)}">${esc(u.role || '')}</option>`).join('');
@endverbatim
</script>
@endpush