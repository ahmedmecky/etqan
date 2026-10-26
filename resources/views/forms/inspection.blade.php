@extends('layouts.app')

@section('title', 'التفتيش الذاتي')

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
  .meter-row{display:flex;align-items:center;gap:10px;margin:0 0 8px}
  .meter{flex:1;height:8px;background:#e9eeec;border-radius:99px;overflow:hidden}
  .meter i{display:block;height:100%;background:var(--brand);width:0;transition:width .2s}
  .filter-row{margin-bottom:6px}
  .filter-row a{color:var(--muted);text-decoration:none;font-weight:600}
  .filter-row a.on{color:var(--brand);text-decoration:underline}
  .sec-h{font-weight:700;color:var(--brand);font-size:13.5px;margin:16px 0 4px;padding-bottom:4px;border-bottom:1.5px solid var(--brand-soft)}
  .ci{display:grid;grid-template-columns:30px 1fr auto;gap:8px;padding:10px 6px;border-bottom:1px solid var(--line);border-radius:6px}
  .ci-n{color:var(--muted);font-weight:700;text-align:center;padding-top:2px}
  .ci-q{font-size:14px;line-height:1.7}
  .ci-meta{font-size:12px;color:var(--muted);margin:2px 0 6px}
  .ci-row{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-bottom:6px}
  .ci-cls{width:auto !important;min-width:130px;display:none}
  .ci.r-nc .ci-cls{display:inline-block}
  .ci-ev{font-size:13px !important}
  .ci.r-ok{background:#f7fbf8} .ci.r-nc{background:#fff5f4} .ci.r-opp{background:#fffaf0} .ci.r-na{opacity:.6}
  .seg4{display:inline-flex;border:1px solid var(--line);border-radius:8px;overflow:hidden;background:#fff}
  .seg4 button{border:0;background:none;padding:6px 12px;font:inherit;font-size:13px;font-weight:600;color:var(--muted);cursor:pointer}
  .seg4 button+button{border-inline-start:1px solid var(--line)}
  .seg4 .on-ok{background:var(--ok);color:#fff} .seg4 .on-nc{background:var(--bad);color:#fff}
  .seg4 .on-opp{background:var(--gold);color:#fff} .seg4 .on-na{background:#8a9895;color:#fff}
  fieldset:disabled .seg4 button{cursor:default}
  .fd{border:1px solid var(--line);border-inline-start:4px solid var(--line);border-radius:8px;padding:10px 12px;margin-bottom:10px}
  .fd.critical{border-inline-start-color:var(--bad)} .fd.major{border-inline-start-color:var(--warn)} .fd.minor{border-inline-start-color:#8a9895}
  .fd-h{display:flex;justify-content:space-between;gap:8px;align-items:flex-start;margin-bottom:4px}
  .fd-g{margin-top:8px;gap:10px}
  .opp-list{margin:4px 0 0;padding-inline-start:20px;font-size:14px}
  .mon{width:100%;padding:5px 6px;border:1px solid var(--line);border-radius:8px;font:inherit;font-size:13px;direction:ltr}
  .dim{opacity:.55}
@endverbatim
</style>
@endpush

@section('content')
@verbatim
<div class="scr-head">
  <h1>التفتيش الذاتي</h1>
  <span class="code">SH-012-F02 · F03 · v02</span>
  <span id="badge" class="badge b-draft">مسودة</span>
</div>
<div class="scr">
<div class="scr-main">
<fieldset id="fs">

  <!-- ١. بيانات التفتيش -->
  <section class="card">
    <h2><span class="step">١</span> بيانات التفتيش</h2>
    <div class="grid">
      <div><label class="f req" for="no">رقم التقرير</label><input type="text" id="no" class="ltr-in" autocomplete="off"></div>
      <div><label class="f req" for="type">نوع التفتيش</label><select id="type"><option value="planned">مخطط</option><option value="surprise">مفاجئ</option><option value="followup">متابعة</option></select></div>
      <div><label class="f req" for="date">تاريخ التفتيش</label><input type="date" id="date"></div>
      <div><label class="f" for="followOf">تفتيش المتابعة على التقرير</label><input type="text" id="followOf" class="ltr-in" placeholder="SI-…"></div>
    </div>
    <div class="grid" style="margin-top:14px">
      <div style="grid-column:span 2"><label class="f req" for="process">العملية / الإجراء المُفتش عليه</label><select id="process"></select></div>
      <div><label class="f req" for="dept">القسم</label><select id="dept"></select></div>
      <div><label class="f req" for="deptMgr">مدير القسم</label><input type="text" id="deptMgr" list="peopleList"></div>
    </div>
    <div class="grid" style="margin-top:14px">
      <div><label class="f req" for="lead">المفتش الرئيسي</label><input type="text" id="lead" list="peopleList"></div>
      <div style="grid-column:span 2"><label class="f" for="inspectors">المفتشون الآخرون</label><input type="text" id="inspectors" placeholder="الأسماء مفصولة بفاصلة"></div>
    </div>
    <label class="chk" style="margin-top:12px"><input type="checkbox" id="independent"> أقر بأن المفتشين لا يفتشون على قسمهم أو عملهم (استقلال المفتش)</label>
  </section>

  <!-- ٢. قائمة الفحص -->
  <section class="card">
    <h2><span class="step">٢</span> قائمة الفحص <span class="small" style="font-weight:400">(SH-012-F02 · الجزء أ)</span>
      <span style="flex:1"></span>
      <button type="button" class="btn ghost small add" data-act="load-bank" id="btnBank">تحميل بنود الإجراء</button>
      <button type="button" class="btn ghost small add" data-act="add-item">+ بند إضافي</button>
    </h2>
    <p class="sub">البنود مأخوذة من خطوات الإجراء. سجل الدليل (مستند، صورة، رقم سجل) لكل بند، واحذف ما لا ينطبق أو ضع «لا ينطبق».</p>
    <div class="meter-row"><div class="meter"><i id="meter"></i></div><span class="small" id="meterTxt"></span></div>
    <div class="filter-row small" id="filterRow">
      عرض: <a href="#" data-flt="all" class="on">الكل</a> · <a href="#" data-flt="todo">لم يُفحص</a> · <a href="#" data-flt="nc">غير مطابق</a>
    </div>
    <div id="items"></div>
  </section>

  <!-- ٣. عينة طلبيات -->
  <section class="card">
    <h2><span class="step">٣</span> عينة طلبيات مجهزة <span class="small" style="font-weight:400">(الجزء ب · اختياري)</span>
      <span style="flex:1"></span>
      <label class="chk" style="margin:0;font-size:13px"><input type="checkbox" id="sampleOn"> إضافة عينة</label>
    </h2>
    <div id="sampleBox" hidden>
      <p class="sub">مراجعة طلبيات مجهزة مقابل فواتيرها: التشغيلة والصلاحية والكمية والموقع.</p>
      <div class="tbl-wrap"><table class="t" style="min-width:1000px">
        <thead><tr><th>م</th><th style="width:100px">رقم الطلبية</th><th style="min-width:130px">العميل</th><th style="min-width:150px">الصنف</th><th style="width:95px">تشغيلة الفاتورة</th><th style="width:95px">تشغيلة المحضّر</th><th style="width:118px">صلاحية الفاتورة</th><th style="width:118px">صلاحية المحضّر</th><th style="width:70px">الزون</th><th>النتيجة</th><th></th></tr></thead>
        <tbody id="orderRows"></tbody>
      </table></div>
      <div class="actions" style="margin-top:10px"><button type="button" class="btn ghost small add" data-act="add-order">+ طلبية</button></div>
    </div>
  </section>

  <!-- ٤. الملاحظات الإيجابية -->
  <section class="card">
    <h2><span class="step">٤</span> الملاحظات الإيجابية</h2>
    <textarea id="positives" placeholder="ما وُجد مطبقاً بشكل جيد…"></textarea>
  </section>

  <!-- ٥. حالات عدم المطابقة -->
  <section class="card" id="findCard">
    <h2><span class="step">٥</span> حالات عدم المطابقة والإجراءات <span class="small" style="font-weight:400">(SH-012-F03)</span></h2>
    <p class="sub">كل حالة عدم مطابقة تُسجل كانحراف (SH-008) ويُكتب رقمه هنا. الحالة الحرجة تحتاج إجراءً فورياً قبل انتهاء التفتيش وإبلاغ المدير الصيدلي.</p>
    <div id="findings"></div>
    <div id="opps"></div>
  </section>

  <!-- ٦. الخلاصة -->
  <section class="card">
    <h2><span class="step">٦</span> الاجتماع الختامي والخلاصة</h2>
    <div class="res" id="res"></div>
    <div class="grid" style="margin-top:14px">
      <div><label class="f req" for="closeDate">تاريخ الاجتماع الختامي</label><input type="date" id="closeDate"><div class="tmsg" id="reportMsg"></div></div>
      <div><label class="f" id="followLbl" for="followDate">موعد تفتيش المتابعة</label><input type="date" id="followDate"><div class="tmsg" id="followMsg"></div></div>
    </div>
    <div style="margin-top:12px"><label class="f" for="summary">ملاحظات الاجتماع الختامي</label><textarea id="summary"></textarea></div>
  </section>

</fieldset>

  <section class="card">
    <div class="actions">
      <button type="button" class="btn" data-act="save" id="btnSave">حفظ</button>
      <button type="button" class="btn okb" data-act="final" id="btnFinal">إصدار التقرير واعتماده</button>
      <button type="button" class="btn ghost" data-act="reopen" id="btnReopen" hidden>إعادة فتح</button>
      <button type="button" class="btn ghost" data-act="print">طباعة</button>
      <span style="flex:1"></span>
      <button type="button" class="btn ghost" data-act="new">تفتيش جديد</button>
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
    <div class="kv"><span>رقم التقرير</span><b id="sNo">—</b></div>
    <div class="kv"><span>العملية</span><span id="sProc" style="text-align:end">—</span></div>
    <div class="kv"><span>تم فحص</span><span id="sDone">0</span></div>
    <div class="kv"><span>نسبة المطابقة</span><b id="sScore">—</b></div>
    <div class="kv"><span>حرجة</span><b id="sCrit" class="c-bad">0</b></div>
    <div class="kv"><span>رئيسية</span><b id="sMaj">0</b></div>
    <div class="kv"><span>ثانوية</span><b id="sMin">0</b></div>
    <div class="kv"><span>فرص تحسين</span><b id="sOpp">0</b></div>
    <div id="sLate"></div>
  </section>
  <section class="card">
    <h2 style="font-size:15px">التفتيشات المحفوظة</h2>
    <a id="regLink" class="btn ghost small" style="margin-bottom:8px;display:inline-block;text-decoration:none">سجل التفتيش والتغطية السنوية</a>
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
const FORM_CODE = 'SH-012-F02 · F03';
const API = BOOT.urls.records;
const BANK = BOOT.urls.bank;
const REPORT_DAYS = 5;                      // إصدار التقرير خلال ٥ أيام عمل
const CRIT_FOLLOW_DAYS = 30;                // تفتيش متابعة الحرجة خلال شهر
const TYPES = { planned: 'مخطط', surprise: 'مفاجئ', followup: 'متابعة' };
const RES = { ok: 'مطابق', nc: 'غير مطابق', opp: 'فرصة تحسين', na: 'لا ينطبق' };
const CLS = { critical: 'حرجة', major: 'رئيسية', minor: 'ثانوية' };
// نفس قائمة الإجراءات في شاشة الانحرافات (لتعبئة «الإجراء المرتبط»)
const DEV_SOPS = ['', 'SH-001 الاستلام', 'SH-002 المرتجعات', 'SH-003 النظافة', 'SH-004 الصرف', 'SH-005 التخزين', 'SH-006 النقل', 'SH-007 الشكاوى', 'SH-008 الإجراءات التصحيحية', 'SH-009 إدارة المخاطر', 'SH-010 الاستدعاء', 'SH-011 المغشوش', 'SH-012 التفتيش الذاتي', 'SH-013 مراجعة الإدارة', 'SH-014 التوثيق', 'SH-015 التدريب', 'SH-016 الجرد', 'SH-017 مكافحة الحريق', 'SH-018 سلامة العاملين', 'SH-019 الكسر والانسكاب', 'SH-020 التخلص من المنتهي', 'SH-021 التوظيف', 'SH-022 الصحة والنظافة الشخصية', 'SH-023 التأهيل والمعايرة', 'SH-024 الأنظمة الحاسوبية', 'SH-025 الأنشطة الخارجية', 'SH-026 مكافحة الآفات', 'SH-027 التحكم في التغيير', 'SH-028 سياسة الصرف', 'SH-029 الأمن', 'SH-030 الحرارة وسلسلة التبريد', 'SH-031 تأهيل الموردين والعملاء', 'SH-032 الأدوية المخدرة', 'SH-033 الطوارئ واستمرارية العمل', 'SH-034 الاجتماعات'];
const DEPTS = ['المخزن', 'الاستلام', 'التوزيع والحركة', 'المبيعات', 'التجاري والمشتريات', 'إدخال البيانات', 'الحسابات', 'إدارة الجودة', 'المدير الصيدلي', 'الموارد البشرية', 'الحاسب والأنظمة', 'السلامة والأمن', 'الإدارة العليا'];

/* =========================================================
   أدوات
   ========================================================= */
const $ = s => document.querySelector(s);
const pad = (n, l = 2) => String(n).padStart(l, '0');
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const iso = d => `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
const today = () => iso(new Date());
const nowStr = () => { const d = new Date(); return `${today()} ${pad(d.getHours())}:${pad(d.getMinutes())}`; };
const fmtDate = s => { if (!s) return ''; const [y, m, d] = s.split('-'); return `<span class="dt">${d}/${m}/${y}</span>`; };
const fmtMonth = s => { if (!s) return ''; const [y, m] = s.split('-'); return `${m}/${y}`; };
const uid = () => Math.random().toString(36).slice(2, 10);
const parse = s => new Date(s + 'T00:00:00');
const daysBetween = (a, b) => Math.round((parse(b) - parse(a)) / 86400000);
function addWorkDays(from, n) { const d = parse(from); let left = n; while (left > 0) { d.setDate(d.getDate() + 1); if (d.getDay() !== 5) left--; } return iso(d); }

/* =========================================================
   الخادم
   ========================================================= */
let LIST = [];
let LOGS = [];
let PROCS = {};
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
let FLT = 'all';

function nextNo(date) {
  const y = (date || today()).slice(0, 4);
  const prefix = `SI-${y}-`;
  const nums = LIST.filter(m => m.number && m.number.startsWith(prefix)).map(m => parseInt(m.number.slice(prefix.length), 10) || 0);
  return prefix + pad((nums.length ? Math.max(...nums) : 0) + 1, 3);
}

function newItem(sec, q, ref, who, custom) {
  return { id: uid(), sec: sec || 'بنود إضافية', q: q || '', ref: ref || '', who: who || '', custom: !!custom,
    res: '', ev: '', cls: '', action: '', owner: '', due: '', devNo: '', imm: '' };
}
function newOrder() { return { id: uid(), order: '', cust: '', item: '', bInv: '', bPick: '', eInv: '', ePick: '', zone: '' }; }

function blankInsp() {
  return {
    id: null, no: nextNo(today()), autoNo: true, type: 'planned', date: today(), followOf: '',
    process: '', processTitle: '', dept: '', deptMgr: '', lead: BOOT.me.name, inspectors: '', independent: false,
    items: [], sampleOn: false, orders: [newOrder()], positives: '',
    closeDate: '', followDate: '', summary: '', issuedBy: '', issuedAt: '',
    status: 'draft'
  };
}

/* =========================================================
   الحسابات
   ========================================================= */
function orderRes(o) {
  if (!o.item.trim()) return null;
  const bad = [];
  if (o.bInv && o.bPick && o.bInv.trim() !== o.bPick.trim()) bad.push('تشغيلة مختلفة');
  if (o.eInv && o.ePick && o.eInv !== o.ePick) bad.push('صلاحية مختلفة');
  if (!(o.bInv && o.bPick && o.eInv && o.ePick)) return { ok: null, text: 'غير مكتمل' };
  return bad.length ? { ok: false, text: bad.join('، ') } : { ok: true, text: 'مطابق' };
}

function stats() {
  const it = C.items;
  const c = k => it.filter(x => x.res === k).length;
  const nc = it.filter(x => x.res === 'nc');
  const ok = c('ok');
  return {
    total: it.length, done: it.filter(x => x.res).length, ok, nc: nc.length, opp: c('opp'), na: c('na'),
    crit: nc.filter(x => x.cls === 'critical').length, maj: nc.filter(x => x.cls === 'major').length, min: nc.filter(x => x.cls === 'minor').length,
    score: ok + nc.length ? Math.round(ok / (ok + nc.length) * 100) : null,
    orders: C.sampleOn ? C.orders.map(orderRes).filter(Boolean) : []
  };
}

/* =========================================================
   الرسم
   ========================================================= */
const FIELDS = ['no', 'type', 'date', 'followOf', 'process', 'dept', 'deptMgr', 'lead', 'inspectors', 'positives', 'closeDate', 'followDate', 'summary'];

function render() {
  FIELDS.forEach(k => { $('#' + k).value = C[k] ?? ''; });
  $('#independent').checked = !!C.independent;
  $('#sampleOn').checked = !!C.sampleOn;
  renderItems(); renderOrders(); renderFindings(); applyState(); refresh(); renderSaved(); renderAudit();
}

function applyState() {
  const final = C.status === 'final';
  $('#fs').disabled = final;
  $('#btnSave').hidden = final;
  $('#btnFinal').hidden = final || !BOOT.can.finalize;
  $('#btnReopen').hidden = !final || !BOOT.can.reopen;
  $('#badge').className = 'badge ' + (final ? 'b-ok' : 'b-draft');
  $('#badge').textContent = final ? 'تقرير معتمد' : 'مسودة';
}

function resButtons(it) {
  return Object.entries(RES).map(([k, l]) => `<button type="button" data-res="${k}" class="${it.res === k ? 'on-' + k : ''}">${l}</button>`).join('');
}

function renderItems() {
  const box = $('#items');
  if (!C.items.length) { box.innerHTML = '<p class="small" style="padding:10px 0">اختر العملية ثم اضغط «تحميل بنود الإجراء»، أو أضف بنوداً يدوياً.</p>'; return; }
  let html = '', sec = null, n = 0;
  C.items.forEach(it => {
    if (it.sec !== sec) { sec = it.sec; html += `<div class="sec-h">${esc(sec)}</div>`; }
    n++;
    html += `
    <div class="ci ${it.res ? 'r-' + it.res : ''}" data-id="${it.id}">
      <div class="ci-n">${n}</div>
      <div class="ci-body">
        ${it.custom ? `<textarea data-f="q" placeholder="المتطلب" style="min-height:40px">${esc(it.q)}</textarea>` : `<div class="ci-q">${esc(it.q)}</div>`}
        <div class="ci-meta">${it.ref ? 'المرجع: ' + esc(it.ref) : ''}${it.who ? ' · المسؤول: ' + esc(it.who) : ''}</div>
        <div class="ci-row">
          <span class="seg4">${resButtons(it)}</span>
          <select data-f="cls" class="ci-cls">${['', 'critical', 'major', 'minor'].map(k => `<option value="${k}" ${it.cls === k ? 'selected' : ''}>${k ? CLS[k] : '— التصنيف —'}</option>`).join('')}</select>
        </div>
        <input type="text" data-f="ev" value="${esc(it.ev)}" class="ci-ev" placeholder="الدليل / الملاحظة (مستند، صورة، رقم سجل)">
      </div>
      <button type="button" class="x" data-act="del-item" title="حذف البند">×</button>
    </div>`;
  });
  box.innerHTML = html;
  applyFilter();
}

function paintItem(it) {
  const el = document.querySelector(`#items .ci[data-id="${it.id}"]`); if (!el) return;
  el.className = 'ci ' + (it.res ? 'r-' + it.res : '');
  el.querySelectorAll('[data-res]').forEach(b => { b.className = b.dataset.res === it.res ? 'on-' + it.res : ''; });
}

function applyFilter() {
  document.querySelectorAll('#filterRow [data-flt]').forEach(a => a.classList.toggle('on', a.dataset.flt === FLT));
  document.querySelectorAll('#items .ci').forEach(el => {
    const it = C.items.find(x => x.id === el.dataset.id);
    el.hidden = FLT === 'todo' ? !!it.res : FLT === 'nc' ? !['nc', 'opp'].includes(it.res) : false;
  });
}

function renderOrders() {
  $('#orderRows').innerHTML = C.orders.map((o, i) => `
    <tr data-id="${o.id}">
      <td class="n">${i + 1}</td>
      <td><input type="text" data-f="order" value="${esc(o.order)}" class="ltr-in"></td>
      <td><input type="text" data-f="cust" value="${esc(o.cust)}"></td>
      <td><input type="text" data-f="item" value="${esc(o.item)}"></td>
      <td><input type="text" data-f="bInv" value="${esc(o.bInv)}" class="ltr-in"></td>
      <td><input type="text" data-f="bPick" value="${esc(o.bPick)}" class="ltr-in"></td>
      <td><input type="month" data-f="eInv" value="${esc(o.eInv)}" class="mon"></td>
      <td><input type="month" data-f="ePick" value="${esc(o.ePick)}" class="mon"></td>
      <td><input type="text" data-f="zone" value="${esc(o.zone)}" class="ltr-in"></td>
      <td class="c" data-o="res">—</td>
      <td><button type="button" class="x" data-act="del-order" title="حذف">×</button></td>
    </tr>`).join('');
}

function paintOrders() {
  document.querySelectorAll('#orderRows tr[data-id]').forEach(tr => {
    const o = C.orders.find(x => x.id === tr.dataset.id); if (!o) return;
    const r = orderRes(o), cell = tr.querySelector('[data-o=res]');
    cell.textContent = r ? r.text : '—';
    cell.className = 'c ' + (!r || r.ok === null ? '' : r.ok ? 'c-ok' : 'c-bad');
    tr.classList.toggle('diff', !!r && r.ok === false);
  });
}

function devHref(it) {
  const q = new URLSearchParams({ ref: C.no, source: 'تفتيش ذاتي (SH-012)', sop: DEV_SOPS.find(x => C.process && x.startsWith(C.process + ' ')) || '' });
  return (BOOT.urls.deviation || '#') + '?' + q.toString();
}

function renderFindings() {
  const nc = C.items.filter(x => x.res === 'nc');
  const opp = C.items.filter(x => x.res === 'opp');
  $('#findCard').hidden = !nc.length && !opp.length;
  $('#findings').innerHTML = nc.length ? nc.map((it, i) => `
    <div class="fd ${it.cls}" data-id="${it.id}">
      <div class="fd-h"><b>${i + 1}. ${esc(it.q) || '—'}</b>${it.cls ? `<span class="badge ${it.cls === 'critical' ? 'b-bad' : it.cls === 'major' ? 'b-warn' : 'b-draft'}" style="padding:1px 10px">${CLS[it.cls]}</span>` : '<span class="badge b-bad" style="padding:1px 10px">حدد التصنيف</span>'}</div>
      <div class="small">${it.ref ? 'المرجع: ' + esc(it.ref) + ' · ' : ''}الدليل: ${esc(it.ev) || '—'}</div>
      <div class="grid fd-g">
        <div style="grid-column:span 2"><label class="f req">الإجراء التصحيحي</label><input type="text" data-f="action" value="${esc(it.action)}"></div>
        <div><label class="f req">المسؤول</label><input type="text" data-f="owner" value="${esc(it.owner)}" list="peopleList"></div>
        <div><label class="f req">الموعد</label><input type="date" data-f="due" value="${esc(it.due)}"></div>
        <div><label class="f req">رقم الانحراف (SH-008)</label><div style="display:flex;gap:6px"><input type="text" data-f="devNo" value="${esc(it.devNo)}" class="ltr-in" placeholder="DEV-…"><a class="btn ghost small dev-link" href="${esc(devHref(it))}" target="_blank" style="white-space:nowrap;text-decoration:none;padding-top:6px">فتح انحراف</a></div></div>
        ${it.cls === 'critical' ? `<div style="grid-column:1 / -1"><label class="f req">الإجراء الفوري قبل انتهاء التفتيش (وإبلاغ المدير الصيدلي)</label><input type="text" data-f="imm" value="${esc(it.imm)}"></div>` : ''}
      </div>
    </div>`).join('') : '';
  $('#opps').innerHTML = opp.length ? `<h3>فرص التحسين</h3><ul class="opp-list">${opp.map(it => `<li>${esc(it.q)}${it.ev ? ' · <span class="small">' + esc(it.ev) + '</span>' : ''}</li>`).join('')}</ul>` : '';
}

function refresh() {
  const S = stats();
  $('#meter').style.width = (S.total ? Math.round(S.done / S.total * 100) : 0) + '%';
  $('#meterTxt').textContent = S.total ? `تم فحص ${S.done} من ${S.total} بنداً` : '';
  $('#sampleBox').hidden = !C.sampleOn;
  paintOrders();
  const card = (l, v, cls) => `<div><span>${l}</span><b class="${cls || ''}">${v}</b></div>`;
  $('#res').innerHTML = card('نسبة المطابقة', S.score === null ? '—' : S.score + '%', S.score !== null && S.score < 80 ? 'c-bad' : 'c-ok')
    + card('مطابق', S.ok) + card('حرجة', S.crit, S.crit ? 'c-bad' : '') + card('رئيسية', S.maj, S.maj ? 'c-warn' : '')
    + card('ثانوية', S.min) + card('فرص تحسين', S.opp) + card('لا ينطبق', S.na)
    + (C.sampleOn ? card('عينة الطلبيات', `${S.orders.filter(r => r.ok).length} / ${S.orders.length}`, S.orders.some(r => r.ok === false) ? 'c-bad' : '') : '');

  const t = today();
  const due = C.closeDate ? addWorkDays(C.closeDate, REPORT_DAYS) : null;
  $('#reportMsg').textContent = due ? `يصدر التقرير حتى ${due}` + (C.status !== 'final' && due < t ? ` · متأخر ${daysBetween(due, t)} يوم` : '') : 'التقرير يصدر خلال ٥ أيام عمل من الاجتماع الختامي';
  $('#reportMsg').className = 'tmsg ' + (due && C.status !== 'final' && due < t ? 'bad' : '');
  const needFollow = S.crit || S.maj;
  $('#followLbl').className = 'f' + (needFollow ? ' req' : '');
  $('#followMsg').textContent = S.crit ? `حالة حرجة: تفتيش متابعة خلال ${CRIT_FOLLOW_DAYS} يوماً` : S.maj ? 'حالة رئيسية: حدد موعد تفتيش المتابعة' : '';
  $('#followMsg').className = 'tmsg ' + (S.crit ? 'bad' : '');

  $('#sNo').textContent = C.no || '—';
  $('#sProc').textContent = C.process ? `${C.process} ${PROCS[C.process] || C.processTitle || ''}` : '—';
  $('#sDone').textContent = S.total ? `${S.done} / ${S.total}` : '0';
  $('#sScore').textContent = S.score === null ? '—' : S.score + '%';
  $('#sCrit').textContent = S.crit; $('#sMaj').textContent = S.maj; $('#sMin').textContent = S.min; $('#sOpp').textContent = S.opp;
  const late = [];
  if (due && C.status !== 'final' && due < t) late.push(`إصدار التقرير متأخر ${daysBetween(due, t)} يوم`);
  if (S.crit && C.items.some(x => x.res === 'nc' && x.cls === 'critical' && !x.imm.trim())) late.push('حالة حرجة بدون إجراء فوري');
  $('#sLate').innerHTML = late.length ? `<ul class="nc-list">${late.map(x => `<li>${esc(x)}</li>`).join('')}</ul>` : '';
  $('#regLink').href = BOOT.urls.register || '#';
}

/* =========================================================
   الأحداث
   ========================================================= */
function bind() {
  FIELDS.forEach(k => $('#' + k).addEventListener('input', e => {
    C[k] = e.target.value;
    if (k === 'no') C.autoNo = false;
    if (k === 'date' && C.autoNo) { C.no = nextNo(C.date); $('#no').value = C.no; }
    if (k === 'process') C.processTitle = PROCS[C.process] || '';
    if (k === 'type') $('#followOf').closest('div').classList.toggle('dim', C.type !== 'followup');
    refresh();
  }));
  $('#independent').addEventListener('change', e => { C.independent = e.target.checked; });
  $('#sampleOn').addEventListener('change', e => { C.sampleOn = e.target.checked; refresh(); });

  // بنود الفحص
  $('#items').addEventListener('click', e => {
    const b = e.target.closest('[data-res]'); if (!b || $('#fs').disabled) return;
    const it = C.items.find(x => x.id === b.closest('[data-id]').dataset.id); if (!it) return;
    it.res = it.res === b.dataset.res ? '' : b.dataset.res;
    if (it.res !== 'nc') it.cls = '';
    const sel = b.closest('.ci').querySelector('[data-f=cls]'); sel.value = it.cls;
    paintItem(it); renderFindings(); refresh(); applyFilter();
  });
  $('#items').addEventListener('input', e => {
    const host = e.target.closest('[data-id]'); const f = e.target.dataset.f; if (!host || !f) return;
    const it = C.items.find(x => x.id === host.dataset.id); if (!it) return;
    it[f] = e.target.value;
    if (f === 'cls' && it.cls && it.res !== 'nc') { it.res = 'nc'; paintItem(it); }
    if (f === 'cls' || f === 'q') renderFindings();
    refresh();
  });
  $('#items').addEventListener('change', e => { if (e.target.matches('select')) e.target.dispatchEvent(new Event('input', { bubbles: true })); });
  $('#items').addEventListener('focusout', e => { if (e.target.dataset.f === 'ev') renderFindings(); });

  // حالات عدم المطابقة
  $('#findings').addEventListener('input', e => {
    const host = e.target.closest('[data-id]'); const f = e.target.dataset.f; if (!host || !f) return;
    const it = C.items.find(x => x.id === host.dataset.id); if (!it) return;
    it[f] = e.target.value; refresh();
  });

  // الطلبيات
  $('#orderRows').addEventListener('input', e => {
    const host = e.target.closest('[data-id]'); const f = e.target.dataset.f; if (!host || !f) return;
    const o = C.orders.find(x => x.id === host.dataset.id); if (!o) return;
    o[f] = e.target.value; refresh();
  });

  $('#filterRow').addEventListener('click', e => { const a = e.target.closest('[data-flt]'); if (!a) return; e.preventDefault(); FLT = a.dataset.flt; applyFilter(); });
}

async function loadBank() {
  if (!C.process) return showMsg(['اختر العملية / الإجراء أولاً']);
  busy(true);
  try {
    const res = await api('GET', `${BANK}/${encodeURIComponent(C.process)}`);
    const have = new Set(C.items.map(x => x.q));
    const add = (res.items || []).filter(r => !have.has(r[1])).map(r => newItem(r[0], r[1], r[2], r[3], false));
    C.items = C.items.filter(x => !x.custom || x.q.trim()).concat(add);
    // البنود الإضافية في النهاية
    C.items.sort((a, b) => (a.custom ? 1 : 0) - (b.custom ? 1 : 0));
    renderItems(); renderFindings(); refresh();
    showMsg(null, add.length ? `تم تحميل ${add.length} بنداً من إجراء ${C.process}.` : 'كل بنود الإجراء موجودة بالفعل.');
  } catch (err) { showMsg(err); }
  finally { busy(false); }
}

/* =========================================================
   التحقق
   ========================================================= */
function vBasic() {
  const e = [];
  if (!C.no.trim()) e.push('رقم التقرير مطلوب');
  if (LIST.some(m => m.number === C.no && m.id !== C.id)) e.push(`رقم التقرير ${C.no} مستخدم في تفتيش آخر`);
  if (!C.date) e.push('تاريخ التفتيش مطلوب');
  return e;
}
function vFinal() {
  const e = vBasic();
  const S = stats();
  if (!C.process) e.push('اختر العملية / الإجراء المُفتش عليه');
  if (!C.dept) e.push('اختر القسم');
  if (!C.deptMgr.trim()) e.push('اكتب اسم مدير القسم');
  if (!C.lead.trim()) e.push('اكتب اسم المفتش الرئيسي');
  if (C.lead.trim() && C.lead.trim() === C.deptMgr.trim()) e.push('المفتش الرئيسي لا يفتش على قسمه');
  if (!C.independent) e.push('أكد استقلال المفتشين');
  if (C.type === 'followup' && !C.followOf.trim()) e.push('تفتيش متابعة: اكتب رقم التقرير السابق');
  if (!S.total) e.push('لا توجد بنود فحص');
  if (S.done < S.total) e.push(`يوجد ${S.total - S.done} بنداً لم يُفحص (افحصه أو ضع «لا ينطبق» أو احذفه)`);
  C.items.forEach((it, i) => {
    const n = `البند ${i + 1}`;
    if (it.custom && !it.q.trim()) e.push(`${n}: اكتب المتطلب`);
    if ((it.res === 'nc' || it.res === 'opp') && !it.ev.trim()) e.push(`${n}: اكتب الدليل / الملاحظة`);
    if (it.res === 'nc') {
      if (!it.cls) e.push(`${n}: حدد تصنيف عدم المطابقة`);
      if (!it.action.trim() || !it.owner.trim() || !it.due) e.push(`${n}: أكمل الإجراء التصحيحي والمسؤول والموعد`);
      if (!/^DEV-/i.test(it.devNo.trim())) e.push(`${n}: سجل رقم الانحراف (SH-008)`);
      if (it.cls === 'critical' && !it.imm.trim()) e.push(`${n}: حالة حرجة تحتاج إجراءً فورياً`);
    }
  });
  if (C.sampleOn && S.orders.some(r => r.ok === null)) e.push('أكمل بيانات عينة الطلبيات أو احذف الصف غير المكتمل');
  if (C.sampleOn && S.orders.some(r => r.ok === false) && !S.nc) e.push('يوجد فرق في عينة الطلبيات: سجله كحالة عدم مطابقة');
  if (!C.closeDate) e.push('سجل تاريخ الاجتماع الختامي');
  if ((S.crit || S.maj) && !C.followDate) e.push('حدد موعد تفتيش المتابعة');
  if (S.crit && C.followDate && C.closeDate && daysBetween(C.closeDate, C.followDate) > CRIT_FOLLOW_DAYS) e.push(`حالة حرجة: تفتيش المتابعة خلال ${CRIT_FOLLOW_DAYS} يوماً من الاجتماع الختامي`);
  return e;
}

function showMsg(errs, ok) {
  $('#msg').innerHTML = errs && errs.length ? `<div class="errors">لم يتم الحفظ:<ul>${errs.map(x => `<li>${esc(x)}</li>`).join('')}</ul></div>`
    : ok ? `<div class="okmsg">${esc(ok)}</div>` : '';
}

function recordTitle() { return [C.process, PROCS[C.process] || C.processTitle, TYPES[C.type]].filter(Boolean).join(' · '); }

async function persist(status) {
  const S = stats();
  const data = Object.assign({}, C, { status, processTitle: PROCS[C.process] || C.processTitle,
    totals: { score: S.score, crit: S.crit, maj: S.maj, min: S.min, opp: S.opp, ok: S.ok, nc: S.nc, total: S.total } });
  const body = { number: C.no.trim(), title: recordTitle(), record_date: C.date || null, status, data };
  const res = C.id ? await api('PUT', `${API}/${C.id}`, body) : await api('POST', API, body);
  loadRecord(res.record);
  await refreshList();
}

function loadRecord(rec) {
  C = fitData(blankInsp(), rec.data || {});
  C.id = rec.id; C.no = rec.number; C.status = rec.status; C.autoNo = false;
  LOGS = rec.logs || [];
  render();
}

document.addEventListener('click', async e => {
  const b = e.target.closest('[data-act]'); if (!b) return;
  const a = b.dataset.act;

  if (a === 'load-bank') loadBank();
  if (a === 'add-item') { C.items.push(newItem('بنود إضافية', '', '', '', true)); renderItems(); refresh(); const t = document.querySelectorAll('#items textarea[data-f=q]'); t[t.length - 1].focus(); }
  if (a === 'del-item') { const id = b.closest('[data-id]').dataset.id; C.items = C.items.filter(x => x.id !== id); renderItems(); renderFindings(); refresh(); }
  if (a === 'add-order') { C.orders.push(newOrder()); renderOrders(); refresh(); }
  if (a === 'del-order') { const id = b.closest('[data-id]').dataset.id; C.orders = C.orders.filter(x => x.id !== id); if (!C.orders.length) C.orders.push(newOrder()); renderOrders(); refresh(); }

  if (a === 'save') {
    const errs = vBasic(); if (errs.length) return showMsg(errs);
    busy(true);
    try { await persist('draft'); showMsg(null, `تم حفظ التفتيش ${C.no}`); } catch (err) { showMsg(err); } finally { busy(false); }
  }
  if (a === 'final') {
    const errs = vFinal(); if (errs.length) return showMsg(errs);
    const snap = { by: C.issuedBy, at: C.issuedAt };
    C.issuedBy = BOOT.me.name; C.issuedAt = nowStr();
    busy(true);
    try { await persist('final'); showMsg(null, `تم إصدار تقرير التفتيش ${C.no}.`); }
    catch (err) { C.issuedBy = snap.by; C.issuedAt = snap.at; showMsg(err); }
    finally { busy(false); }
  }
  if (a === 'reopen') {
    busy(true);
    try { const res = await api('POST', `${API}/${C.id}/reopen`); loadRecord(res.record); await refreshList(); showMsg(null, 'أُعيد فتح التقرير للتعديل.'); }
    catch (err) { showMsg(err); } finally { busy(false); }
  }
  if (a === 'print') { buildDoc(); window.print(); }
  if (a === 'new') { C = blankInsp(); LOGS = []; FLT = 'all'; render(); showMsg(); window.scrollTo(0, 0); }
  if (a === 'open') {
    busy(true);
    try { const res = await api('GET', `${API}/${b.dataset.id}`); FLT = 'all'; loadRecord(res.record); showMsg(); window.scrollTo(0, 0); }
    catch (err) { showMsg(err); } finally { busy(false); }
  }
  if (a === 'del') {
    const id = Number(b.dataset.id);
    if (pendingDelete !== id) { pendingDelete = id; renderSaved(); return; }
    pendingDelete = null; busy(true);
    try { await api('DELETE', `${API}/${id}`); if (C.id === id) { C = blankInsp(); LOGS = []; } await refreshList(); render(); showMsg(null, 'تم حذف المسودة.'); }
    catch (err) { showMsg(err); renderSaved(); } finally { busy(false); }
  }
  if (a === 'cancel-del') { pendingDelete = null; renderSaved(); }
});

function renderSaved() {
  $('#saved').innerHTML = LIST.length ? LIST.map(m => `
    <div class="row ${m.id === C.id ? 'cur' : ''}">
      <div><b>${esc(m.number)}</b> <span class="badge ${m.status === 'final' ? 'b-ok' : 'b-draft'}" style="padding:1px 8px;font-size:11px">${m.status === 'final' ? 'معتمد' : 'مسودة'}</span><div class="small">${esc(m.title || '')} · ${fmtDate(m.record_date)}</div></div>
      <div class="actions" style="gap:4px">
        ${pendingDelete === m.id
          ? `<button type="button" class="btn danger small" data-act="del" data-id="${m.id}">تأكيد الحذف</button><button type="button" class="btn ghost small" data-act="cancel-del">إلغاء</button>`
          : `<button type="button" class="btn ghost small" data-act="open" data-id="${m.id}">فتح</button>${m.status !== 'final' ? `<button type="button" class="x" data-act="del" data-id="${m.id}" title="حذف">×</button>` : ''}`}
      </div>
    </div>`).join('') : '<div class="small">لا توجد تفتيشات محفوظة.</div>';
}

function renderAudit() {
  $('#audit').innerHTML = LOGS.length
    ? LOGS.map(x => `<tr><td class="tm">${esc(x.t)}</td><td>${esc(x.user)}</td><td><b>${esc(x.action)}</b></td><td>${esc(x.detail || '')}</td></tr>`).join('')
    : '<tr><td class="small" style="padding:10px">لم يُحفظ التفتيش بعد.</td></tr>';
}

/* =========================================================
   نسخة الطباعة
   ========================================================= */
function buildDoc() {
  const logo = document.querySelector('header .brand svg').outerHTML.replace('width="34" height="37"', 'width="38" height="42"').replace('fill="#ffffff"', 'fill="#0f5e5a"');
  const S = stats();
  const nl = s => esc(s).replace(/\n/g, '<br>');
  const nc = C.items.filter(x => x.res === 'nc'), opp = C.items.filter(x => x.res === 'opp');
  let sec = null;
  const rows = C.items.map((it, i) => {
    let h = '';
    if (it.sec !== sec) { sec = it.sec; h += `<tr><td colspan="5" style="background:#f2f7f6;font-weight:700;color:#24514d">${esc(sec)}</td></tr>`; }
    return h + `<tr class="${it.res === 'nc' ? 'diff' : ''}"><td>${i + 1}</td><td>${esc(it.q)}</td><td>${esc(it.ref)}</td><td>${RES[it.res] || '—'}${it.cls ? ' · ' + CLS[it.cls] : ''}</td><td>${esc(it.ev)}</td></tr>`;
  }).join('');
  const orders = C.sampleOn ? C.orders.filter(o => o.item.trim()) : [];
  $('#doc').innerHTML = `
    <div class="d-head">
      <div class="d-brand">${logo}<div><div class="ar">إتقان</div><div class="sl">إتقان في كل خطوة</div></div></div>
      <div class="d-title"><div class="t1">تقرير التفتيش الذاتي</div><div class="t2">Self-Inspection Report</div></div>
      <div class="d-tenant">${BOOT.tenant.logo ? `<img src="${esc(BOOT.tenant.logo)}" alt="">` : ''}<div><b>${esc(BOOT.tenant.name_ar)}</b><span>${esc(BOOT.tenant.name_en)}</span></div></div>
    </div>
    <div class="d-code" style="display:flex;gap:18px;justify-content:space-between;margin:-4px 0 8px">
      <span>رقم التقرير: <b>${esc(C.no)}</b></span><span>النموذج: <b>${FORM_CODE}</b></span><span>الحالة: ${C.status === 'final' ? 'معتمد' : 'مسودة'}</span>
    </div>
    <table class="d">
      <tr><td class="k">العملية المُفتش عليها</td><td>${esc(C.process)} ${esc(PROCS[C.process] || C.processTitle)}</td><td class="k">نوع التفتيش</td><td>${TYPES[C.type]}${C.followOf ? ' · على <span class="dt">' + esc(C.followOf) + '</span>' : ''}</td></tr>
      <tr><td class="k">تاريخ التفتيش</td><td>${fmtDate(C.date)}</td><td class="k">الاجتماع الختامي</td><td>${fmtDate(C.closeDate) || '—'}</td></tr>
      <tr><td class="k">القسم / مديره</td><td>${esc(C.dept)} · ${esc(C.deptMgr)}</td><td class="k">المفتشون</td><td>${esc(C.lead)}${C.inspectors ? '، ' + esc(C.inspectors) : ''}</td></tr>
    </table>

    <div class="keep"><div class="d-h2">الخلاصة</div>
    <table class="d"><tr><th>نسبة المطابقة</th><th>مطابق</th><th>حرجة</th><th>رئيسية</th><th>ثانوية</th><th>فرص تحسين</th><th>لا ينطبق</th><th>تفتيش المتابعة</th></tr>
      <tr><td class="num">${S.score === null ? '—' : S.score + '%'}</td><td class="num">${S.ok}</td><td class="num">${S.crit}</td><td class="num">${S.maj}</td><td class="num">${S.min}</td><td class="num">${S.opp}</td><td class="num">${S.na}</td><td class="num">${fmtDate(C.followDate) || '—'}</td></tr></table></div>

    ${C.positives.trim() ? `<div class="d-h2">الملاحظات الإيجابية</div><div class="d-p">${nl(C.positives)}</div>` : ''}

    ${nc.length ? `<div class="keep"><div class="d-h2">حالات عدم المطابقة · SH-012-F03</div>
    <table class="d"><tr><th style="width:4%">م</th><th>الملاحظة والدليل</th><th>المرجع</th><th>التصنيف</th><th>الإجراء التصحيحي</th><th>المسؤول</th><th>الموعد</th><th>رقم SH-008</th></tr>
      ${nc.map((it, i) => `<tr><td>${i + 1}</td><td>${esc(it.q)}<br><span style="color:#5f6f6c">${esc(it.ev)}</span>${it.imm ? '<br><b>إجراء فوري:</b> ' + esc(it.imm) : ''}</td><td>${esc(it.ref)}</td><td>${CLS[it.cls] || ''}</td><td>${esc(it.action)}</td><td>${esc(it.owner)}</td><td class="num">${fmtDate(it.due)}</td><td class="num">${esc(it.devNo)}</td></tr>`).join('')}</table></div>` : ''}

    ${opp.length ? `<div class="d-h2">فرص التحسين</div><table class="d">${opp.map((it, i) => `<tr><td style="width:4%">${i + 1}</td><td>${esc(it.q)}</td><td>${esc(it.ev)}</td></tr>`).join('')}</table>` : ''}
    ${C.summary.trim() ? `<div class="d-h2">ملاحظات الاجتماع الختامي</div><div class="d-p">${nl(C.summary)}</div>` : ''}

    <div class="d-signs">
      <div>المفتش الرئيسي<br>${esc(C.lead)}</div>
      <div>مدير القسم<br>${esc(C.deptMgr)}</div>
      <div>مدير إدارة الجودة<br>${esc(C.issuedBy)}${C.issuedAt ? '<br><span class="dt" style="font-size:10px">' + esc(C.issuedAt) + '</span>' : ''}</div>
    </div>

    <div style="break-before:page"></div>
    <div class="d-h2">قائمة الفحص · SH-012-F02 · الجزء أ</div>
    <table class="d"><tr><th style="width:4%">م</th><th>المتطلب</th><th style="width:16%">المرجع</th><th style="width:13%">النتيجة</th><th style="width:26%">الدليل / الملاحظة</th></tr>${rows}</table>
    ${orders.length ? `<div class="keep"><div class="d-h2">الجزء ب · عينة طلبيات مجهزة</div>
    <table class="d"><tr><th>م</th><th>الطلبية</th><th>العميل</th><th>الصنف</th><th>تشغيلة الفاتورة</th><th>تشغيلة المحضّر</th><th>صلاحية الفاتورة</th><th>صلاحية المحضّر</th><th>الزون</th><th>النتيجة</th></tr>
      ${orders.map((o, i) => { const r = orderRes(o); return `<tr class="${r && r.ok === false ? 'diff' : ''}"><td>${i + 1}</td><td class="num">${esc(o.order)}</td><td>${esc(o.cust)}</td><td>${esc(o.item)}</td><td class="num">${esc(o.bInv)}</td><td class="num">${esc(o.bPick)}</td><td class="num">${fmtMonth(o.eInv)}</td><td class="num">${fmtMonth(o.ePick)}</td><td class="num">${esc(o.zone)}</td><td>${r ? esc(r.text) : ''}</td></tr>`; }).join('')}</table></div>` : ''}
    <div class="d-foot">${esc(BOOT.tenant.name_ar)} · إتقان · نظام إدارة الجودة · ${FORM_CODE} · طبعه ${esc(BOOT.me.name)} في ${nowStr()}</div>`;
}

/* =========================================================
   البدء
   ========================================================= */
$('#dept').innerHTML = '<option value="">— اختر —</option>' + DEPTS.map(d => `<option>${esc(d)}</option>`).join('');
C = blankInsp();
bind();
render();
Promise.all([
  api('GET', BANK).then(r => { PROCS = r.processes || {}; }).catch(e => showMsg(e)),
  refreshList()
]).then(async () => {
  $('#process').innerHTML = '<option value="">— اختر الإجراء —</option>' + Object.entries(PROCS).map(([k, t]) => `<option value="${k}">${k} · ${esc(t)}</option>`).join('');
  $('#process').value = C.process || '';
  const open = new URLSearchParams(location.search).get('open');
  if (open) {
    const m = LIST.find(x => x.number === open);
    if (m) { try { const res = await api('GET', `${API}/${m.id}`); loadRecord(res.record); $('#process').value = C.process || ''; return; } catch (e) { showMsg(e); } }
  }
  const proc = new URLSearchParams(location.search).get('process');
  if (proc && PROCS[proc] && !C.id) { C.process = proc; C.processTitle = PROCS[proc]; $('#process').value = proc; }
  if (!C.id && C.autoNo) { C.no = nextNo(C.date); $('#no').value = C.no; }
  refresh();
});

document.getElementById('peopleList').innerHTML = BOOT.users.map(u => `<option value="${esc(u.name)}">${esc(u.role || '')}</option>`).join('');
@endverbatim
</script>
@endpush