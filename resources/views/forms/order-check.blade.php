@extends('layouts.app')

@section('title', 'فحص دقة التحضير')

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
    header.top,footer.foot,.oc,main.wrap>.alert{display:none !important}
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

  .oc{max-width:760px;margin:0 auto;font-size:15.5px;padding-bottom:30px}
  .oc.busy{opacity:.6;pointer-events:none}
  .oc .hero{text-align:center;margin-bottom:12px}
  .oc .hero h1{font-size:23px;margin:0 0 4px;color:var(--brand)}
  .oc .hero p{margin:0 0 6px;color:var(--muted);font-size:14.5px}
  .oc .hero .code{font-size:12px;color:var(--muted);direction:ltr}
  .oc .box{background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px;margin-bottom:12px}
  .oc .box h2{font-size:17px;margin:0 0 10px;color:var(--ink)}
  .oc h3{font-size:14px;color:var(--brand);margin:14px 0 6px}
  .oc input[type=text],.oc input[type=date],.oc input[type=number],.oc textarea{font-size:16px;padding:10px 11px;border-radius:10px}
  .g2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
  .g3{display:grid;grid-template-columns:1fr 1.4fr 1.2fr;gap:10px;margin-bottom:10px}
  @media (max-width:560px){.g2,.g3{grid-template-columns:1fr}}
  .muted{color:var(--muted)} .small{font-size:13px}
  .o-h{display:flex;justify-content:space-between;align-items:center}
  .o-h h2{margin:0 !important}
  .ln{border:1px solid var(--line);border-radius:12px;padding:10px;margin-bottom:8px;background:#fbfcfc}
  .ln.r-ok{background:#f3faf5;border-color:#bfe3cc} .ln.r-nc{background:#fff5f4;border-color:#efc2bd}
  .ln-h{display:flex;gap:8px;align-items:center}
  .ln-h > b{color:var(--muted);min-width:18px;text-align:center}
  .pick{position:relative;flex:1}
  .pick .list{position:absolute;inset-inline:0;top:100%;z-index:6;background:#fff;border:1px solid var(--line);border-radius:10px;max-height:240px;overflow:auto;box-shadow:0 8px 20px rgba(0,0,0,.08)}
  .pick .list div{padding:10px 12px;border-bottom:1px solid #eef1f0;cursor:pointer;display:flex;justify-content:space-between;gap:10px;font-size:14.5px}
  .pick .list div:hover,.pick .list div.on{background:var(--brand-soft)}
  .pick .list .pz{color:var(--muted);direction:ltr;white-space:nowrap;font-size:12.5px}
  .ln-m{display:flex;gap:8px;align-items:center;justify-content:space-between;margin:8px 0}
  .zone{background:var(--brand-soft);color:var(--brand);border-radius:99px;padding:3px 12px;font-size:13px}
  .zone.none{background:#eef0f0;color:var(--muted)}
  .qty{max-width:120px;direction:ltr}
  .res2{display:grid;grid-template-columns:1fr 1fr;gap:8px}
  .res2 button{border:2px solid var(--line);background:#fff;border-radius:12px;padding:11px;font:inherit;font-size:16px;font-weight:800;cursor:pointer}
  .res2 [data-res=ok]{color:var(--ok)} .res2 [data-res=nc]{color:var(--bad)}
  .res2 [data-res=ok].on{background:var(--ok);border-color:var(--ok);color:#fff}
  .res2 [data-res=nc].on{background:var(--bad);border-color:var(--bad);color:#fff}
  .errs{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}
  .errs button{border:1px solid #e3b0ac;background:#fff;border-radius:99px;padding:7px 12px;font:inherit;font-size:13.5px;cursor:pointer;color:var(--bad)}
  .errs button small{background:var(--bad-soft);border-radius:99px;padding:0 6px;margin-inline-start:4px;font-weight:700}
  .errs button.on{background:var(--bad);color:#fff;border-color:var(--bad)}
  .errs button.on small{background:rgba(255,255,255,.25)}
  .errs input{flex-basis:100%}
  .x{background:none;border:0;color:var(--bad);font-size:22px;line-height:1;cursor:pointer;padding:2px 6px}
  fieldset:disabled .x,fieldset:disabled .add{display:none !important}
  .link{background:none;border:0;color:var(--brand);font:inherit;font-weight:700;cursor:pointer;padding:6px 0;text-decoration:none}
  .link.center{display:block;text-align:center;margin-top:6px}
  .big{display:block;width:100%;border:0;border-radius:14px;padding:14px;font:inherit;font-size:17px;font-weight:800;background:var(--brand);color:#fff;cursor:pointer;margin-bottom:10px}
  .big.go{background:var(--ok)}
  .big.ghost{background:#fff;color:var(--brand);border:2px solid var(--brand)}
  .row2{display:grid;grid-template-columns:1fr 1fr;gap:10px}
  .zrow{display:flex;gap:10px;align-items:center;margin-bottom:8px}
  .zname{min-width:70px;font-weight:800;color:var(--brand)}
  .score{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
  @media (max-width:560px){.score{grid-template-columns:1fr 1fr}}
  .sc{background:#fafbfb;border:1px solid var(--line);border-radius:12px;padding:10px;text-align:center}
  .sc b{display:block;font-size:22px;direction:ltr}
  .sc span{font-size:12.5px;color:var(--muted)}
  .sc.good b{color:var(--ok)} .sc.mid b{color:var(--warn)} .sc.low b{color:var(--bad)}
  table.zt{width:100%;border-collapse:collapse;font-size:14px}
  table.zt th,table.zt td{padding:7px 6px;border-bottom:1px solid var(--line);text-align:center}
  table.zt th{color:var(--muted);font-size:12.5px}
  table.zt tr.bad td{background:#fff5f4}
  .tchips span{display:inline-block;background:var(--bad-soft);color:var(--bad);border-radius:99px;padding:3px 10px;font-size:13px;margin:0 0 6px 4px}
  .saved .row{display:flex;justify-content:space-between;align-items:center;gap:6px;padding:8px 2px;border-bottom:1px solid var(--line)}
  .saved .row.cur{background:var(--brand-soft);border-radius:8px}
  .saved .badge{padding:1px 8px;font-size:11px}
  .acts{display:flex;gap:4px;align-items:center}
  .sbtn{border:1px solid var(--brand);background:#fff;color:var(--brand);border-radius:8px;padding:4px 10px;font:inherit;font-size:13px;cursor:pointer}
  .sbtn.danger{border-color:#e3b0ac;color:var(--bad)}
  .toast{position:fixed;bottom:18px;left:50%;transform:translateX(-50%);background:#1c2826;color:#fff;padding:12px 18px;border-radius:12px;font-weight:700;z-index:50;max-width:90vw;text-align:center}
  .toast.bad{background:var(--bad)}
  .d-code{display:flex;gap:14px;justify-content:space-between;flex-wrap:wrap;margin:-4px 0 8px}
  @media (max-width:600px){ main.wrap{padding:10px !important} }
@endverbatim
</style>
@endpush

@section('content')
@verbatim
<div class="oc" id="app">
  <div class="hero">
    <h1>فحص دقة التحضير</h1>
    <p>عينة عشوائية من ٥ إلى ١٠ طلبيات جاهزة للخروج. لكل صنف: الاسم والكمية والتشغيلة والصلاحية.</p>
    <span class="code">SH-004-F04</span> <span id="badge" class="badge b-draft">مسودة</span>
  </div>

<fieldset id="fs">
  <div class="box">
    <div class="g2">
      <div><label class="f">رقم الفحص</label><input type="text" id="no" class="ltr-in"></div>
      <div><label class="f">التاريخ</label><input type="date" id="date"></div>
      <div><label class="f">الرند / الوردية</label><input type="text" id="round" placeholder="مثال: رند الصباح"></div>
      <div><label class="f">القائم بالفحص</label><input type="text" id="inspector" list="peopleList"></div>
    </div>
  </div>

  <div id="orders"></div>
  <button type="button" class="big ghost add" data-act="add-order">+ إضافة طلبية</button>

  <div class="box" id="zoneBox" hidden>
    <h2>المحضر المسؤول عن كل زون</h2>
    <p class="muted small">يُحفظ ويظهر تلقائياً في الفحص القادم.</p>
    <div id="zones"></div>
  </div>
</fieldset>

  <div class="box">
    <h2>النتيجة</h2>
    <div class="score" id="score"></div>
    <div id="zoneRes"></div>
    <div id="typeRes"></div>
  </div>

  <div class="box">
    <label class="f" for="notes">ملاحظات</label>
    <textarea id="notes" placeholder="ملاحظات عامة على التحضير والمراجعة…"></textarea>
  </div>

  <div id="msg"></div>
  <div class="actions-col">
    <button type="button" class="big" data-act="save" id="btnSave">حفظ</button>
    <button type="button" class="big go" data-act="final" id="btnFinal">اعتماد الفحص</button>
    <button type="button" class="big ghost" data-act="reopen" id="btnReopen" hidden>إعادة فتح</button>
    <div class="row2">
      <button type="button" class="big ghost" data-act="print">طباعة</button>
      <button type="button" class="big ghost" data-act="new">فحص جديد</button>
    </div>
    <a id="regLink" class="link center">تقرير الزونات والمحضرين الشهري ←</a>
  </div>

  <div class="box">
    <h2>الفحوصات المحفوظة</h2>
    <div class="saved" id="saved"></div>
  </div>
  <div class="box">
    <h2>سجل التعديلات</h2>
    <div class="audit-wrap"><table class="audit"><tbody id="audit"></tbody></table></div>
  </div>

  <div class="toast" id="toast" hidden></div>
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
   فحص دقة التحضير العشوائي (SH-004-F04) · المدير الصيدلي
   ========================================================= */
const FORM_CODE = 'SH-004-F04';
const API = BOOT.urls.records;
const SET = BOOT.settings || {};
const ERR = [   // أنواع الأخطاء وأوزانها (من إعدادات المخزن)
  { k: 'item',   t: 'صنف خطأ (اسم · تركيز · شكل)', w: Number(SET.oc_w_item ?? 5) },
  { k: 'short',  t: 'صلاحية منتهية أو قصيرة',      w: Number(SET.oc_w_short ?? 4) },
  { k: 'qty',    t: 'كمية خطأ',                    w: Number(SET.oc_w_qty ?? 3) },
  { k: 'batch',  t: 'تشغيلة خطأ',                  w: Number(SET.oc_w_batch ?? 2) },
  { k: 'exp',    t: 'صلاحية مختلفة عن الفاتورة',   w: Number(SET.oc_w_exp ?? 2) },
  { k: 'damage', t: 'عبوة تالفة',                  w: Number(SET.oc_w_damage ?? 2) },
];
const W = Object.fromEntries(ERR.map(e => [e.k, e]));
const MAXW = Math.max(...ERR.map(e => e.w));
const TARGET = Number(SET.oc_target ?? 98);
const ZONE_FIRST = SET.oc_zone_first === true;     // الزون = الجزء الأول من الموقع (A-01-03 → A)
const MIN_ORDERS = 5, MAX_ORDERS = 10;

/* ---------- أدوات ---------- */
const $ = s => document.querySelector(s);
const pad = (n, l = 2) => String(n).padStart(l, '0');
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const today = () => { const d = new Date(); return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`; };
const nowStr = () => { const d = new Date(); return `${today()} ${pad(d.getHours())}:${pad(d.getMinutes())}`; };
const fmtDate = s => { if (!s) return ''; const [y, m, d] = s.split('-'); return `<span class="dt">${d}/${m}/${y}</span>`; };
const uid = () => Math.random().toString(36).slice(2, 10);
const norm = s => String(s).toLowerCase().replace(/[أإآ]/g, 'ا').replace(/ة/g, 'ه').replace(/ى/g, 'ي').replace(/\s+/g, ' ').trim();
const zoneOf = loc => { const l = String(loc || '').trim(); if (!l) return ''; return ZONE_FIRST ? l.split(/[-\s/]/)[0] : l; };
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
    if (res.status === 419 || res.status === 401) throw ['انتهت الجلسة. سجّل الدخول مرة أخرى.'];
    if (json.errors) throw Object.values(json.errors).flat();
    throw [json.message || ('خطأ من الخادم (' + res.status + ')')];
  }
  return json;
}
function toast(t, bad) {
  const el = $('#toast'); el.textContent = t; el.className = 'toast' + (bad ? ' bad' : ''); el.hidden = false;
  clearTimeout(toast.t); toast.t = setTimeout(() => { el.hidden = true; }, bad ? 4000 : 1800);
}
function busy(on) { document.getElementById('app').classList.toggle('busy', !!on); }

/* ---------- الحالة ---------- */
let PRODUCTS = [];
let LIST = [];
let LOGS = [];
let LAST_PICKERS = {};
let C = null;
let pendingDelete = null;

function nextNo(date) {
  const prefix = `OC-${(date || today()).slice(0, 4)}-`;
  const nums = LIST.filter(m => m.number && m.number.startsWith(prefix)).map(m => parseInt(m.number.slice(prefix.length), 10) || 0);
  return prefix + pad((nums.length ? Math.max(...nums) : 0) + 1, 3);
}
function newLine() { return { id: uid(), pid: null, name: '', loc: '', zone: '', qty: '', res: '', errs: [], note: '' }; }
function newOrder() { return { id: uid(), invoice: '', customer: '', checker: '', lines: [newLine()] }; }
function blankCheck() {
  return { id: null, no: nextNo(today()), autoNo: true, date: today(), round: '', inspector: BOOT.me.name,
    orders: [newOrder()], pickers: Object.assign({}, LAST_PICKERS), notes: '', approvedBy: '', approvedAt: '', status: 'draft' };
}

/* ---------- الحسابات ---------- */
function linePoints(l) { return l.res === 'nc' ? l.errs.reduce((s, k) => s + (W[k] ? W[k].w : 0), 0) : 0; }

function calc() {
  const lines = C.orders.flatMap(o => o.lines.filter(l => l.name && l.res).map(l => ({ o, l })));
  const bad = lines.filter(x => x.l.res === 'nc');
  const pts = bad.reduce((s, x) => s + linePoints(x.l), 0);
  const capped = bad.reduce((s, x) => s + Math.min(linePoints(x.l), MAXW), 0);
  const zones = {};
  lines.forEach(({ l }) => {
    const z = l.zone || '—';
    zones[z] = zones[z] || { zone: z, lines: 0, errs: 0, pts: 0 };
    zones[z].lines++;
    if (l.res === 'nc') { zones[z].errs++; zones[z].pts += linePoints(l); }
  });
  const types = {};
  bad.forEach(({ l }) => l.errs.forEach(k => { types[k] = (types[k] || 0) + 1; }));
  const ordersBad = C.orders.filter(o => o.lines.some(l => l.res === 'nc')).length;
  return {
    orders: C.orders.length, ordersBad, lines: lines.length, bad: bad.length, pts,
    lineAcc: lines.length ? (lines.length - bad.length) / lines.length * 100 : null,
    wAcc: lines.length ? 100 - capped / (lines.length * MAXW) * 100 : null,
    zones: Object.values(zones).sort((a, b) => b.pts - a.pts || b.errs - a.errs), types
  };
}

/* ---------- الرسم ---------- */
const FIELDS = ['no', 'date', 'round', 'inspector', 'notes'];

function render() {
  FIELDS.forEach(k => { $('#' + k).value = C[k] ?? ''; });
  renderOrders(); applyState(); refresh(); renderSaved(); renderAudit();
}

function applyState() {
  const final = C.status === 'final';
  $('#fs').disabled = final;
  $('#btnSave').hidden = final;
  $('#btnFinal').hidden = final || !BOOT.can.finalize;
  $('#btnReopen').hidden = !final || !BOOT.can.reopen;
  $('#badge').className = 'badge ' + (final ? 'b-ok' : 'b-draft');
  $('#badge').textContent = final ? 'معتمد' + (C.approvedBy ? ' · ' + C.approvedBy : '') : 'مسودة';
}

function lineHtml(o, l, j) {
  return `
    <div class="ln ${l.res ? 'r-' + l.res : ''}" data-line="${l.id}">
      <div class="ln-h"><b>${j + 1}</b>
        <div class="pick">
          <input type="text" class="q" placeholder="ابحث عن الصنف…" value="${esc(l.name)}" autocomplete="off">
          <div class="list" hidden></div>
        </div>
        <button type="button" class="x" data-act="del-line" title="حذف الصنف">×</button>
      </div>
      <div class="ln-m">
        <span class="zone ${l.zone ? '' : 'none'}">${l.zone ? 'الزون: <b>' + esc(l.zone) + '</b>' : 'بدون زون'}</span>
        <input type="number" data-f="qty" min="0" step="any" placeholder="الكمية" value="${esc(l.qty)}" class="qty">
      </div>
      <div class="res2">
        <button type="button" data-res="ok" class="${l.res === 'ok' ? 'on' : ''}">✓ مطابق</button>
        <button type="button" data-res="nc" class="${l.res === 'nc' ? 'on' : ''}">✗ يوجد خطأ</button>
      </div>
      <div class="errs" ${l.res === 'nc' ? '' : 'hidden'}>
        ${ERR.map(e => `<button type="button" data-err="${e.k}" class="${l.errs.includes(e.k) ? 'on' : ''}">${e.t} <small>${e.w}</small></button>`).join('')}
        <input type="text" data-f="note" placeholder="تفاصيل (المطلوب / المحضّر)" value="${esc(l.note)}">
      </div>
    </div>`;
}

function renderOrders() {
  $('#orders').innerHTML = C.orders.map((o, i) => `
    <div class="box order" data-order="${o.id}">
      <div class="o-h"><h2>طلبية ${i + 1}</h2><button type="button" class="x" data-act="del-order" title="حذف الطلبية">×</button></div>
      <div class="g3">
        <div><label class="f">رقم الفاتورة</label><input type="text" data-of="invoice" value="${esc(o.invoice)}" class="ltr-in" inputmode="numeric"></div>
        <div><label class="f">العميل</label><input type="text" data-of="customer" value="${esc(o.customer)}"></div>
        <div><label class="f">المراجع (الكنترول)</label><input type="text" data-of="checker" value="${esc(o.checker)}" list="peopleList"></div>
      </div>
      <div class="lines">${o.lines.map((l, j) => lineHtml(o, l, j)).join('')}</div>
      <button type="button" class="link add" data-act="add-line">+ صنف آخر</button>
    </div>`).join('');
  $('.add[data-act=add-order]').hidden = C.orders.length >= MAX_ORDERS;
}

function renderZones() {
  const zs = [...new Set(C.orders.flatMap(o => o.lines.filter(l => l.name).map(l => l.zone || '—')))].sort();
  $('#zoneBox').hidden = !zs.length;
  const active = document.activeElement && document.activeElement.closest('#zones');
  if (active) return;
  $('#zones').innerHTML = zs.map(z => `<div class="zrow"><span class="zname">${esc(z)}</span><input type="text" data-zone="${esc(z)}" value="${esc(C.pickers[z] || '')}" placeholder="اسم المحضر" list="peopleList"></div>`).join('');
}

function accCls(v) { return v === null ? '' : v >= TARGET ? 'good' : v >= TARGET - 3 ? 'mid' : 'low'; }

function refresh() {
  renderZones();
  const R = calc();
  const f = v => v === null ? '—' : v.toFixed(1) + '%';
  $('#score').innerHTML = `
    <div class="sc ${accCls(R.wAcc)}"><b>${f(R.wAcc)}</b><span>الدقة الموزونة</span></div>
    <div class="sc ${accCls(R.lineAcc)}"><b>${f(R.lineAcc)}</b><span>دقة الأصناف</span></div>
    <div class="sc"><b>${R.orders}</b><span>طلبيات (${R.ordersBad} بها خطأ)</span></div>
    <div class="sc"><b>${R.lines}</b><span>صنف تم فحصه</span></div>
    <div class="sc ${R.bad ? 'low' : ''}"><b>${R.bad}</b><span>صنف به خطأ</span></div>
    <div class="sc ${R.pts ? 'low' : ''}"><b>${R.pts}</b><span>نقاط الأخطاء</span></div>
    <p class="muted small" style="grid-column:1/-1;margin:0">المستهدف ${TARGET}% · الدقة الموزونة = ١٠٠٪ − (نقاط الأخطاء ÷ (عدد الأصناف × ${MAXW}))</p>`;
  $('#zoneRes').innerHTML = R.zones.length ? `<h3>الزونات</h3><table class="zt"><tr><th>الزون</th><th>المحضر</th><th>أصناف</th><th>أخطاء</th><th>نقاط</th></tr>
    ${R.zones.map(z => `<tr class="${z.pts ? 'bad' : ''}"><td><b>${esc(z.zone)}</b></td><td>${esc(C.pickers[z.zone] || '—')}</td><td>${z.lines}</td><td>${z.errs}</td><td>${z.pts}</td></tr>`).join('')}</table>` : '';
  const ts = Object.entries(R.types).sort((a, b) => W[b[0]].w - W[a[0]].w);
  $('#typeRes').innerHTML = ts.length ? `<h3>أنواع الأخطاء</h3><div class="tchips">${ts.map(([k, n]) => `<span>${W[k].t} · <b>${n}</b></span>`).join('')}</div>` : '';
}

/* ---------- البحث عن الصنف ---------- */
function attachPicker(lineEl) {
  const q = lineEl.querySelector('.q'), list = lineEl.querySelector('.list');
  let shown = [], cur = -1;
  const find = () => {
    const ord = C.orders.find(o => o.lines.some(l => l.id === lineEl.dataset.line));
    return ord.lines.find(l => l.id === lineEl.dataset.line);
  };
  const show = () => {
    const t = norm(q.value);
    if (t.length < 2) { list.hidden = true; return; }
    const parts = t.split(' ');
    shown = PRODUCTS.filter(p => parts.every(w => p.k.includes(w))).slice(0, 30);
    cur = -1;
    list.innerHTML = shown.length ? shown.map((p, i) => `<div data-k="${i}"><span>${esc(p.n)}</span><span class="pz">${esc(zoneOf(p.l))}</span></div>`).join('')
      : `<div data-free="1" class="muted">صنف غير موجود في القائمة: استخدم «${esc(q.value)}»</div>`;
    list.hidden = false;
  };
  const choose = p => {
    const l = find();
    if (p) { l.pid = p.i; l.name = p.n; l.loc = p.l || ''; l.zone = zoneOf(p.l); }
    else { l.pid = null; l.name = q.value.trim(); }
    q.value = l.name; list.hidden = true;
    const z = lineEl.querySelector('.zone');
    z.className = 'zone ' + (l.zone ? '' : 'none'); z.innerHTML = l.zone ? 'الزون: <b>' + esc(l.zone) + '</b>' : 'بدون زون';
    refresh();
  };
  q.addEventListener('input', show);
  q.addEventListener('keydown', e => {
    if (list.hidden) return;
    if (e.key === 'ArrowDown') cur = Math.min(cur + 1, shown.length - 1);
    else if (e.key === 'ArrowUp') cur = Math.max(cur - 1, 0);
    else if (e.key === 'Enter') { e.preventDefault(); choose(shown[cur < 0 ? 0 : cur]); return; }
    else return;
    e.preventDefault();
    list.querySelectorAll('[data-k]').forEach(d => d.classList.toggle('on', +d.dataset.k === cur));
  });
  list.addEventListener('mousedown', e => {
    const d = e.target.closest('[data-k],[data-free]'); if (!d) return;
    e.preventDefault(); choose(d.dataset.k !== undefined ? shown[+d.dataset.k] : null);
  });
  q.addEventListener('blur', () => setTimeout(() => {
    list.hidden = true;
    const l = find(); if (q.value.trim() !== l.name) { q.value = l.name; }
  }, 150));
}
function attachAll() { document.querySelectorAll('#orders .ln').forEach(attachPicker); }

/* ---------- الأحداث ---------- */
function findLine(el) {
  const lid = el.closest('[data-line]').dataset.line;
  for (const o of C.orders) { const l = o.lines.find(x => x.id === lid); if (l) return { o, l }; }
  return {};
}
function findOrder(el) { return C.orders.find(o => o.id === el.closest('[data-order]').dataset.order); }

document.addEventListener('click', async e => {
  const t = e.target;
  if ($('#fs').disabled && t.closest('#fs')) return;
  const r = t.closest('[data-res]');
  if (r) {
    const { l } = findLine(r); l.res = r.dataset.res; if (l.res === 'ok') { l.errs = []; l.note = ''; }
    const el = r.closest('.ln'); el.className = 'ln r-' + l.res;
    el.querySelectorAll('[data-res]').forEach(b => b.classList.toggle('on', b.dataset.res === l.res));
    el.querySelector('.errs').hidden = l.res !== 'nc';
    if (l.res === 'ok') el.querySelectorAll('[data-err]').forEach(b => b.classList.remove('on'));
    refresh(); return;
  }
  const er = t.closest('[data-err]');
  if (er) {
    const { l } = findLine(er); const k = er.dataset.err;
    l.errs = l.errs.includes(k) ? l.errs.filter(x => x !== k) : l.errs.concat(k);
    er.classList.toggle('on', l.errs.includes(k)); refresh(); return;
  }
  const b = t.closest('[data-act]'); if (!b) return;
  const a = b.dataset.act;
  if (a === 'add-order') { if (C.orders.length < MAX_ORDERS) { C.orders.push(newOrder()); renderOrders(); attachAll(); refresh(); document.querySelector('#orders .order:last-child input').focus(); } return; }
  if (a === 'del-order') { const o = findOrder(b); C.orders = C.orders.filter(x => x !== o); if (!C.orders.length) C.orders.push(newOrder()); renderOrders(); attachAll(); refresh(); return; }
  if (a === 'add-line') { const o = findOrder(b); o.lines.push(newLine()); renderOrders(); attachAll(); refresh(); const ls = b.closest('.order').querySelectorAll('.q'); ls[ls.length - 1].focus(); return; }
  if (a === 'del-line') { const { o, l } = findLine(b); o.lines = o.lines.filter(x => x !== l); if (!o.lines.length) o.lines.push(newLine()); renderOrders(); attachAll(); refresh(); return; }

  if (a === 'save') {
    const errs = vBasic(); if (errs.length) return showMsg(errs);
    busy(true); try { await persist('draft'); showMsg(null, `تم حفظ الفحص ${C.no}`); } catch (err) { showMsg(err); } finally { busy(false); }
  }
  if (a === 'final') {
    const errs = vFinal(); if (errs.length) return showMsg(errs);
    const snap = [C.approvedBy, C.approvedAt]; C.approvedBy = BOOT.me.name; C.approvedAt = nowStr();
    busy(true); try { await persist('final'); showMsg(null, `تم اعتماد الفحص ${C.no}.`); } catch (err) { [C.approvedBy, C.approvedAt] = snap; showMsg(err); } finally { busy(false); }
  }
  if (a === 'reopen') {
    busy(true); try { const res = await api('POST', `${API}/${C.id}/reopen`); loadRecord(res.record); await refreshList(); showMsg(null, 'أُعيد فتح الفحص.'); } catch (err) { showMsg(err); } finally { busy(false); }
  }
  if (a === 'print') { buildDoc(); window.print(); }
  if (a === 'new') { C = blankCheck(); LOGS = []; render(); attachAll(); showMsg(); window.scrollTo(0, 0); }
  if (a === 'open') {
    busy(true); try { const res = await api('GET', `${API}/${b.dataset.id}`); loadRecord(res.record); showMsg(); window.scrollTo(0, 0); } catch (err) { showMsg(err); } finally { busy(false); }
  }
  if (a === 'del') {
    const id = Number(b.dataset.id);
    if (pendingDelete !== id) { pendingDelete = id; renderSaved(); return; }
    pendingDelete = null; busy(true);
    try { await api('DELETE', `${API}/${id}`); if (C.id === id) { C = blankCheck(); LOGS = []; } await refreshList(); render(); attachAll(); showMsg(null, 'تم حذف المسودة.'); }
    catch (err) { showMsg(err); renderSaved(); } finally { busy(false); }
  }
  if (a === 'cancel-del') { pendingDelete = null; renderSaved(); }
});

document.addEventListener('input', e => {
  const t = e.target;
  if (FIELDS.includes(t.id)) {
    C[t.id] = t.value;
    if (t.id === 'no') C.autoNo = false;
    if (t.id === 'date' && C.autoNo) { C.no = nextNo(C.date); $('#no').value = C.no; }
    return;
  }
  if (t.dataset.of) { findOrder(t)[t.dataset.of] = t.value; return; }
  if (t.dataset.f && t.closest('[data-line]')) { findLine(t).l[t.dataset.f] = t.value; return; }
  if (t.dataset.zone !== undefined) { C.pickers[t.dataset.zone] = t.value; refresh(); }
});

/* ---------- التحقق والحفظ ---------- */
function vBasic() {
  const e = [];
  if (!C.no.trim()) e.push('رقم الفحص مطلوب');
  if (LIST.some(m => m.number === C.no && m.id !== C.id)) e.push(`رقم الفحص ${C.no} مستخدم`);
  if (!C.date) e.push('التاريخ مطلوب');
  return e;
}
function vFinal() {
  const e = vBasic();
  if (!C.inspector.trim()) e.push('اكتب اسم القائم بالفحص');
  const n = C.orders.length;
  if (n < MIN_ORDERS) e.push(`العينة ${n} طلبية فقط: المطلوب من ${MIN_ORDERS} إلى ${MAX_ORDERS}`);
  C.orders.forEach((o, i) => {
    const t = `الطلبية ${i + 1}`;
    if (!o.invoice.trim()) e.push(`${t}: رقم الفاتورة مطلوب`);
    const ls = o.lines.filter(l => l.name);
    if (!ls.length) e.push(`${t}: أضف صنفاً واحداً على الأقل`);
    o.lines.forEach((l, j) => {
      if (!l.name && !l.res) return;
      if (!l.name) e.push(`${t} · صنف ${j + 1}: اختر الصنف`);
      if (!l.res) e.push(`${t} · صنف ${j + 1}: حدد مطابق أو يوجد خطأ`);
      if (l.res === 'nc' && !l.errs.length) e.push(`${t} · صنف ${j + 1}: حدد نوع الخطأ`);
    });
  });
  calc().zones.forEach(z => { if (!(C.pickers[z.zone] || '').trim()) e.push(`اكتب اسم المحضر لزون «${z.zone}»`); });
  return e;
}
function showMsg(errs, ok) {
  $('#msg').innerHTML = errs && errs.length ? `<div class="errors">لم يتم الحفظ:<ul>${errs.map(x => `<li>${esc(x)}</li>`).join('')}</ul></div>`
    : ok ? `<div class="okmsg">${esc(ok)}</div>` : '';
  if (errs && errs.length) $('#msg').scrollIntoView({ behavior: 'smooth', block: 'center' });
}
async function persist(status) {
  const R = calc();
  // تنظيف الأسطر الفارغة
  C.orders.forEach(o => { o.lines = o.lines.filter(l => l.name || l.res); if (!o.lines.length) o.lines.push(newLine()); });
  const used = new Set(R.zones.map(z => z.zone));
  const pickers = Object.fromEntries(Object.entries(C.pickers).filter(([z, p]) => used.has(z) && p.trim()));
  const totals = { orders: R.orders, ordersBad: R.ordersBad, lines: R.lines, bad: R.bad, pts: R.pts,
    lineAcc: R.lineAcc === null ? null : +R.lineAcc.toFixed(2), wAcc: R.wAcc === null ? null : +R.wAcc.toFixed(2), maxw: MAXW };
  const body = { number: C.no.trim(), record_date: C.date, status,
    title: `${R.orders} طلبيات · دقة ${R.wAcc === null ? '—' : R.wAcc.toFixed(1) + '%'}${C.round ? ' · ' + C.round : ''}`,
    data: Object.assign({}, C, { pickers, totals, weights: Object.fromEntries(ERR.map(x => [x.k, x.w])), status }) };
  const res = C.id ? await api('PUT', `${API}/${C.id}`, body) : await api('POST', API, body);
  loadRecord(res.record);
  await refreshList();
}
function loadRecord(rec) {
  C = Object.assign(blankCheck(), rec.data || {});
  C.pickers = Object.assign({}, LAST_PICKERS, C.pickers || {});
  C.id = rec.id; C.no = rec.number; C.status = rec.status; C.autoNo = false;
  LOGS = rec.logs || [];
  render(); attachAll();
}
async function refreshList() {
  try { LIST = (await api('GET', API)).records || []; } catch (e) { LIST = []; }
  renderSaved();
}
function renderSaved() {
  $('#saved').innerHTML = LIST.length ? LIST.map(m => `
    <div class="row ${C && m.id === C.id ? 'cur' : ''}">
      <div><b class="ltr">${esc(m.number)}</b> <span class="badge ${m.status === 'final' ? 'b-ok' : 'b-draft'}">${m.status === 'final' ? 'معتمد' : 'مسودة'}</span><div class="muted small">${esc(m.title || '')} · ${fmtDate(m.record_date)}</div></div>
      <div class="acts">
        ${pendingDelete === m.id
          ? `<button type="button" class="sbtn danger" data-act="del" data-id="${m.id}">تأكيد الحذف</button><button type="button" class="sbtn" data-act="cancel-del">إلغاء</button>`
          : `<button type="button" class="sbtn" data-act="open" data-id="${m.id}">فتح</button>${m.status !== 'final' ? `<button type="button" class="x" data-act="del" data-id="${m.id}" title="حذف">×</button>` : ''}`}
      </div>
    </div>`).join('') : '<div class="muted small">لا توجد فحوصات محفوظة.</div>';
}
function renderAudit() {
  $('#audit').innerHTML = LOGS.length
    ? LOGS.map(x => `<tr><td class="tm">${esc(x.t)}</td><td>${esc(x.user)}</td><td><b>${esc(x.action)}</b></td><td>${esc(x.detail || '')}</td></tr>`).join('')
    : '<tr><td class="muted small" style="padding:8px">لم يُحفظ الفحص بعد.</td></tr>';
}

/* ---------- الطباعة ---------- */
function buildDoc() {
  const logo = document.querySelector('header .brand svg').outerHTML.replace('width="34" height="37"', 'width="38" height="42"').replace('fill="#ffffff"', 'fill="#0f5e5a"');
  const R = calc();
  const f = v => v === null ? '—' : v.toFixed(1) + '%';
  $('#doc').innerHTML = `
    <div class="d-head">
      <div class="d-brand">${logo}<div><div class="ar">إتقان</div><div class="sl">إتقان في كل خطوة</div></div></div>
      <div class="d-title"><div class="t1">فحص دقة التحضير العشوائي</div><div class="t2">Picking Accuracy Check</div></div>
      <div class="d-tenant">${BOOT.tenant.logo ? `<img src="${esc(BOOT.tenant.logo)}" alt="">` : ''}<div><b>${esc(BOOT.tenant.name_ar)}</b><span>${esc(BOOT.tenant.name_en)}</span></div></div>
    </div>
    <div class="d-code"><span>رقم الفحص: <b>${esc(C.no)}</b></span><span>النموذج: <b>${FORM_CODE}</b></span><span>التاريخ: ${fmtDate(C.date)}${C.round ? ' · ' + esc(C.round) : ''}</span><span>الفحص: ${esc(C.inspector)}</span></div>
    <table class="d"><tr><th>الدقة الموزونة</th><th>دقة الأصناف</th><th>طلبيات</th><th>طلبيات بها خطأ</th><th>أصناف</th><th>أصناف بها خطأ</th><th>نقاط الأخطاء</th><th>المستهدف</th></tr>
      <tr><td class="num"><b>${f(R.wAcc)}</b></td><td class="num">${f(R.lineAcc)}</td><td class="num">${R.orders}</td><td class="num">${R.ordersBad}</td><td class="num">${R.lines}</td><td class="num">${R.bad}</td><td class="num">${R.pts}</td><td class="num">${TARGET}%</td></tr></table>
    <div class="d-h2">الأخطاء حسب الزون</div>
    <table class="d"><tr><th>الزون</th><th>المحضر</th><th>أصناف</th><th>أخطاء</th><th>نقاط</th></tr>
      ${R.zones.map(z => `<tr class="${z.pts ? 'diff' : ''}"><td><b>${esc(z.zone)}</b></td><td>${esc(C.pickers[z.zone] || '')}</td><td class="num">${z.lines}</td><td class="num">${z.errs}</td><td class="num">${z.pts}</td></tr>`).join('')}</table>
    <div class="d-h2">تفاصيل الطلبيات</div>
    <table class="d"><tr><th>الطلبية</th><th>العميل · المراجع</th><th>الصنف</th><th>الزون</th><th>الكمية</th><th>النتيجة</th></tr>
      ${C.orders.map(o => o.lines.filter(l => l.name).map((l, j) => `<tr class="${l.res === 'nc' ? 'diff' : ''}">${j === 0 ? `<td rowspan="${o.lines.filter(x => x.name).length}" class="num">${esc(o.invoice)}</td><td rowspan="${o.lines.filter(x => x.name).length}">${esc(o.customer)}<br><span style="color:#5f6f6c">${esc(o.checker)}</span></td>` : ''}<td>${esc(l.name)}</td><td>${esc(l.zone)}</td><td class="num">${esc(l.qty)}</td><td>${l.res === 'ok' ? 'مطابق' : l.res === 'nc' ? l.errs.map(k => W[k].t).join('، ') + (l.note ? ' · ' + esc(l.note) : '') + ` (${linePoints(l)})` : ''}</td></tr>`).join('')).join('')}</table>
    ${C.notes.trim() ? `<div class="d-h2">ملاحظات</div><div>${esc(C.notes).replace(/\n/g, '<br>')}</div>` : ''}
    <div class="d-signs"><div>القائم بالفحص<br>${esc(C.inspector)}</div><div>أمين المخزن</div><div>المدير الصيدلي (اعتماد)<br>${esc(C.approvedBy)}</div></div>
    <div class="d-foot">${esc(BOOT.tenant.name_ar)} · إتقان · ${FORM_CODE} · أوزان الأخطاء: ${ERR.map(x => x.t + ' ' + x.w).join(' · ')} · طبعه ${esc(BOOT.me.name)} في ${nowStr()}</div>`;
}

/* ---------- البدء ---------- */
$('#regLink').href = BOOT.urls.register || '#';
document.getElementById('peopleList').innerHTML = BOOT.users.map(u => `<option value="${esc(u.name)}">${esc(u.role || '')}</option>`).join('');
(async function () {
  try { PRODUCTS = ((await api('GET', BOOT.urls.products)).products || []).map(p => Object.assign(p, { k: norm(p.n) })); }
  catch (e) { toast('تعذر تحميل قائمة الأصناف', true); }
  await refreshList();
  // المحضرون من آخر فحص
  const last = LIST.find(m => m.status === 'final') || LIST[0];
  if (last) { try { LAST_PICKERS = ((await api('GET', `${API}/${last.id}`)).record.data || {}).pickers || {}; } catch (e) {} }
  C = blankCheck();
  render(); attachAll();
  if (!PRODUCTS.length) toast('قائمة الأصناف فارغة: ارفعها من «الأصناف» ليظهر الزون تلقائياً', true);
})();
@endverbatim
</script>
@endpush