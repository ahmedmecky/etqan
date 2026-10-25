@extends('layouts.app')

@section('title', 'محاضر الجرد')

@push('head')
<style>
@verbatim
  :root{
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
@endverbatim
</style>
@endpush

@section('content')
@verbatim
<div class="scr-head">
  <h1>محضر جرد مخزن ونتيجة الجرد</h1>
  <span class="code">SH-016-F01 · F03 · v02</span>
  <span id="badge" class="badge b-draft">مسودة</span>
</div>
<div class="scr">
<div class="scr-main">
<fieldset id="fs">

  <!-- ١. بيانات الجرد -->
  <section class="card">
    <h2><span class="step">١</span> بيانات الجرد</h2>
    <div class="grid">
      <div><label class="f req" for="no">رقم المحضر</label><input type="text" id="no" class="ltr-in" autocomplete="off"></div>
      <div><label class="f req" for="type">نوع الجرد</label>
        <select id="type">
          <option value="full">جرد شامل</option>
          <option value="monthly">جرد عشوائي شهري</option>
          <option value="weekly">جرد عشوائي أسبوعي (المدير الصيدلي)</option>
          <option value="special">جرد استثنائي</option>
        </select></div>
      <div><label class="f req" for="date">التاريخ</label><input type="date" id="date"></div>
      <div><label class="f" for="day">اليوم</label><input type="text" id="day" readonly></div>
      <div><label class="f req" for="tStart">وقت بدء الجرد</label><input type="time" id="tStart"></div>
      <div><label class="f" for="tEnd">وقت الانتهاء</label><input type="time" id="tEnd"></div>
    </div>
    <div class="grid" style="margin-top:14px">
      <div style="grid-column:span 2"><label class="f req" for="company">اسم الشركة / المخزن</label><input type="text" id="company"></div>
      <div style="grid-column:span 2"><label class="f req" for="zones">المخزن / المناطق المجرودة</label><input type="text" id="zones" placeholder="مثال: مخزن الجملة · الثلاجات"></div>
    </div>
  </section>

  <!-- ٢. آخر حركة -->
  <section class="card">
    <h2><span class="step">٢</span> آخر حركة قبل الجرد (قفل الدفاتر)</h2>
    <p class="sub">تُسجل آخر فاتورة وارد وآخر فاتورة صادر قبل بدء العد، حتى تكون المقارنة مع رصيد النظام في نفس اللحظة.</p>
    <h3>آخر فاتورة وارد</h3>
    <div class="grid3">
      <div><label class="f req" for="inSup">الشركة / المورد</label><input type="text" id="inSup"></div>
      <div><label class="f req" for="inNo">رقم الفاتورة</label><input type="text" id="inNo" class="ltr-in"></div>
      <div><label class="f req" for="inDate">التاريخ</label><input type="date" id="inDate"></div>
    </div>
    <h3>آخر فاتورة صادر</h3>
    <div class="grid3">
      <div><label class="f req" for="outCus">العميل / الصيدلية</label><input type="text" id="outCus"></div>
      <div><label class="f req" for="outNo">رقم الفاتورة</label><input type="text" id="outNo" class="ltr-in"></div>
      <div><label class="f req" for="outDate">التاريخ</label><input type="date" id="outDate"></div>
    </div>
    <div class="grid" style="margin-top:12px">
      <div><label class="chk"><input type="checkbox" id="moveStop"> تم إيقاف الحركة على النظام أثناء الجرد</label></div>
      <div><label class="chk"><input type="checkbox" id="pendFlag"> توجد شحنات واردة لم تُسجل أو طلبيات مجهزة لم تخرج</label></div>
    </div>
    <div id="pendBox" hidden style="margin-top:8px"><label class="f" for="pending">تفاصيل الشحنات والطلبيات المعلقة</label><textarea id="pending"></textarea></div>
  </section>

  <!-- ٣. لجنة الجرد -->
  <section class="card">
    <h2><span class="step">٣</span> لجنة الجرد</h2>
    <div class="grid">
      <div><label class="f req" for="head">رئيس لجنة الجرد</label><input type="text" id="head" placeholder="الاسم" list="peopleList"></div>
      <div><label class="f" for="headRole">وظيفته</label><select id="headRole"></select></div>
      <div><label class="f req" for="pharm">المدير الصيدلي</label><input type="text" id="pharm" placeholder="الاسم" list="peopleList"></div>
      <div><label class="f" for="delegate">مفوض الرئيس التنفيذي</label><input type="text" id="delegate" placeholder="الاسم" list="peopleList"></div>
      <div><label class="f req" for="keeper">أمين المخزن</label><input type="text" id="keeper" placeholder="الاسم" list="peopleList"></div>
    </div>
    <h3 style="display:flex;align-items:center;gap:10px">فرق العد <span style="flex:1"></span><button type="button" class="btn ghost small add" data-act="add-team">+ إضافة فريق</button></h3>
    <p class="sub" style="margin:0 0 8px">كل فريق = محاسب ومحضر، ولا يعد المحضر منطقته منفرداً.</p>
    <div class="tbl-wrap">
      <table class="t">
        <thead><tr><th>م</th><th style="min-width:160px">المنطقة</th><th style="min-width:160px">المحاسب</th><th style="min-width:160px">المحضر</th><th>عدد قوائم العد</th><th></th></tr></thead>
        <tbody id="teamRows"></tbody>
      </table>
    </div>
  </section>

  <!-- ٤. الأصناف -->
  <section class="card">
    <h2><span class="step">٤</span> <span id="s4Title">الأصناف المجرودة</span>
      <span style="flex:1"></span>
      <span class="seg" id="modeSeg">
        <label><input type="radio" name="mode" value="items" checked> بالأصناف</label>
        <label><input type="radio" name="mode" value="totals"> إجمالي فقط</label>
      </span>
    </h2>
    <div id="itemsBox">
      <p class="sub">يُسجل كل صنف بالتشغيلة. الفرق والقيمة يُحسبان تلقائياً. أي فرق يحتاج سبباً وقراراً.</p>
      <div class="tbl-wrap">
        <table class="t items">
          <thead><tr><th>م</th><th style="min-width:150px">الصنف والتركيز</th><th style="width:90px">التشغيلة</th><th style="width:112px">الصلاحية</th><th style="width:75px">الموقع</th><th>رصيد النظام</th><th>الفعلي</th><th>سعر الوحدة</th><th>الفرق</th><th>قيمة الفرق</th><th>حرج</th><th></th></tr></thead>
          <tbody id="itemRows"></tbody>
        </table>
      </div>
      <div class="actions" style="margin-top:10px"><button type="button" class="btn ghost small add" data-act="add-item">+ إضافة صنف</button></div>
    </div>
    <div id="totalsBox" hidden>
      <p class="sub" id="totalsSub">للجرد الشامل: تُدخل الإجماليات من قوائم العد (SH-016-F02)، وتُسجل الأصناف التي بها فروق فقط في الجدول بالأسفل.</p>
      <div class="grid" id="erpRefBox" hidden style="margin-bottom:14px">
        <div><label class="f req" for="erpRef">رقم تقرير الجرد على ERP</label><input type="text" id="erpRef" class="ltr-in"></div>
        <div><label class="f" for="erpDate">تاريخ التقرير</label><input type="date" id="erpDate"></div>
      </div>
      <div class="grid">
        <div><label class="f req" for="tCount">عدد الأصناف المجرودة</label><input type="number" id="tCount" min="0" step="1"></div>
        <div><label class="f req" for="tBook">القيمة الكلية الدفترية</label><input type="number" id="tBook" min="0" step="0.01"></div>
        <div><label class="f req" for="tActual">القيمة الكلية الفعلية</label><input type="number" id="tActual" min="0" step="0.01"></div>
      </div>
      <p class="small" id="totalsHint" style="margin:10px 0 0">الأصناف التي بها فروق: أضفها من تبويب «بالأصناف».</p>
      <div id="erpDiffBox" hidden style="margin-top:14px">
        <h3 style="margin:0 0 8px">الفروق والقرار</h3>
        <div class="grid">
          <div style="grid-column:span 3"><label class="f" for="diffNote">أسباب الفروق والقرار (تفاصيل الأصناف في تقرير ERP)</label><textarea id="diffNote"></textarea></div>
          <div><label class="f" for="devNo">رقم الانحراف (SH-008)</label><input type="text" id="devNo" class="ltr-in" placeholder="DEV-..."></div>
        </div>
      </div>
    </div>
  </section>

  <!-- ٥. النتيجة -->
  <section class="card">
    <h2><span class="step">٥</span> نتيجة الجرد</h2>
    <div class="res">
      <div><span>عدد الأصناف</span><b id="rCount">0</b></div>
      <div><span>القيمة الكلية الدفترية</span><b id="rBook">0.00</b></div>
      <div><span>القيمة الكلية الفعلية</span><b id="rActual">0.00</b></div>
      <div><span>فرق الجرد</span><b id="rDiff">0.00</b></div>
      <div><span>نسبة الاختلاف</span><b id="rPct">0.00%</b></div>
      <div><span>أصناف بها فروق</span><b id="rDiffItems">0</b></div>
    </div>
    <div class="grid" style="margin-top:12px">
      <div><label class="f" for="tol">الحد المقبول لنسبة الاختلاف في القيمة %</label><input type="number" id="tol" min="0" step="0.1"></div>
    </div>
    <div id="verdict" class="verdict ok"></div>
    <div id="devLinks"></div>
  </section>

  <!-- ٦. الفروق -->
  <section class="card" id="diffCard">
    <h2><span class="step">٦</span> التحقيق في الفروق والقرار</h2>
    <p class="sub">لكل صنف به فرق: إعادة عد بلجنة أخرى، ثم السبب والقرار. الفروق خارج الحدود تُسجل كانحراف (SH-008).</p>
    <div class="tbl-wrap"><table class="t" style="min-width:980px">
      <thead><tr><th>م</th><th style="min-width:180px">الصنف والتشغيلة</th><th>الفرق</th><th>إعادة العد</th><th style="min-width:220px">السبب</th><th style="min-width:160px">القرار</th><th style="min-width:110px">رقم الانحراف</th></tr></thead>
      <tbody id="diffRows"></tbody>
    </table></div>
  </section>

  <!-- ٧. نص المحضر -->
  <section class="card">
    <h2><span class="step">٧</span> نص المحضر</h2>
    <p class="sub">يُكتب تلقائياً بنفس صيغة المحضر الورقي. ما يظهر باللون الأحمر لم يُستكمل بعد.</p>
    <div class="tpl" id="tpl"></div>
    <div style="margin-top:12px"><label class="f" for="notes">ملاحظات اللجنة (أصناف تالفة أو منتهية أو في غير مكانها…)</label><textarea id="notes"></textarea></div>
  </section>

</fieldset>

  <section class="card">
    <div class="actions">
      <button type="button" class="btn" data-act="save" id="btnSave">حفظ</button>
      <button type="button" class="btn okb" data-act="final" id="btnFinal">اعتماد وإقفال المحضر</button>
      <button type="button" class="btn ghost" data-act="reopen" id="btnReopen" hidden>إعادة فتح للتعديل</button>
      <button type="button" class="btn gold" data-act="print">طباعة</button>
      <span style="flex:1"></span>
      <button type="button" class="btn ghost" data-act="new">محضر جديد</button>
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
    <div class="kv"><span>رقم المحضر</span><b id="sNo">—</b></div>
    <div class="kv"><span>نوع الجرد</span><span id="sType">—</span></div>
    <div class="kv"><span>عدد الأصناف</span><b id="sCount">0</b></div>
    <div class="kv"><span>نسبة الاختلاف</span><b id="sPct">0.00%</b></div>
    <div class="kv"><span>النتيجة</span><span id="sRes">—</span></div>
    <div class="kv"><span>الحالة</span><span id="sSt">مسودة</span></div>
  </section>
  <section class="card">
    <h2 style="font-size:15px">المحاضر المحفوظة</h2>
    <div class="saved" id="saved"></div>
    <p class="small" style="margin:8px 0 0">المحاضر محفوظة على الخادم لهذا المخزن، ويراها كل مستخدمي المخزن.</p>
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
const FORM_CODE = 'SH-016-F01 · F03';
const API = BOOT.urls.records;
const SET = BOOT.settings || {};
const ITEMS_ON = SET.inventory_items !== false;   // إعداد المخزن: تسجيل الأصناف على إتقان
const DEFAULT_TOL = SET.inventory_tolerance ?? 0.5;  // % من القيمة الكلية (SH-016)
const TYPES = { full: 'جرد شامل', monthly: 'جرد عشوائي شهري', weekly: 'جرد عشوائي أسبوعي', special: 'جرد استثنائي' };
const HEAD_ROLES = ['مدير الحسابات', 'المدير المالي', 'المدير الصيدلي', 'مدير المخزن', 'مدير إدارة الجودة', 'أخرى'];
const CAUSES = ['', 'خطأ في التحضير / الصرف', 'خطأ في الاستلام', 'مرتجع لم يُسجل', 'خطأ إدخال على النظام', 'تشغيلة مسجلة خطأ', 'كسر أو تلف لم يُسجل', 'صنف في غير مكانه (وُجد)', 'فقد غير مبرر', 'أخرى'];
const DECISIONS = ['', 'تسوية معتمدة', 'تصحيح التشغيلة على النظام', 'لا تسوية (وُجد بعد إعادة العد)', 'تحقيق كانحراف (SH-008)', 'اشتباه (SH-011 / SH-029)'];
const DAYS = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];

/* =========================================================
   أدوات
   ========================================================= */
const $ = s => document.querySelector(s);
const pad = (n, l = 2) => String(n).padStart(l, '0');
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const today = () => { const d = new Date(); return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`; };
const nowStr = () => { const d = new Date(); return `${today()} ${pad(d.getHours())}:${pad(d.getMinutes())}`; };
const nowTime = () => { const d = new Date(); return `${pad(d.getHours())}:${pad(d.getMinutes())}`; };
const dayName = iso => iso ? DAYS[new Date(iso + 'T00:00:00').getDay()] : '';
const fmtDate = iso => { if (!iso) return ''; const [y, m, d] = iso.split('-'); return `<span class="dt">${d}/${m}/${y}</span>`; };
const fmtMonth = iso => { if (!iso) return ''; const [y, m] = iso.split('-'); return `${m}/${y}`; };
function fmtTime(t) { if (!t) return ''; let [h, m] = t.split(':').map(Number); const s = h < 12 ? 'ص' : 'م'; h = h % 12 || 12; return `${h}:${pad(m)} ${s}`; }
const num = v => { const n = parseFloat(v); return isFinite(n) ? n : 0; };
const has = v => v !== '' && v !== null && v !== undefined && isFinite(parseFloat(v));
const money = n => (Math.round(n * 100) / 100).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const qty = n => (Math.round(n * 1000) / 1000).toLocaleString('en-US');
const signed = (n, f) => (n > 0 ? '+' : '') + f(n);
const uid = () => Math.random().toString(36).slice(2, 10);
const blank = t => `<span class="blank">${t}</span>`;

/* =========================================================
   التخزين
   ========================================================= */
let LIST = [];      // ملخص المحاضر المحفوظة
let LOGS = [];      // سجل حركات المحضر الحالي (من الخادم)
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
  const prefix = `INV-${y}-`;
  const nums = LIST.filter(m => m.number && m.number.startsWith(prefix)).map(m => parseInt(m.number.slice(prefix.length), 10) || 0);
  return prefix + pad((nums.length ? Math.max(...nums) : 0) + 1, 3);
}

function newItem() { return { id: uid(), name: '', batch: '', exp: '', loc: '', book: '', actual: '', price: '', critical: false, recount: '', cause: '', decision: '', dev: '' }; }

function blankCount() {
  return {
    id: null, no: nextNo(today()), autoNo: true, type: 'weekly', date: today(), tStart: nowTime(), tEnd: '',
    company: BOOT.tenant.name_ar, zones: '',
    inSup: '', inNo: '', inDate: '', outCus: '', outNo: '', outDate: '', moveStop: false, pendFlag: false, pending: '',
    head: '', headRole: 'مدير الحسابات', pharm: '', delegate: '', keeper: '',
    teams: [{ id: uid(), zone: '', acc: '', picker: '', sheets: '' }],
    erp: !ITEMS_ON,     // true = العد على ERP، المحضر يسجل الإجماليات فقط
    mode: ITEMS_ON ? 'items' : 'totals', items: [newItem()], tCount: '', tBook: '', tActual: '', tol: DEFAULT_TOL,
    erpRef: '', erpDate: '', diffNote: '', devNo: '',
    notes: '', status: 'draft'
  };
}

/* =========================================================
   الحسابات
   ========================================================= */
function itemCalc(it) {
  const book = num(it.book), act = has(it.recount) ? num(it.recount) : num(it.actual), price = num(it.price);
  const filled = has(it.book) && has(it.actual);
  const d = act - book;
  return { filled, book, act, d, dv: d * price, bv: book * price, av: act * price };
}

function result() {
  const rows = C.erp ? [] : C.items.filter(it => it.name.trim());
  const calcs = rows.map(it => ({ it, c: itemCalc(it) }));
  const diffs = calcs.filter(x => x.c.filled && x.c.d !== 0);
  let count, book, actual;
  if (C.erp || C.mode === 'totals') {
    count = num(C.tCount); book = num(C.tBook); actual = num(C.tActual);
  } else {
    count = rows.length;
    book = calcs.reduce((s, x) => s + x.c.bv, 0);
    actual = calcs.reduce((s, x) => s + x.c.av, 0);
  }
  const diff = actual - book;
  const pct = book ? Math.abs(diff) / book * 100 : (diff ? 100 : 0);
  const critDiff = diffs.some(x => x.it.critical);
  const tol = num(C.tol);
  const within = pct <= tol && !critDiff;
  return { rows, calcs, diffs, count, book, actual, diff, pct, critDiff, within, tol };
}

/* =========================================================
   الرسم
   ========================================================= */
const FIELDS = ['no', 'type', 'date', 'tStart', 'tEnd', 'company', 'zones', 'inSup', 'inNo', 'inDate', 'outCus', 'outNo', 'outDate', 'pending', 'head', 'pharm', 'delegate', 'keeper', 'tCount', 'tBook', 'tActual', 'tol', 'notes', 'erpRef', 'erpDate', 'diffNote', 'devNo'];

function render() {
  const locked = C.status === 'final';
  $('#fs').disabled = locked;
  $('#badge').className = 'badge ' + (locked ? 'b-ok' : 'b-draft');
  $('#badge').textContent = locked ? 'معتمد' : 'مسودة';
  $('#btnFinal').hidden = locked; $('#btnSave').hidden = locked; $('#btnReopen').hidden = !locked || !BOOT.can.reopen;

  FIELDS.forEach(k => { $('#' + k).value = C[k] ?? ''; });
  $('#day').value = dayName(C.date);
  $('#moveStop').checked = !!C.moveStop; $('#pendFlag').checked = !!C.pendFlag; $('#pendBox').hidden = !C.pendFlag;
  $('#headRole').innerHTML = HEAD_ROLES.map(r => `<option ${r === C.headRole ? 'selected' : ''}>${r}</option>`).join('');
  document.querySelectorAll('input[name=mode]').forEach(r => { r.checked = r.value === C.mode; });
  applyErp();

  renderTeams(); renderItems(); refresh(); renderSaved(); renderAudit();
  DevLinks.refresh();
}

/* وضع ERP: يُخفى جدول الأصناف ويُسجل المحضر الإجماليات من تقرير ERP */
function applyErp() {
  const erp = !!C.erp;
  $('#s4Title').textContent = erp ? 'نتيجة العد من نظام ERP' : 'الأصناف المجرودة';
  $('#modeSeg').hidden = erp;
  $('#itemsBox').hidden = erp;
  $('#totalsBox').hidden = !erp && C.mode !== 'totals';
  $('#erpRefBox').hidden = !erp;
  $('#erpDiffBox').hidden = !erp;
  $('#totalsHint').hidden = erp;
  $('#totalsSub').textContent = erp
    ? 'العد يتم على نظام ERP. تُنقل الإجماليات من تقرير الجرد على ERP، وتُرفق نسخة التقرير مع المحضر الورقي.'
    : 'للجرد الشامل: تُدخل الإجماليات من قوائم العد (SH-016-F02)، وتُسجل الأصناف التي بها فروق فقط في الجدول بالأسفل.';
}

function renderTeams() {
  $('#teamRows').innerHTML = C.teams.map((t, i) => `
    <tr data-id="${t.id}">
      <td class="n">${i + 1}</td>
      <td><input type="text" data-f="zone" value="${esc(t.zone)}"></td>
      <td><input type="text" data-f="acc" value="${esc(t.acc)}" list="peopleList"></td>
      <td><input type="text" data-f="picker" value="${esc(t.picker)}" list="peopleList"></td>
      <td style="width:120px"><input type="number" data-f="sheets" min="0" step="1" value="${esc(t.sheets)}"></td>
      <td><button type="button" class="x" data-act="del-team" title="حذف">×</button></td>
    </tr>`).join('');
}

function renderItems() {
  $('#itemRows').innerHTML = C.items.map((it, i) => `
    <tr data-id="${it.id}">
      <td class="n">${i + 1}</td>
      <td><input type="text" data-f="name" value="${esc(it.name)}" placeholder="اسم الصنف والتركيز"></td>
      <td><input type="text" data-f="batch" value="${esc(it.batch)}" class="ltr-in"></td>
      <td><input type="month" data-f="exp" value="${esc(it.exp)}" style="width:100%;padding:5px 6px;border:1px solid var(--line);border-radius:8px;font:inherit;font-size:13px;direction:ltr"></td>
      <td><input type="text" data-f="loc" value="${esc(it.loc)}" class="ltr-in"></td>
      <td style="width:78px"><input type="number" data-f="book" value="${esc(it.book)}" step="any"></td>
      <td style="width:78px"><input type="number" data-f="actual" value="${esc(it.actual)}" min="0" step="any"></td>
      <td style="width:84px"><input type="number" data-f="price" value="${esc(it.price)}" min="0" step="0.01"></td>
      <td class="c" data-o="d">—</td>
      <td class="c" data-o="dv">—</td>
      <td style="text-align:center;padding-top:9px"><input type="checkbox" data-f="critical" ${it.critical ? 'checked' : ''} title="صنف ثلاجة أو عالي القيمة: لا يُقبل أي فرق" style="width:17px;height:17px;accent-color:var(--bad)"></td>
      <td><button type="button" class="x" data-act="del-item" title="حذف">×</button></td>
    </tr>`).join('');
}

function updateItemCells() {
  document.querySelectorAll('#itemRows tr[data-id]').forEach(tr => {
    const it = C.items.find(x => x.id === tr.dataset.id); if (!it) return;
    const c = itemCalc(it);
    const dCell = tr.querySelector('[data-o=d]'), vCell = tr.querySelector('[data-o=dv]');
    if (!c.filled) { dCell.textContent = '—'; vCell.textContent = '—'; dCell.className = vCell.className = 'c'; tr.classList.remove('diff'); return; }
    dCell.textContent = signed(c.d, qty); vCell.textContent = has(it.price) ? signed(c.dv, money) : '—';
    const cls = c.d === 0 ? 'c c-ok' : 'c c-bad';
    dCell.className = vCell.className = cls;
    tr.classList.toggle('diff', c.d !== 0);
  });
}

function renderDiffs(R) {
  const rows = R.diffs;
  $('#diffCard').hidden = !rows.length;
  $('#diffRows').innerHTML = rows.map(({ it, c }, i) => `
    <tr data-id="${it.id}">
      <td class="n">${i + 1}</td>
      <td style="padding-top:10px"><b>${esc(it.name)}</b><div class="small">${esc(it.batch) || '—'}${it.critical ? ' · <span class="c-bad">حرج</span>' : ''}</div></td>
      <td class="c c-bad">${signed(num(it.actual) - num(it.book), qty)}</td>
      <td style="width:100px"><input type="number" data-f="recount" value="${esc(it.recount)}" min="0" step="any" placeholder="الكمية"></td>
      <td><select data-f="cause">${CAUSES.map(x => `<option value="${x}" ${x === it.cause ? 'selected' : ''}>${x || '— اختر —'}</option>`).join('')}</select></td>
      <td><select data-f="decision">${DECISIONS.map(x => `<option value="${x}" ${x === it.decision ? 'selected' : ''}>${x || '— اختر —'}</option>`).join('')}</select></td>
      <td><input type="text" data-f="dev" value="${esc(it.dev)}" class="ltr-in" placeholder="DEV-..."></td>
    </tr>`).join('');
}

function refresh() {
  updateItemCells();
  const R = result();
  $('#rCount').textContent = R.count;
  $('#rBook').textContent = money(R.book);
  $('#rActual').textContent = money(R.actual);
  $('#rDiff').textContent = signed(R.diff, money);
  $('#rDiff').className = R.diff ? 'c-bad' : '';
  $('#rPct').textContent = R.pct.toFixed(2) + '%';
  $('#rPct').className = R.pct > R.tol ? 'c-bad' : '';
  $('#rDiffItems').textContent = R.diffs.length;
  const v = $('#verdict');
  if (!R.count) { v.className = 'verdict'; v.style.background = '#eef0f0'; v.style.color = 'var(--muted)'; v.textContent = C.erp ? 'أدخل الإجماليات من تقرير ERP لعرض النتيجة.' : 'أدخل الأصناف أو الإجماليات لعرض النتيجة.'; }
  else {
    v.style.background = ''; v.style.color = '';
    v.className = 'verdict ' + (R.within ? 'ok' : 'bad');
    v.textContent = R.within
      ? (R.diff === 0 && !R.diffs.length ? 'مطابق: لا توجد فروق.' : `داخل الحدود المقبولة (${R.tol}%). الفروق تُسوى بقرار معتمد.`)
      : (R.critDiff ? 'خارج الحدود: يوجد فرق في صنف حرج (ثلاجة / عالي القيمة). يُسجل انحراف (SH-008).' : `خارج الحدود: نسبة الاختلاف ${R.pct.toFixed(2)}% أكبر من ${R.tol}%. يُسجل انحراف (SH-008).`);
  }
  // لا نعيد رسم جدول الفروق أثناء الكتابة فيه
  if (!document.activeElement || !document.activeElement.closest('#diffRows')) renderDiffs(R);

  $('#sNo').textContent = C.no || '—';
  $('#sType').textContent = TYPES[C.type];
  $('#sCount').textContent = R.count;
  $('#sPct').textContent = R.pct.toFixed(2) + '%';
  $('#sRes').innerHTML = !R.count ? '—' : R.within ? '<span class="c-ok">داخل الحدود</span>' : '<span class="c-bad">خارج الحدود</span>';
  $('#sSt').textContent = C.status === 'final' ? 'معتمد' : 'مسودة';

  $('#tpl').innerHTML = templateHtml(true);
}

/* نص المحضر بنفس صيغة المحضر الورقي */
function templateHtml(screen) {
  const B = (v, t) => v ? `<b>${esc(v)}</b>` : (screen ? blank(t) : '……………');
  const D = (iso, t) => iso ? `<b>${fmtDate(iso)}</b>` : (screen ? blank(t) : '……………');
  let h = `إنه في يوم ${B(dayName(C.date), 'اليوم')} الموافق ${D(C.date, 'التاريخ')} في تمام الساعة ${B(fmtTime(C.tStart), 'الوقت')}، `;
  h += `تم بدء ${B(TYPES[C.type], 'نوع الجرد')} لمخزن ${B(C.company, 'اسم الشركة / المخزن')}${C.zones ? ` (${esc(C.zones)})` : ''}.<br>`;
  h += `آخر فاتورة وارد ← شركة / ${B(C.inSup, 'المورد')} رقم / ${B(C.inNo, 'الرقم')} تاريخ / ${D(C.inDate, 'التاريخ')}.<br>`;
  h += `آخر فاتورة صادر ← صيدلية / ${B(C.outCus, 'العميل')} رقم / ${B(C.outNo, 'الرقم')} تاريخ / ${D(C.outDate, 'التاريخ')}.<br>`;
  h += C.moveStop ? 'وقد تم إيقاف الحركة على النظام أثناء الجرد.' : (screen ? blank('لم يُؤكد إيقاف الحركة على النظام') : 'ولم تُوقف الحركة على النظام.');
  if (C.pendFlag) h += ` وتوجد شحنات أو طلبيات معلقة: ${esc(C.pending) || '—'}.`;
  h += '<br>';
  h += `وذلك بمعرفة السيد / ${B(C.head, 'رئيس اللجنة')} ${esc(C.headRole)} رئيس لجنة الجرد، `;
  h += `في وجود المدير الصيدلي د / ${B(C.pharm, 'المدير الصيدلي')}، `;
  if (C.delegate || C.type === 'full') h += `ومفوض الرئيس التنفيذي / ${B(C.delegate, 'المفوض')}، `;
  h += `وأمين المخزن / ${B(C.keeper, 'أمين المخزن')}.`;
  const R = result();
  if (R.count) {
    if (C.erp) h += `<br>وتم العد على نظام ERP وفقاً لتقرير الجرد رقم ${B(C.erpRef, 'رقم تقرير ERP')}${C.erpDate ? ` بتاريخ ${D(C.erpDate)}` : ''}.`;
    h += `<br>وأسفر الجرد عن: عدد الأصناف <b>${R.count}</b>، القيمة الدفترية <b class="dt">${money(R.book)}</b>، القيمة الفعلية <b class="dt">${money(R.actual)}</b>، فرق الجرد <b class="dt">${signed(R.diff, money)}</b> بنسبة <b class="dt">${R.pct.toFixed(2)}%</b>، `;
    h += R.within ? 'وهو داخل الحدود المقبولة.' : 'وهو خارج الحدود المقبولة ويُحقق فيه كانحراف.';
    if (C.erp && C.diffNote.trim()) h += `<br>الفروق والقرار: ${esc(C.diffNote).replace(/\n/g, '<br>')}`;
    if (C.erp && C.devNo.trim()) h += ` (انحراف رقم <b class="dt">${esc(C.devNo)}</b>)`;
  }
  if (C.notes.trim()) h += `<br>${esc(C.notes).replace(/\n/g, '<br>')}`;
  if (C.tEnd) h += `<br>وانتهى الجرد في تمام الساعة <b>${fmtTime(C.tEnd)}</b>.`;
  return h;
}

/* =========================================================
   الأحداث
   ========================================================= */
function bind() {
  FIELDS.forEach(k => $('#' + k).addEventListener('input', e => {
    C[k] = e.target.value;
    if (k === 'no') C.autoNo = false;
    if (k === 'date') { $('#day').value = dayName(C.date); if (C.autoNo) { C.no = nextNo(C.date); $('#no').value = C.no; } }
    if (k === 'type') { if (!C.erp && C.type === 'full' && C.mode !== 'totals') setMode('totals'); }
    refresh();
  }));
  $('#headRole').addEventListener('change', e => { C.headRole = e.target.value; refresh(); });
  $('#moveStop').addEventListener('change', e => { C.moveStop = e.target.checked; refresh(); });
  $('#pendFlag').addEventListener('change', e => { C.pendFlag = e.target.checked; $('#pendBox').hidden = !C.pendFlag; refresh(); });
  document.querySelectorAll('input[name=mode]').forEach(r => r.addEventListener('change', e => setMode(e.target.value)));

  const rowEdit = (sel, arr) => $(sel).addEventListener('input', e => {
    const host = e.target.closest('[data-id]'); const f = e.target.dataset.f; if (!host || !f) return;
    const it = C[arr].find(x => x.id === host.dataset.id); if (!it) return;
    it[f] = e.target.type === 'checkbox' ? e.target.checked : e.target.value;
    refresh();
  });
  rowEdit('#teamRows', 'teams');
  rowEdit('#itemRows', 'items');
  rowEdit('#diffRows', 'items');
  $('#diffRows').addEventListener('focusout', () => setTimeout(() => { if (!document.activeElement.closest('#diffRows')) renderDiffs(result()); }, 0));
}

function setMode(m) {
  C.mode = m;
  document.querySelectorAll('input[name=mode]').forEach(r => { r.checked = r.value === m; });
  applyErp();
  refresh();
}


function validate(final) {
  const e = [];
  if (!C.no.trim()) e.push('رقم المحضر مطلوب');
  if (LIST.some(m => m.number === C.no && m.id !== C.id)) e.push(`رقم المحضر ${C.no} مستخدم في محضر آخر`);
  if (!C.date) e.push('التاريخ مطلوب');
  if (!final) return e;
  if (!C.tStart) e.push('وقت بدء الجرد مطلوب');
  if (!C.tEnd) e.push('وقت الانتهاء مطلوب');
  if (!C.company.trim()) e.push('اسم الشركة / المخزن مطلوب');
  if (!C.zones.trim()) e.push('حدد المخزن أو المناطق المجرودة');
  if (!C.inSup.trim() || !C.inNo.trim() || !C.inDate) e.push('أكمل بيانات آخر فاتورة وارد (المورد، الرقم، التاريخ)');
  if (!C.outCus.trim() || !C.outNo.trim() || !C.outDate) e.push('أكمل بيانات آخر فاتورة صادر (العميل، الرقم، التاريخ)');
  if (C.inDate && C.inDate > C.date) e.push('تاريخ آخر فاتورة وارد بعد تاريخ الجرد');
  if (C.outDate && C.outDate > C.date) e.push('تاريخ آخر فاتورة صادر بعد تاريخ الجرد');
  if (!C.moveStop) e.push('أكد إيقاف الحركة على النظام أثناء الجرد');
  if (C.pendFlag && !C.pending.trim()) e.push('اكتب تفاصيل الشحنات والطلبيات المعلقة');
  if (!C.head.trim()) e.push('اسم رئيس لجنة الجرد مطلوب');
  if (!C.pharm.trim()) e.push('اسم المدير الصيدلي مطلوب');
  if (!C.keeper.trim()) e.push('اسم أمين المخزن مطلوب');
  if (C.type === 'full') {
    if (!C.delegate.trim()) e.push('الجرد الشامل يحتاج حضور مفوض الرئيس التنفيذي');
    if (!C.teams.some(t => t.zone.trim() && t.acc.trim() && t.picker.trim())) e.push('أدخل فريق عد واحداً على الأقل (منطقة، محاسب، محضر)');
  }
  const R = result();
  if (C.erp) {
    if (!C.erpRef.trim()) e.push('اكتب رقم تقرير الجرد على ERP');
    if (!has(C.tCount) || !has(C.tBook) || !has(C.tActual)) e.push('أدخل عدد الأصناف والقيمة الدفترية والفعلية من تقرير ERP');
    if (R.diff !== 0 && !C.diffNote.trim()) e.push('يوجد فرق في القيمة: اكتب أسباب الفروق والقرار');
    if (!R.within && !C.devNo.trim()) e.push('النتيجة خارج الحدود: اكتب رقم الانحراف (SH-008)');
    return e;
  }
  if (C.mode === 'totals') {
    if (!has(C.tCount) || !has(C.tBook) || !has(C.tActual)) e.push('أدخل عدد الأصناف والقيمة الدفترية والفعلية');
  } else {
    if (!R.rows.length) e.push('أضف صنفاً واحداً على الأقل');
    R.calcs.forEach(({ it, c }, i) => {
      if (!it.batch.trim()) e.push(`التشغيلة مطلوبة للصنف «${it.name}»`);
      if (!c.filled) e.push(`أدخل رصيد النظام والكمية الفعلية للصنف «${it.name}»`);
      if (!has(it.price)) e.push(`أدخل سعر الوحدة للصنف «${it.name}»`);
    });
  }
  R.diffs.forEach(({ it }) => {
    if (!it.cause) e.push(`حدد سبب الفرق في «${it.name}»`);
    if (!it.decision) e.push(`حدد القرار في فرق «${it.name}»`);
  });
  if (!R.within && !R.diffs.some(x => x.it.dev.trim())) e.push('النتيجة خارج الحدود: اكتب رقم الانحراف (SH-008) لصنف واحد على الأقل');
  return e;
}

function showMsg(errs, ok) {
  $('#msg').innerHTML = errs && errs.length ? `<div class="errors">لم يتم الحفظ:<ul>${errs.map(x => `<li>${esc(x)}</li>`).join('')}</ul></div>`
    : ok ? `<div class="okmsg">${esc(ok)}</div>` : '';
}

function recordTitle() {
  return (TYPES[C.type] || '') + (C.zones ? ' · ' + C.zones : '');
}

/* حفظ على الخادم · status: draft أو final */
async function persist(status) {
  const body = { number: C.no.trim(), title: recordTitle(), record_date: C.date || null, status, data: Object.assign({}, C, { status }) };
  const res = C.id ? await api('PUT', `${API}/${C.id}`, body) : await api('POST', API, body);
  loadRecord(res.record);
  await refreshList();
}

/* تحميل سجل من الخادم إلى الشاشة */
function loadRecord(rec) {
  C = Object.assign(blankCount(), rec.data || {});
  if (!rec.data || rec.data.erp === undefined) C.erp = false;   // محاضر قبل إعداد ERP كانت بالأصناف
  C.id = rec.id;
  C.no = rec.number;
  C.status = rec.status;
  LOGS = rec.logs || [];
  render();
}

document.addEventListener('click', async e => {
  const b = e.target.closest('[data-act]'); if (!b) return;
  const a = b.dataset.act;

  if (a === 'add-team') { C.teams.push({ id: uid(), zone: '', acc: '', picker: '', sheets: '' }); renderTeams(); }
  if (a === 'del-team') { const id = b.closest('[data-id]').dataset.id; C.teams = C.teams.filter(x => x.id !== id); renderTeams(); }
  if (a === 'add-item') { C.items.push(newItem()); renderItems(); refresh(); const ins = document.querySelectorAll('#itemRows [data-f=name]'); ins[ins.length - 1].focus(); }
  if (a === 'del-item') { const id = b.closest('[data-id]').dataset.id; C.items = C.items.filter(x => x.id !== id); renderItems(); refresh(); }

  if (a === 'save') {
    const errs = validate(false); if (errs.length) return showMsg(errs);
    busy(true);
    try { await persist('draft'); showMsg(null, `تم حفظ المحضر ${C.no}`); }
    catch (err) { showMsg(err); }
    finally { busy(false); }
  }
  if (a === 'final') {
    const errs = validate(true); if (errs.length) return showMsg(errs);
    busy(true);
    try { await persist('final'); showMsg(null, `تم اعتماد المحضر ${C.no}. يمكنك الطباعة الآن.`); }
    catch (err) { showMsg(err); }
    finally { busy(false); }
  }
  if (a === 'reopen') {
    busy(true);
    try { const res = await api('POST', `${API}/${C.id}/reopen`); loadRecord(res.record); await refreshList(); showMsg(null, 'المحضر مفتوح للتعديل. اعتمده مرة أخرى بعد الانتهاء.'); }
    catch (err) { showMsg(err); }
    finally { busy(false); }
  }
  if (a === 'print') { buildDoc(); window.print(); }
  if (a === 'new') { C = blankCount(); LOGS = []; render(); showMsg(); window.scrollTo(0, 0); }

  if (a === 'open') {
    busy(true);
    try { const res = await api('GET', `${API}/${b.dataset.id}`); loadRecord(res.record); showMsg(); window.scrollTo(0, 0); }
    catch (err) { showMsg(err); }
    finally { busy(false); }
  }
  if (a === 'del') {
    const id = Number(b.dataset.id);
    if (pendingDelete !== id) { pendingDelete = id; renderSaved(); return; }
    pendingDelete = null;
    busy(true);
    try {
      await api('DELETE', `${API}/${id}`);
      if (C.id === id) { C = blankCount(); LOGS = []; }
      await refreshList(); render(); showMsg(null, 'تم حذف المسودة.');
    } catch (err) { showMsg(err); renderSaved(); }
    finally { busy(false); }
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
    </div>`).join('') : '<div class="small">لا توجد محاضر محفوظة.</div>';
}

function renderAudit() {
  $('#audit').innerHTML = LOGS.length
    ? LOGS.map(x => `<tr><td class="tm">${esc(x.t)}</td><td>${esc(x.user)}</td><td><b>${esc(x.action)}</b></td><td>${esc(x.detail || '')}</td></tr>`).join('')
    : '<tr><td class="small" style="padding:10px">لم يُحفظ المحضر بعد.</td></tr>';
}

/* =========================================================
   نسخة الطباعة
   ========================================================= */
function buildDoc() {
  const R = result();
  const logo = document.querySelector('header .brand svg').outerHTML.replace('width="34" height="37"', 'width="38" height="42"').replace('fill="#ffffff"', 'fill="#0f5e5a"');
  const teams = C.teams.filter(t => t.zone.trim() || t.acc.trim() || t.picker.trim());
  const items = R.calcs;
  $('#doc').innerHTML = `
    <div class="d-head">
      <div class="d-brand">${logo}<div><div class="ar">إتقان</div><div class="sl">إتقان في كل خطوة</div></div></div>
      <div class="d-title"><div class="t1">محضر جرد مخزن</div><div class="t2">${esc(TYPES[C.type])} · Inventory Count Minutes</div></div>
      <div class="d-tenant">${BOOT.tenant.logo ? `<img src="${esc(BOOT.tenant.logo)}" alt="">` : ''}<div><b>${esc(BOOT.tenant.name_ar)}</b><span>${esc(BOOT.tenant.name_en)}</span></div></div>
    </div>
    <div class="d-code" style="display:flex;gap:18px;justify-content:space-between;margin:-4px 0 8px">
      <span>رقم المحضر: <b>${esc(C.no)}</b></span><span>النموذج: <b>${FORM_CODE}</b></span><span>الحالة: ${C.status === 'final' ? 'معتمد' : 'مسودة'}</span>
    </div>

    <div class="d-p">${templateHtml(false)}</div>

    ${teams.length ? `<div class="d-h2">فرق العد</div>
    <table class="d"><tr><th style="width:5%">م</th><th>المنطقة</th><th>المحاسب</th><th>المحضر</th><th style="width:14%">عدد القوائم</th></tr>
      ${teams.map((t, i) => `<tr><td>${i + 1}</td><td>${esc(t.zone)}</td><td>${esc(t.acc)}</td><td>${esc(t.picker)}</td><td class="num">${esc(t.sheets)}</td></tr>`).join('')}</table>` : ''}

    <div class="keep"><div class="d-h2">نتيجة الجرد</div>
    <table class="d"><tr><th>عدد الأصناف</th><th>القيمة الكلية الدفترية</th><th>القيمة الكلية الفعلية</th><th>فرق الجرد</th><th>نسبة الاختلاف</th><th>النتيجة</th></tr>
      ${C.erp ? `<tr><td colspan="6" style="text-align:start">المصدر: تقرير الجرد على نظام ERP رقم <b>${esc(C.erpRef)}</b>${C.erpDate ? ' بتاريخ ' + fmtDate(C.erpDate) : ''}</td></tr>` : ''}
      <tr><td class="num">${R.count}</td><td class="num">${money(R.book)}</td><td class="num">${money(R.actual)}</td><td class="num">${signed(R.diff, money)}</td><td class="num">${R.pct.toFixed(2)}%</td><td>${R.within ? 'داخل الحدود المقبولة' : 'خارج الحدود · انحراف'} (${R.tol}%)</td></tr></table></div>

    ${items.length ? `<div class="d-h2">${C.mode === 'totals' ? 'الأصناف التي بها فروق' : 'الأصناف المجرودة'}</div>
    <table class="d"><tr><th style="width:4%">م</th><th>الصنف</th><th>التشغيلة</th><th>الصلاحية</th><th>الموقع</th><th>النظام</th><th>الفعلي</th><th>إعادة العد</th><th>الفرق</th><th>قيمة الفرق</th></tr>
      ${items.map(({ it, c }, i) => `<tr class="${c.d ? 'diff' : ''}"><td>${i + 1}</td><td>${esc(it.name)}${it.critical ? ' ⚑' : ''}</td><td class="num">${esc(it.batch)}</td><td class="num">${fmtMonth(it.exp)}</td><td class="num">${esc(it.loc)}</td><td class="num">${esc(it.book)}</td><td class="num">${esc(it.actual)}</td><td class="num">${esc(it.recount)}</td><td class="num">${c.filled ? signed(c.d, qty) : ''}</td><td class="num">${c.filled && has(it.price) ? signed(c.dv, money) : ''}</td></tr>`).join('')}</table>
    ${items.some(x => x.it.critical) ? '<div style="font-size:10.5px;color:#5f6f6c;margin-top:2px">⚑ صنف حرج (ثلاجة / عالي القيمة): لا يُقبل أي فرق.</div>' : ''}` : ''}

    ${C.erp && (C.diffNote.trim() || C.devNo.trim()) ? `<div class="keep"><div class="d-h2">الفروق والقرار</div>
    <table class="d"><tr><th>أسباب الفروق والقرار</th><th style="width:18%">رقم الانحراف</th></tr>
      <tr><td>${esc(C.diffNote).replace(/\n/g, '<br>')}</td><td class="num">${esc(C.devNo)}</td></tr></table></div>` : ''}

    ${R.diffs.length ? `<div class="keep"><div class="d-h2">التحقيق في الفروق والقرار</div>
    <table class="d"><tr><th style="width:4%">م</th><th>الصنف والتشغيلة</th><th>السبب</th><th>القرار</th><th>رقم الانحراف</th></tr>
      ${R.diffs.map(({ it }, i) => `<tr><td>${i + 1}</td><td>${esc(it.name)} · ${esc(it.batch)}</td><td>${esc(it.cause)}</td><td>${esc(it.decision)}</td><td class="num">${esc(it.dev)}</td></tr>`).join('')}</table></div>` : ''}

    <div class="d-signs">
      <div>رئيس لجنة الجرد<br>${esc(C.head)}</div>
      <div>المدير الصيدلي<br>${esc(C.pharm)}</div>
      <div>أمين المخزن<br>${esc(C.keeper)}</div>
    </div>
    <div class="d-signs" style="grid-template-columns:1fr 1fr;margin-top:30px">
      <div>مفوض الرئيس التنفيذي<br>${esc(C.delegate)}</div>
      <div>اعتماد الرئيس التنفيذي</div>
    </div>
    <div class="d-foot">${esc(BOOT.tenant.name_ar)} · إتقان · نظام إدارة الجودة · ${FORM_CODE} · طبعه ${esc(BOOT.me.name)} في ${nowStr()}</div>`;
}

/* =========================================================
   البدء
   ========================================================= */
C = blankCount();
bind();
render();
DevLinks.mount({
  el: '#devLinks',
  ref: () => C.no,
  saved: () => !!C.id,
  prefill: () => {
    const R = result();
    let what = `جرد ${C.no} (${TYPES[C.type] || ''})${C.zones ? ' · ' + C.zones : ''} · فرق القيمة ${money(R.diff)} · نسبة الاختلاف ${R.pct.toFixed(2)}% (الحد ${R.tol}%)`;
    if (R.critDiff) what += ' · فروق في أصناف حرجة';
    if (R.diffs.length) what += ':\n' + R.diffs.slice(0, 25).map(x => `- ${x.it.name}${x.it.batch ? ' · ' + x.it.batch : ''}: ${signed(x.c.d, qty)}`).join('\n');
    if (C.erp && C.diffNote) what += '\n' + C.diffNote;
    return { source: 'فروقات الجرد (SH-016)', sop: 'SH-016 الجرد', dept: 'المخزن', what };
  },
  onLinks: list => {
    const fs = document.querySelector('#fs');
    if (list.length && C.erp && !C.devNo && !(fs && fs.disabled)) { C.devNo = list[0].number; $('#devNo').value = C.devNo; refresh(); }
  },
});
refreshList().then(async () => {
  if (await openFromQuery(LIST, async id => { busy(true); try { const res = await api('GET', `${API}/${id}`); loadRecord(res.record); } catch (e) { showMsg(e); } finally { busy(false); } })) return;
  if (!C.id && C.autoNo) { C.no = nextNo(C.date); render(); }
});

/* اقتراح أسماء مستخدمي المخزن */
document.getElementById('peopleList').innerHTML = BOOT.users.map(u => `<option value="${esc(u.name)}">${esc(u.role || '')}</option>`).join('');
@endverbatim
</script>
@endpush