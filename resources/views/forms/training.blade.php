@extends('layouts.app')

@section('title', 'محضر التدريب')

@push('head')
<style>
@verbatim  :root{
    --bg:#f4f6f5; --card:#ffffff; --ink:#1c2826; --muted:#5f6f6c; --line:#dde3e1;
    --brand:#0f5e5a; --brand-soft:#e3f0ee; --gold:#c08a1e; --gold-soft:#f8efdc;
    --ok:#1e7a46; --ok-soft:#e4f4ea; --warn:#9a6300; --warn-soft:#fdf1dc;
    --bad:#b3261e; --bad-soft:#fbe7e5; --radius:10px;
  }
  .badge{display:inline-block;padding:4px 14px;border-radius:999px;font-size:13px;font-weight:700;white-space:nowrap}
  .b-draft{background:#e9eeec;color:#44524f} .b-ok{background:var(--ok-soft);color:var(--ok)}
  .b-warn{background:var(--warn-soft);color:var(--warn)} .b-bad{background:var(--bad-soft);color:var(--bad)}

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
  label.f{display:block;font-size:13px;color:var(--muted);margin-bottom:4px;font-weight:600}
  .req::after{content:" *";color:var(--bad)}
  input[type=text],input[type=date],input[type=time],input[type=number],input[type=datetime-local],select,textarea{width:100%;padding:7px 9px;border:1px solid var(--line);border-radius:8px;font:inherit;background:#fff;color:var(--ink)}
  input[type=date],input[type=time],input[type=number],input[type=datetime-local],.ltr-in{direction:ltr;text-align:right}
  input[readonly]{background:#f0f3f2;color:var(--muted)}
  input:focus,select:focus,textarea:focus{outline:2px solid var(--brand-soft);border-color:var(--brand)}
  textarea{min-height:56px;resize:vertical}
  fieldset:disabled input,fieldset:disabled select,fieldset:disabled textarea{background:#f6f8f7;color:#44524f}
  .chk{display:flex;gap:8px;align-items:center;font-size:14px;margin-top:6px}
  .chk input{width:17px;height:17px;accent-color:var(--brand)}
  .btn{background:var(--brand);color:#fff;border:1px solid var(--brand);border-radius:8px;padding:8px 18px;font:inherit;font-weight:600;cursor:pointer;text-decoration:none;display:inline-block}
  .btn.ghost{background:#fff;color:var(--brand)}
  .btn.gold{background:var(--gold);border-color:var(--gold)}
  .btn.okb{background:var(--ok);border-color:var(--ok)}
  .btn.danger{background:#fff;color:var(--bad);border-color:#e3b0ac}
  .btn.small{padding:3px 10px;font-size:13px}
  .actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  .x{background:none;border:0;color:var(--bad);font-size:20px;line-height:1;cursor:pointer;padding:2px 6px}
  fieldset:disabled .x,fieldset:disabled .add{display:none}
  .yn{display:inline-flex;border:1px solid var(--line);border-radius:8px;overflow:hidden}
  .yn button{background:#fff;border:0;padding:6px 14px;font:inherit;font-size:13px;font-weight:600;color:var(--muted);cursor:pointer}
  .yn button+button{border-inline-start:1px solid var(--line)}
  .yn button.on-ok{background:var(--ok);color:#fff} .yn button.on-bad{background:var(--bad);color:#fff}
  fieldset:disabled .yn button{cursor:default}
  table.t{width:100%;border-collapse:collapse;font-size:13.5px}
  table.t th{background:#fafbfb;color:var(--muted);font-weight:600;padding:8px 5px;text-align:start;border-bottom:1px solid var(--line);white-space:nowrap;font-size:12.5px}
  table.t td{padding:5px 4px;border-bottom:1px solid var(--line);vertical-align:top}
  table.t td input,table.t td select{padding:5px 6px;font-size:13px}
  table.t td.n{width:32px;text-align:center;color:var(--muted);font-weight:700;padding-top:10px}
  table.t tr.bad td{background:#fff7f6}
  .att{min-width:860px}
  .att td.c{text-align:center;padding-top:9px}
  .att input[type=checkbox]{width:17px;height:17px;accent-color:var(--brand)}
  .seg{display:inline-flex;border:1px solid var(--line);border-radius:8px;overflow:hidden;flex-wrap:wrap}
  .seg label{padding:6px 12px;cursor:pointer;font-weight:600;color:var(--muted);font-size:13px}
  .seg label+label{border-inline-start:1px solid var(--line)}
  .seg input{display:none}
  .seg label:has(input:checked){background:var(--brand);color:#fff}
  fieldset:disabled .seg label{cursor:default}
  .pick{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:10px}
  .pick select{width:auto;min-width:220px}
  table.t td input:disabled,table.t td select:disabled,input:disabled{background:#f6f8f7;color:#44524f}
  .tbl-wrap{overflow-x:auto}
  .items{min-width:900px}
  .tmsg{font-size:12.5px;margin-top:4px;color:var(--muted)}
  .tmsg.ok{color:var(--ok)} .tmsg.bad{color:var(--bad);font-weight:700}
  .flag{display:inline-block;border-radius:99px;padding:1px 8px;font-size:11px;font-weight:700;margin:2px 2px 0 0;white-space:nowrap}
  .flag.bad{background:var(--bad-soft);color:var(--bad)} .flag.warn{background:var(--warn-soft);color:var(--warn)}
  .errors{background:var(--bad-soft);color:var(--bad);border-radius:8px;padding:10px 12px;font-size:13px;margin-top:12px}
  .errors ul{margin:6px 0 0;padding-inline-start:18px}
  .okmsg{background:var(--ok-soft);color:var(--ok);border-radius:8px;padding:8px 12px;font-size:13px;margin-top:12px;font-weight:600}
  .info{background:var(--brand-soft);border:1px solid #c6dfdb;color:#24514d;border-radius:var(--radius);padding:10px 14px;font-size:13px}
  .alarm{background:var(--bad-soft);border:1px solid #efc1bc;color:var(--bad);border-radius:var(--radius);padding:10px 14px;font-size:13.5px;font-weight:600;margin-top:12px}
  .signed{background:var(--brand-soft);color:#24514d;border-radius:8px;padding:8px 12px;font-size:13px;font-weight:600;margin-top:10px}
  .flow{display:flex;gap:0;flex-wrap:wrap;margin-bottom:14px}
  .flow span{flex:1;min-width:110px;text-align:center;padding:8px 6px;background:#eef0f0;color:var(--muted);font-size:12.5px;font-weight:700;border-left:2px solid #fff}
  .flow span.done{background:var(--ok-soft);color:var(--ok)} .flow span.now{background:var(--brand);color:#fff}
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

  #doc{display:none}
  @page{size:A4;margin:13mm 13mm 15mm}
  @media print{
    body{background:#fff;font-size:12px}
    header.top,footer.foot,.scr,.scr-head,main.wrap>.alert{display:none !important}
    main.wrap{padding:0;max-width:none}
    #doc{display:block}
    *{-webkit-print-color-adjust:exact;print-color-adjust:exact}
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
  table.d tr.bad td{background:#fdf1ef}
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
  <h1>محضر التدريب وتقييمه</h1>
  <span class="code">SH-015-F03</span>
  <span id="badge" class="badge b-draft">مسودة</span>
</div>
<div class="scr">
<div class="scr-main">
  <div class="flow" id="flow"></div>

<fieldset id="fsK">
  <!-- ١. التدريب -->
  <section class="card">
    <h2><span class="step">١</span> بيانات التدريب</h2>
    <div class="grid">
      <div><label class="f req" for="no">رقم التدريب</label><input type="text" id="no" class="ltr-in" autocomplete="off"></div>
      <div><label class="f req" for="date">التاريخ</label><input type="date" id="date"></div>
      <div><label class="f" for="tStart">الساعة</label><input type="time" id="tStart"></div>
      <div><label class="f req" for="hours">المدة (ساعات)</label><input type="number" id="hours" min="0.25" step="0.25"></div>
    </div>
    <div style="margin-top:14px"><label class="f req">نوع التدريب</label><span class="seg" id="typeSeg"></span></div>
    <div class="grid" style="margin-top:14px">
      <div style="grid-column:span 2"><label class="f req" for="topic">موضوع التدريب</label><input type="text" id="topic" placeholder="مثال: الصرف بنظام FEFO وأقرب صلاحية أولاً"></div>
      <div><label class="f" for="ref">المرجع (الإجراء / الوصف الوظيفي)</label><input type="text" id="ref" list="refList" placeholder="SH-004 الصرف"></div>
    </div>
    <div style="margin-top:14px"><label class="f req" for="details">المحتوى والنقاط التي تم التدريب عليها</label><textarea id="details" style="min-height:80px"></textarea></div>

    <h3>سبب التدريب</h3>
    <div class="grid">
      <div><label class="f req" for="trigger">المصدر</label><select id="trigger"></select></div>
      <div><label class="f" for="triggerRef" id="triggerRefLbl">رقم المستند المرتبط</label><input type="text" id="triggerRef" class="ltr-in" placeholder="DEV-… · CMP-… · SI-…"></div>
      <div style="grid-column:span 2"><label class="f" for="reason">التوضيح</label><input type="text" id="reason" placeholder="مثال: اكتشاف أصناف منتهية الصلاحية في الرفوف بتاريخ …"></div>
    </div>

    <h3>المدرب</h3>
    <div class="grid">
      <div><label class="f req">جهة التدريب</label><span class="seg" id="trSeg"><label><input type="radio" name="trType" value="internal">داخلي</label><label><input type="radio" name="trType" value="external">جهة خارجية</label></span></div>
      <div><label class="f req" for="trainer">اسم المدرب</label><input type="text" id="trainer" list="peopleList"></div>
      <div><label class="f" for="trainerJob" id="trainerJobLbl">وظيفة المدرب</label><input type="text" id="trainerJob"></div>
    </div>
    <div class="info" id="extInfo" hidden style="margin-top:10px">الجهة الخارجية يجب أن تكون في قائمة مقدمي الخدمات المعتمدين (SH-025)، مع التأكد من تأهيل المدرب ومحتوى التدريب.</div>
  </section>

  <!-- ٢. المتدربون -->
  <section class="card">
    <h2><span class="step">٢</span> المتدربون والتقييم</h2>
    <p class="sub">كل متدرب باسمه ووظيفته. الحضور ونتيجة التقييم لكل فرد. من لم يجتز التقييم لا يمارس المهمة منفرداً حتى إعادة التدريب.</p>
    <div class="grid">
      <div><label class="f req">طريقة التقييم</label><span class="seg" id="evalSeg"><label><input type="radio" name="evalMethod" value="test">اختبار</label><label><input type="radio" name="evalMethod" value="practical">ملاحظة عملية</label><label><input type="radio" name="evalMethod" value="both">الاثنين</label></span></div>
    </div>
    <div class="pick add">
      <select id="pickUser"></select>
      <button type="button" class="btn ghost small" data-act="add-user">+ إضافة</button>
      <select id="pickRole"></select>
      <button type="button" class="btn ghost small" data-act="add-role">+ كل موظفي الوظيفة</button>
      <button type="button" class="btn ghost small" data-act="add-other">+ اسم من خارج المستخدمين</button>
    </div>
    <div class="tbl-wrap" style="margin-top:10px">
      <table class="t att">
        <thead><tr><th>م</th><th style="min-width:180px">الاسم</th><th style="min-width:150px">الوظيفة</th><th>حضر</th><th style="min-width:150px">نتيجة التقييم</th><th>يحتاج إعادة</th><th style="min-width:160px">ملاحظات</th><th></th></tr></thead>
        <tbody id="attRows"></tbody>
      </table>
    </div>
    <div class="small" id="attSum" style="margin-top:8px"></div>
  </section>
</fieldset>

<fieldset id="fsA">
  <section class="card">
    <h2><span class="step">٣</span> اعتماد المدير الصيدلي</h2>
    <p class="sub" id="apHint"></p>
    <label class="f" for="apNote">ملاحظات</label><textarea id="apNote"></textarea>
    <div class="signed" id="aSigned" hidden></div>
  </section>
</fieldset>

<fieldset id="fsQ">
  <section class="card">
    <h2><span class="step">٤</span> متابعة فاعلية التدريب</h2>
    <p class="sub" id="effHint"></p>
    <div class="grid">
      <div><label class="f req" for="effDue">موعد المتابعة</label><input type="date" id="effDue"></div>
      <div><label class="f req">النتيجة</label><span class="seg" id="effSeg"><label><input type="radio" name="effResult" value="effective">فعّال</label><label><input type="radio" name="effResult" value="partial">فعّال جزئياً</label><label><input type="radio" name="effResult" value="not">غير فعّال</label></span></div>
    </div>
    <div style="margin-top:12px"><label class="f req" for="effEvidence" id="effEvLbl">الدليل (الأخطاء · الانحرافات · مؤشرات الأداء قبل وبعد)</label><textarea id="effEvidence" placeholder="مثال: أخطاء الإدخال انخفضت من ١٢ إلى ٣ في الأسبوعين التاليين"></textarea></div>
    <div id="notBox" hidden style="margin-top:12px">
      <label class="f req" for="effAction">الإجراء (مراجعة المحتوى والطريقة والمدرب · إعادة التدريب · انحراف SH-008)</label><textarea id="effAction"></textarea>
    </div>
    <div class="signed" id="qSigned" hidden></div>
  </section>
</fieldset>

  <section class="card">
    <div class="actions">
      <button type="button" class="btn" data-act="save" id="btnSave">حفظ</button>
      <button type="button" class="btn gold" data-act="held" id="btnHeld">تم التدريب · إرسال للاعتماد</button>
      <button type="button" class="btn gold" data-act="approve" id="btnApprove" hidden>اعتماد التدريب</button>
      <button type="button" class="btn okb" data-act="final" id="btnFinal" hidden>اعتماد الفاعلية وإغلاق</button>
      <button type="button" class="btn ghost" data-act="back" id="btnBack" hidden>إعادة للمرحلة السابقة</button>
      <button type="button" class="btn ghost" data-act="reopen" id="btnReopen" hidden>إعادة فتح للتعديل</button>
      <button type="button" class="btn ghost" data-act="print">طباعة</button>
      <span style="flex:1"></span>
      <button type="button" class="btn ghost" data-act="new">تدريب جديد</button>
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
    <div class="kv"><span>رقم التدريب</span><b id="sNo">—</b></div>
    <div class="kv"><span>النوع</span><span id="sType">—</span></div>
    <div class="kv"><span>المتدربون</span><b id="sAtt">0</b></div>
    <div class="kv"><span>اجتاز</span><b id="sPass">—</b></div>
    <div class="kv"><span>ساعات التدريب</span><b id="sHours">0</b></div>
    <div class="kv"><span>الحالة</span><span id="sSt">مسودة</span></div>
  </section>
  <section class="card">
    <h2 style="font-size:15px">التدريبات المحفوظة</h2>
    <div class="saved" id="saved"></div>
    <a id="regLink" class="btn ghost small" style="margin-top:10px">تقرير الدورات والمحاضرين</a>
  </section>
</aside>
</div>

<div id="doc"></div>
<datalist id="peopleList"></datalist>
<datalist id="refList"></datalist>
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
const FORM_CODE = 'SH-015-F03';
const API = BOOT.urls.records;
const EFF_MONTHS = 3;
const TYPES = { induction: 'تعريفي (عامل جديد / منقول)', ojt: 'على رأس العمل', refresher: 'تنشيطي', corrective: 'تصحيحي', newsop: 'إجراء جديد / معدل', external: 'خارجي / دورة' };
const TRIGGERS = { '': '— اختر —', plan: 'خطة التدريب السنوية', newhire: 'عامل جديد / منقول', deviation: 'انحراف (SH-008)', complaint: 'شكوى (SH-007)', inspection: 'ملاحظة تفتيش (SH-012)', kpi: 'نتيجة تقييم الأداء', newsop: 'إجراء جديد أو معدل (SH-014)', request: 'طلب الإدارة / تطوير', other: 'أخرى' };
const NEEDS_REF = ['deviation', 'complaint', 'inspection'];
const RESULTS = { '': '—', pass: 'اجتاز', fail: 'لم يجتز', absent: 'غائب' };
const EVAL = { test: 'اختبار', practical: 'ملاحظة عملية', both: 'اختبار وملاحظة عملية' };
const EFF = { effective: 'فعّال', partial: 'فعّال جزئياً', not: 'غير فعّال' };
const STAGES = [['draft', 'المدرب / المنظم'], ['held', 'اعتماد المدير الصيدلي'], ['approved', 'متابعة الفاعلية'], ['final', 'مغلق']];
const SOPS = ['', 'SH-001 الاستلام', 'SH-002 المرتجعات', 'SH-003 النظافة', 'SH-004 الصرف', 'SH-005 التخزين', 'SH-006 النقل', 'SH-007 الشكاوى', 'SH-008 الإجراءات التصحيحية', 'SH-009 إدارة المخاطر', 'SH-010 الاستدعاء', 'SH-011 المغشوش', 'SH-012 التفتيش الذاتي', 'SH-013 مراجعة الإدارة', 'SH-014 التوثيق', 'SH-015 التدريب', 'SH-016 الجرد', 'SH-017 مكافحة الحريق', 'SH-018 سلامة العاملين', 'SH-019 الكسر والانسكاب', 'SH-020 التخلص من المنتهي', 'SH-021 التوظيف', 'SH-022 الصحة والنظافة الشخصية', 'SH-023 التأهيل والمعايرة', 'SH-024 الأنظمة الحاسوبية', 'SH-025 الأنشطة الخارجية', 'SH-026 مكافحة الآفات', 'SH-027 التحكم في التغيير', 'SH-028 سياسة الصرف', 'SH-029 الأمن', 'SH-030 الحرارة وسلسلة التبريد', 'SH-031 تأهيل الموردين والعملاء', 'SH-032 الأدوية المخدرة', 'SH-033 الطوارئ واستمرارية العمل', 'SH-034 الاجتماعات'];

/* =========================================================
   أدوات
   ========================================================= */
const $ = s => document.querySelector(s);
const pad = (n, l = 2) => String(n).padStart(l, '0');
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const today = () => { const d = new Date(); return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`; };
const nowTime = () => { const d = new Date(); return `${pad(d.getHours())}:${pad(d.getMinutes())}`; };
const nowStr = () => `${today()} ${nowTime()}`;
const fmtDate = iso => { if (!iso) return ''; const [y, m, d] = iso.slice(0, 10).split('-'); return `<span class="dt">${d}/${m}/${y}</span>`; };
const has = v => v !== '' && v !== null && v !== undefined && isFinite(parseFloat(v));
const uid = () => Math.random().toString(36).slice(2, 10);
function addMonths(iso, m) { if (!iso) return ''; const d = new Date(iso + 'T00:00:00'); d.setMonth(d.getMonth() + m); return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`; }

/* =========================================================
   الخادم
   ========================================================= */
let LIST = [];
let LOGS = [];
const CSRF = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
async function api(method, url, body) {
  const res = await fetch(url, { method, credentials: 'same-origin', body: body ? JSON.stringify(body) : undefined,
    headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' } });
  let json = {};
  try { json = await res.json(); } catch (e) {}
  if (!res.ok) {
    if (res.status === 419 || res.status === 401) throw ['انتهت الجلسة. حدّث الصفحة وسجّل الدخول مرة أخرى.'];
    if (res.status === 403) throw [json.message || 'ليست لديك صلاحية لهذا الإجراء.'];
    if (json.errors) throw Object.values(json.errors).flat();
    throw [json.message || ('خطأ من الخادم (' + res.status + ')')];
  }
  return json;
}
async function refreshList() { try { LIST = (await api('GET', API)).records || []; } catch (e) { LIST = []; showMsg(e); } renderSaved(); }
function busy(on) { document.querySelector('.scr').classList.toggle('busy', !!on); }

/* =========================================================
   الحالة
   ========================================================= */
let C = null;
let pendingDelete = null;

function nextNo(date) {
  const y = (date || today()).slice(0, 4), prefix = `TRN-${y}-`;
  const nums = LIST.filter(m => m.number && m.number.startsWith(prefix)).map(m => parseInt(m.number.slice(prefix.length), 10) || 0);
  return prefix + pad((nums.length ? Math.max(...nums) : 0) + 1, 3);
}
function blankRec() {
  return {
    id: null, no: nextNo(today()), autoNo: true, date: today(), tStart: '', hours: '',
    type: '', topic: '', ref: '', details: '', trigger: '', triggerRef: '', reason: '',
    trType: 'internal', trainer: BOOT.me.name, trainerJob: BOOT.me.role || '',
    evalMethod: '', attendees: [], apNote: '',
    effDue: '', effAuto: true, effResult: '', effEvidence: '', effAction: '',
    trainees: [], stage: 'draft', sign: {}, status: 'draft'
  };
}
function newAtt(u) { return { id: uid(), userId: u ? u.id : null, name: u ? u.name : '', job: u ? (u.role || '') : '', present: true, result: '', retrain: false, note: '' }; }

/* =========================================================
   الحسابات والصلاحيات
   ========================================================= */
const rows = () => C.attendees.filter(a => a.name.trim());
function stats() {
  const r = rows(), present = r.filter(a => a.present);
  const pass = present.filter(a => a.result === 'pass').length, fail = present.filter(a => a.result === 'fail').length;
  return { total: r.length, present: present.length, pass, fail, retrain: r.filter(a => a.retrain || a.result === 'fail').length,
    pct: present.length ? Math.round(pass / present.length * 100) : null };
}
const canApprove = () => !!(BOOT.can.steps && BOOT.can.steps.approve);
const canFinal = () => !!BOOT.can.finalize;

/* =========================================================
   الرسم
   ========================================================= */
const FIELDS = ['no', 'date', 'tStart', 'hours', 'topic', 'ref', 'details', 'triggerRef', 'reason', 'trainer', 'trainerJob', 'apNote', 'effDue', 'effEvidence', 'effAction'];
const RADIOS = { trType: 'trType', evalMethod: 'evalMethod', effResult: 'effResult' };

function render() {
  FIELDS.forEach(k => { $('#' + k).value = C[k] ?? ''; });
  $('#typeSeg').innerHTML = Object.entries(TYPES).map(([k, v]) => `<label><input type="radio" name="type" value="${k}" ${C.type === k ? 'checked' : ''}>${v}</label>`).join('');
  $('#trigger').innerHTML = Object.entries(TRIGGERS).map(([k, v]) => `<option value="${k}" ${C.trigger === k ? 'selected' : ''}>${v}</option>`).join('');
  Object.keys(RADIOS).forEach(n => document.querySelectorAll(`input[name=${n}]`).forEach(r => { r.checked = r.value === C[RADIOS[n]]; }));
  renderAtt(); applyState(); refresh(); renderSaved(); renderAudit();
}

function applyState() {
  const final = C.status === 'final', st = final ? 'final' : C.stage;
  $('#fsK').disabled = final || st !== 'draft';
  $('#fsA').disabled = final || st !== 'held' || !canApprove();
  $('#fsQ').disabled = final || st !== 'approved' || !canFinal();
  $('#btnSave').hidden = final || (st === 'held' && !canApprove()) || (st === 'approved' && !canFinal());
  $('#btnHeld').hidden = final || st !== 'draft';
  $('#btnApprove').hidden = final || st !== 'held' || !canApprove();
  $('#btnFinal').hidden = final || st !== 'approved' || !canFinal();
  $('#btnBack').hidden = final || st === 'draft' || (st === 'held' && !canApprove()) || (st === 'approved' && !canFinal());
  $('#btnReopen').hidden = !final || !BOOT.can.reopen;
  const i = STAGES.findIndex(s => s[0] === st);
  $('#flow').innerHTML = STAGES.map((s, k) => `<span class="${k < i || final ? 'done' : k === i ? 'now' : ''}">${s[1]}</span>`).join('');
  const b = $('#badge');
  if (final) { b.className = 'badge b-ok'; b.textContent = 'مغلق · ' + (EFF[C.effResult] || ''); }
  else if (st === 'held') { b.className = 'badge b-warn'; b.textContent = 'بانتظار اعتماد المدير الصيدلي'; }
  else if (st === 'approved') { b.className = 'badge b-warn'; b.textContent = 'معتمد · بانتظار متابعة الفاعلية'; }
  else { b.className = 'badge b-draft'; b.textContent = 'مسودة'; }
  $('#sSt').textContent = b.textContent;
  $('#apHint').textContent = st === 'draft' ? 'يُتاح بعد تسجيل الحضور والتقييم وإرسال التدريب.' : st === 'held' && !canApprove() ? 'الاعتماد للمدير الصيدلي (أو مدير الجودة / مدير المخزن).' : st === 'held' ? 'راجع المحتوى والحضور ونتائج التقييم ثم اعتمد.' : '';
  $('#effHint').textContent = final ? '' : st !== 'approved' ? `يُتاح بعد الاعتماد. المتابعة بعد ${EFF_MONTHS} أشهر من التدريب.` : !canFinal() ? 'متابعة الفاعلية لمدير إدارة الجودة.' : 'هل انعكس التدريب على الأخطاء والانحرافات ومؤشرات الأداء؟';
  const s = C.sign || {};
  $('#aSigned').hidden = !s.approve; $('#aSigned').textContent = s.approve ? `اعتمده ${s.approve.by} في ${s.approve.at}` : '';
  $('#qSigned').hidden = !s.final; $('#qSigned').textContent = s.final ? `أغلقه ${s.final.by} في ${s.final.at}` : '';
}

function renderAtt() {
  const ro = C.status === 'final' || C.stage !== 'draft' ? 'disabled' : '';
  $('#attRows').innerHTML = C.attendees.length ? C.attendees.map((a, i) => `
    <tr data-id="${a.id}" class="${a.result === 'fail' ? 'bad' : ''}">
      <td class="n">${i + 1}</td>
      <td><input type="text" data-f="name" value="${esc(a.name)}" ${a.userId ? 'readonly' : ''} ${ro}></td>
      <td><input type="text" data-f="job" value="${esc(a.job)}" ${ro}></td>
      <td class="c"><input type="checkbox" data-f="present" ${a.present ? 'checked' : ''} ${ro}></td>
      <td><select data-f="result" ${ro}>${Object.entries(RESULTS).map(([k, v]) => `<option value="${k}" ${a.result === k ? 'selected' : ''}>${v}</option>`).join('')}</select></td>
      <td class="c"><input type="checkbox" data-f="retrain" ${a.retrain || a.result === 'fail' ? 'checked' : ''} ${a.result === 'fail' ? 'disabled' : ''} ${ro}></td>
      <td><input type="text" data-f="note" value="${esc(a.note)}" ${ro}></td>
      <td>${ro ? '' : '<button type="button" class="x" data-act="del-att" title="حذف">×</button>'}</td>
    </tr>`).join('') : '<tr><td colspan="8" class="small" style="padding:14px;text-align:center">أضف المتدربين بالاسم من القائمة أعلاه.</td></tr>';
}

function refresh() {
  const S = stats();
  $('#attSum').textContent = S.total ? `${S.total} متدرب · حضر ${S.present} · اجتاز ${S.pass}${S.fail ? ` · لم يجتز ${S.fail} (يحتاج إعادة تدريب قبل ممارسة المهمة منفرداً)` : ''}` : '';
  $('#attSum').style.color = S.fail ? 'var(--bad)' : '';
  $('#triggerRefLbl').className = 'f' + (NEEDS_REF.includes(C.trigger) ? ' req' : '');
  $('#extInfo').hidden = C.trType !== 'external';
  $('#trainerJobLbl').textContent = C.trType === 'external' ? 'الجهة الخارجية' : 'وظيفة المدرب';
  $('#notBox').hidden = !['not', 'partial'].includes(C.effResult);
  $('#sNo').textContent = C.no || '—';
  $('#sType').textContent = TYPES[C.type] || '—';
  $('#sAtt').textContent = S.total;
  $('#sPass').textContent = S.pct === null ? '—' : S.pct + '%';
  $('#sHours').textContent = has(C.hours) ? (parseFloat(C.hours) * S.present).toLocaleString('en-US') : '0';
  // ملخص للسجل
  C.trainees = rows().filter(a => a.present).map(a => ({ userId: a.userId, name: a.name, job: a.job, result: a.result }));
}

/* =========================================================
   الأحداث
   ========================================================= */
function bind() {
  FIELDS.forEach(k => $('#' + k).addEventListener('input', e => {
    C[k] = e.target.value;
    if (k === 'no') C.autoNo = false;
    if (k === 'effDue') C.effAuto = false;
    if (k === 'date' && C.autoNo) { C.no = nextNo(C.date); $('#no').value = C.no; }
    refresh();
  }));
  $('#typeSeg').addEventListener('change', e => { C.type = e.target.value; if (C.type === 'corrective' && !C.trigger) { C.trigger = 'deviation'; $('#trigger').value = 'deviation'; } refresh(); });
  $('#trigger').addEventListener('change', e => { C.trigger = e.target.value; refresh(); });
  Object.keys(RADIOS).forEach(n => document.querySelectorAll(`input[name=${n}]`).forEach(r => r.addEventListener('change', e => { C[RADIOS[n]] = e.target.value; refresh(); })));
  $('#attRows').addEventListener('input', e => {
    const tr = e.target.closest('[data-id]'), f = e.target.dataset.f; if (!tr || !f) return;
    const a = C.attendees.find(x => x.id === tr.dataset.id); if (!a) return;
    a[f] = e.target.type === 'checkbox' ? e.target.checked : e.target.value;
    if (f === 'present' && !a.present) a.result = 'absent';
    if (f === 'result' && a.result === 'absent') a.present = false;
    if (f === 'result' && a.result !== 'absent' && !a.present) a.present = true;
    if (['present', 'result'].includes(f)) renderAtt();
    refresh();
  });
  $('#attRows').addEventListener('change', e => { if (e.target.matches('select,[type=checkbox]')) e.target.dispatchEvent(new Event('input', { bubbles: true })); });
}

/* =========================================================
   التحقق
   ========================================================= */
function validateBasic() {
  const e = [];
  if (!C.no.trim()) e.push('رقم التدريب مطلوب');
  if (LIST.some(m => m.number === C.no && m.id !== C.id)) e.push(`رقم التدريب ${C.no} مستخدم من قبل`);
  if (!C.date) e.push('تاريخ التدريب مطلوب');
  return e;
}
function validateHeld() {
  const e = validateBasic(), S = stats();
  if (C.date > today()) e.push('لا يمكن تسجيل تدريب لم يتم بعد (التاريخ في المستقبل)');
  if (!has(C.hours) || parseFloat(C.hours) <= 0) e.push('اكتب مدة التدريب بالساعات');
  if (!C.type) e.push('اختر نوع التدريب');
  if (!C.topic.trim()) e.push('اكتب موضوع التدريب');
  if (!C.details.trim()) e.push('اكتب محتوى التدريب');
  if (!C.trigger) e.push('اختر مصدر (سبب) التدريب');
  if (NEEDS_REF.includes(C.trigger) && !C.triggerRef.trim()) e.push('اكتب رقم المستند المرتبط (الانحراف / الشكوى / التفتيش)');
  if (C.type === 'corrective' && !['deviation', 'complaint', 'inspection', 'kpi'].includes(C.trigger)) e.push('التدريب التصحيحي يجب أن يرتبط بانحراف أو شكوى أو ملاحظة تفتيش أو نتيجة تقييم أداء');
  if (!C.trainer.trim()) e.push('اكتب اسم المدرب');
  if (C.trType === 'external' && !C.trainerJob.trim()) e.push('اكتب اسم الجهة الخارجية');
  if (!C.evalMethod) e.push('اختر طريقة التقييم');
  if (!S.total) e.push('أضف المتدربين بالاسم');
  if (S.total && !S.present) e.push('لم يحضر أي متدرب');
  rows().forEach(a => { if (a.present && !['pass', 'fail'].includes(a.result)) e.push(`سجل نتيجة تقييم «${a.name}»`); });
  return e;
}
function validateFinal() {
  const e = [];
  if (!C.effDue) e.push('حدد موعد متابعة الفاعلية');
  if (!C.effResult) e.push('حدد نتيجة متابعة الفاعلية');
  if (!C.effEvidence.trim()) e.push('اكتب دليل الفاعلية');
  if (['not', 'partial'].includes(C.effResult) && !C.effAction.trim()) e.push('اكتب الإجراء المطلوب لعدم الفاعلية');
  return e;
}
function showMsg(errs, ok) {
  $('#msg').innerHTML = errs && errs.length ? `<div class="errors">لم يتم الحفظ:<ul>${errs.map(x => `<li>${esc(x)}</li>`).join('')}</ul></div>` : ok ? `<div class="okmsg">${esc(ok)}</div>` : '';
}

async function persist(status) {
  refresh();
  const body = { number: C.no.trim(), title: C.topic.trim() || 'تدريب', record_date: C.date || null, status, data: Object.assign({}, C, { status }) };
  const res = C.id ? await api('PUT', `${API}/${C.id}`, body) : await api('POST', API, body);
  loadRecord(res.record); await refreshList();
}
function loadRecord(rec) {
  C = Object.assign(blankRec(), rec.data || {});
  C.id = rec.id; C.no = rec.number; C.status = rec.status; C.autoNo = false;
  if (!C.sign) C.sign = {};
  if (C.status !== 'final' && C.stage === 'final') C.stage = 'approved';
  LOGS = rec.logs || [];
  render();
}
async function run(fn, okText) { busy(true); try { await fn(); if (okText) showMsg(null, okText); } catch (err) { showMsg(err); } finally { busy(false); } }
async function move(stage, sign, status, okText) {
  const prev = { stage: C.stage, sign: JSON.parse(JSON.stringify(C.sign || {})) };
  C.stage = stage; if (sign) C.sign[sign] = { by: BOOT.me.name, at: nowStr() };
  run(async () => { try { await persist(status || 'draft'); } catch (err) { C.stage = prev.stage; C.sign = prev.sign; throw err; } }, okText);
}

function addUsers(list) {
  let n = 0;
  list.forEach(u => { if (!C.attendees.some(a => a.userId && String(a.userId) === String(u.id))) { C.attendees.push(newAtt(u)); n++; } });
  renderAtt(); refresh(); return n;
}

document.addEventListener('click', async e => {
  const b = e.target.closest('[data-act]'); if (!b) return;
  const a = b.dataset.act;
  if (a === 'add-user') { const id = $('#pickUser').value; const u = BOOT.users.find(x => String(x.id) === id); if (u) addUsers([u]); }
  if (a === 'add-role') { const r = $('#pickRole').value; if (r) { const n = addUsers(BOOT.users.filter(u => u.role === r)); showMsg(null, `تمت إضافة ${n} موظف بوظيفة «${r}». احذف من لم يحضر أو علّمه غائباً.`); } }
  if (a === 'add-other') { C.attendees.push(newAtt(null)); renderAtt(); refresh(); const ins = document.querySelectorAll('#attRows [data-f=name]'); ins[ins.length - 1].focus(); }
  if (a === 'del-att') { const id = b.closest('[data-id]').dataset.id; C.attendees = C.attendees.filter(x => x.id !== id); renderAtt(); refresh(); }

  if (a === 'save') { const errs = validateBasic(); if (errs.length) return showMsg(errs); run(() => persist('draft'), `تم حفظ التدريب ${C.no}`); }
  if (a === 'held') {
    const errs = validateHeld(); if (errs.length) return showMsg(errs);
    if (C.effAuto || !C.effDue) C.effDue = addMonths(C.date, EFF_MONTHS);
    move('held', 'held', 'draft', 'تم تسجيل التدريب وإرساله للمدير الصيدلي للاعتماد.');
  }
  if (a === 'approve') { const errs = validateHeld(); if (errs.length) return showMsg(errs); move('approved', 'approve', 'draft', `تم اعتماد التدريب. متابعة الفاعلية في ${C.effDue}.`); }
  if (a === 'final') { const errs = validateFinal(); if (errs.length) return showMsg(errs); move('final', 'final', 'final', 'تم اعتماد الفاعلية وإغلاق التدريب.'); }
  if (a === 'back') { const to = C.stage === 'approved' ? 'held' : 'draft'; move(to, null, 'draft', 'أُعيد التدريب للمرحلة السابقة.'); }
  if (a === 'reopen') run(async () => { const res = await api('POST', `${API}/${C.id}/reopen`); loadRecord(res.record); await refreshList(); }, 'التدريب مفتوح للتعديل.');
  if (a === 'print') { refresh(); buildDoc(); window.print(); }
  if (a === 'new') { C = blankRec(); C.no = nextNo(C.date); LOGS = []; render(); showMsg(); history.replaceState(null, '', location.pathname); window.scrollTo(0, 0); }
  if (a === 'open') openRecord(b.dataset.id);
  if (a === 'del') {
    const id = Number(b.dataset.id);
    if (pendingDelete !== id) { pendingDelete = id; renderSaved(); return; }
    pendingDelete = null;
    run(async () => { await api('DELETE', `${API}/${id}`); if (C.id === id) { C = blankRec(); LOGS = []; } await refreshList(); render(); }, 'تم حذف المسودة.');
  }
  if (a === 'cancel-del') { pendingDelete = null; renderSaved(); }
});
function openRecord(id) { return run(async () => { const res = await api('GET', `${API}/${id}`); loadRecord(res.record); showMsg(); window.scrollTo(0, 0); }); }

function stLabel(m) {
  if (m.status === 'final') return ['مغلق', 'b-ok'];
  return m.stage === 'held' ? ['للاعتماد', 'b-warn'] : m.stage === 'approved' ? ['متابعة الفاعلية', 'b-warn'] : ['مسودة', 'b-draft'];
}
function renderSaved() {
  $('#saved').innerHTML = LIST.length ? LIST.map(m => { const [t, cls] = stLabel(m); return `
    <div class="row ${m.id === C.id ? 'cur' : ''}">
      <div><b>${esc(m.number)}</b> <span class="badge ${cls}" style="padding:1px 8px;font-size:11px">${t}</span><div class="small">${esc(m.title || '')} · ${fmtDate(m.record_date)}</div></div>
      <div class="actions" style="gap:4px">${pendingDelete === m.id
        ? `<button type="button" class="btn danger small" data-act="del" data-id="${m.id}">تأكيد الحذف</button><button type="button" class="btn ghost small" data-act="cancel-del">إلغاء</button>`
        : `<button type="button" class="btn ghost small" data-act="open" data-id="${m.id}">فتح</button>${m.status !== 'final' ? `<button type="button" class="x" data-act="del" data-id="${m.id}" title="حذف">×</button>` : ''}`}</div>
    </div>`; }).join('') : '<div class="small">لا توجد تدريبات محفوظة.</div>';
}
function renderAudit() {
  $('#audit').innerHTML = LOGS.length ? LOGS.map(x => `<tr><td class="tm">${esc(x.t)}</td><td>${esc(x.user)}</td><td><b>${esc(x.action)}</b></td><td>${esc(x.detail || '')}</td></tr>`).join('')
    : '<tr><td class="small" style="padding:10px">لم يُحفظ التدريب بعد.</td></tr>';
}

/* =========================================================
   نسخة الطباعة (SH-015-F03)
   ========================================================= */
function buildDoc() {
  const svg = document.querySelector('header .brand svg');
  const logo = svg ? svg.outerHTML.replace('width="34" height="37"', 'width="38" height="42"').replace('fill="#ffffff"', 'fill="#0f5e5a"') : '';
  const S = stats(), s = C.sign || {};
  const box = (on, t) => `${on ? '☑' : '☐'} ${t}`;
  const sg = (title, x, name) => `<div>${title}<br>${esc(x ? x.by : name || '')}${x ? '<br><span class="dt" style="font-size:10px">' + esc(x.at) + '</span>' : ''}</div>`;
  $('#doc').innerHTML = `
    <div class="d-head">
      <div class="d-brand">${logo}<div><div class="ar">إتقان</div><div class="sl">إتقان في كل خطوة</div></div></div>
      <div class="d-title"><div class="t1">محضر التدريب وتقييمه</div><div class="t2">Training Record &amp; Evaluation</div></div>
      <div class="d-tenant">${BOOT.tenant.logo ? `<img src="${esc(BOOT.tenant.logo)}" alt="">` : ''}<div><b>${esc(BOOT.tenant.name_ar)}</b><span>${esc(BOOT.tenant.name_en)}</span></div></div>
    </div>
    <div class="d-code" style="display:flex;gap:18px;justify-content:space-between;margin:-4px 0 8px">
      <span>رقم التدريب: <b>${esc(C.no)}</b></span><span>النموذج: <b>${FORM_CODE}</b></span><span>التاريخ: ${fmtDate(C.date)} ${esc(C.tStart)}</span><span>المدة: <b>${esc(C.hours)}</b> ساعة</span>
    </div>
    <table class="d">
      <tr><td class="k">موضوع التدريب</td><td colspan="3"><b>${esc(C.topic)}</b></td></tr>
      <tr><td class="k">المرجع</td><td>${esc(C.ref) || '—'}</td><td class="k">المدرب / الجهة</td><td>${esc(C.trainer)}${C.trainerJob ? ' · ' + esc(C.trainerJob) : ''}${C.trType === 'external' ? ' (خارجي)' : ''}</td></tr>
      <tr><td class="k">نوع التدريب</td><td colspan="3">${Object.entries(TYPES).map(([k, v]) => box(C.type === k, v)).join(' &nbsp; ')}</td></tr>
      <tr><td class="k">سبب التدريب</td><td colspan="3">${esc(TRIGGERS[C.trigger] || '')}${C.triggerRef ? ' · <span class="dt">' + esc(C.triggerRef) + '</span>' : ''}${C.reason ? ' · ' + esc(C.reason) : ''}</td></tr>
      <tr><td class="k">المحتوى</td><td colspan="3">${esc(C.details).replace(/\n/g, '<br>')}</td></tr>
      <tr><td class="k">طريقة التقييم</td><td colspan="3">${Object.entries(EVAL).map(([k, v]) => box(C.evalMethod === k, v)).join(' &nbsp; ')}</td></tr>
    </table>
    <div class="d-h2">الحاضرون ونتيجة التقييم</div>
    <table class="d"><tr><th style="width:4%">م</th><th>الاسم</th><th>الوظيفة</th><th style="width:12%">نتيجة التقييم</th><th style="width:10%">يحتاج إعادة</th><th style="width:20%">التوقيع</th></tr>
      ${rows().map((a, i) => `<tr class="${a.result === 'fail' ? 'bad' : ''}"><td>${i + 1}</td><td>${esc(a.name)}</td><td>${esc(a.job)}</td><td>${RESULTS[a.result] || ''}</td><td>${a.retrain || a.result === 'fail' ? 'نعم' : ''}</td><td></td></tr>`).join('')}
    </table>
    <div class="small" style="margin-top:4px">حضر ${S.present} من ${S.total} · اجتاز ${S.pass}${S.pct !== null ? ' (' + S.pct + '%)' : ''}</div>
    <div class="keep"><div class="d-h2">متابعة الفاعلية (بعد ${EFF_MONTHS} أشهر)</div>
    <table class="d"><tr><td class="k">موعد المتابعة</td><td>${fmtDate(C.effDue) || '—'}</td><td class="k">النتيجة</td><td><b>${EFF[C.effResult] || '—'}</b></td></tr>
      <tr><td class="k">الدليل</td><td colspan="3">${esc(C.effEvidence) || '—'}</td></tr>
      ${C.effAction ? `<tr><td class="k">الإجراء</td><td colspan="3">${esc(C.effAction)}</td></tr>` : ''}</table></div>
    <div class="d-signs">${sg('المدرب', s.held, C.trainer)}${sg('المدير الصيدلي', s.approve)}${sg('مدير إدارة الجودة (الفاعلية)', s.final)}</div>
    <div class="d-foot">${esc(BOOT.tenant.name_ar)} · إتقان · نظام إدارة الجودة · ${FORM_CODE} · طبعه ${esc(BOOT.me.name)} في ${nowStr()}</div>`;
}

/* =========================================================
   البدء
   ========================================================= */
C = blankRec();
bind();
document.getElementById('peopleList').innerHTML = BOOT.users.map(u => `<option value="${esc(u.name)}">${esc(u.role || '')}</option>`).join('');
document.getElementById('refList').innerHTML = SOPS.filter(Boolean).map(s => `<option value="${esc(s)}"></option>`).join('');
$('#pickUser').innerHTML = '<option value="">— اختر موظفاً —</option>' + BOOT.users.map(u => `<option value="${u.id}">${esc(u.name)}${u.role ? ' · ' + esc(u.role) : ''}</option>`).join('');
const roles = [...new Set(BOOT.users.map(u => u.role).filter(Boolean))];
$('#pickRole').innerHTML = '<option value="">— وظيفة —</option>' + roles.map(r => `<option>${esc(r)}</option>`).join('');
$('#regLink').href = BOOT.urls.register || '#';
render();
(async () => {
  await refreshList();
  const q = new URLSearchParams(location.search);
  if (q.get('open')) { const m = LIST.find(x => x.number === q.get('open') || String(x.id) === q.get('open')); if (m) return openRecord(m.id); }
  // تدريب تصحيحي من انحراف / شكوى: ?trigger=deviation&ref=DEV-…&topic=…
  if (q.get('trigger') && TRIGGERS[q.get('trigger')]) { C.trigger = q.get('trigger'); C.triggerRef = q.get('ref') || ''; C.type = 'corrective'; C.topic = q.get('topic') || ''; }
  if (C.autoNo) C.no = nextNo(C.date);
  render();
})();
@endverbatim
</script>
@endpush