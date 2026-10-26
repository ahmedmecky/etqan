@extends('layouts.app')

@section('title', 'تقرير انحراف الحرارة')

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
  <h1>تقرير انحراف الحرارة</h1>
  <span class="code">SH-030-F03</span>
  <span id="badge" class="badge b-draft">مسودة</span>
</div>
<div class="scr">
<div class="scr-main">
  <div class="flow" id="flow"></div>

<fieldset id="fsK">
  <!-- ١. الانحراف -->
  <section class="card">
    <h2><span class="step">١</span> المنطقة والانحراف</h2>
    <div class="grid">
      <div><label class="f req" for="no">رقم التقرير</label><input type="text" id="no" class="ltr-in" autocomplete="off"></div>
      <div><label class="f req" for="date">تاريخ التقرير</label><input type="date" id="date"></div>
      <div><label class="f req" for="reporter">أمين المخزن</label><input type="text" id="reporter" list="peopleList"></div>
    </div>
    <div class="grid" style="margin-top:14px">
      <div style="grid-column:span 2"><label class="f req" for="pointId">المنطقة / الثلاجة</label><select id="pointId"></select>
        <div class="tmsg" id="pointMsg"></div></div>
      <div><label class="f" for="device">رقم الجهاز</label><input type="text" id="device" class="ltr-in"></div>
    </div>
    <div class="grid" style="margin-top:14px">
      <div><label class="f req" for="start">بداية الانحراف</label><input type="datetime-local" id="start"></div>
      <div><label class="f" for="end">نهاية الانحراف (عودة الحرارة)</label><input type="datetime-local" id="end">
        <div class="tmsg" id="durMsg"></div></div>
      <div><label class="f req" for="maxRead">أعلى قراءة °م</label><input type="number" id="maxRead" step="0.1"></div>
      <div><label class="f req" for="minRead">أدنى قراءة °م</label><input type="number" id="minRead" step="0.1"></div>
    </div>
    <div class="tmsg" id="rangeMsg" style="margin-top:6px"></div>
    <div class="alarm" id="freezeMsg" hidden>❄ أدنى قراءة صفر أو أقل: احتمال تجميد. أي أصناف ثلاجة «لا يُجمد» تعرضت للتجميد قرارها الإعدام.</div>

    <h3>السبب المبدئي والإجراء الفوري</h3>
    <div class="grid">
      <div><label class="f req" for="cause">السبب المبدئي</label><select id="cause"></select></div>
      <div style="grid-column:span 2"><label class="f" for="causeNote" id="causeNoteLbl">تفاصيل السبب</label><input type="text" id="causeNote"></div>
    </div>
    <div class="grid" style="margin-top:14px">
      <div><label class="f req">تم التحقق من القراءة بجهاز آخر؟</label>
        <span class="yn" id="verifiedYN"><button type="button" data-v="Y">نعم</button><button type="button" data-v="N">لا</button></span></div>
      <div><label class="f req" for="actionTime">وقت الإجراء الفوري</label><input type="time" id="actionTime"></div>
      <div><label class="f req" for="informedAt">وقت إبلاغ المدير الصيدلي</label><input type="time" id="informedAt"></div>
    </div>
    <div style="margin-top:14px"><label class="f req" for="action">الإجراء الفوري</label>
      <textarea id="action" placeholder="غلق الباب · إصلاح · تشغيل المولد · نقل الأصناف لثلاجة احتياطية…"></textarea></div>
    <div class="grid" style="margin-top:14px">
      <div><label class="f req">عادت الحرارة للحدود خلال ٣٠ دقيقة؟</label>
        <span class="yn" id="back30YN"><button type="button" data-v="Y">نعم</button><button type="button" data-v="N">لا</button></span></div>
    </div>
    <div id="moveBox" hidden style="margin-top:10px">
      <div class="info">لم تعد الحرارة خلال ٣٠ دقيقة: انقل المستحضرات لمكان مؤهل، وضعها تحت الحجر، وأوقفها على النظام.</div>
      <div class="grid" style="margin-top:10px">
        <div style="grid-column:span 2"><label class="f req" for="movedTo">نُقلت إلى</label><input type="text" id="movedTo" placeholder="الثلاجة الاحتياطية / صناديق مبردة مؤهلة…"></div>
        <div><label class="f req" for="movedAt">وقت النقل</label><input type="time" id="movedAt"></div>
      </div>
      <label class="chk"><input type="checkbox" id="quarantined"> وُضعت تحت الحجر بملصق وأُوقفت على النظام لحين القرار</label>
    </div>
  </section>
</fieldset>

  <!-- ٢. الأصناف -->
  <section class="card" id="itemsCard">
    <h2><span class="step">٢</span> المستحضرات المتأثرة</h2>
    <p class="sub">كل صنف وتشغيلة كانت في المنطقة أثناء الانحراف. ظروف التخزين كما هي مكتوبة على الملصق.</p>
    <label class="chk" style="margin:-4px 0 10px"><input type="checkbox" id="noItems"> لا توجد مستحضرات في المنطقة وقت الانحراف</label>
    <div id="noItemsBox" hidden><label class="f req" for="noItemsWhy">التوضيح</label><input type="text" id="noItemsWhy"></div>
    <div id="itemsBox">
      <div class="tbl-wrap">
        <table class="t items">
          <thead><tr><th>م</th><th style="min-width:200px">الصنف</th><th style="width:110px">التشغيلة</th><th style="width:80px">الكمية</th><th style="min-width:150px">ظروف التخزين على الملصق</th>
            <th style="min-width:170px">مرجع التقييم</th><th style="min-width:150px">القرار</th><th></th></tr></thead>
          <tbody id="itemRows"></tbody>
        </table>
      </div>
      <div class="actions" style="margin-top:10px"><button type="button" class="btn ghost small add" data-act="add-item">+ إضافة صنف</button></div>
      <p class="small" style="margin:8px 0 0">مرجع التقييم والقرار يكملهما المدير الصيدلي بعد مراجعة بيانات الثبات أو رأي الشركة المنتجة.</p>
    </div>
  </section>

<fieldset id="fsE">
  <!-- ٣. التقييم -->
  <section class="card">
    <h2><span class="step">٣</span> تقييم المدير الصيدلي</h2>
    <p class="sub" id="evalHint"></p>
    <div class="grid">
      <div><label class="f req">هل صُرفت أصناف متأثرة للعملاء؟</label>
        <span class="yn" id="dispensedYN"><button type="button" data-v="N">لا</button><button type="button" data-v="Y">نعم</button></span></div>
      <div><label class="f req" for="devNo">رقم الانحراف (SH-008)</label><input type="text" id="devNo" class="ltr-in" placeholder="DEV-…">
        <a class="btn ghost small" id="devLink" target="_blank" style="margin-top:6px">فتح انحراف SH-008 ←</a></div>
    </div>
    <div id="recallBox" hidden style="margin-top:12px">
      <label class="f req" for="recallNote">تقييم الحاجة للاستدعاء (SH-010)</label>
      <textarea id="recallNote" placeholder="العملاء · الكميات · القرار: استدعاء / لا حاجة · السبب"></textarea>
    </div>
    <div style="margin-top:12px"><label class="f req" for="rootCause">السبب الجذري</label><textarea id="rootCause"></textarea></div>
    <div style="margin-top:12px"><label class="f req" for="capa">الإجراء التصحيحي والوقائي</label>
      <textarea id="capa" placeholder="الصيانة · إعادة التأهيل عند التكرار (SH-023) · التدريب · تعديل الإجراء"></textarea></div>
    <div class="signed" id="eSigned" hidden></div>
  </section>
</fieldset>

<fieldset id="fsQ">
  <section class="card">
    <h2><span class="step">٤</span> اعتماد مدير إدارة الجودة</h2>
    <p class="sub" id="qaHint"></p>
    <label class="f" for="qaNote">ملاحظات</label><textarea id="qaNote"></textarea>
    <div class="signed" id="qSigned" hidden></div>
  </section>
</fieldset>

  <section class="card">
    <div class="actions">
      <button type="button" class="btn" data-act="save" id="btnSave">حفظ</button>
      <button type="button" class="btn gold" data-act="submit" id="btnSubmit">إرسال للمدير الصيدلي</button>
      <button type="button" class="btn gold" data-act="evaluate" id="btnEval" hidden>إرسال لمدير الجودة</button>
      <button type="button" class="btn okb" data-act="final" id="btnFinal" hidden>اعتماد وإغلاق</button>
      <button type="button" class="btn ghost" data-act="back" id="btnBack" hidden>إعادة للمرحلة السابقة</button>
      <button type="button" class="btn ghost" data-act="reopen" id="btnReopen" hidden>إعادة فتح للتعديل</button>
      <button type="button" class="btn ghost" data-act="print">طباعة</button>
      <span style="flex:1"></span>
      <button type="button" class="btn ghost" data-act="new">تقرير جديد</button>
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
    <div class="kv"><span>المنطقة</span><span id="sPoint">—</span></div>
    <div class="kv"><span>المدة</span><b id="sDur">—</b></div>
    <div class="kv"><span>أعلى / أدنى</span><b id="sRange">—</b></div>
    <div class="kv"><span>الأصناف</span><b id="sItems">0</b></div>
    <div class="kv"><span>القرار</span><span id="sDec">—</span></div>
    <div class="kv"><span>الحالة</span><span id="sSt">مسودة</span></div>
  </section>
  <section class="card">
    <h2 style="font-size:15px">التقارير المحفوظة</h2>
    <div class="saved" id="saved"></div>
    <a id="regLink" class="btn ghost small" style="margin-top:10px">تقرير الحرارة الشهري</a>
  </section>
</aside>
</div>

<div id="doc"></div>
<datalist id="peopleList"></datalist>
<datalist id="prodList"></datalist>
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
const FORM_CODE = 'SH-030-F03';
const API = BOOT.urls.records;
const CAUSES = ['', 'انقطاع الكهرباء', 'باب مفتوح / فتح متكرر', 'عطل الثلاجة', 'عطل التكييف', 'تحميل زائد فوق السعة', 'خطأ أو عطل جهاز القياس', 'حرارة الجو (الصيف)', 'أخرى'];
const LABELS = ['', '٢–٨°م · لا يُجمد', '٢–٨°م', 'لا تزيد عن ٢٥°م', 'لا تزيد عن ٣٠°م', 'أقل من ٢٥°م · يُحفظ من الرطوبة', 'أخرى'];
const DECISIONS = { '': '— اختر —', release: 'إفراج', destroy: 'إعدام', mfr: 'بانتظار رأي المصنع' };
const STAGES = [['open', 'أمين المخزن'], ['submitted', 'تقييم المدير الصيدلي'], ['evaluated', 'اعتماد الجودة'], ['final', 'مغلق']];
const BACK30 = 30;

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
const fmtDT = v => v ? `<span class="dt">${v.slice(8, 10)}/${v.slice(5, 7)} ${v.slice(11, 16)}</span>` : '';
const has = v => v !== '' && v !== null && v !== undefined && isFinite(parseFloat(v));
const num = v => { const n = parseFloat(v); return isFinite(n) ? n : null; };
const f1 = v => v === null || v === undefined ? '' : String(Math.round(v * 10) / 10);
const uid = () => Math.random().toString(36).slice(2, 10);
const yn = v => v === 'Y' ? 'نعم' : v === 'N' ? 'لا' : '—';

/* =========================================================
   الخادم
   ========================================================= */
let LIST = [];
let LOGS = [];
let POINTS = [];
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
    if (res.status === 403) throw [json.message || 'ليست لديك صلاحية لهذا الإجراء.'];
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
  const prefix = `TE-${y}-`;
  const nums = LIST.filter(m => m.number && m.number.startsWith(prefix)).map(m => parseInt(m.number.slice(prefix.length), 10) || 0);
  return prefix + pad((nums.length ? Math.max(...nums) : 0) + 1, 3);
}

function newItem() { return { id: uid(), name: '', batch: '', qty: '', label: '', evalRef: '', decision: '' }; }

function blankRec() {
  return {
    id: null, no: nextNo(today()), autoNo: true, date: today(), reporter: BOOT.me.name,
    readingId: null, pointId: '', pointName: '', pointType: '', limits: '', tMin: null, tMax: null, device: '',
    start: '', end: '', maxRead: '', minRead: '',
    cause: '', causeNote: '', verified: '', actionTime: '', informedAt: '', action: '',
    back30: '', movedTo: '', movedAt: '', quarantined: false,
    noItems: false, noItemsWhy: '', items: [newItem()],
    dispensed: '', recallNote: '', devNo: '', rootCause: '', capa: '', qaNote: '',
    durationText: '', decisionText: '',
    stage: 'open', sign: {},
    status: 'draft'
  };
}

/* =========================================================
   الحسابات
   ========================================================= */
function point() { return POINTS.find(p => String(p.id) === String(C.pointId)) || null; }
function isCold() { return ['fridge', 'coldroom'].includes(C.pointType); }

function duration() {
  if (!C.start || !C.end) return null;
  const m = Math.round((new Date(C.end) - new Date(C.start)) / 60000);
  return isFinite(m) ? m : null;
}
function durText(m) {
  if (m === null) return C.start && !C.end ? 'مستمر / لم تُحدد النهاية' : '';
  if (m < 0) return '';
  const d = Math.floor(m / 1440), h = Math.floor((m % 1440) / 60), mm = m % 60;
  return [d ? `${d} يوم` : '', h ? `${h} ساعة` : '', mm || (!d && !h) ? `${mm} دقيقة` : ''].filter(Boolean).join(' و');
}

function rangeState() {
  const mx = num(C.maxRead), mn = num(C.minRead), out = [];
  if (C.tMax !== null && mx !== null && mx > C.tMax) out.push(`أعلى قراءة ${f1(mx)}°م فوق الحد ${f1(C.tMax)}`);
  if (C.tMin !== null && mn !== null && mn < C.tMin) out.push(`أدنى قراءة ${f1(mn)}°م تحت الحد ${f1(C.tMin)}`);
  return { out, freeze: mn !== null && mn <= 0 };
}

function rows() { return C.noItems ? [] : C.items.filter(it => it.name.trim()); }

function frozenRisk(it) { return rangeState().freeze && /لا يُجمد/.test(it.label || ''); }

function decisionSummary() {
  const c = { release: 0, destroy: 0, mfr: 0 };
  rows().forEach(it => { if (c[it.decision] !== undefined) c[it.decision]++; });
  return [c.release ? `إفراج ${c.release}` : '', c.destroy ? `إعدام ${c.destroy}` : '', c.mfr ? `رأي المصنع ${c.mfr}` : ''].filter(Boolean).join(' · ');
}

/* =========================================================
   الصلاحيات
   ========================================================= */
const canEval = () => !!(BOOT.can.steps && BOOT.can.steps.evaluate);
const canFinal = () => !!BOOT.can.finalize;

/* =========================================================
   الرسم
   ========================================================= */
const FIELDS = ['no', 'date', 'reporter', 'device', 'start', 'end', 'maxRead', 'minRead', 'causeNote', 'actionTime', 'informedAt', 'action',
  'movedTo', 'movedAt', 'noItemsWhy', 'recallNote', 'devNo', 'rootCause', 'capa', 'qaNote'];
const YN = { verifiedYN: 'verified', back30YN: 'back30', dispensedYN: 'dispensed' };

function render() {
  FIELDS.forEach(k => { $('#' + k).value = C[k] ?? ''; });
  renderPoints();
  $('#cause').innerHTML = CAUSES.map(c => `<option value="${esc(c)}" ${c === C.cause ? 'selected' : ''}>${c || '— اختر —'}</option>`).join('');
  $('#quarantined').checked = !!C.quarantined;
  $('#noItems').checked = !!C.noItems;
  renderItems(); applyState(); refresh(); renderSaved(); renderAudit();
}

function renderPoints() {
  const list = POINTS.slice();
  if (C.pointId && !point()) list.push({ id: C.pointId, name: C.pointName || ('#' + C.pointId), limits: C.limits });
  $('#pointId').innerHTML = '<option value="">— اختر —</option>' + list.map(p => `<option value="${p.id}" ${String(p.id) === String(C.pointId) ? 'selected' : ''}>${esc(p.name)} · ${esc(p.limits || '')}</option>`).join('');
}

function applyState() {
  const final = C.status === 'final';
  const st = final ? 'final' : C.stage;
  $('#fsK').disabled = final || st !== 'open';
  // التقييم: مرجع وقرار كل صنف في الجدول يُفتح للمدير الصيدلي في مرحلته
  $('#fsE').disabled = final || st !== 'submitted' || !canEval();
  $('#fsQ').disabled = final || st !== 'evaluated' || !canFinal();
  $('#btnSave').hidden = final || (st === 'submitted' && !canEval()) || (st === 'evaluated' && !canFinal());
  $('#btnSubmit').hidden = final || st !== 'open';
  $('#btnEval').hidden = final || st !== 'submitted' || !canEval();
  $('#btnFinal').hidden = final || st !== 'evaluated' || !canFinal();
  $('#btnBack').hidden = final || st === 'open' || (st === 'submitted' && !canEval()) || (st === 'evaluated' && !canFinal());
  $('#btnReopen').hidden = !final || !BOOT.can.reopen;
  const keeperOpen = !final && st === 'open';
  $('#noItems').disabled = !keeperOpen;
  $('#noItemsWhy').disabled = !keeperOpen;
  document.querySelector('[data-act=add-item]').hidden = !keeperOpen;

  const i = STAGES.findIndex(s => s[0] === st);
  $('#flow').innerHTML = STAGES.map((s, k) => `<span class="${k < i || final ? 'done' : k === i ? 'now' : ''}">${s[1]}</span>`).join('');

  const b = $('#badge');
  if (final) { b.className = 'badge b-ok'; b.textContent = 'مغلق · معتمد'; }
  else if (st === 'submitted') { b.className = 'badge b-warn'; b.textContent = 'بانتظار تقييم المدير الصيدلي'; }
  else if (st === 'evaluated') { b.className = 'badge b-warn'; b.textContent = 'بانتظار اعتماد مدير الجودة'; }
  else { b.className = 'badge b-bad'; b.textContent = 'مفتوح · لدى أمين المخزن'; }
  $('#sSt').textContent = b.textContent;

  $('#evalHint').textContent = st === 'open' ? 'يُتاح بعد إرسال التقرير من أمين المخزن.'
    : st === 'submitted' && !canEval() ? 'التقييم للمدير الصيدلي (أو مدير الجودة / مدير المخزن).'
    : 'راجع بيانات جهاز التسجيل وبيانات الثبات أو رأي الشركة المنتجة، ثم حدد مرجع التقييم والقرار لكل صنف في الجدول.';
  $('#qaHint').textContent = final ? '' : st !== 'evaluated' ? 'يُتاح بعد تقييم المدير الصيدلي.' : !canFinal() ? 'الاعتماد لمدير إدارة الجودة أو مدير المخزن.' : 'راجع التقرير والقرار والانحراف المرتبط ثم اعتمد.';
  const s = C.sign || {};
  $('#eSigned').hidden = !s.eval; $('#eSigned').textContent = s.eval ? `قيّمه ${s.eval.by} في ${s.eval.at}` : '';
  $('#qSigned').hidden = !s.final; $('#qSigned').textContent = s.final ? `اعتمده ${s.final.by} في ${s.final.at}` : '';
}

function itemEditable(field) {
  if (C.status === 'final') return false;
  if (['evalRef', 'decision'].includes(field)) return C.stage === 'submitted' && canEval();
  return C.stage === 'open';
}

function renderItems() {
  const ro = f => itemEditable(f) ? '' : 'disabled';
  $('#itemRows').innerHTML = C.items.map((it, i) => `
    <tr data-id="${it.id}">
      <td class="n">${i + 1}</td>
      <td><input type="text" data-f="name" value="${esc(it.name)}" list="prodList" ${ro('name')}><div data-o="flags"></div></td>
      <td><input type="text" data-f="batch" value="${esc(it.batch)}" class="ltr-in" ${ro('batch')}></td>
      <td><input type="number" data-f="qty" value="${esc(it.qty)}" min="0" step="any" ${ro('qty')}></td>
      <td><select data-f="label" ${ro('label')}>${LABELS.map(x => `<option value="${esc(x)}" ${x === it.label ? 'selected' : ''}>${x || '— اختر —'}</option>`).join('')}</select></td>
      <td><input type="text" data-f="evalRef" value="${esc(it.evalRef)}" placeholder="بيانات الثبات / رأي المصنع" ${ro('evalRef')}></td>
      <td><select data-f="decision" ${ro('decision')}>${Object.entries(DECISIONS).map(([k, v]) => `<option value="${k}" ${k === it.decision ? 'selected' : ''}>${v}</option>`).join('')}</select></td>
      <td>${C.stage === 'open' && C.status !== 'final' ? '<button type="button" class="x" data-act="del-item" title="حذف">×</button>' : ''}</td>
    </tr>`).join('');
}

function paintYN(box, v) {
  const good = box.id === 'dispensedYN' ? 'N' : 'Y';
  box.querySelectorAll('button').forEach(b => { b.className = b.dataset.v !== v ? '' : v === good ? 'on-ok' : 'on-bad'; });
}

function refresh() {
  const p = point();
  $('#pointMsg').textContent = p ? `${p.typeLabel || ''} · الحدود ${p.limits}` : '';
  const m = duration();
  C.durationText = durText(m);
  $('#durMsg').textContent = m !== null && m < 0 ? 'النهاية قبل البداية' : C.durationText ? 'المدة: ' + C.durationText : '';
  $('#durMsg').className = 'tmsg ' + (m !== null && m < 0 ? 'bad' : '');
  const R = rangeState();
  $('#rangeMsg').textContent = R.out.length ? '⚠ ' + R.out.join(' · ') : (has(C.maxRead) || has(C.minRead)) && C.tMax !== null ? 'القراءات المسجلة داخل الحدود: تأكد أنها انحراف فعلاً.' : '';
  $('#rangeMsg').className = 'tmsg ' + (R.out.length ? 'bad' : '');
  $('#freezeMsg').hidden = !(R.freeze && isCold());
  $('#causeNoteLbl').className = 'f' + (C.cause === 'أخرى' ? ' req' : '');

  paintYN($('#verifiedYN'), C.verified);
  paintYN($('#back30YN'), C.back30);
  paintYN($('#dispensedYN'), C.dispensed);
  $('#moveBox').hidden = C.back30 !== 'N';
  $('#recallBox').hidden = C.dispensed !== 'Y';
  $('#noItemsBox').hidden = !C.noItems;
  $('#itemsBox').hidden = C.noItems;

  document.querySelectorAll('#itemRows tr[data-id]').forEach(tr => {
    const it = C.items.find(x => x.id === tr.dataset.id); if (!it) return;
    const fl = [];
    if (frozenRisk(it) && isCold()) fl.push({ t: 'تجميد محتمل', bad: true });
    if (it.decision === 'destroy') fl.push({ t: 'إعدام', bad: true });
    if (it.decision === 'mfr') fl.push({ t: 'بانتظار المصنع', bad: false });
    tr.querySelector('[data-o=flags]').innerHTML = fl.map(x => `<span class="flag ${x.bad ? 'bad' : 'warn'}">${x.t}</span>`).join('');
    tr.classList.toggle('bad', fl.some(x => x.bad));
  });

  C.decisionText = C.noItems ? 'لا توجد أصناف' : decisionSummary();
  const q = new URLSearchParams({ ref: C.no, source: 'انحراف حرارة (SH-030)', sop: 'SH-030 الحرارة وسلسلة التبريد' });
  $('#devLink').href = (BOOT.urls.deviation || '#') + '?' + q.toString();
  $('#devLink').hidden = !!C.devNo.trim() || $('#fsE').disabled;

  $('#sNo').textContent = C.no || '—';
  $('#sPoint').textContent = C.pointName || '—';
  $('#sDur').textContent = C.durationText || '—';
  $('#sRange').textContent = has(C.maxRead) || has(C.minRead) ? `${f1(num(C.maxRead)) || '—'} / ${f1(num(C.minRead)) || '—'}` : '—';
  $('#sItems').textContent = rows().length;
  $('#sDec').textContent = C.decisionText || '—';
}

/* =========================================================
   الأحداث
   ========================================================= */
function setPoint(id) {
  C.pointId = id ? String(id) : '';
  const p = point();
  if (p) {
    C.pointName = p.name; C.pointType = p.type; C.limits = p.limits; C.tMin = p.min; C.tMax = p.max;
    if (!C.device) C.device = p.device || '';
  } else { C.pointName = ''; C.pointType = ''; C.limits = ''; C.tMin = null; C.tMax = null; }
}

function bind() {
  FIELDS.forEach(k => $('#' + k).addEventListener('input', e => {
    C[k] = e.target.value;
    if (k === 'no') C.autoNo = false;
    if (k === 'date' && C.autoNo) { C.no = nextNo(C.date); $('#no').value = C.no; }
    refresh();
  }));
  $('#pointId').addEventListener('change', e => { setPoint(e.target.value); $('#device').value = C.device; refresh(); });
  $('#cause').addEventListener('change', e => { C.cause = e.target.value; refresh(); });
  $('#quarantined').addEventListener('change', e => { C.quarantined = e.target.checked; });
  $('#noItems').addEventListener('change', e => { C.noItems = e.target.checked; refresh(); });
  Object.keys(YN).forEach(boxId => $('#' + boxId).addEventListener('click', e => {
    const b = e.target.closest('button[data-v]'); if (!b || b.closest('fieldset').disabled) return;
    C[YN[boxId]] = b.dataset.v; refresh();
  }));
  $('#itemRows').addEventListener('input', e => {
    const host = e.target.closest('[data-id]'); const f = e.target.dataset.f; if (!host || !f) return;
    const it = C.items.find(x => x.id === host.dataset.id); if (!it) return;
    it[f] = e.target.value; refresh();
  });
  $('#itemRows').addEventListener('change', e => { if (e.target.matches('select')) e.target.dispatchEvent(new Event('input', { bubbles: true })); });
}

/* =========================================================
   التحقق
   ========================================================= */
function validateBasic() {
  const e = [];
  if (!C.no.trim()) e.push('رقم التقرير مطلوب');
  if (LIST.some(m => m.number === C.no && m.id !== C.id)) e.push(`رقم التقرير ${C.no} مستخدم في تقرير آخر`);
  if (!C.date) e.push('تاريخ التقرير مطلوب');
  return e;
}

function validateKeeper() {
  const e = validateBasic();
  if (!C.reporter.trim()) e.push('اسم أمين المخزن مطلوب');
  if (!C.pointId) e.push('اختر المنطقة أو الثلاجة');
  if (!C.start) e.push('سجل وقت بداية الانحراف');
  const m = duration();
  if (m !== null && m < 0) e.push('نهاية الانحراف قبل بدايته');
  if (!has(C.maxRead) || !has(C.minRead)) e.push('سجل أعلى وأدنى قراءة من جهاز التسجيل');
  else if (num(C.maxRead) < num(C.minRead)) e.push('أعلى قراءة أقل من أدنى قراءة');
  if (!C.cause) e.push('اختر السبب المبدئي');
  if (C.cause === 'أخرى' && !C.causeNote.trim()) e.push('اكتب تفاصيل السبب');
  if (!C.verified) e.push('حدد هل تم التحقق من القراءة بجهاز آخر');
  if (!C.action.trim()) e.push('اكتب الإجراء الفوري');
  if (!C.actionTime) e.push('سجل وقت الإجراء الفوري');
  if (!C.informedAt) e.push('سجل وقت إبلاغ المدير الصيدلي');
  if (!C.back30) e.push('حدد هل عادت الحرارة خلال ٣٠ دقيقة');
  if (C.back30 === 'N') {
    if (!C.movedTo.trim()) e.push('اكتب المكان المؤهل الذي نُقلت إليه المستحضرات');
    if (!C.movedAt) e.push('سجل وقت نقل المستحضرات');
    if (!C.quarantined) e.push('أكد وضع المستحضرات تحت الحجر وإيقافها على النظام');
  }
  if (C.noItems) { if (!C.noItemsWhy.trim()) e.push('وضح لماذا لا توجد مستحضرات متأثرة'); }
  else {
    if (!rows().length) e.push('أضف المستحضرات المتأثرة، أو علّم «لا توجد مستحضرات»');
    rows().forEach(it => {
      if (!it.batch.trim()) e.push(`التشغيلة مطلوبة للصنف «${it.name}»`);
      if (!has(it.qty)) e.push(`الكمية مطلوبة للصنف «${it.name}»`);
      if (!it.label) e.push(`حدد ظروف التخزين على الملصق للصنف «${it.name}»`);
    });
  }
  return e;
}

function validateEval() {
  const e = validateKeeper();
  rows().forEach(it => {
    if (!it.evalRef.trim()) e.push(`اكتب مرجع التقييم للصنف «${it.name}»`);
    if (!it.decision) e.push(`حدد القرار للصنف «${it.name}»`);
    if (it.decision === 'release' && frozenRisk(it) && isCold()) e.push(`«${it.name}» لا يُجمد وتعرض لحرارة صفر أو أقل: لا يمكن الإفراج`);
  });
  if (!C.dispensed) e.push('حدد هل صُرفت أصناف متأثرة للعملاء');
  if (C.dispensed === 'Y' && !C.recallNote.trim()) e.push('اكتب تقييم الحاجة للاستدعاء (SH-010)');
  if (!C.devNo.trim()) e.push('سجل رقم الانحراف SH-008 (افتحه من الزر إن لم يُسجل)');
  if (!C.rootCause.trim()) e.push('اكتب السبب الجذري');
  if (!C.capa.trim()) e.push('اكتب الإجراء التصحيحي والوقائي');
  return e;
}

function validateFinal() {
  const e = validateEval();
  if (rows().some(it => it.decision === 'mfr')) e.push('يوجد صنف بانتظار رأي المصنع: لا يُغلق التقرير قبل القرار النهائي (إفراج أو إعدام)');
  return e;
}

function showMsg(errs, ok) {
  $('#msg').innerHTML = errs && errs.length ? `<div class="errors">لم يتم الحفظ:<ul>${errs.map(x => `<li>${esc(x)}</li>`).join('')}</ul></div>`
    : ok ? `<div class="okmsg">${esc(ok)}</div>` : '';
}

function recordTitle() { return [C.pointName, C.durationText].filter(Boolean).join(' · '); }

async function persist(status) {
  refresh();
  const body = { number: C.no.trim(), title: recordTitle(), record_date: C.date || null, status, data: Object.assign({}, C, { status }) };
  const res = C.id ? await api('PUT', `${API}/${C.id}`, body) : await api('POST', API, body);
  loadRecord(res.record);
  await refreshList();
}

function loadRecord(rec) {
  C = fitData(blankRec(), rec.data || {});
  C.id = rec.id; C.no = rec.number; C.status = rec.status; C.autoNo = false;
  if (C.status !== 'final' && C.stage === 'final') C.stage = 'evaluated';   // بعد إعادة الفتح
  if (!C.sign) C.sign = {};
  LOGS = rec.logs || [];
  render();
}

async function run(fn, okText) {
  busy(true);
  try { await fn(); if (okText) showMsg(null, okText); }
  catch (err) { showMsg(err); }
  finally { busy(false); }
}

async function move(stage, sign, status, okText) {
  const prev = { stage: C.stage, sign: JSON.parse(JSON.stringify(C.sign || {})) };
  C.stage = stage;
  if (sign) C.sign[sign] = { by: BOOT.me.name, at: nowStr() };
  run(async () => { try { await persist(status || 'draft'); } catch (err) { C.stage = prev.stage; C.sign = prev.sign; throw err; } }, okText);
}

document.addEventListener('click', async e => {
  const b = e.target.closest('[data-act]'); if (!b) return;
  const a = b.dataset.act;

  if (a === 'add-item') { C.items.push(newItem()); renderItems(); refresh(); const ins = document.querySelectorAll('#itemRows [data-f=name]'); ins[ins.length - 1].focus(); }
  if (a === 'del-item') { const id = b.closest('[data-id]').dataset.id; C.items = C.items.filter(x => x.id !== id); if (!C.items.length) C.items.push(newItem()); renderItems(); refresh(); }

  if (a === 'save') { const errs = validateBasic(); if (errs.length) return showMsg(errs); run(() => persist('draft'), `تم حفظ التقرير ${C.no}`); }
  if (a === 'submit') {
    const errs = validateKeeper(); if (errs.length) return showMsg(errs);
    move('submitted', 'keeper', 'draft', 'تم الإرسال للمدير الصيدلي للتقييم. المستحضرات تبقى تحت الحجر لحين القرار.');
  }
  if (a === 'evaluate') {
    const errs = validateEval(); if (errs.length) return showMsg(errs);
    move('evaluated', 'eval', 'draft', 'تم التقييم والإرسال لمدير إدارة الجودة للاعتماد.');
  }
  if (a === 'final') {
    const errs = validateFinal(); if (errs.length) return showMsg(errs);
    move('final', 'final', 'final', 'تم اعتماد التقرير وإغلاقه. يمكنك الطباعة الآن.');
  }
  if (a === 'back') {
    const to = C.stage === 'evaluated' ? 'submitted' : 'open';
    move(to, null, 'draft', to === 'open' ? 'أُعيد التقرير لأمين المخزن.' : 'أُعيد التقرير للمدير الصيدلي.');
  }
  if (a === 'reopen') {
    run(async () => { const res = await api('POST', `${API}/${C.id}/reopen`); loadRecord(res.record); await refreshList(); },
      'التقرير مفتوح للتعديل. اعتمده مرة أخرى بعد الانتهاء.');
  }
  if (a === 'print') { buildDoc(); window.print(); }
  if (a === 'new') { C = blankRec(); C.no = nextNo(C.date); LOGS = []; render(); showMsg(); history.replaceState(null, '', location.pathname); window.scrollTo(0, 0); }
  if (a === 'open') { openRecord(b.dataset.id); }
  if (a === 'del') {
    const id = Number(b.dataset.id);
    if (pendingDelete !== id) { pendingDelete = id; renderSaved(); return; }
    pendingDelete = null;
    run(async () => { await api('DELETE', `${API}/${id}`); if (C.id === id) { C = blankRec(); LOGS = []; } await refreshList(); render(); }, 'تم حذف المسودة.');
  }
  if (a === 'cancel-del') { pendingDelete = null; renderSaved(); }
});

function openRecord(id) {
  return run(async () => { const res = await api('GET', `${API}/${id}`); loadRecord(res.record); showMsg(); window.scrollTo(0, 0); });
}

function stLabel(m) {
  if (m.status === 'final') return ['مغلق', 'b-ok'];
  const s = m.stage || 'open';
  return s === 'submitted' ? ['لدى الصيدلي', 'b-warn'] : s === 'evaluated' ? ['لدى الجودة', 'b-warn'] : ['مفتوح', 'b-bad'];
}

function renderSaved() {
  $('#saved').innerHTML = LIST.length ? LIST.map(m => {
    const [t, cls] = stLabel(m);
    return `
    <div class="row ${m.id === C.id ? 'cur' : ''}">
      <div><b>${esc(m.number)}</b> <span class="badge ${cls}" style="padding:1px 8px;font-size:11px">${t}</span><div class="small">${esc(m.title || '')} · ${fmtDate(m.record_date)}</div></div>
      <div class="actions" style="gap:4px">
        ${pendingDelete === m.id
          ? `<button type="button" class="btn danger small" data-act="del" data-id="${m.id}">تأكيد الحذف</button><button type="button" class="btn ghost small" data-act="cancel-del">إلغاء</button>`
          : `<button type="button" class="btn ghost small" data-act="open" data-id="${m.id}">فتح</button>${m.status !== 'final' ? `<button type="button" class="x" data-act="del" data-id="${m.id}" title="حذف">×</button>` : ''}`}
      </div>
    </div>`;
  }).join('') : '<div class="small">لا توجد تقارير محفوظة.</div>';
}

function renderAudit() {
  $('#audit').innerHTML = LOGS.length
    ? LOGS.map(x => `<tr><td class="tm">${esc(x.t)}</td><td>${esc(x.user)}</td><td><b>${esc(x.action)}</b></td><td>${esc(x.detail || '')}</td></tr>`).join('')
    : '<tr><td class="small" style="padding:10px">لم يُحفظ التقرير بعد.</td></tr>';
}

/* =========================================================
   نسخة الطباعة
   ========================================================= */
function buildDoc() {
  refresh();
  const svg = document.querySelector('header .brand svg');
  const logo = svg ? svg.outerHTML.replace('width="34" height="37"', 'width="38" height="42"').replace('fill="#ffffff"', 'fill="#0f5e5a"') : '';
  const R = rangeState(), s = C.sign || {};
  const sg = (title, x, name) => `<div>${title}<br>${esc(x ? x.by : name || '')}${x ? '<br><span class="dt" style="font-size:10px">' + esc(x.at) + '</span>' : ''}</div>`;
  $('#doc').innerHTML = `
    <div class="d-head">
      <div class="d-brand">${logo}<div><div class="ar">إتقان</div><div class="sl">إتقان في كل خطوة</div></div></div>
      <div class="d-title"><div class="t1">تقرير انحراف الحرارة</div><div class="t2">Temperature Excursion Report</div></div>
      <div class="d-tenant">${BOOT.tenant.logo ? `<img src="${esc(BOOT.tenant.logo)}" alt="">` : ''}<div><b>${esc(BOOT.tenant.name_ar)}</b><span>${esc(BOOT.tenant.name_en)}</span></div></div>
    </div>
    <div class="d-code" style="display:flex;gap:18px;justify-content:space-between;margin:-4px 0 8px">
      <span>رقم التقرير: <b>${esc(C.no)}</b></span><span>النموذج: <b>${FORM_CODE}</b></span><span>التاريخ: ${fmtDate(C.date)}</span><span>الحالة: ${C.status === 'final' ? 'مغلق' : 'مفتوح'}</span>
    </div>
    <table class="d">
      <tr><td class="k">المنطقة / الثلاجة</td><td>${esc(C.pointName)} <span style="color:#5f6f6c">(${esc(C.limits)})</span></td><td class="k">رقم الجهاز</td><td class="num">${esc(C.device) || '—'}</td></tr>
      <tr><td class="k">بداية الانحراف</td><td>${fmtDT(C.start)}</td><td class="k">نهاية الانحراف</td><td>${fmtDT(C.end) || 'لم تُحدد'}</td></tr>
      <tr><td class="k">أعلى / أدنى قراءة</td><td><span class="dt">${esc(C.maxRead)} / ${esc(C.minRead)}</span> °م</td><td class="k">المدة الإجمالية</td><td>${esc(C.durationText) || '—'}</td></tr>
      <tr><td class="k">السبب المبدئي</td><td>${esc(C.cause)}${C.causeNote ? ' · ' + esc(C.causeNote) : ''}</td><td class="k">التحقق بجهاز آخر</td><td>${yn(C.verified)}</td></tr>
      <tr><td class="k">الإجراء الفوري ووقته</td><td colspan="3">${esc(C.action)} · <span class="dt">${esc(C.actionTime)}</span> · إبلاغ المدير الصيدلي <span class="dt">${esc(C.informedAt)}</span></td></tr>
      <tr><td class="k">عادت خلال ٣٠ دقيقة</td><td colspan="3">${yn(C.back30)}${C.back30 === 'N' ? ` · نُقلت إلى ${esc(C.movedTo)} <span class="dt">${esc(C.movedAt)}</span> · ${C.quarantined ? 'تحت الحجر وموقوفة على النظام' : ''}` : ''}</td></tr>
      ${R.out.length ? `<tr class="bad"><td class="k">تجاوز الحدود</td><td colspan="3">${R.out.map(esc).join(' · ')}${R.freeze && isCold() ? ' · احتمال تجميد' : ''}</td></tr>` : ''}
      <tr><td class="k">رقم الانحراف (SH-008)</td><td class="num">${esc(C.devNo) || '—'}</td><td class="k">صُرفت أصناف متأثرة؟</td><td>${yn(C.dispensed)}${C.dispensed === 'Y' ? ' (SH-010)' : ''}</td></tr>
    </table>
    <div class="keep"><div class="d-h2">المستحضرات المتأثرة والقرار</div>
    ${C.noItems ? `<table class="d"><tr><td>لا توجد مستحضرات: ${esc(C.noItemsWhy)}</td></tr></table>` : `
    <table class="d"><tr><th style="width:4%">م</th><th>الصنف</th><th>التشغيلة</th><th>الكمية</th><th>ظروف التخزين على الملصق</th><th>مرجع التقييم</th><th>القرار</th></tr>
      ${rows().map((it, i) => `<tr class="${it.decision === 'destroy' ? 'bad' : ''}"><td>${i + 1}</td><td>${esc(it.name)}</td><td class="num">${esc(it.batch)}</td><td class="num">${esc(it.qty)}</td><td>${esc(it.label)}</td><td>${esc(it.evalRef)}</td><td><b>${DECISIONS[it.decision] && it.decision ? DECISIONS[it.decision] : ''}</b></td></tr>`).join('')}
    </table>`}</div>
    ${C.dispensed === 'Y' ? `<div class="d-h2">تقييم الحاجة للاستدعاء</div><table class="d"><tr><td>${esc(C.recallNote).replace(/\n/g, '<br>')}</td></tr></table>` : ''}
    <div class="keep"><div class="d-h2">السبب الجذري والإجراء التصحيحي</div>
    <table class="d"><tr><td class="k">السبب الجذري</td><td>${esc(C.rootCause).replace(/\n/g, '<br>') || '—'}</td></tr>
      <tr><td class="k">الإجراء التصحيحي والوقائي</td><td>${esc(C.capa).replace(/\n/g, '<br>') || '—'}</td></tr>
      ${C.qaNote ? `<tr><td class="k">ملاحظات الجودة</td><td>${esc(C.qaNote)}</td></tr>` : ''}</table></div>
    <div class="d-signs">${sg('أمين المخزن', s.keeper, C.reporter)}${sg('المدير الصيدلي', s.eval)}${sg('مدير إدارة الجودة', s.final)}</div>
    <div class="d-foot">${esc(BOOT.tenant.name_ar)} · إتقان · نظام إدارة الجودة · ${FORM_CODE} · طبعه ${esc(BOOT.me.name)} في ${nowStr()}</div>`;
}

/* =========================================================
   البدء
   ========================================================= */
async function prefillFromReading(id) {
  try {
    const r = (await api('GET', `${BOOT.urls.temp}/readings/${id}`)).reading;
    if (r.f03) return openRecord(r.f03);   // يوجد تقرير لنفس القراءة
    C.readingId = r.id; setPoint(r.pointId);
    C.date = today();
    if (C.autoNo) C.no = nextNo(C.date);
    C.start = `${r.date}T${r.time || '00:00'}`;
    C.maxRead = r.high !== null ? String(r.high) : String(r.cur);
    C.minRead = r.low !== null ? String(r.low) : String(r.cur);
    C.action = r.note || '';
    C.actionTime = r.time || '';
    render();
    showMsg(null, `بيانات القراءة (${r.date} ${r.shiftLabel || ''}) نُقلت للتقرير. أكمل باقي البيانات.`);
  } catch (e) { showMsg(e); }
}

C = blankRec();
bind();
render();
$('#regLink').href = BOOT.urls.register || '#';
$('#regLink').hidden = !BOOT.urls.register;
document.getElementById('peopleList').innerHTML = BOOT.users.map(u => `<option value="${esc(u.name)}">${esc(u.role || '')}</option>`).join('');

(async () => {
  try { POINTS = (await api('GET', BOOT.urls.points)).points || []; } catch (e) { POINTS = []; }
  try {
    const pr = (await api('GET', BOOT.urls.products)).products || [];
    document.getElementById('prodList').innerHTML = pr.map(p => `<option value="${esc(p.n)}"></option>`).join('');
  } catch (e) {}
  await refreshList();
  const q = new URLSearchParams(location.search);
  if (q.get('open')) await openRecord(q.get('open'));
  else if (q.get('reading')) await prefillFromReading(q.get('reading'));
  else { if (C.autoNo) C.no = nextNo(C.date); render(); }
})();
@endverbatim
</script>
@endpush