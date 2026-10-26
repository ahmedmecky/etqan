@extends('layouts.app')

@section('title', 'استلام الشحنات')

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
    header.top,footer.foot,.scr,.scr-head,main.wrap>.alert{display:none !important}
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
@endverbatim
</style>
@endpush

@section('content')
@verbatim
<div class="scr-head">
  <h1>فحص واستلام شحنة واردة</h1>
  <span class="code">SH-001-F02 · F03 · v02</span>
  <span id="badge" class="badge b-draft">مسودة</span>
</div>
<div class="scr">
<div class="scr-main">
<fieldset id="fsK">

  <!-- ١. بيانات الشحنة -->
  <section class="card">
    <h2><span class="step">١</span> بيانات الشحنة</h2>
    <div class="grid">
      <div><label class="f req" for="no">رقم الاستلام</label><input type="text" id="no" class="ltr-in" autocomplete="off"></div>
      <div><label class="f req" for="date">تاريخ الوصول</label><input type="date" id="date"></div>
      <div><label class="f req" for="tArrive">وقت الوصول</label><input type="time" id="tArrive"></div>
      <div><label class="f req" for="keeper">أمين مخزن الاستلام</label><input type="text" id="keeper" list="peopleList"></div>
    </div>
    <h3>المورد والمستندات</h3>
    <div class="grid">
      <div style="grid-column:span 2"><label class="f req" for="supplier">المورد</label><input type="text" id="supplier"></div>
      <div><label class="f req" for="invoice">رقم الفاتورة / إذن التسليم</label><input type="text" id="invoice" class="ltr-in"></div>
      <div><label class="f" for="invDate">تاريخ الفاتورة</label><input type="date" id="invDate"></div>
      <div><label class="f" for="po">رقم أمر الشراء</label><input type="text" id="po" class="ltr-in"></div>
    </div>
    <h3>السيارة</h3>
    <div class="grid">
      <div><label class="f req" for="plate">رقم السيارة</label><input type="text" id="plate"></div>
      <div><label class="f req" for="driver">اسم السائق</label><input type="text" id="driver"></div>
      <div><label class="f" for="license">رقم الرخصة</label><input type="text" id="license" class="ltr-in"></div>
    </div>
  </section>

  <!-- ٢. الحرارة -->
  <section class="card">
    <h2><span class="step">٢</span> درجة الحرارة</h2>
    <div class="temp">
      <div>
        <label class="f req" for="boxTemp">حرارة صندوق السيارة عند الوصول (°م)</label>
        <input type="number" id="boxTemp" step="0.1">
        <div class="tmsg" id="boxMsg"></div>
      </div>
      <div>
        <label class="f req">هل الشحنة بها أصناف ثلاجة؟</label>
        <span class="yn" id="fridgeYN"><button type="button" data-v="Y">نعم</button><button type="button" data-v="N">لا</button></span>
      </div>
    </div>
    <div id="fridgeBox" hidden style="margin-top:14px">
      <h3>أصناف الثلاجة (٢ – ٨ °م)</h3>
      <div class="temp">
        <div>
          <label class="f req" for="fridgeTemp">حرارة صندوق التبريد عند الوصول (°م)</label>
          <input type="number" id="fridgeTemp" step="0.1">
          <div class="tmsg" id="fridgeMsg"></div>
        </div>
        <div>
          <label class="f req">جهاز تسجيل الحرارة (Data logger) مرفق؟</label>
          <span class="yn" id="loggerYN"><button type="button" data-v="Y">نعم</button><button type="button" data-v="N">لا</button></span>
        </div>
        <div id="excBox">
          <label class="f req">هل سجل الجهاز انحرافاً خلال الرحلة؟</label>
          <span class="yn" id="excYN"><button type="button" data-v="Y">نعم</button><button type="button" data-v="N">لا</button></span>
        </div>
        <div>
          <label class="f req" for="tFridge">وقت إدخال الأصناف الثلاجة</label>
          <input type="time" id="tFridge">
          <div class="tmsg" id="fridgeTimeMsg"></div>
        </div>
      </div>
      <div class="grid" style="margin-top:12px">
        <div><label class="f" for="loggerRef">رقم الجهاز / ملف القراءة</label><input type="text" id="loggerRef" class="ltr-in"></div>
      </div>
    </div>
    <div id="tempNoteBox" hidden style="margin-top:12px">
      <label class="f req" for="tempNote">وصف مشكلة الحرارة والإجراء المتخذ</label>
      <textarea id="tempNote"></textarea>
    </div>
  </section>

  <!-- ٣. فحص السيارة والمستندات -->
  <section class="card">
    <h2><span class="step">٣</span> فحص السيارة والمستندات <span class="small" style="font-weight:400">(الجزء أ)</span></h2>
    <p class="sub">أي إجابة «لا» تظهر باللون الأحمر وتحتاج وصفاً للمشكلة والإجراء.</p>
    <div id="checks"></div>
  </section>

  <!-- ٤. الأصناف -->
  <section class="card">
    <h2><span class="step">٤</span> <span id="s4Title">فحص الأصناف</span> <span class="small" style="font-weight:400" id="s4Part">(الجزء ب)</span></h2>
    <div id="itemsBox">
      <p class="sub">قارن كل صنف مع الفاتورة: الاسم والتركيز، التشغيلة، الصلاحية، الكمية، والحالة الظاهرية. الحد الأدنى للصلاحية المتبقية: <b id="minShelf"></b> شهر.</p>
      <div class="tbl-wrap">
        <table class="t items">
          <thead><tr><th>م</th><th style="min-width:170px">الصنف والتركيز</th><th style="width:95px">التشغيلة</th><th style="width:118px">الصلاحية</th><th style="width:80px">كمية الفاتورة</th><th style="width:80px">المستلم</th><th>الفرق</th><th style="min-width:150px">الحالة الظاهرية</th><th>ثلاجة</th><th></th></tr></thead>
          <tbody id="itemRows"></tbody>
        </table>
      </div>
      <div class="actions" style="margin-top:10px"><button type="button" class="btn ghost small add" data-act="add-item">+ إضافة صنف</button></div>
    </div>
    <div id="erpBox" hidden>
      <p class="sub">الأصناف والتشغيلات والكميات تُسجل على نظام ERP. سجل هنا رقم إذن الاستلام والفروقات إن وجدت.</p>
      <div class="grid">
        <div><label class="f req" for="erpRef">رقم إذن الاستلام على ERP</label><input type="text" id="erpRef" class="ltr-in"></div>
        <div><label class="f req" for="erpCount">عدد الأصناف</label><input type="number" id="erpCount" min="0" step="1"></div>
      </div>
      <label class="chk" style="margin-top:12px"><input type="checkbox" id="erpHasDiff"> توجد فروقات (عجز، زيادة، تلف، تشغيلة أو صلاحية مختلفة، صلاحية قصيرة)</label>
      <div id="erpDiffBox" hidden style="margin-top:8px">
        <label class="f req" for="erpDiff">الفروقات والإجراء مع المورد</label>
        <textarea id="erpDiff" placeholder="الصنف · التشغيلة · نوع الفرق · الكمية · الإجراء"></textarea>
      </div>
    </div>
  </section>

  <!-- ٥. الفروقات -->
  <section class="card" id="diffCard" hidden>
    <h2><span class="step">٥</span> سجل فروقات الاستلام <span class="small" style="font-weight:400">(SH-001-F03)</span></h2>
    <p class="sub">يُسجل الفرق على مستندات الشحنة بتوقيع مندوب المورد، ويُبلغ مسؤول المشتريات.</p>
    <div class="tbl-wrap"><table class="t" style="min-width:860px">
      <thead><tr><th>م</th><th style="min-width:170px">الصنف والتشغيلة</th><th>نوع الفرق</th><th>الكمية</th><th style="min-width:190px">الإجراء مع المورد</th><th style="min-width:180px">ملاحظات</th></tr></thead>
      <tbody id="diffRows"></tbody>
    </table></div>
  </section>

  <!-- ٦. إقرار أمين المخزن -->
  <section class="card">
    <h2><span class="step">٦</span> إقرار أمين مخزن الاستلام</h2>
    <div class="grid" id="repBox" hidden style="margin-bottom:10px">
      <div><label class="f req" for="supRep">اسم مندوب المورد (وقّع على الفروق)</label><input type="text" id="supRep"></div>
    </div>
    <label class="chk"><input type="checkbox" id="confirm"> أقر بأنني قمت بهذا الفحص بنفسي، وأن الشحنة كاملة في منطقة الحجر بملصق «تحت الفحص» لحين القرار.</label>
    <div class="signed" id="kSigned" hidden></div>
  </section>

</fieldset>

<fieldset id="fsD">
  <!-- ٧. القرار -->
  <section class="card" id="decCard">
    <h2><span class="step">٧</span> قرار المدير الصيدلي <span class="small" style="font-weight:400">(الجزء ج)</span></h2>
    <p class="sub" id="decHint"></p>
    <div class="decs">
      <label class="dec release"><input type="radio" name="decision" value="release"><b>إفراج</b><small>إلى المخزون الصالح</small></label>
      <label class="dec partial"><input type="radio" name="decision" value="partial"><b>رفض جزئي</b><small>رفض الأصناف المخالفة والإفراج عن الباقي</small></label>
      <label class="dec reject"><input type="radio" name="decision" value="reject"><b>رفض كلي</b><small>إرجاع الشحنة للمورد</small></label>
      <label class="dec hold"><input type="radio" name="decision" value="hold"><b>استمرار الحجر</b><small>لحين انتهاء التحقيق</small></label>
    </div>
    <div class="grid">
      <div style="grid-column:span 3"><label class="f" for="reason" id="reasonLbl">سبب القرار والملاحظات</label><textarea id="reason"></textarea></div>
      <div><label class="f" for="devNo">رقم الانحراف (SH-008)</label><input type="text" id="devNo" class="ltr-in" placeholder="DEV-..."></div>
    </div>
    <div id="devLinks"></div>
    <div class="signed" id="dSigned" hidden></div>
  </section>
</fieldset>

  <section class="card">
    <div class="actions">
      <button type="button" class="btn" data-act="save" id="btnSave">حفظ</button>
      <button type="button" class="btn gold" data-act="submit" id="btnSubmit">إرسال للمدير الصيدلي</button>
      <button type="button" class="btn okb" data-act="final" id="btnFinal" hidden>اعتماد القرار</button>
      <button type="button" class="btn ghost" data-act="back" id="btnBack" hidden>إعادة لأمين المخزن</button>
      <button type="button" class="btn ghost" data-act="reopen" id="btnReopen" hidden>إعادة فتح للتعديل</button>
      <button type="button" class="btn ghost" data-act="print">طباعة</button>
      <span style="flex:1"></span>
      <button type="button" class="btn ghost" data-act="new">استلام جديد</button>
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
    <div class="kv"><span>رقم الاستلام</span><b id="sNo">—</b></div>
    <div class="kv"><span>المورد</span><span id="sSup">—</span></div>
    <div class="kv"><span>عدد الأصناف</span><b id="sCount">0</b></div>
    <div class="kv"><span>الحالة</span><span id="sSt">مسودة</span></div>
  </section>
  <section class="card">
    <h2 style="font-size:15px">حالات عدم المطابقة <span class="badge b-draft" id="ncCount">0</span></h2>
    <ul class="nc-list" id="ncList"></ul>
    <div class="suggest b-draft" id="suggest" style="margin-top:8px">أكمل الفحص</div>
  </section>
  <section class="card">
    <h2 style="font-size:15px">الاستلامات المحفوظة</h2>
    <div class="saved" id="saved"></div>
    <p class="small" style="margin:8px 0 0">محفوظة على الخادم لهذا المخزن، ويراها كل مستخدمي المخزن.</p>
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
@include('forms._devlinks')
<script>
@verbatim
/* =========================================================
   الإعدادات
   ========================================================= */
const FORM_CODE = 'SH-001-F02 · F03';
const API = BOOT.urls.records;
const SET = BOOT.settings || {};
const ITEMS_ON = SET.receiving_items !== false;          // إعداد المخزن: تسجيل الأصناف على إتقان
const MIN_SHELF = Number(SET.receiving_min_shelf ?? 6);  // الحد الأدنى للصلاحية المتبقية بالشهور
const ROOM_MAX = 25;                                     // حرارة الغرفة: لا تزيد عن ٢٥ °م
const FRIDGE = { min: 2, max: 8 };
const FRIDGE_MINUTES = 15;                               // إدخال أصناف الثلاجة خلال ١٥ دقيقة من الوصول

const CHECKS = [
  { id: 'covered',  q: 'السيارة مغطاة أو مغلقة' },
  { id: 'floor',    q: 'أرضية السيارة نظيفة وجافة' },
  { id: 'pallets',  q: 'كل الأدوية على طبالي نظيفة' },
  { id: 'spill',    q: 'لا يوجد انسكاب أو كسر' },
  { id: 'pests',    q: 'لا يوجد ما يشير لوجود حشرات أو قوارض' },
  { id: 'smell',    q: 'لا توجد روائح كريهة' },
  { id: 'damagedSep', q: 'التوالف إن وجدت مفصولة عن البضاعة', na: true },
  { id: 'supplier', q: 'المورد معتمد ومرخص (قائمة الموردين المعتمدين)' },
  { id: 'invoicePo', q: 'الفاتورة مطابقة لأمر الشراء (الأصناف، الكميات، السعر والخصم)' },
  { id: 'alerts',   q: 'الأصناف والتشغيلات غير مدرجة بمنشورات هيئة الدواء عن المغشوش أو المسحوب', critical: true },
];
const CONDS = ['سليم', 'كسر / تلف', 'علامات عبث', 'بيانات العبوة غير واضحة', 'تشغيلة أو صلاحية مختلفة عن الفاتورة'];
const ACTIONS = ['', 'مرتجع للمورد', 'إشعار خصم من المورد', 'استكمال الكمية لاحقاً', 'قبول بموافقة المدير التجاري', 'تعديل الفاتورة', 'تحت التحقيق'];
const DECISIONS = {
  release: { label: 'إفراج إلى المخزون الصالح', badge: 'b-ok' },
  partial: { label: 'رفض جزئي والإفراج عن الباقي', badge: 'b-warn' },
  reject:  { label: 'رفض كلي', badge: 'b-bad' },
  hold:    { label: 'استمرار الحجر لحين التحقيق', badge: 'b-warn' },
};

/* =========================================================
   أدوات
   ========================================================= */
const $ = s => document.querySelector(s);
const pad = (n, l = 2) => String(n).padStart(l, '0');
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const today = () => { const d = new Date(); return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`; };
const nowTime = () => { const d = new Date(); return `${pad(d.getHours())}:${pad(d.getMinutes())}`; };
const nowStr = () => `${today()} ${nowTime()}`;
const fmtDate = iso => { if (!iso) return ''; const [y, m, d] = iso.split('-'); return `<span class="dt">${d}/${m}/${y}</span>`; };
const fmtMonth = iso => { if (!iso) return ''; const [y, m] = iso.split('-'); return `${m}/${y}`; };
function fmtTime(t) { if (!t) return ''; let [h, m] = t.split(':').map(Number); const s = h < 12 ? 'ص' : 'م'; h = h % 12 || 12; return `${h}:${pad(m)} ${s}`; }
const has = v => v !== '' && v !== null && v !== undefined && isFinite(parseFloat(v));
const num = v => { const n = parseFloat(v); return isFinite(n) ? n : 0; };
const signed = n => (n > 0 ? '+' : '') + n.toLocaleString('en-US');
const uid = () => Math.random().toString(36).slice(2, 10);
const minutes = t => { if (!t) return null; const [h, m] = t.split(':').map(Number); return h * 60 + m; };
const yn = v => v === 'Y' ? 'نعم' : v === 'N' ? 'لا' : v === 'NA' ? 'لا ينطبق' : '—';

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
  const prefix = `RCV-${y}-`;
  const nums = LIST.filter(m => m.number && m.number.startsWith(prefix)).map(m => parseInt(m.number.slice(prefix.length), 10) || 0);
  return prefix + pad((nums.length ? Math.max(...nums) : 0) + 1, 3);
}

function newItem() { return { id: uid(), name: '', batch: '', exp: '', qInv: '', qRec: '', cond: 'سليم', fridge: false, action: '', note: '' }; }

function blankRec() {
  return {
    id: null, no: nextNo(today()), autoNo: true, date: today(), tArrive: nowTime(), keeper: BOOT.me.name,
    supplier: '', invoice: '', invDate: '', po: '', plate: '', driver: '', license: '',
    boxTemp: '', fridge: '', fridgeTemp: '', logger: '', exc: '', tFridge: '', loggerRef: '', tempNote: '',
    checks: {}, checkNotes: {},
    erp: !ITEMS_ON, minShelf: MIN_SHELF,
    items: [newItem()], erpRef: '', erpCount: '', erpHasDiff: false, erpDiff: '',
    supRep: '', confirm: false,
    stage: 'open', submittedBy: '', submittedAt: '',
    decision: '', reason: '', devNo: '', decidedBy: '', decidedAt: '',
    status: 'draft'
  };
}

/* =========================================================
   الحسابات
   ========================================================= */
function monthsLeft(exp, from) {
  if (!exp) return null;
  const [ey, em] = exp.split('-').map(Number);
  const [y, m] = (from || today()).split('-').map(Number);
  return (ey - y) * 12 + (em - m);
}

function itemIssues(it) {
  const out = [];
  const filled = has(it.qInv) && has(it.qRec);
  const d = filled ? num(it.qRec) - num(it.qInv) : 0;
  if (d < 0) out.push({ t: 'عجز', bad: false });
  if (d > 0) out.push({ t: 'زيادة', bad: false });
  if (it.cond && it.cond !== 'سليم') out.push({ t: it.cond, bad: true });
  const ml = monthsLeft(it.exp, C.date);
  if (ml !== null && ml <= 0) out.push({ t: 'منتهي الصلاحية', bad: true });
  else if (ml !== null && ml < num(C.minShelf)) out.push({ t: `صلاحية قصيرة (${ml} شهر)`, bad: false });
  return { list: out, d, filled, ml };
}

function tempState() {
  const s = { box: null, fridge: null, time: null };
  if (has(C.boxTemp)) s.box = num(C.boxTemp) <= ROOM_MAX ? 'ok' : 'bad';
  if (C.fridge === 'Y') {
    if (has(C.fridgeTemp)) { const t = num(C.fridgeTemp); s.fridge = t >= FRIDGE.min && t <= FRIDGE.max ? 'ok' : 'bad'; }
    const a = minutes(C.tArrive), f = minutes(C.tFridge);
    if (a !== null && f !== null) s.time = (f - a) <= FRIDGE_MINUTES && f >= a ? 'ok' : 'bad';
  }
  return s;
}

function analyse() {
  const T = tempState();
  const nc = [];
  if (T.box === 'bad') nc.push({ t: `حرارة صندوق السيارة أعلى من ${ROOM_MAX} °م`, k: 'temp' });
  if (C.fridge === 'Y') {
    if (T.fridge === 'bad') nc.push({ t: 'حرارة أصناف الثلاجة خارج ٢ – ٨ °م', k: 'cold' });
    if (C.logger === 'N') nc.push({ t: 'لا يوجد جهاز تسجيل حرارة مع أصناف الثلاجة', k: 'cold' });
    if (C.logger === 'Y' && C.exc === 'Y') nc.push({ t: 'جهاز تسجيل الحرارة سجل انحرافاً خلال الرحلة', k: 'cold' });
    if (T.time === 'bad') nc.push({ t: `أصناف الثلاجة لم تُدخل التبريد خلال ${FRIDGE_MINUTES} دقيقة`, k: 'cold' });
  }
  CHECKS.forEach(c => { if (C.checks[c.id] === 'N') nc.push({ t: c.q, k: c.critical ? 'fake' : 'check' }); });
  const rows = C.erp ? [] : C.items.filter(it => it.name.trim());
  const calcs = rows.map(it => ({ it, x: itemIssues(it) }));
  const diffs = calcs.filter(r => r.x.list.length);
  calcs.forEach(({ it, x }) => {
    if (x.list.some(i => i.t === 'علامات عبث')) nc.push({ t: `علامات عبث: ${it.name}`, k: 'fake' });
    else if (x.list.some(i => i.bad)) nc.push({ t: `${it.name}: ${x.list.filter(i => i.bad).map(i => i.t).join('، ')}`, k: 'item' });
  });
  if (C.erp && C.erpHasDiff) nc.push({ t: 'فروقات مسجلة على ERP', k: 'diff' });
  let suggest;
  if (nc.some(n => n.k === 'fake')) suggest = { v: 'hold', text: 'اشتباه غش أو عبث: حجر وإبلاغ فوري (SH-011)', cls: 'b-bad' };
  else if (nc.some(n => n.k === 'cold')) suggest = { v: 'hold', text: 'انحراف حرارة الثلاجة: حجر لحين التقييم (SH-008)', cls: 'b-warn' };
  else if (nc.some(n => n.k === 'item')) suggest = { v: 'partial', text: 'رفض جزئي للأصناف المخالفة', cls: 'b-warn' };
  else if (nc.length) suggest = { v: 'hold', text: 'يوجد عدم مطابقة: راجع قبل الإفراج', cls: 'b-warn' };
  else suggest = { v: 'release', text: 'إفراج: لا توجد حالات عدم مطابقة', cls: 'b-ok' };
  return { T, nc, rows, calcs, diffs, suggest, count: C.erp ? num(C.erpCount) : rows.length };
}

/* =========================================================
   الرسم
   ========================================================= */
const FIELDS = ['no', 'date', 'tArrive', 'keeper', 'supplier', 'invoice', 'invDate', 'po', 'plate', 'driver', 'license',
  'boxTemp', 'fridgeTemp', 'tFridge', 'loggerRef', 'tempNote', 'erpRef', 'erpCount', 'erpDiff', 'supRep', 'reason', 'devNo'];
const YN = { fridgeYN: 'fridge', loggerYN: 'logger', excYN: 'exc' };

function canDecide() { return !!BOOT.can.finalize; }

function render() {
  FIELDS.forEach(k => { $('#' + k).value = C[k] ?? ''; });
  $('#confirm').checked = !!C.confirm;
  $('#erpHasDiff').checked = !!C.erpHasDiff;
  document.querySelectorAll('input[name=decision]').forEach(r => { r.checked = r.value === C.decision; });
  $('#minShelf').textContent = C.minShelf;
  renderChecks(); renderItems(); applyState(); refresh(); renderSaved(); renderAudit();
  DevLinks.refresh();
}

function applyState() {
  const final = C.status === 'final';
  const sub = C.stage === 'submitted';
  $('#fsK').disabled = final || sub;
  $('#fsD').disabled = final || !sub || !canDecide();
  $('#btnSave').hidden = final || sub;
  $('#btnSubmit').hidden = final || sub;
  $('#btnFinal').hidden = final || !sub || !canDecide();
  $('#btnBack').hidden = final || !sub || !canDecide();
  $('#btnReopen').hidden = !final || !BOOT.can.reopen;

  const b = $('#badge');
  if (final) { const d = DECISIONS[C.decision] || {}; b.className = 'badge ' + (d.badge || 'b-ok'); b.textContent = 'معتمد · ' + (d.label || ''); }
  else if (sub) { b.className = 'badge b-warn'; b.textContent = 'بانتظار قرار المدير الصيدلي'; }
  else { b.className = 'badge b-draft'; b.textContent = 'مسودة'; }
  $('#sSt').textContent = b.textContent;

  $('#decHint').textContent = !sub && !final ? 'يُتاح بعد إرسال الفحص من أمين مخزن الاستلام.'
    : (!canDecide() && !final ? 'القرار للمدير الصيدلي أو مدير الجودة أو مدير المخزن.' : 'راجع الفحص والفروقات ثم اختر القرار.');
  $('#kSigned').hidden = !C.submittedAt;
  $('#kSigned').textContent = C.submittedAt ? `أرسله ${C.submittedBy} في ${C.submittedAt}` : '';
  $('#dSigned').hidden = !final;
  $('#dSigned').textContent = final ? `القرار «${(DECISIONS[C.decision] || {}).label || ''}» اعتمده ${C.decidedBy} في ${C.decidedAt}` : '';

  // وضع ERP
  $('#itemsBox').hidden = C.erp;
  $('#erpBox').hidden = !C.erp;
  $('#s4Title').textContent = C.erp ? 'الأصناف على نظام ERP' : 'فحص الأصناف';
}

function renderChecks() {
  $('#checks').innerHTML = CHECKS.map(c => `
    <div class="q" data-check="${c.id}">
      <div class="qt">${esc(c.q)}${c.critical ? ' <span class="flag bad">حرج</span>' : ''}</div>
      <span class="yn"><button type="button" data-v="Y">نعم</button><button type="button" data-v="N">لا</button>${c.na ? '<button type="button" data-v="NA">لا ينطبق</button>' : ''}</span>
      <textarea data-note="${c.id}" placeholder="وصف المشكلة والإجراء المتخذ">${esc(C.checkNotes[c.id] || '')}</textarea>
    </div>`).join('');
}

function paintYN(box, v) {
  box.querySelectorAll('button').forEach(b => {
    b.className = b.dataset.v !== v ? '' : v === 'Y' ? 'on-ok' : v === 'N' ? 'on-bad' : 'on-na';
  });
}

function renderItems() {
  $('#itemRows').innerHTML = C.items.map((it, i) => `
    <tr data-id="${it.id}">
      <td class="n">${i + 1}</td>
      <td><input type="text" data-f="name" value="${esc(it.name)}" placeholder="اسم الصنف والتركيز"><div data-o="flags"></div></td>
      <td><input type="text" data-f="batch" value="${esc(it.batch)}" class="ltr-in"></td>
      <td><input type="month" data-f="exp" value="${esc(it.exp)}" style="width:100%;padding:5px 6px;border:1px solid var(--line);border-radius:8px;font:inherit;font-size:13px;direction:ltr"></td>
      <td><input type="number" data-f="qInv" value="${esc(it.qInv)}" min="0" step="any"></td>
      <td><input type="number" data-f="qRec" value="${esc(it.qRec)}" min="0" step="any"></td>
      <td class="c" data-o="d">—</td>
      <td><select data-f="cond">${CONDS.map(x => `<option ${x === it.cond ? 'selected' : ''}>${x}</option>`).join('')}</select></td>
      <td style="text-align:center;padding-top:9px"><input type="checkbox" data-f="fridge" ${it.fridge ? 'checked' : ''} style="width:17px;height:17px;accent-color:var(--brand)"></td>
      <td><button type="button" class="x" data-act="del-item" title="حذف">×</button></td>
    </tr>`).join('');
}

function updateItemCells() {
  document.querySelectorAll('#itemRows tr[data-id]').forEach(tr => {
    const it = C.items.find(x => x.id === tr.dataset.id); if (!it) return;
    const x = itemIssues(it);
    const dc = tr.querySelector('[data-o=d]');
    dc.textContent = x.filled ? signed(x.d) : '—';
    dc.className = 'c ' + (!x.filled ? '' : x.d === 0 ? 'c-ok' : 'c-bad');
    tr.querySelector('[data-o=flags]').innerHTML = x.list.map(i => `<span class="flag ${i.bad ? 'bad' : 'warn'}">${esc(i.t)}</span>`).join('');
    tr.classList.toggle('diff', x.list.length > 0);
  });
}

function renderDiffs(A) {
  const show = !C.erp && A.diffs.length > 0;
  $('#diffCard').hidden = !show;
  $('#diffRows').innerHTML = !show ? '' : A.diffs.map(({ it, x }, i) => `
    <tr data-id="${it.id}">
      <td class="n">${i + 1}</td>
      <td style="padding-top:10px"><b>${esc(it.name)}</b><div class="small">${esc(it.batch) || '—'}</div></td>
      <td style="padding-top:8px">${x.list.map(f => `<span class="flag ${f.bad ? 'bad' : 'warn'}">${esc(f.t)}</span>`).join('')}</td>
      <td class="c ${x.d ? 'c-bad' : ''}">${x.filled && x.d ? signed(x.d) : '—'}</td>
      <td><select data-f="action">${ACTIONS.map(a => `<option value="${a}" ${a === it.action ? 'selected' : ''}>${a || '— اختر —'}</option>`).join('')}</select></td>
      <td><input type="text" data-f="note" value="${esc(it.note)}"></td>
    </tr>`).join('');
}

function refresh() {
  const A = analyse();
  // الحرارة
  const T = A.T;
  const bm = $('#boxMsg');
  bm.textContent = T.box === null ? `المقبول: لا تزيد عن ${ROOM_MAX} °م` : T.box === 'ok' ? 'ضمن المدى المقبول' : `أعلى من ${ROOM_MAX} °م`;
  bm.className = 'tmsg ' + (T.box || '');
  paintYN($('#fridgeYN'), C.fridge);
  paintYN($('#loggerYN'), C.logger);
  paintYN($('#excYN'), C.exc);
  $('#fridgeBox').hidden = C.fridge !== 'Y';
  $('#excBox').hidden = C.logger !== 'Y';
  const fm = $('#fridgeMsg');
  fm.textContent = T.fridge === null ? 'المقبول: من ٢ إلى ٨ °م' : T.fridge === 'ok' ? 'ضمن المدى المقبول' : 'خارج المدى ٢ – ٨ °م';
  fm.className = 'tmsg ' + (T.fridge || '');
  const tm = $('#fridgeTimeMsg');
  tm.textContent = T.time === null ? `خلال ${FRIDGE_MINUTES} دقيقة من الوصول` : T.time === 'ok' ? 'خلال المدة المسموحة' : `بعد أكثر من ${FRIDGE_MINUTES} دقيقة من الوصول`;
  tm.className = 'tmsg ' + (T.time || '');
  $('#tempNoteBox').hidden = !A.nc.some(n => n.k === 'temp' || n.k === 'cold');

  // قائمة الفحص
  document.querySelectorAll('#checks .q').forEach(el => {
    const v = C.checks[el.dataset.check];
    paintYN(el, v);
    el.classList.toggle('nc', v === 'N');
  });

  updateItemCells();
  if (!document.activeElement || !document.activeElement.closest('#diffRows')) renderDiffs(A);
  $('#erpDiffBox').hidden = !C.erpHasDiff;
  $('#repBox').hidden = !(A.diffs.length || (C.erp && C.erpHasDiff));

  // الملخص
  $('#sNo').textContent = C.no || '—';
  $('#sSup').textContent = C.supplier || '—';
  $('#sCount').textContent = A.count;
  $('#ncCount').textContent = A.nc.length;
  $('#ncCount').className = 'badge ' + (A.nc.length ? 'b-bad' : 'b-ok');
  $('#ncList').innerHTML = A.nc.map(n => `<li>${esc(n.t)}</li>`).join('');
  const sg = $('#suggest');
  const ready = C.supplier && C.invoice && has(C.boxTemp) && C.fridge && CHECKS.every(c => C.checks[c.id]);
  sg.className = 'suggest ' + (ready ? A.suggest.cls : 'b-draft');
  sg.textContent = ready ? 'المقترح: ' + A.suggest.text : 'أكمل الفحص لعرض القرار المقترح';
  const needReason = A.nc.length > 0 || (C.decision && C.decision !== 'release');
  $('#reasonLbl').className = 'f' + (needReason ? ' req' : '');
}

/* =========================================================
   الأحداث
   ========================================================= */
function bind() {
  FIELDS.forEach(k => $('#' + k).addEventListener('input', e => {
    C[k] = e.target.value;
    if (k === 'no') C.autoNo = false;
    if (k === 'date' && C.autoNo) { C.no = nextNo(C.date); $('#no').value = C.no; }
    refresh();
  }));
  $('#confirm').addEventListener('change', e => { C.confirm = e.target.checked; });
  $('#erpHasDiff').addEventListener('change', e => { C.erpHasDiff = e.target.checked; refresh(); });
  document.querySelectorAll('input[name=decision]').forEach(r => r.addEventListener('change', e => { C.decision = e.target.value; refresh(); }));

  Object.keys(YN).forEach(boxId => $('#' + boxId).addEventListener('click', e => {
    const b = e.target.closest('button[data-v]'); if (!b || $('#fsK').disabled) return;
    const k = YN[boxId]; C[k] = b.dataset.v;
    if (k === 'fridge' && C.fridge !== 'Y') { C.logger = ''; C.exc = ''; }
    if (k === 'logger' && C.logger !== 'Y') C.exc = '';
    refresh();
  }));
  $('#checks').addEventListener('click', e => {
    const b = e.target.closest('button[data-v]'); if (!b || $('#fsK').disabled) return;
    C.checks[b.closest('[data-check]').dataset.check] = b.dataset.v; refresh();
  });
  $('#checks').addEventListener('input', e => { const k = e.target.dataset.note; if (k) C.checkNotes[k] = e.target.value; });

  const rowEdit = sel => $(sel).addEventListener('input', e => {
    const host = e.target.closest('[data-id]'); const f = e.target.dataset.f; if (!host || !f) return;
    const it = C.items.find(x => x.id === host.dataset.id); if (!it) return;
    it[f] = e.target.type === 'checkbox' ? e.target.checked : e.target.value;
    refresh();
  });
  rowEdit('#itemRows'); rowEdit('#diffRows');
  $('#itemRows').addEventListener('change', e => { if (e.target.matches('select,[type=checkbox]')) e.target.dispatchEvent(new Event('input', { bubbles: true })); });
  $('#diffRows').addEventListener('change', e => { if (e.target.matches('select')) e.target.dispatchEvent(new Event('input', { bubbles: true })); });
  $('#diffRows').addEventListener('focusout', () => setTimeout(() => { if (!document.activeElement.closest('#diffRows')) renderDiffs(analyse()); }, 0));
}

/* =========================================================
   التحقق
   ========================================================= */
function validateBasic() {
  const e = [];
  if (!C.no.trim()) e.push('رقم الاستلام مطلوب');
  if (LIST.some(m => m.number === C.no && m.id !== C.id)) e.push(`رقم الاستلام ${C.no} مستخدم في استلام آخر`);
  if (!C.date) e.push('تاريخ الوصول مطلوب');
  return e;
}

function validateKeeper() {
  const e = validateBasic();
  const A = analyse();
  if (!C.tArrive) e.push('وقت الوصول مطلوب');
  if (!C.keeper.trim()) e.push('اسم أمين مخزن الاستلام مطلوب');
  if (!C.supplier.trim()) e.push('اسم المورد مطلوب');
  if (!C.invoice.trim()) e.push('رقم الفاتورة / إذن التسليم مطلوب');
  if (C.invDate && C.invDate > C.date) e.push('تاريخ الفاتورة بعد تاريخ الوصول');
  if (!C.plate.trim()) e.push('رقم السيارة مطلوب');
  if (!C.driver.trim()) e.push('اسم السائق مطلوب');
  if (!has(C.boxTemp)) e.push('سجل حرارة صندوق السيارة');
  if (!C.fridge) e.push('حدد هل الشحنة بها أصناف ثلاجة');
  if (C.fridge === 'Y') {
    if (!has(C.fridgeTemp)) e.push('سجل حرارة صندوق التبريد');
    if (!C.logger) e.push('حدد هل يوجد جهاز تسجيل حرارة');
    if (C.logger === 'Y' && !C.exc) e.push('حدد هل سجل الجهاز انحرافاً');
    if (!C.tFridge) e.push('سجل وقت إدخال أصناف الثلاجة');
  }
  if (A.nc.some(n => n.k === 'temp' || n.k === 'cold') && !C.tempNote.trim()) e.push('اكتب وصف مشكلة الحرارة والإجراء المتخذ');
  CHECKS.forEach(c => {
    if (!C.checks[c.id]) e.push(`أجب على: ${c.q}`);
    else if (C.checks[c.id] === 'N' && !(C.checkNotes[c.id] || '').trim()) e.push(`اكتب وصف المشكلة: ${c.q}`);
  });
  if (C.erp) {
    if (!C.erpRef.trim()) e.push('اكتب رقم إذن الاستلام على ERP');
    if (!has(C.erpCount)) e.push('اكتب عدد الأصناف');
    if (C.erpHasDiff && !C.erpDiff.trim()) e.push('اكتب الفروقات والإجراء مع المورد');
  } else {
    if (!A.rows.length) e.push('أضف صنفاً واحداً على الأقل');
    A.calcs.forEach(({ it, x }) => {
      if (!it.batch.trim()) e.push(`التشغيلة مطلوبة للصنف «${it.name}»`);
      if (!it.exp) e.push(`الصلاحية مطلوبة للصنف «${it.name}»`);
      if (!x.filled) e.push(`أدخل كمية الفاتورة والكمية المستلمة للصنف «${it.name}»`);
    });
    A.diffs.forEach(({ it }) => { if (!it.action) e.push(`حدد الإجراء مع المورد لفرق «${it.name}»`); });
    if (C.fridge === 'N' && A.rows.some(it => it.fridge)) e.push('يوجد صنف ثلاجة بينما الإجابة «لا توجد أصناف ثلاجة»');
  }
  if ((A.diffs.length || (C.erp && C.erpHasDiff)) && !C.supRep.trim()) e.push('اكتب اسم مندوب المورد الذي وقّع على الفروق');
  if (!C.confirm) e.push('ضع علامة على إقرار أمين مخزن الاستلام');
  return e;
}

function validateDecision() {
  const e = [];
  const A = analyse();
  if (!C.decision) e.push('اختر القرار');
  if ((A.nc.length || (C.decision && C.decision !== 'release')) && !C.reason.trim()) e.push('اكتب سبب القرار');
  if (C.decision === 'release' && A.nc.some(n => n.k === 'fake')) e.push('لا يمكن الإفراج مع اشتباه غش أو عبث. اختر الحجر أو الرفض');
  if (C.decision === 'partial' && !C.erp && !A.diffs.length) e.push('الرفض الجزئي يحتاج صنفاً مخالفاً واحداً على الأقل');
  return e;
}

function showMsg(errs, ok) {
  $('#msg').innerHTML = errs && errs.length ? `<div class="errors">لم يتم الحفظ:<ul>${errs.map(x => `<li>${esc(x)}</li>`).join('')}</ul></div>`
    : ok ? `<div class="okmsg">${esc(ok)}</div>` : '';
}

function recordTitle() { return [C.supplier, C.invoice].filter(Boolean).join(' · '); }

async function persist(status) {
  const body = { number: C.no.trim(), title: recordTitle(), record_date: C.date || null, status, data: Object.assign({}, C, { status }) };
  const res = C.id ? await api('PUT', `${API}/${C.id}`, body) : await api('POST', API, body);
  loadRecord(res.record);
  await refreshList();
}

function loadRecord(rec) {
  C = fitData(blankRec(), rec.data || {});
  C.id = rec.id;
  C.no = rec.number;
  C.status = rec.status;
  C.autoNo = false;
  LOGS = rec.logs || [];
  render();
}

async function run(fn, okText) {
  busy(true);
  try { await fn(); if (okText) showMsg(null, okText); }
  catch (err) { showMsg(err); }
  finally { busy(false); }
}

document.addEventListener('click', async e => {
  const b = e.target.closest('[data-act]'); if (!b) return;
  const a = b.dataset.act;

  if (a === 'add-item') { C.items.push(newItem()); renderItems(); refresh(); const ins = document.querySelectorAll('#itemRows [data-f=name]'); ins[ins.length - 1].focus(); }
  if (a === 'del-item') { const id = b.closest('[data-id]').dataset.id; C.items = C.items.filter(x => x.id !== id); if (!C.items.length) C.items.push(newItem()); renderItems(); refresh(); }

  if (a === 'save') {
    const errs = validateBasic(); if (errs.length) return showMsg(errs);
    run(() => persist('draft'), `تم حفظ الاستلام ${C.no}`);
  }
  if (a === 'submit') {
    const errs = validateKeeper(); if (errs.length) return showMsg(errs);
    const prev = { stage: C.stage, submittedBy: C.submittedBy, submittedAt: C.submittedAt };
    C.stage = 'submitted'; C.submittedBy = BOOT.me.name; C.submittedAt = nowStr();
    run(async () => { try { await persist('draft'); } catch (err) { Object.assign(C, prev); throw err; } },
      'تم إرسال الفحص للمدير الصيدلي. الشحنة تبقى في الحجر لحين القرار.');
  }
  if (a === 'back') {
    const prev = C.stage;
    C.stage = 'open'; C.decision = '';
    run(async () => { try { await persist('draft'); } catch (err) { C.stage = prev; throw err; } },
      'أُعيد الفحص لأمين مخزن الاستلام للتعديل.');
  }
  if (a === 'final') {
    const errs = validateDecision(); if (errs.length) return showMsg(errs);
    C.decidedBy = BOOT.me.name; C.decidedAt = nowStr();
    run(() => persist('final'), `تم اعتماد القرار: ${DECISIONS[C.decision].label}. يمكنك الطباعة الآن.`);
  }
  if (a === 'reopen') {
    run(async () => { const res = await api('POST', `${API}/${C.id}/reopen`); loadRecord(res.record); await refreshList(); },
      'الاستلام مفتوح لتعديل القرار. اعتمده مرة أخرى بعد الانتهاء.');
  }
  if (a === 'print') { buildDoc(); window.print(); }
  if (a === 'new') { C = blankRec(); LOGS = []; render(); showMsg(); window.scrollTo(0, 0); }

  if (a === 'open') {
    run(async () => { const res = await api('GET', `${API}/${b.dataset.id}`); loadRecord(res.record); showMsg(); window.scrollTo(0, 0); });
  }
  if (a === 'del') {
    const id = Number(b.dataset.id);
    if (pendingDelete !== id) { pendingDelete = id; renderSaved(); return; }
    pendingDelete = null;
    run(async () => {
      await api('DELETE', `${API}/${id}`);
      if (C.id === id) { C = blankRec(); LOGS = []; }
      await refreshList(); render();
    }, 'تم حذف المسودة.');
  }
  if (a === 'cancel-del') { pendingDelete = null; renderSaved(); }
});

function stateLabel(m) {
  return m.status === 'final' ? ['معتمد', 'b-ok'] : ['مسودة', 'b-draft'];
}

function renderSaved() {
  $('#saved').innerHTML = LIST.length ? LIST.map(m => {
    const [t, cls] = stateLabel(m);
    return `
    <div class="row ${m.id === C.id ? 'cur' : ''}">
      <div><b>${esc(m.number)}</b> <span class="badge ${cls}" style="padding:1px 8px;font-size:11px">${t}</span><div class="small">${esc(m.title || '')} · ${fmtDate(m.record_date)}</div></div>
      <div class="actions" style="gap:4px">
        ${pendingDelete === m.id
          ? `<button type="button" class="btn danger small" data-act="del" data-id="${m.id}">تأكيد الحذف</button><button type="button" class="btn ghost small" data-act="cancel-del">إلغاء</button>`
          : `<button type="button" class="btn ghost small" data-act="open" data-id="${m.id}">فتح</button>${m.status !== 'final' ? `<button type="button" class="x" data-act="del" data-id="${m.id}" title="حذف">×</button>` : ''}`}
      </div>
    </div>`;
  }).join('') : '<div class="small">لا توجد استلامات محفوظة.</div>';
}

function renderAudit() {
  $('#audit').innerHTML = LOGS.length
    ? LOGS.map(x => `<tr><td class="tm">${esc(x.t)}</td><td>${esc(x.user)}</td><td><b>${esc(x.action)}</b></td><td>${esc(x.detail || '')}</td></tr>`).join('')
    : '<tr><td class="small" style="padding:10px">لم يُحفظ الاستلام بعد.</td></tr>';
}

/* =========================================================
   نسخة الطباعة
   ========================================================= */
function buildDoc() {
  const A = analyse();
  const logo = document.querySelector('header .brand svg').outerHTML.replace('width="34" height="37"', 'width="38" height="42"').replace('fill="#ffffff"', 'fill="#0f5e5a"');
  const dec = DECISIONS[C.decision];
  const T = A.T;
  const tRow = (k, v, ok) => `<tr><td class="k">${k}</td><td>${v}${ok === 'bad' ? ' <b style="color:#b3261e">✗</b>' : ok === 'ok' ? ' ✓' : ''}</td></tr>`;
  $('#doc').innerHTML = `
    <div class="d-head">
      <div class="d-brand">${logo}<div><div class="ar">إتقان</div><div class="sl">إتقان في كل خطوة</div></div></div>
      <div class="d-title"><div class="t1">قائمة فحص الشحنة الواردة</div><div class="t2">Incoming Shipment Inspection</div></div>
      <div class="d-tenant">${BOOT.tenant.logo ? `<img src="${esc(BOOT.tenant.logo)}" alt="">` : ''}<div><b>${esc(BOOT.tenant.name_ar)}</b><span>${esc(BOOT.tenant.name_en)}</span></div></div>
    </div>
    <div class="d-code" style="display:flex;gap:18px;justify-content:space-between;margin:-4px 0 8px">
      <span>رقم الاستلام: <b>${esc(C.no)}</b></span><span>النموذج: <b>${FORM_CODE}</b></span><span>الحالة: ${C.status === 'final' ? 'معتمد' : C.stage === 'submitted' ? 'بانتظار القرار' : 'مسودة'}</span>
    </div>

    <table class="d">
      <tr><td class="k">المورد</td><td>${esc(C.supplier)}</td><td class="k">رقم الفاتورة</td><td class="num">${esc(C.invoice)}${C.invDate ? ' · ' + fmtDate(C.invDate) : ''}</td></tr>
      <tr><td class="k">رقم أمر الشراء</td><td class="num">${esc(C.po) || '—'}</td><td class="k">تاريخ ووقت الوصول</td><td>${fmtDate(C.date)} ${fmtTime(C.tArrive)}</td></tr>
      <tr><td class="k">رقم السيارة</td><td>${esc(C.plate)}</td><td class="k">السائق / الرخصة</td><td>${esc(C.driver)}${C.license ? ' · <span class="dt">' + esc(C.license) + '</span>' : ''}</td></tr>
    </table>

    <div class="keep"><div class="d-h2">الجزء أ · الحرارة وفحص السيارة والمستندات</div>
    <table class="d">
      ${tRow('حرارة صندوق السيارة', has(C.boxTemp) ? `<span class="dt">${esc(C.boxTemp)} °م</span>` : '—', T.box)}
      ${tRow('أصناف ثلاجة', yn(C.fridge))}
      ${C.fridge === 'Y' ? tRow('حرارة صندوق التبريد', has(C.fridgeTemp) ? `<span class="dt">${esc(C.fridgeTemp)} °م</span>` : '—', T.fridge)
        + tRow('جهاز تسجيل الحرارة', yn(C.logger) + (C.logger === 'Y' ? ' · انحراف: ' + yn(C.exc) : '') + (C.loggerRef ? ' · <span class="dt">' + esc(C.loggerRef) + '</span>' : ''), C.logger === 'N' || C.exc === 'Y' ? 'bad' : C.logger ? 'ok' : null)
        + tRow('وقت إدخال الثلاجة', fmtTime(C.tFridge), T.time) : ''}
      ${C.tempNote.trim() ? tRow('مشكلة الحرارة والإجراء', esc(C.tempNote)) : ''}
    </table>
    <table class="d" style="margin-top:6px"><tr><th style="width:4%">م</th><th>البند</th><th style="width:12%">النتيجة</th><th style="width:34%">ملاحظات</th></tr>
      ${CHECKS.map((c, i) => `<tr class="${C.checks[c.id] === 'N' ? 'diff' : ''}"><td>${i + 1}</td><td>${esc(c.q)}</td><td>${yn(C.checks[c.id])}</td><td>${esc(C.checkNotes[c.id] || '')}</td></tr>`).join('')}
    </table></div>

    <div class="d-h2">الجزء ب · فحص الأصناف</div>
    ${C.erp ? `<table class="d"><tr><td class="k">إذن الاستلام على ERP</td><td class="num">${esc(C.erpRef)}</td><td class="k">عدد الأصناف</td><td class="num">${esc(C.erpCount)}</td></tr>
      <tr><td class="k">الفروقات</td><td colspan="3">${C.erpHasDiff ? esc(C.erpDiff).replace(/\n/g, '<br>') : 'لا توجد'}</td></tr></table>`
    : `<table class="d"><tr><th style="width:4%">م</th><th>الصنف والتركيز</th><th>التشغيلة</th><th>الصلاحية</th><th>الفاتورة</th><th>المستلم</th><th>الفرق</th><th>الحالة الظاهرية</th></tr>
      ${A.calcs.map(({ it, x }, i) => `<tr class="${x.list.length ? 'diff' : ''}"><td>${i + 1}</td><td>${esc(it.name)}${it.fridge ? ' ❄' : ''}</td><td class="num">${esc(it.batch)}</td><td class="num">${fmtMonth(it.exp)}</td><td class="num">${esc(it.qInv)}</td><td class="num">${esc(it.qRec)}</td><td class="num">${x.filled && x.d ? signed(x.d) : ''}</td><td>${esc(it.cond)}</td></tr>`).join('')}</table>`}

    ${!C.erp && A.diffs.length ? `<div class="keep"><div class="d-h2">سجل فروقات الاستلام · SH-001-F03</div>
    <table class="d"><tr><th style="width:4%">م</th><th>الصنف والتشغيلة</th><th>نوع الفرق</th><th>الكمية</th><th>الإجراء مع المورد</th><th>ملاحظات</th></tr>
      ${A.diffs.map(({ it, x }, i) => `<tr><td>${i + 1}</td><td>${esc(it.name)} · <span class="dt">${esc(it.batch)}</span></td><td>${x.list.map(f => esc(f.t)).join('، ')}</td><td class="num">${x.filled && x.d ? signed(x.d) : ''}</td><td>${esc(it.action)}</td><td>${esc(it.note)}</td></tr>`).join('')}</table></div>` : ''}

    <div class="keep"><div class="d-h2">الجزء ج · القرار</div>
    <table class="d">
      <tr><td class="k">القرار</td><td><b>${dec ? dec.label : '—'}</b></td><td class="k">رقم الانحراف</td><td class="num">${esc(C.devNo) || '—'}</td></tr>
      <tr><td class="k">سبب القرار</td><td colspan="3">${esc(C.reason).replace(/\n/g, '<br>') || '—'}</td></tr>
      ${A.nc.length ? `<tr><td class="k">عدم المطابقة</td><td colspan="3">${A.nc.map(n => esc(n.t)).join('، ')}</td></tr>` : ''}
    </table></div>

    <div class="d-signs">
      <div>أمين مخزن الاستلام<br>${esc(C.keeper)}${C.submittedAt ? '<br><span class="dt" style="font-size:10px">' + esc(C.submittedAt) + '</span>' : ''}</div>
      <div>المدير الصيدلي<br>${esc(C.decidedBy)}${C.decidedAt ? '<br><span class="dt" style="font-size:10px">' + esc(C.decidedAt) + '</span>' : ''}</div>
      <div>مندوب المورد (عند وجود فروق)<br>${esc(C.supRep)}</div>
    </div>
    <div class="d-foot">${esc(BOOT.tenant.name_ar)} · إتقان · نظام إدارة الجودة · ${FORM_CODE} · طبعه ${esc(BOOT.me.name)} في ${nowStr()}</div>`;
}

/* =========================================================
   البدء
   ========================================================= */
C = blankRec();
bind();
render();
DevLinks.mount({
  el: '#devLinks',
  ref: () => C.no,
  saved: () => !!C.id,
  prefill: () => {
    const A = analyse();
    const head = `استلام ${C.no}${C.supplier ? ' · المورد ' + C.supplier : ''}${C.invoice ? ' · فاتورة ' + C.invoice : ''}`;
    return { source: 'فروقات الاستلام (SH-001)', sop: 'SH-001 الاستلام', dept: 'الاستلام',
      what: head + (A.nc.length ? ':\n' + A.nc.map(n => '- ' + n.t).join('\n') : '') };
  },
  // رقم الانحراف يُكتب تلقائياً في القرار إذا كان فارغاً
  onLinks: list => { if (list.length && !C.devNo && !$('#fsD').disabled) { C.devNo = list[0].number; $('#devNo').value = C.devNo; } },
});
refreshList().then(async () => {
  if (await openFromQuery(LIST, id => run(async () => { const res = await api('GET', `${API}/${id}`); loadRecord(res.record); }))) return;
  if (!C.id && C.autoNo) { C.no = nextNo(C.date); $('#no').value = C.no; refresh(); }
});

document.getElementById('peopleList').innerHTML = BOOT.users.map(u => `<option value="${esc(u.name)}">${esc(u.role || '')}</option>`).join('');
@endverbatim
</script>
@endpush