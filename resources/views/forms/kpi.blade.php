@extends('layouts.app')

@section('title', 'تقييم الأداء الشهري')

@push('head')
<style>
@verbatim

  :root{
    --bg:#f4f6f5; --card:#ffffff; --ink:#1c2826; --muted:#5f6f6c; --line:#dde3e1;
    --brand:#0f5e5a; --brand-soft:#e3f0ee; --gold:#c08a1e; --gold-soft:#f8efdc;
    --ok:#1e7a46; --ok-soft:#e4f4ea;
    --warn:#9a6300; --warn-soft:#fdf1dc;
    --bad:#b3261e; --bad-soft:#fbe7e5;
    --radius:10px;
  }


  .badge{display:inline-block;padding:4px 14px;border-radius:999px;font-size:13px;font-weight:700;white-space:nowrap}
  .b-draft{background:#e9eeec;color:#44524f}
  .b-warn{background:var(--warn-soft);color:var(--warn)}
  .b-ok{background:var(--ok-soft);color:var(--ok)}
  .b-bad{background:var(--bad-soft);color:var(--bad)}
  .b-info{background:var(--brand-soft);color:var(--brand)}

  fieldset{border:0;padding:0;margin:0;min-width:0}
  fieldset:disabled .lockable{opacity:.7}

  .card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:18px 20px;margin-bottom:16px}
  .card h2{font-size:17px;margin:0 0 14px;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
  .step{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:var(--brand-soft);color:var(--brand);font-size:14px;font-weight:700}
  .sub{font-size:13px;color:var(--muted);margin:-8px 0 12px}

  .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:14px}
  label.f{display:block;font-size:13px;color:var(--muted);margin-bottom:4px;font-weight:600}
  .req::after{content:" *";color:var(--bad)}
  input[type=text],input[type=number],input[type=date],input[type=month],select,textarea{width:100%;padding:7px 9px;border:1px solid var(--line);border-radius:8px;font:inherit;background:#fff;color:var(--ink)}
  input[readonly]{background:#f0f3f2;color:var(--muted)}
  input[type=date],input[type=month],input[type=number],.ltr-in{direction:ltr;text-align:right}
  input:focus,select:focus,textarea:focus{outline:2px solid var(--brand-soft);border-color:var(--brand)}
  textarea{min-height:64px;resize:vertical}

  .btn{background:var(--brand);color:#fff;border:1px solid var(--brand);border-radius:8px;padding:8px 18px;font:inherit;font-weight:600;cursor:pointer}
  .btn.ghost{background:#fff;color:var(--brand)}
  .btn.gold{background:var(--gold);border-color:var(--gold)}
  .btn.small{padding:4px 12px;font-size:13px}
  .btn:disabled{opacity:.5;cursor:not-allowed}
  .actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}

  .seg{display:inline-flex;border:1px solid var(--line);border-radius:8px;overflow:hidden}
  .seg label{padding:7px 16px;cursor:pointer;font-weight:600;color:var(--muted);font-size:14px}
  .seg label+label{border-inline-start:1px solid var(--line)}
  .seg input{display:none}
  .seg label:has(input:checked){background:var(--brand);color:#fff}

  /* الجدول */
  .tbl-wrap{overflow-x:auto}
  table.kpi{width:100%;border-collapse:collapse;font-size:13px;min-width:960px}
  table.kpi th{background:#fafbfb;color:var(--muted);font-weight:600;padding:8px 6px;text-align:start;border-bottom:1px solid var(--line);white-space:nowrap}
  table.kpi td{padding:6px 5px;border-bottom:1px solid var(--line);vertical-align:middle}
  table.kpi td input,table.kpi td select{padding:5px 7px;font-size:13px}
  table.kpi tr.grp td{background:var(--brand-soft);color:var(--brand);font-weight:700;padding:7px 10px}
  table.kpi tr.grp td .gw{float:left;font-size:12px}
  table.kpi tr.na td{color:#a3adab;background:#f6f7f7}
  table.kpi tr.na td input{background:#f0f2f2}
  table.kpi tr.low td{background:#fff7f6}
  .code{font-weight:700;direction:ltr;display:inline-block}
  .kname{font-weight:600}
  .khint{font-size:11.5px;color:var(--muted);line-height:1.4}
  .num{direction:ltr;text-align:center;white-space:nowrap;font-variant-numeric:tabular-nums}
  .w{font-weight:700;color:var(--brand)}
  .sc{font-weight:700}
  .flag{font-size:11.5px;font-weight:700;padding:2px 8px;border-radius:99px;white-space:nowrap}
  .flag.bad{background:var(--bad-soft);color:var(--bad)}
  .flag.cap{background:var(--gold-soft);color:var(--warn)}
  .flag.zero{background:var(--bad);color:#fff}
  .flag.na{background:#eef0f0;color:#8a9592}
  .bar{height:6px;border-radius:99px;background:#e9eeec;overflow:hidden;min-width:60px;margin-top:3px}
  .bar i{display:block;height:100%;background:var(--brand)}
  .bar i.low{background:var(--bad)} .bar i.over{background:var(--gold)}
  td.in{width:108px}
  td.in input{min-width:88px}

  .checks{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:8px 18px}
  .check{display:flex;gap:10px;align-items:flex-start;font-size:14px}
  .check input{margin-top:5px;width:18px;height:18px;flex-shrink:0;accent-color:var(--bad)}
  .gate-on{background:var(--bad-soft);border:1px solid #efc1bd;color:var(--bad);border-radius:8px;padding:8px 12px;font-size:13px;font-weight:600;margin-top:12px}

  .plan{width:100%;border-collapse:collapse;font-size:13px;min-width:720px}
  .plan th,.plan td{padding:7px 6px;border-bottom:1px solid var(--line);text-align:start;vertical-align:top}
  .plan th{color:var(--muted);font-weight:600;background:#fafbfb}

  .signs{display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:14px}
  .sbox{border:1px solid var(--line);border-radius:10px;padding:12px 14px;background:#fbfcfc}
  .sbox h4{margin:0 0 8px;font-size:14px;display:flex;justify-content:space-between;align-items:center}
  .sbox .stamp{font-size:12.5px;color:var(--ok);font-weight:600;margin-top:8px}
  .sbox.done{border-color:#b9dcc6;background:var(--ok-soft)}
  .errors{background:var(--bad-soft);color:var(--bad);border-radius:8px;padding:10px 12px;font-size:13px;margin-top:12px}
  .errors ul{margin:6px 0 0;padding-inline-start:18px}
  .info{background:var(--brand-soft);border:1px solid #c6dfdb;color:#24514d;border-radius:var(--radius);padding:10px 14px;font-size:13px}

  details.ref{margin-top:4px}
  details.ref summary{cursor:pointer;font-weight:700;color:var(--brand);font-size:15px}
  table.def{width:100%;border-collapse:collapse;font-size:12.5px;min-width:1000px;margin-top:12px}
  table.def th,table.def td{padding:6px;border-bottom:1px solid var(--line);text-align:start;vertical-align:top}
  table.def th{color:var(--muted);font-weight:600;background:#fafbfb;white-space:nowrap}

  table.audit{width:100%;border-collapse:collapse;font-size:13px}
  table.audit th,table.audit td{padding:6px 8px;border-bottom:1px solid var(--line);text-align:start;vertical-align:top}
  table.audit th{color:var(--muted);font-weight:600}
  td.t{direction:ltr;text-align:right;white-space:nowrap}
  .audit-wrap{max-height:260px;overflow:auto}

  aside{position:sticky;top:16px}
  @media (max-width:1050px){aside{position:static}}
  .sum h3{font-size:14px;margin:0 0 8px;color:var(--muted)}
  .score{font-size:40px;font-weight:700;line-height:1.1;direction:ltr;text-align:right}
  .meter{height:8px;background:#e9eeec;border-radius:99px;overflow:hidden;margin:8px 0 4px}
  .meter i{display:block;height:100%;background:var(--brand);width:0;transition:width .2s}
  .small{font-size:13px;color:var(--muted)}
  .kv{display:flex;justify-content:space-between;gap:8px;font-size:14px;padding:4px 0;border-bottom:1px dashed var(--line)}
  .kv:last-child{border-bottom:0}
  .kv b{font-weight:700;direction:ltr}
  .c-ok{color:var(--ok)} .c-bad{color:var(--bad)} .c-warn{color:var(--warn)} .c-brand{color:var(--brand)}
  .bands .kv span:first-child{display:flex;align-items:center;gap:6px}
  .dot{width:10px;height:10px;border-radius:50%;display:inline-block}
  .bands .kv.on{background:var(--brand-soft);border-radius:6px;padding-inline:6px;font-weight:700}

  [hidden]{display:none !important}
  .scr{display:grid;grid-template-columns:1fr 290px;gap:20px;align-items:start}
  @media (max-width:1050px){.scr{grid-template-columns:1fr}}
  .scr-main{min-width:0}
  .scr-head{display:flex;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px}
  .scr-head h1{margin:0;font-size:20px}
  .scr-head .code{font-size:12px;color:var(--muted)}
  .busy{opacity:.6;pointer-events:none}
  .okmsg{background:var(--ok-soft);color:var(--ok);border-radius:8px;padding:8px 12px;font-size:13px;margin-top:12px;font-weight:600}
  .x{background:none;border:0;color:var(--bad);font-size:20px;line-height:1;cursor:pointer;padding:2px 6px}
  .saved{max-height:320px;overflow:auto}
  .saved .row{display:flex;justify-content:space-between;align-items:center;gap:6px;padding:7px 4px;border-bottom:1px solid var(--line);font-size:13px}
  .saved .row.cur{background:var(--brand-soft);border-radius:6px}
  .ltr{direction:ltr;unicode-bidi:isolate}
  .ph{display:none}
  .okmsg,.errors{margin-top:12px}
  @media print{
    @page{size:A4 landscape;margin:10mm}
    body{background:#fff;font-size:11.5px}
    header.top,footer.foot,.no-print,.btn,.actions,main.wrap>.alert{display:none !important}
    main.wrap{padding:0;max-width:none}
    .ph{display:flex;justify-content:space-between;align-items:center;border-bottom:3px solid #c08a1e;padding-bottom:6px;margin-bottom:10px}
    .ph b{display:block;font-size:15px;color:#0f5e5a}
    .ph img{height:40px}
    .scr{display:block}
    .card{break-inside:avoid;border-color:#999}
    aside{position:static}
    input,select,textarea{border:0;background:none !important}
    table.kpi{min-width:0}
    *{-webkit-print-color-adjust:exact;print-color-adjust:exact}
  }
@endverbatim
</style>
@endpush

@section('content')
@verbatim
<div class="ph">
  <div><b id="phTenant"></b><span>تقييم الأداء الشهري · <span id="phSet" class="ltr"></span></span></div>
  <img id="phLogo" alt="" hidden>
</div>
<div class="scr-head">
  <h1 id="title">تقييم الأداء الشهري</h1>
  <span class="code ltr" id="setCode"></span>
  <span id="badge" class="badge b-draft">مسودة</span>
</div>
<div class="scr">
<div class="scr-main">
  <fieldset id="fsA">
    <!-- ١. بيانات التقييم -->
    <section class="card lockable">
      <h2><span class="step">١</span>بيانات التقييم</h2>
      <div class="grid">
        <div><label class="f req" for="job">الوظيفة</label><select id="job"></select></div>
        <div id="varBox" hidden><label class="f req">القسم</label><span class="seg" id="variants"></span></div>
        <div><label class="f req" for="empPick">الموظف</label>
          <input type="text" id="empPick" list="empList" placeholder="اختر من المستخدمين أو اكتب الاسم" autocomplete="off">
          <div class="small" id="empHint"></div></div>
        <div><label class="f" for="empCode">كود الموظف</label><input type="text" id="empCode" class="ltr-in"></div>
      </div>
      <div class="grid" style="margin-top:14px">
        <div><label class="f">القسم / الوصف الوظيفي</label><input type="text" id="dept" readonly></div>
        <div><label class="f req" for="period">شهر التقييم</label><input type="month" id="period"></div>
        <div><label class="f req" for="workDays">أيام العمل في الشهر</label><input type="number" id="workDays" min="1" max="31"></div>
        <div><label class="f">المقيِّم (المدير المباشر)</label><input type="text" id="evaluator" readonly></div>
        <div><label class="f" for="no">رقم التقييم</label><input type="text" id="no" class="ltr-in"></div>
      </div>
    </section>

    <!-- ٢. المؤشرات -->
    <section class="card lockable">
      <h2><span class="step">٢</span>مؤشرات الأداء</h2>
      <p class="sub">أدخل «المستهدف» و«الفعلي» لكل مؤشر. نسبة الإنجاز والدرجة المحتسبة والمرجحة تُحسب تلقائياً طبقاً للأوزان والحدود المعتمدة.</p>
      <div class="tbl-wrap">
        <table class="kpi">
          <thead><tr>
            <th style="width:64px">الكود</th><th>المؤشر</th><th style="width:56px">الوزن</th>
            <th>المستهدف</th><th>الفعلي</th><th style="width:96px">نسبة الإنجاز</th>
            <th style="width:86px">الحد الأدنى / الأقصى</th><th style="width:80px">الدرجة المحتسبة</th><th style="width:76px">المرجحة</th><th style="width:96px">تنبيه</th>
          </tr></thead>
          <tbody id="kpiBody"></tbody>
        </table>
      </div>
    </section>

    <!-- ٣. المخالفات الجوهرية -->
    <section class="card lockable">
      <h2><span class="step">٣</span>المخالفات الجوهرية</h2>
      <p class="sub">أي مخالفة واحدة تجعل سقف النتيجة النهائية ٧٠٪ مهما كانت نتائج المؤشرات. تُوثَّق المخالفة بمستند.</p>
      <div class="checks" id="gateList"></div>
      <div style="margin-top:12px"><label class="f" for="gateRef">المستند / التفاصيل</label><input type="text" id="gateRef"></div>
      <div id="gateMsg" class="gate-on" hidden></div>
    </section>

    <!-- ٤. خطة التحسين -->
    <section class="card lockable">
      <h2><span class="step">٤</span>خطة التحسين وتعليق المدير</h2>
      <p class="sub">المؤشرات التي درجتها أقل من ٨٠٪ تظهر هنا، ويجب تحديد إجراء ومسؤول وموعد لكل منها قبل الاعتماد.</p>
      <div class="tbl-wrap" id="planWrap"></div>
      <div style="margin-top:14px"><label class="f" for="mgrComment">تعليق المدير على التقييم</label><textarea id="mgrComment"></textarea></div>
    </section>
  </fieldset>

  <!-- ٥. الاعتماد -->
  <section class="card">
    <h2><span class="step">٥</span>الاعتماد والتوقيعات</h2>
    <p class="sub">التسلسل: المدير المباشر يعتمد ← الموظف يوقع بالعلم ← الموارد البشرية تعتمد وتغلق (الرئيس التنفيذي إذا لم يوجد مدير موارد بشرية).</p>
    <div class="signs">
      <div class="sbox" id="sb-mgr">
        <h4>المدير المباشر <span class="badge b-draft" id="st-mgr">لم يوقع</span></h4>
        <div class="stamp" id="ss-mgr" hidden></div>
        <div class="actions" style="margin-top:10px"><button type="button" class="btn small" data-act="sign-mgr" id="btn-mgr">اعتماد التقييم</button></div>
      </div>
      <div class="sbox" id="sb-emp">
        <h4>الموظف (بالعلم) <span class="badge b-draft" id="st-emp">لم يوقع</span></h4>
        <label class="f" for="empComment">ملاحظات الموظف</label>
        <textarea id="empComment" disabled></textarea>
        <div class="actions" style="margin-top:10px">
          <button type="button" class="btn small" data-act="sign-emp" id="btn-emp" hidden>توقيع بالعلم</button>
          <button type="button" class="btn small ghost" data-act="sign-paper" id="btn-paper" hidden>وقّع الموظف ورقياً</button>
        </div>
        <div class="small" id="empWait"></div>
        <div class="stamp" id="ss-emp" hidden></div>
      </div>
      <div class="sbox" id="sb-hr">
        <h4>الموارد البشرية <span class="badge b-draft" id="st-hr">لم يوقع</span></h4>
        <div class="stamp" id="ss-hr" hidden></div>
        <div class="actions" style="margin-top:10px">
          <button type="button" class="btn small" data-act="sign-hr" id="btn-hr" hidden>اعتماد وإغلاق</button>
          <button type="button" class="btn small ghost" data-act="return" id="btn-ret" hidden>إعادة للمدير</button>
        </div>
      </div>
    </div>
    <div id="msg"></div>
    <div class="actions no-print" style="margin-top:16px">
      <button type="button" class="btn" data-act="save" id="btnSave">حفظ</button>
      <button type="button" class="btn ghost" data-act="reopen" id="btnReopen" hidden>إعادة فتح</button>
      <button type="button" class="btn gold" onclick="window.print()">طباعة</button>
      <span style="flex:1"></span>
      <button type="button" class="btn ghost" data-act="new">تقييم جديد</button>
    </div>
  </section>

  <section class="card no-print">
    <details class="ref">
      <summary>تعريف المؤشرات والأوزان المعتمدة (المرجع)</summary>
      <div class="tbl-wrap"><table class="def" id="defTbl"></table></div>
      <div class="info" style="margin-top:12px" id="formula"></div>
      <div class="tbl-wrap" style="margin-top:12px"><table class="def" id="rubricTbl" style="min-width:600px"></table></div>
    </details>
  </section>

  <section class="card no-print">
    <h2>سجل التعديلات</h2>
    <div class="audit-wrap"><table class="audit"><tbody id="audit"></tbody></table></div>
  </section>
</div>

<aside>
  <section class="card sum">
    <h3>النتيجة النهائية</h3>
    <div class="score" id="finalScore">—</div>
    <div style="margin-top:6px"><span class="badge b-draft" id="ratingBadge">—</span></div>
    <div class="meter"><i id="finalMeter"></i></div>
    <div class="small" id="statusText">أدخل بيانات المؤشرات</div>
  </section>
  <section class="card sum">
    <h3>تفاصيل النتيجة</h3>
    <div class="kv"><span>المؤشرات المكتملة</span><b id="sDone">0 / 0</b></div>
    <div class="kv"><span>إجمالي الأوزان</span><b id="sWeight">0%</b></div>
    <div id="sGroups"></div>
    <div class="kv"><span>قبل المخالفات</span><b id="sRaw">—</b></div>
    <div class="kv"><span>مؤشرات تحتاج تحسين</span><b id="sLow" class="c-bad">0</b></div>
    <div class="kv"><span>مخالفات جوهرية</span><b id="sGate">0</b></div>
  </section>
  <section class="card sum bands no-print">
    <h3>مقياس التقدير</h3>
    <div id="bandList"></div>
  </section>
  <section class="card sum no-print">
    <h3>التقييمات المحفوظة</h3>
    <a id="regLink" class="btn ghost small" style="margin-bottom:8px;display:inline-block;text-decoration:none">سجل التقييمات الشهري</a>
    <div class="saved" id="saved"></div>
  </section>
</aside>
</div>
<datalist id="empList"></datalist>
@endverbatim
@endsection

@push('scripts')
<script>
const BOOT = {!! json_encode($boot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!};
</script>
<script>
@verbatim
/* =========================================================
   تقييم الأداء الشهري (KPI) · كل الوظائف · المدير ← الموظف ← الموارد البشرية
   ========================================================= */
const API = BOOT.urls.records;
const ME = BOOT.me;
let SETS = {}, BANDS = [], CAP = 0.7, LOW_FLAG = 0.8, PLAN_LIMIT = 0.65;

/* ---------- أدوات ---------- */
const $ = s => document.querySelector(s);
const pad = n => String(n).padStart(2, '0');
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const pct = (v, d = 0) => v == null || isNaN(v) ? '—' : (v * 100).toFixed(d) + '%';
const today = () => { const d = new Date(); return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`; };
const nowStr = () => { const d = new Date(); return `${today()} ${pad(d.getHours())}:${pad(d.getMinutes())}`; };
const prevMonth = () => { const d = new Date(); d.setDate(1); d.setMonth(d.getMonth() - 1); return `${d.getFullYear()}-${pad(d.getMonth() + 1)}`; };
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
function busy(on) { document.querySelector('.scr').classList.toggle('busy', !!on); }

/* ---------- الحالة ---------- */
let LIST = [], LOGS = [], C = null, pendingDelete = null;

function nextNo() {
  const prefix = `PE-${today().slice(0, 4)}-`;
  const nums = LIST.filter(m => m.number && m.number.startsWith(prefix)).map(m => parseInt(m.number.slice(prefix.length), 10) || 0);
  return prefix + String((nums.length ? Math.max(...nums) : 0) + 1).padStart(3, '0');
}
function blankEval() {
  const job = Object.keys(SETS)[0] || '';
  return { id: null, no: nextNo(), autoNo: true, job, variant: job ? Object.keys(SETS[job].variants)[0] : '',
    empName: '', empId: null, empCode: '', period: prevMonth(), workDays: '', evaluator: ME.name, evaluatorId: ME.id,
    vals: {}, gates: [], gateRef: '', plan: {}, mgrComment: '', empComment: '',
    stage: 'draft', sign: {}, result: null, status: 'draft' };
}
const S = () => SETS[C.job];
const KPIS = () => S() ? S().kpis : [];
const weight = k => Number(k.w[C.variant] ?? 0);
const band = s => BANDS.find(b => s >= b.min);

/* ---------- الحساب ---------- */
function evalKpi(k) {
  const w = weight(k), v = C.vals[k.code] || { t: '', a: '' };
  let t = v.t, a = v.a;
  if (k.type === 'rating') t = 5;
  if (k.type === 'days') t = C.workDays;
  if (w === 0) return { w, na: true };
  if (t === '' || t == null || a === '' || a == null) return { w, done: false };
  t = Number(t); a = Number(a);
  let ach;
  if (t === 0) { if (k.type === 'zeroOk') ach = 1; else return { w, done: false, err: 'المستهدف صفر' }; }
  else if (k.type === 'lower') ach = a === 0 ? k.max : t / a;
  else ach = a / t;
  const score = ach < k.min ? 0 : Math.min(ach, k.max);
  return { w, done: true, ach, score, ws: score * w, zero: ach < k.min && k.min > 0, capped: ach > k.max, low: score < LOW_FLAG };
}
function calc() {
  const res = {}; let wSum = 0, done = 0, need = 0, raw = 0; const low = [], g = {};
  Object.keys(S() ? S().groups : {}).forEach(x => { g[x] = [0, 0]; });
  KPIS().forEach(k => {
    const r = res[k.code] = evalKpi(k);
    wSum += r.w; g[k.g][1] += r.w;
    if (r.na) return;
    need++;
    if (r.done) { done++; raw += r.ws; g[k.g][0] += r.ws; if (r.low) low.push(k); }
  });
  const gateCount = C.gates.length;
  const final = done === 0 ? null : (gateCount ? Math.min(raw, CAP) : raw);
  return { res, wSum, done, need, raw: done ? raw : null, final, complete: need > 0 && done === need, low, g, gateCount };
}

/* ---------- الرسم ---------- */
function canEditMain() { return C.status !== 'final' && C.stage === 'draft' && (!C.evaluatorId || C.evaluatorId === ME.id || BOOT.can.reopen); }

function render() {
  $('#job').value = C.job;
  const vs = S() ? S().variants : {};
  $('#varBox').hidden = Object.keys(vs).length < 2;
  $('#variants').innerHTML = Object.entries(vs).map(([k, v]) => `<label><input type="radio" name="variant" value="${k}" ${k === C.variant ? 'checked' : ''}> ${esc(v.name)}</label>`).join('');
  $('#empPick').value = C.empName; $('#empCode').value = C.empCode; $('#period').value = C.period; $('#workDays').value = C.workDays;
  $('#evaluator').value = C.evaluator; $('#no').value = C.no; $('#gateRef').value = C.gateRef; $('#mgrComment').value = C.mgrComment; $('#empComment').value = C.empComment;
  $('#title').textContent = 'تقييم الأداء الشهري – ' + (S() ? S().title : '');
  $('#setCode').textContent = S() ? S().code : '';
  $('#phSet').textContent = (S() ? S().code + ' · ' + S().title : '') + ' · ' + C.period;
  buildTable(); buildRefs(); refresh(); applyState(); renderSaved(); renderAudit();
}

function buildTable() {
  let html = '';
  const ro = !canEditMain() ? 'disabled' : '';
  Object.entries(S() ? S().groups : {}).forEach(([g, gname]) => {
    html += `<tr class="grp"><td colspan="10">${esc(gname)} <span class="gw" id="gw-${g}"></span></td></tr>`;
    KPIS().filter(k => k.g === g).forEach(k => {
      const v = C.vals[k.code] || (C.vals[k.code] = { t: '', a: '' });
      let tCell, aCell;
      if (k.type === 'rating') {
        tCell = '<input type="text" value="5" readonly class="num">';
        aCell = `<select data-k="${k.code}" data-f="a" ${ro}><option value="">—</option>${S().rubric.map(r => `<option value="${r[0]}" ${String(v.a) === String(r[0]) ? 'selected' : ''} title="${esc(r[1])}">${r[0]} · ${esc(r[1].split(':')[0])}</option>`).join('')}</select>`;
      } else {
        tCell = k.type === 'days' ? `<input type="text" readonly class="num" id="t-${k.code}" value="${esc(C.workDays)}">`
          : `<input type="number" min="0" step="any" data-k="${k.code}" data-f="t" value="${esc(v.t)}" ${ro}>`;
        aCell = `<input type="number" min="0" step="any" data-k="${k.code}" data-f="a" value="${esc(v.a)}" ${ro}>`;
      }
      html += `<tr id="r-${k.code}">
        <td><span class="code">${k.code}</span></td>
        <td><div class="kname">${esc(k.name)}</div><div class="khint">${esc(k.def)}</div></td>
        <td class="num w" id="w-${k.code}"></td>
        <td class="in">${tCell}<div class="khint">${esc(k.t)}</div></td>
        <td class="in">${aCell}<div class="khint">${esc(k.a)}</div></td>
        <td class="num"><span id="ach-${k.code}">—</span><div class="bar"><i id="bar-${k.code}"></i></div></td>
        <td class="small" style="text-align:center;white-space:nowrap"><bdi>${pct(k.min)}</bdi> / <bdi>${pct(k.max)}</bdi></td>
        <td class="num sc" id="sc-${k.code}">—</td>
        <td class="num sc" id="ws-${k.code}">—</td>
        <td id="fl-${k.code}"></td>
      </tr>`;
    });
  });
  $('#kpiBody').innerHTML = html;
  $('#gateList').innerHTML = (S() ? S().gates : []).map((g, i) => `<label class="check"><input type="checkbox" data-gate="${i}" ${C.gates.includes(i) ? 'checked' : ''}>${esc(g)}</label>`).join('');
}

function buildRefs() {
  if (!S()) return;
  const v = C.variant;
  $('#defTbl').innerHTML = `<thead><tr><th>الكود</th><th>المجموعة</th><th>المؤشر</th><th>التعريف والمعادلة</th><th>مصدر البيانات</th><th>الدورية</th><th>الحد الأدنى</th><th>الحد الأقصى</th><th>الوزن</th></tr></thead><tbody>` +
    KPIS().map(k => `<tr><td class="code">${k.code}</td><td>${esc(S().groups[k.g])}</td><td>${esc(k.name)}</td><td>${esc(k.def)}</td><td>${esc(k.src)}</td><td>${esc(k.freq)}</td><td class="num">${pct(k.min)}</td><td class="num">${pct(k.max)}</td><td class="num">${pct(k.w[v] ?? 0)}</td></tr>`).join('') + '</tbody>';
  $('#rubricTbl').innerHTML = `<thead><tr><th style="width:70px">التقييم</th><th>مقياس مؤشرات التقييم (١–٥)</th></tr></thead><tbody>` +
    S().rubric.map(r => `<tr><td class="num"><b>${r[0]}</b></td><td>${esc(r[1])}</td></tr>`).join('') + '</tbody>';
  $('#formula').innerHTML = `<b>المعادلة:</b> نسبة الإنجاز = الفعلي ÷ المستهدف (مؤشرات التقييم: التقييم ÷ ٥) · الدرجة المحتسبة = صفر إذا كانت نسبة الإنجاز أقل من الحد الأدنى، وإلا الأقل من (نسبة الإنجاز، الحد الأقصى) · الدرجة المرجحة = الدرجة × الوزن · النتيجة = مجموع الدرجات المرجحة، وسقفها ${pct(CAP)} عند وجود مخالفة جوهرية. المرجع: ${esc(S().ref)}`;
}

function refresh() {
  if (!S()) return;
  const c = calc();
  KPIS().forEach(k => {
    const r = c.res[k.code], row = $('#r-' + k.code);
    $('#w-' + k.code).textContent = pct(r.w);
    const tIn = $('#t-' + k.code); if (tIn) tIn.value = C.workDays;
    row.classList.toggle('na', !!r.na);
    row.classList.toggle('low', !!(r.done && r.low));
    const bar = $('#bar-' + k.code);
    if (r.done) {
      $('#ach-' + k.code).textContent = pct(r.ach); $('#sc-' + k.code).textContent = pct(r.score); $('#ws-' + k.code).textContent = pct(r.ws, 1);
      bar.style.width = Math.min(r.ach, 1.5) / 1.5 * 100 + '%'; bar.className = r.low ? 'low' : r.capped || r.ach > 1 ? 'over' : '';
    } else { $('#ach-' + k.code).textContent = '—'; $('#sc-' + k.code).textContent = '—'; $('#ws-' + k.code).textContent = '—'; bar.style.width = '0'; bar.className = ''; }
    $('#fl-' + k.code).innerHTML = r.na ? '<span class="flag na">لا ينطبق</span>' : r.err ? `<span class="flag bad">${r.err}</span>`
      : r.done && r.zero ? '<span class="flag zero">أقل من الحد الأدنى</span>' : r.done && r.low ? '<span class="flag bad">خطة تحسين</span>'
      : r.done && r.capped ? '<span class="flag cap">تجاوز الحد الأقصى</span>' : '';
  });
  $('#sGroups').innerHTML = Object.entries(S().groups).map(([gk, gn]) => {
    const [s, w] = c.g[gk]; const el = $('#gw-' + gk);
    if (el) el.textContent = `الوزن ${pct(w)} · المحقق ${c.done ? pct(s, 1) : '—'}`;
    return `<div class="kv"><span>${esc(gn)}</span><b>${c.done ? pct(s, 1) + ' من ' + pct(w) : '—'}</b></div>`;
  }).join('');
  $('#sDone').textContent = `${c.done} / ${c.need}`;
  $('#sWeight').textContent = pct(c.wSum); $('#sWeight').className = Math.abs(c.wSum - 1) < 1e-6 ? 'c-ok' : 'c-bad';
  $('#sRaw').textContent = pct(c.raw, 1); $('#sLow').textContent = c.low.length;
  $('#sGate').textContent = c.gateCount; $('#sGate').className = c.gateCount ? 'c-bad' : '';
  $('#finalScore').textContent = pct(c.final, 1);
  $('#finalMeter').style.width = c.final == null ? '0' : Math.min(c.final, 1) * 100 + '%';
  const b = c.final == null ? null : band(c.final);
  $('#ratingBadge').textContent = b ? b.name : '—'; $('#ratingBadge').className = 'badge ' + (b ? b.cls : 'b-draft');
  $('#finalMeter').style.background = b ? b.color : ''; $('#finalScore').style.color = b ? b.color : '';
  $('#statusText').textContent = !c.done ? 'أدخل بيانات المؤشرات' : !c.complete ? `غير مكتمل: ${c.need - c.done} مؤشر بدون بيانات`
    : c.gateCount ? `مخالفة جوهرية: النتيجة لا تتجاوز ${pct(CAP)}` : c.final < PLAN_LIMIT ? 'يستوجب خطة تحسين مكتوبة بمتابعة الموارد البشرية' : 'جاهز للاعتماد';
  $('#bandList').innerHTML = BANDS.map((x, i) => {
    const hi = i === 0 ? '' : ` – ${(BANDS[i - 1].min * 100 - 0.1).toFixed(1)}%`;
    return `<div class="kv ${b && b.name === x.name ? 'on' : ''}"><span><i class="dot" style="background:${x.color}"></i>${esc(x.name)}</span><b>${i === 0 ? '≥ ' + pct(x.min) : pct(x.min) + hi}</b></div>`;
  }).join('');
  const gm = $('#gateMsg'); gm.hidden = !c.gateCount;
  if (c.gateCount) gm.textContent = `تم تسجيل ${c.gateCount} مخالفة جوهرية: سقف النتيجة ${pct(CAP)} (النتيجة قبل المخالفات ${pct(c.raw, 1)}).`;
  renderPlan(c);
  $('#dept').value = S().variants[C.variant] ? S().variants[C.variant].dept : '';
  const u = BOOT.users.find(x => x.id === C.empId);
  $('#empHint').textContent = C.empName ? (u ? `مستخدم في النظام · سيوقّع بنفسه (${u.role || ''})` : 'ليس له حساب · يوقّع ورقياً') : '';
}

function renderPlan(c) {
  if (!c.low.length) { $('#planWrap').innerHTML = '<div class="info">لا توجد مؤشرات أقل من ٨٠٪.</div>'; return; }
  if (document.activeElement && document.activeElement.closest('#planWrap')) return;
  const dis = canEditMain() ? '' : 'disabled';
  $('#planWrap').innerHTML = `<table class="plan"><thead><tr><th style="width:70px">الكود</th><th style="width:190px">المؤشر</th><th style="width:80px">الدرجة</th><th>الإجراء المتفق عليه</th><th style="width:150px">المسؤول</th><th style="width:150px">الموعد</th></tr></thead><tbody>` +
    c.low.map(k => {
      const p = C.plan[k.code] || (C.plan[k.code] = { act: '', own: '', due: '' });
      return `<tr><td class="code">${k.code}</td><td>${esc(k.name)}</td><td class="num c-bad"><b>${pct(c.res[k.code].score)}</b></td>
        <td><input type="text" data-p="${k.code}" data-pf="act" value="${esc(p.act)}" ${dis}></td>
        <td><input type="text" data-p="${k.code}" data-pf="own" value="${esc(p.own)}" ${dis}></td>
        <td><input type="date" data-p="${k.code}" data-pf="due" value="${esc(p.due)}" ${dis}></td></tr>`;
    }).join('') + '</tbody></table>';
}

const STAGE = { draft: ['مسودة', 'b-draft'], mgr: ['بانتظار توقيع الموظف', 'b-warn'], emp: ['بانتظار الموارد البشرية', 'b-warn'] };

function applyState() {
  const final = C.status === 'final';
  const st = final ? 'final' : C.stage;
  $('#fsA').disabled = !canEditMain();
  $('#btnSave').hidden = !canEditMain();
  $('#btn-mgr').hidden = st !== 'draft' || !canEditMain();
  const isEmp = C.empId && C.empId === ME.id;
  $('#empComment').disabled = !(st === 'mgr' && isEmp);
  $('#btn-emp').hidden = !(st === 'mgr' && isEmp);
  $('#btn-paper').hidden = !(st === 'mgr' && !C.empId && (C.evaluatorId === ME.id || BOOT.can.finalize));
  $('#empWait').textContent = st === 'mgr' && C.empId && !isEmp ? `بانتظار توقيع ${C.empName} من حسابه.` : '';
  $('#btn-hr').hidden = !(st === 'emp' && BOOT.can.finalize);
  $('#btn-ret').hidden = !(st === 'emp' && BOOT.can.finalize);
  $('#btnReopen').hidden = !(final && BOOT.can.reopen);
  if (final) { const b = band(C.result ? C.result.final : 0); $('#badge').className = 'badge ' + (b ? b.cls : 'b-ok'); $('#badge').textContent = 'معتمد · ' + (b ? b.name : ''); }
  else { $('#badge').className = 'badge ' + STAGE[st][1]; $('#badge').textContent = STAGE[st][0]; }
  ['mgr', 'emp', 'hr'].forEach(w => {
    const s = C.sign[w];
    $('#sb-' + w).classList.toggle('done', !!s);
    $('#st-' + w).textContent = s ? 'تم التوقيع' : 'لم يوقع'; $('#st-' + w).className = 'badge ' + (s ? 'b-ok' : 'b-draft');
    $('#ss-' + w).hidden = !s; $('#ss-' + w).textContent = s ? `✓ ${s.by} · ${s.at}${s.paper ? ' · ورقياً' : ''}` : '';
  });
}

/* ---------- الأحداث ---------- */
function setEmployee(name) {
  C.empName = name.trim();
  const u = BOOT.users.find(x => x.name === C.empName);
  C.empId = u ? u.id : null;
  refresh();
}
document.addEventListener('input', e => {
  const el = e.target;
  if (el.dataset.k) { C.vals[el.dataset.k][el.dataset.f] = el.value; refresh(); return; }
  if (el.dataset.p) { C.plan[el.dataset.p][el.dataset.pf] = el.value; return; }
  if (el.id === 'empPick') { setEmployee(el.value); return; }
  const map = { empCode: 'empCode', period: 'period', workDays: 'workDays', gateRef: 'gateRef', mgrComment: 'mgrComment', empComment: 'empComment', no: 'no' };
  if (map[el.id]) { C[map[el.id]] = el.value; if (el.id === 'no') C.autoNo = false; if (el.id === 'workDays') refresh(); if (el.id === 'period') $('#phSet').textContent = S().code + ' · ' + S().title + ' · ' + C.period; }
});
document.addEventListener('change', e => {
  const el = e.target;
  if (el.dataset.k) { C.vals[el.dataset.k][el.dataset.f] = el.value; refresh(); return; }
  if (el.dataset.gate !== undefined) { const i = +el.dataset.gate; C.gates = el.checked ? [...new Set([...C.gates, i])] : C.gates.filter(x => x !== i); refresh(); return; }
  if (el.id === 'job') { C.job = el.value; C.variant = Object.keys(S().variants)[0]; C.vals = {}; C.gates = []; C.plan = {}; render(); return; }
  if (el.name === 'variant') { C.variant = el.value; buildTable(); buildRefs(); refresh(); }
});

function vMgr() {
  const c = calc(), e = [];
  if (!C.empName) e.push('اسم الموظف');
  if (C.empId && C.empId === ME.id) e.push('لا يمكنك تقييم نفسك');
  if (!C.period) e.push('شهر التقييم');
  if (!C.workDays) e.push('أيام العمل في الشهر');
  if (Math.abs(c.wSum - 1) > 1e-6) e.push('إجمالي الأوزان لا يساوي ١٠٠٪');
  if (!c.complete) KPIS().filter(k => { const r = c.res[k.code]; return !r.na && !r.done; }).forEach(k => e.push(`بيانات المؤشر ${k.code} ${k.name}`));
  if (c.gateCount && !C.gateRef.trim()) e.push('مستند أو تفاصيل المخالفة الجوهرية');
  c.low.forEach(k => { const p = C.plan[k.code] || {}; if (!p.act || !p.own || !p.due) e.push(`خطة التحسين للمؤشر ${k.code}: الإجراء والمسؤول والموعد`); });
  if ((c.final < PLAN_LIMIT || c.gateCount) && !C.mgrComment.trim()) e.push('تعليق المدير (مطلوب عند ضعف الأداء أو وجود مخالفة)');
  const dup = LIST.find(m => m.id !== C.id && m.title === title());
  if (dup) e.push(`يوجد تقييم لنفس الموظف ونفس الشهر: ${dup.number}`);
  return e;
}
function showMsg(errs, ok) {
  $('#msg').innerHTML = errs && errs.length ? `<div class="errors"><b>لا يمكن الحفظ أو الاعتماد:</b><ul>${errs.map(x => `<li>${esc(x)}</li>`).join('')}</ul></div>`
    : ok ? `<div class="okmsg">${esc(ok)}</div>` : '';
}
const title = () => `${C.empName} · ${S() ? S().title : ''} · ${C.period}`;

async function persist(status) {
  const c = calc();
  C.result = c.final == null ? null : { raw: c.raw, final: c.final, rating: band(c.final).name, low: c.low.map(k => k.code), gates: c.gateCount };
  const body = { number: C.no.trim(), title: title(), record_date: C.period ? C.period + '-01' : today(), status,
    data: Object.assign({}, C, { status, setCode: S().code, jobTitle: S().title }) };
  const res = C.id ? await api('PUT', `${API}/${C.id}`, body) : await api('POST', API, body);
  loadRecord(res.record);
  await refreshList();
}
function loadRecord(rec) {
  C = Object.assign(blankEval(), rec.data || {});
  C.id = rec.id; C.no = rec.number; C.status = rec.status; C.autoNo = false;
  if (C.status !== 'final' && C.stage === 'final') { C.stage = 'emp'; C.sign = Object.assign({}, C.sign); delete C.sign.hr; }
  LOGS = rec.logs || [];
  render();
}
async function move(stage, signKey, extra, status, okText) {
  const snap = JSON.stringify(C);
  C.stage = stage;
  if (stage === 'draft') C.sign = {};      // إعادة للمدير: تُلغى التوقيعات
  if (signKey) C.sign = Object.assign({}, C.sign, { [signKey]: Object.assign({ by: ME.name, at: nowStr() }, extra || {}) });
  busy(true);
  try { await persist(status); showMsg(null, okText); }
  catch (err) { C = JSON.parse(snap); showMsg(err); }
  finally { busy(false); }
}

document.addEventListener('click', async e => {
  const b = e.target.closest('[data-act]'); if (!b) return;
  const a = b.dataset.act;
  if (a === 'save') {
    if (!C.empName) return showMsg(['اسم الموظف']);
    busy(true); try { await persist('draft'); showMsg(null, `تم حفظ التقييم ${C.no}`); } catch (err) { showMsg(err); } finally { busy(false); }
  }
  if (a === 'sign-mgr') { const err = vMgr(); if (err.length) return showMsg(err); move('mgr', 'mgr', null, 'draft', C.empId ? 'تم اعتماد التقييم. يظهر للموظف ليوقع بالعلم من حسابه.' : 'تم اعتماد التقييم. اطبعه ليوقع الموظف ورقياً، ثم سجل التوقيع.'); }
  if (a === 'sign-emp') move('emp', 'emp', { by: ME.name }, 'draft', 'تم توقيعك بالعلم. التقييم الآن لدى الموارد البشرية.');
  if (a === 'sign-paper') move('emp', 'emp', { by: C.empName, paper: true, recordedBy: ME.name }, 'draft', 'تم تسجيل توقيع الموظف الورقي. التقييم الآن لدى الموارد البشرية.');
  if (a === 'sign-hr') move('final', 'hr', null, 'final', 'تم اعتماد التقييم وإغلاقه.');
  if (a === 'return') move('draft', null, null, 'draft', 'أُعيد التقييم للمدير المباشر للتعديل.');
  if (a === 'reopen') { busy(true); try { const res = await api('POST', `${API}/${C.id}/reopen`); loadRecord(res.record); await refreshList(); showMsg(null, 'أُعيد فتح التقييم.'); } catch (err) { showMsg(err); } finally { busy(false); } }
  if (a === 'new') { C = blankEval(); LOGS = []; render(); showMsg(); window.scrollTo(0, 0); }
  if (a === 'open') { busy(true); try { const res = await api('GET', `${API}/${b.dataset.id}`); loadRecord(res.record); showMsg(); window.scrollTo(0, 0); } catch (err) { showMsg(err); } finally { busy(false); } }
  if (a === 'del') {
    const id = Number(b.dataset.id);
    if (pendingDelete !== id) { pendingDelete = id; renderSaved(); return; }
    pendingDelete = null; busy(true);
    try { await api('DELETE', `${API}/${id}`); if (C.id === id) { C = blankEval(); LOGS = []; } await refreshList(); render(); showMsg(null, 'تم حذف المسودة.'); }
    catch (err) { showMsg(err); renderSaved(); } finally { busy(false); }
  }
  if (a === 'cancel-del') { pendingDelete = null; renderSaved(); }
});

async function refreshList() { try { LIST = (await api('GET', API)).records || []; } catch (e) { LIST = []; } renderSaved(); }
function renderSaved() {
  $('#saved').innerHTML = LIST.length ? LIST.map(m => `
    <div class="row ${C && m.id === C.id ? 'cur' : ''}">
      <div><b class="ltr">${esc(m.number)}</b> <span class="badge ${m.status === 'final' ? 'b-ok' : 'b-draft'}" style="padding:1px 8px;font-size:11px">${m.status === 'final' ? 'معتمد' : 'جارٍ'}</span><div class="small">${esc(m.title || '')}</div></div>
      <div class="actions" style="gap:4px">
        ${pendingDelete === m.id
          ? `<button type="button" class="btn small" data-act="del" data-id="${m.id}">تأكيد</button><button type="button" class="btn ghost small" data-act="cancel-del">إلغاء</button>`
          : `<button type="button" class="btn ghost small" data-act="open" data-id="${m.id}">فتح</button>${m.status !== 'final' ? `<button type="button" class="x" data-act="del" data-id="${m.id}" title="حذف">×</button>` : ''}`}
      </div>
    </div>`).join('') : '<div class="small">لا توجد تقييمات.</div>';
}
function renderAudit() {
  $('#audit').innerHTML = LOGS.length
    ? LOGS.map(x => `<tr><td class="t">${esc(x.t)}</td><td>${esc(x.user)}</td><td><b>${esc(x.action)}</b></td><td>${esc(x.detail || '')}</td></tr>`).join('')
    : '<tr><td class="small" style="padding:8px">لم يُحفظ التقييم بعد.</td></tr>';
}

/* ---------- البدء ---------- */
(async function () {
  try {
    const r = await api('GET', BOOT.urls.sets);
    SETS = r.sets; BANDS = r.bands; CAP = r.cap; LOW_FLAG = r.low; PLAN_LIMIT = r.plan;
  } catch (e) { showMsg(['تعذر تحميل مجموعات المؤشرات']); return; }
  $('#job').innerHTML = Object.entries(SETS).map(([k, s]) => `<option value="${k}">${esc(s.title)} · ${s.code}</option>`).join('');
  $('#empList').innerHTML = BOOT.users.filter(u => u.id !== ME.id).map(u => `<option value="${esc(u.name)}">${esc(u.role || '')}</option>`).join('');
  $('#phTenant').textContent = BOOT.tenant.name_ar;
  if (BOOT.tenant.logo) { $('#phLogo').src = BOOT.tenant.logo; $('#phLogo').hidden = false; }
  $('#regLink').href = BOOT.urls.register || '#';
  await refreshList();
  C = blankEval();
  const q = new URLSearchParams(location.search);
  const open = q.get('open');
  if (open) { const m = LIST.find(x => x.number === open); if (m) { try { const res = await api('GET', `${API}/${m.id}`); loadRecord(res.record); return; } catch (e) {} } }
  if (q.get('job') && SETS[q.get('job')]) { C.job = q.get('job'); C.variant = Object.keys(S().variants)[0]; }
  render();
})();
@endverbatim
</script>
@endpush