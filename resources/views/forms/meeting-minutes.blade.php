@extends('layouts.app')

@section('title', 'محاضر الاجتماعات')

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
  .step{display:inline-flex;align-items:center;justify-content:center;width:28px;height:28px;border-radius:50%;background:var(--brand-soft);color:var(--brand);font-size:14px;font-weight:700}
  .sub{font-size:13px;color:var(--muted);margin:-8px 0 12px}
  .grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:14px}
  label.f{display:block;font-size:13px;color:var(--muted);margin-bottom:4px;font-weight:600}
  .req::after{content:" *";color:var(--bad)}
  input[type=text],input[type=date],input[type=time],select,textarea{width:100%;padding:7px 9px;border:1px solid var(--line);border-radius:8px;font:inherit;background:#fff;color:var(--ink)}
  input[type=date],input[type=time],.ltr-in{direction:ltr;text-align:right}
  input[readonly]{background:#f0f3f2;color:var(--muted)}
  input:focus,select:focus,textarea:focus{outline:2px solid var(--brand-soft);border-color:var(--brand)}
  textarea{min-height:60px;resize:vertical}
  fieldset:disabled input,fieldset:disabled select,fieldset:disabled textarea{background:#f6f8f7;color:#44524f}

  .btn{background:var(--brand);color:#fff;border:1px solid var(--brand);border-radius:8px;padding:8px 18px;font:inherit;font-weight:600;cursor:pointer}
  .btn.ghost{background:#fff;color:var(--brand)}
  .btn.gold{background:var(--gold);border-color:var(--gold)}
  .btn.danger{background:#fff;color:var(--bad);border-color:#e3b0ac}
  .btn.small{padding:3px 10px;font-size:13px}
  .btn:disabled{opacity:.5;cursor:not-allowed}
  .actions{display:flex;gap:10px;flex-wrap:wrap;align-items:center}
  .x{background:none;border:0;color:var(--bad);font-size:20px;line-height:1;cursor:pointer;padding:2px 6px}
  fieldset:disabled .x,fieldset:disabled .add{display:none}

  table.t{width:100%;border-collapse:collapse;font-size:14px}
  table.t th{background:#fafbfb;color:var(--muted);font-weight:600;padding:8px 6px;text-align:start;border-bottom:1px solid var(--line);white-space:nowrap;font-size:13px}
  table.t td{padding:6px 5px;border-bottom:1px solid var(--line);vertical-align:top}
  table.t td.n{width:36px;text-align:center;color:var(--muted);font-weight:700;padding-top:12px}
  .tbl-wrap{overflow-x:auto}

  .agenda-item{border:1px solid var(--line);border-radius:10px;padding:10px 12px;margin-bottom:10px;background:#fbfcfc}
  .agenda-item .head{display:flex;gap:8px;align-items:center}
  .agenda-item .num{flex-shrink:0;width:28px;height:28px;border-radius:50%;background:var(--brand);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:14px}
  .agenda-item textarea{margin-top:8px;min-height:50px}

  .tpl{background:#fdfcf8;border:1px solid #eadcb9;border-radius:10px;padding:16px 18px;line-height:2.1;font-size:15px}
  .tpl b{color:var(--brand)}
  .tpl .blank{color:var(--bad);font-weight:600}

  .errors{background:var(--bad-soft);color:var(--bad);border-radius:8px;padding:10px 12px;font-size:13px;margin-top:12px}
  .errors ul{margin:6px 0 0;padding-inline-start:18px}
  .okmsg{background:var(--ok-soft);color:var(--ok);border-radius:8px;padding:8px 12px;font-size:13px;margin-top:12px;font-weight:600}
  .info{background:var(--brand-soft);border:1px solid #c6dfdb;color:#24514d;border-radius:var(--radius);padding:10px 14px;font-size:13px}

  aside{position:sticky;top:16px}
  @media (max-width:1050px){aside{position:static}}
  .kv{display:flex;justify-content:space-between;gap:8px;font-size:14px;padding:5px 0;border-bottom:1px dashed var(--line)}
  .kv:last-child{border-bottom:0}
  .kv b{font-weight:700}
  .small{font-size:13px;color:var(--muted)}
  .saved{max-height:340px;overflow:auto}
  .saved .row{display:flex;justify-content:space-between;align-items:center;gap:6px;padding:7px 4px;border-bottom:1px solid var(--line);font-size:13px}
  .saved .row b{direction:ltr;display:inline-block}
  .saved .row.cur{background:var(--brand-soft);border-radius:6px}
  table.audit{width:100%;border-collapse:collapse;font-size:12.5px}
  table.audit td{padding:5px 6px;border-bottom:1px solid var(--line);vertical-align:top}
  td.tm{direction:ltr;text-align:right;white-space:nowrap;color:var(--muted)}
  .audit-wrap{max-height:220px;overflow:auto}

  /* ===== نسخة الطباعة ===== */
  #doc{display:none}
  @page{size:A4;margin:14mm 14mm 16mm}
  @media print{
    body{background:#fff;font-size:12.5px}
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
  .d-code{font-size:11px;text-align:left;direction:rtl;line-height:1.7}
  .d-code b{direction:ltr;display:inline-block}
  .d-h2{font-size:14px;font-weight:700;color:var(--brand);border-bottom:1.5px solid var(--brand);padding-bottom:2px;margin:14px 0 6px}
  table.d{width:100%;border-collapse:collapse;font-size:12px}
  table.d th,table.d td{border:1px solid #9fb3b0;padding:5px 7px;text-align:start;vertical-align:top}
  table.d th{background:#e3f0ee;color:var(--brand);font-weight:700}
  table.d td.k{background:#f2f7f6;font-weight:700;color:#24514d;width:18%}
  .d-p{line-height:2;text-align:justify;font-size:13px}
  .d-ol{margin:4px 0;padding-inline-start:22px}
  .d-ol li{margin-bottom:2px}
  .d-signs{display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-top:22px;break-inside:avoid}
  .d-signs div{border-top:1px solid #555;padding-top:6px;text-align:center;font-size:12px}
  .d-foot{margin-top:16px;border-top:1px solid #9fb3b0;padding-top:4px;font-size:10px;color:var(--muted);text-align:center}
  .keep{break-inside:avoid}
  .dt{direction:ltr;unicode-bidi:isolate;white-space:nowrap;display:inline-block}
@endverbatim
</style>
@endpush

@section('content')
@verbatim
<div class="scr-head">
  <h1>محضر اجتماع</h1>
  <span class="code">SH-034-F02 · v01</span>
  <span id="badge" class="badge b-draft">مسودة</span>
</div>
<div class="scr">
<div class="scr-main">
<fieldset id="fs">

  <!-- ١. بيانات الاجتماع -->
  <section class="card">
    <h2><span class="step">١</span> بيانات الاجتماع</h2>
    <div class="grid">
      <div><label class="f req" for="no">رقم المحضر</label><input type="text" id="no" class="ltr-in" autocomplete="off"></div>
      <div><label class="f req" for="type">نوع الاجتماع</label>
        <select id="type">
          <option>اجتماع الإدارة العليا</option><option>اجتماع الجودة</option><option>اجتماع العمليات</option>
          <option>اجتماع قسم</option><option>اجتماع طارئ</option><option>اجتماع مجلس الإدارة</option>
        </select></div>
      <div><label class="f req" for="date">التاريخ</label><input type="date" id="date"></div>
      <div><label class="f" for="day">اليوم</label><input type="text" id="day" readonly></div>
      <div><label class="f req" for="tStart">وقت البدء</label><input type="time" id="tStart"></div>
      <div><label class="f" for="tEnd">وقت إقفال المحضر</label><input type="time" id="tEnd"></div>
      <div><label class="f req" for="place">مكان الانعقاد</label><input type="text" id="place" value="مقر الشركة"></div>
      <div><label class="f" for="dept">القسم (لاجتماع القسم)</label><input type="text" id="dept"></div>
    </div>
  </section>

  <!-- ٢. الحضور -->
  <section class="card">
    <h2><span class="step">٢</span> الحضور <span style="flex:1"></span><button type="button" class="btn ghost small add" data-act="add-att">+ إضافة عضو</button></h2>
    <p class="sub">اختر الوظيفة من القائمة واكتب الاسم. أول عضو حاضر يمكن اختياره رئيساً للاجتماع.</p>
    <div class="tbl-wrap">
      <table class="t">
        <thead><tr><th>م</th><th style="min-width:200px">الوظيفة</th><th style="min-width:180px">الاسم</th><th>الحضور</th><th></th></tr></thead>
        <tbody id="attRows"></tbody>
      </table>
    </div>
    <div class="grid" style="margin-top:14px">
      <div><label class="f req" for="chair">رئيس الاجتماع</label><select id="chair"></select></div>
      <div><label class="f req" for="secretary">أمين الاجتماع (كاتب المحضر)</label><select id="secretary"></select></div>
      <div><label class="f">النصاب</label><input type="text" id="quorum" readonly></div>
    </div>
  </section>

  <!-- ٣. جدول الأعمال -->
  <section class="card">
    <h2><span class="step">٣</span> جدول الأعمال والمناقشات <span style="flex:1"></span><button type="button" class="btn ghost small add" data-act="add-ag">+ إضافة بند</button></h2>
    <p class="sub">اكتب كل بند، ثم ملخص ما نوقش فيه.</p>
    <div id="agRows"></div>
  </section>

  <!-- ٤. القرارات -->
  <section class="card">
    <h2><span class="step">٤</span> القرارات <span style="flex:1"></span><button type="button" class="btn ghost small add" data-act="add-dec">+ إضافة قرار</button></h2>
    <p class="sub">كل قرار يجب أن يكون محدداً وقابلاً للقياس، وله مسؤول واحد وموعد تنفيذ.</p>
    <div class="tbl-wrap">
      <table class="t">
        <thead><tr><th>م</th><th style="min-width:280px">القرار</th><th style="min-width:170px">المسؤول</th><th>الموعد</th><th style="min-width:110px">البند</th><th></th></tr></thead>
        <tbody id="decRows"></tbody>
      </table>
    </div>
    <div id="devLinks"></div>
  </section>

  <!-- ٥. نص المحضر -->
  <section class="card">
    <h2><span class="step">٥</span> نص المحضر</h2>
    <p class="sub">يُكتب تلقائياً من البيانات أعلاه. ما يظهر باللون الأحمر لم يُستكمل بعد.</p>
    <div class="tpl" id="tpl"></div>
    <div style="margin-top:12px"><label class="f" for="notes">ملاحظات إضافية تُضاف للمحضر</label><textarea id="notes"></textarea></div>
    <div class="grid" style="margin-top:12px">
      <div><label class="f" for="nDate">موعد الاجتماع القادم</label><input type="date" id="nDate"></div>
      <div><label class="f" for="nTime">الوقت</label><input type="time" id="nTime"></div>
      <div><label class="f" for="nPlace">المكان</label><input type="text" id="nPlace"></div>
    </div>
  </section>

</fieldset>

  <section class="card">
    <div class="actions">
      <button type="button" class="btn" data-act="save">حفظ</button>
      <button type="button" class="btn ok" data-act="final" id="btnFinal" style="background:var(--ok);border-color:var(--ok)">اعتماد وإقفال المحضر</button>
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
    <div class="kv"><span>رقم المحضر</span><b id="sNo" style="direction:ltr">—</b></div>
    <div class="kv"><span>الحضور</span><b id="sAtt">—</b></div>
    <div class="kv"><span>بنود جدول الأعمال</span><b id="sAg">0</b></div>
    <div class="kv"><span>القرارات</span><b id="sDec">0</b></div>
    <div class="kv"><span>الحالة</span><b id="sSt">مسودة</b></div>
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
const FORM_CODE = BOOT.code;
const API = BOOT.urls.records;
const ROLES = BOOT.roles.length ? BOOT.roles : [
  'رئيس مجلس الإدارة', 'الرئيس التنفيذي', 'العضو المنتدب / المدير العام', 'المدير التجاري', 'المدير الصيدلي',
  'مدير إدارة الجودة', 'مدير الموارد البشرية', 'مدير المخزن', 'مدير الحسابات', 'مدير بيع الجملة',
  'مشرف بيع القطاعي', 'مسؤول المشتريات', 'مشرف التوزيع', 'مشرف الحركة', 'أمين مخزن الجملة',
  'أمين مخزن القطاعي', 'أمين مخزن الاستلامات', 'مشرف التحضير', 'مسؤول الموارد البشرية', 'مسؤول الحاسب',
  'مسؤول السلامة', 'أخرى'
];
const DAYS = ['الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
const TYPE_CODE = { 'اجتماع الإدارة العليا': 'MGT', 'اجتماع الجودة': 'QA', 'اجتماع العمليات': 'OPS', 'اجتماع قسم': 'DEP', 'اجتماع طارئ': 'EMG', 'اجتماع مجلس الإدارة': 'BRD' };

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
const members = n => n === 1 ? 'عضو واحد' : n === 2 ? 'عضوان' : (n >= 3 && n <= 10) ? `${n} أعضاء` : `${n} عضواً`;
function fmtTime(t) {
  if (!t) return '';
  let [h, m] = t.split(':').map(Number);
  const s = h < 12 ? 'ص' : 'م';
  h = h % 12 || 12;
  return `${h}:${pad(m)} ${s}`;
}
const uid = () => Math.random().toString(36).slice(2, 10);
const blank = t => `<span class="blank">${t}</span>`;
const personLabel = a => a ? (a.name ? `${a.role === 'أخرى' ? (a.other || 'أخرى') : a.role} / ${a.name}` : (a.role === 'أخرى' ? (a.other || 'أخرى') : a.role)) : '';
const roleOf = a => a.role === 'أخرى' ? (a.other || 'أخرى') : a.role;

/* =========================================================
   الخادم (قاعدة البيانات)
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
let M = null;          // المحضر الحالي
let pendingDelete = null;

function nextNo(type, date) {
  const y = (date || today()).slice(0, 4);
  const code = TYPE_CODE[type] || 'MTG';
  const prefix = `${code}-${y}-`;
  const nums = LIST.filter(m => m.number && m.number.startsWith(prefix)).map(m => parseInt(m.number.slice(prefix.length), 10) || 0);
  return prefix + pad((nums.length ? Math.max(...nums) : 0) + 1, 3);
}

function blankMinutes() {
  const type = 'اجتماع الإدارة العليا';
  return {
    id: null, no: nextNo(type, today()), autoNo: true, type, date: today(), tStart: nowTime(), tEnd: '', place: 'مقر الشركة', dept: '',
    attendees: [{ id: uid(), role: ROLES.includes('الرئيس التنفيذي') ? 'الرئيس التنفيذي' : ROLES[0], other: '', name: '', status: 'present' }],
    chair: '', secretary: '',
    agenda: [{ id: uid(), text: 'متابعة قرارات الاجتماع السابق', disc: '' }],
    decisions: [],
    notes: '', nDate: '', nTime: '', nPlace: '',
    status: 'draft'
  };
}

/* =========================================================
   الرسم
   ========================================================= */
function render() {
  const locked = M.status === 'final';
  $('#fs').disabled = locked;
  $('#badge').className = 'badge ' + (locked ? 'b-ok' : 'b-draft');
  $('#badge').textContent = locked ? 'معتمد' : 'مسودة';
  $('#btnFinal').hidden = locked;
  $('#btnReopen').hidden = !locked || !BOOT.can.reopen;
  document.querySelector('[data-act=save]').hidden = locked;

  ['no', 'type', 'date', 'tStart', 'tEnd', 'place', 'dept', 'notes', 'nDate', 'nTime', 'nPlace'].forEach(k => { $('#' + k).value = M[k] ?? ''; });
  $('#day').value = dayName(M.date);

  renderAtt();
  renderAgenda();
  renderDec();
  refresh();
  renderSaved();
  renderAudit();
  DevLinks.refresh();
}

function roleOptions(sel) {
  return ROLES.map(r => `<option ${r === sel ? 'selected' : ''}>${r}</option>`).join('');
}

function renderAtt() {
  $('#attRows').innerHTML = M.attendees.map((a, i) => `
    <tr data-id="${a.id}">
      <td class="n">${i + 1}</td>
      <td><select data-f="role">${roleOptions(a.role)}</select>
        <input type="text" data-f="other" placeholder="اكتب الوظيفة" value="${esc(a.other)}" style="margin-top:6px" ${a.role === 'أخرى' ? '' : 'hidden'}></td>
      <td><input type="text" data-f="name" value="${esc(a.name)}" placeholder="الاسم" list="peopleList"></td>
      <td><select data-f="status"><option value="present" ${a.status === 'present' ? 'selected' : ''}>حاضر</option><option value="apology" ${a.status === 'apology' ? 'selected' : ''}>معتذر</option><option value="absent" ${a.status === 'absent' ? 'selected' : ''}>غائب</option></select></td>
      <td><button type="button" class="x" data-act="del-att" title="حذف">×</button></td>
    </tr>`).join('');
}

function peopleOptions(sel, onlyPresent) {
  const list = M.attendees.filter(a => !onlyPresent || a.status === 'present');
  return `<option value="">— اختر —</option>` + list.map(a => `<option value="${a.id}" ${a.id === sel ? 'selected' : ''}>${esc(personLabel(a))}</option>`).join('');
}

function renderAgenda() {
  $('#agRows').innerHTML = M.agenda.map((g, i) => `
    <div class="agenda-item" data-id="${g.id}">
      <div class="head"><span class="num">${i + 1}</span>
        <input type="text" data-f="text" value="${esc(g.text)}" placeholder="عنوان البند">
        <button type="button" class="x" data-act="del-ag" title="حذف">×</button></div>
      <textarea data-f="disc" placeholder="ملخص المناقشة">${esc(g.disc)}</textarea>
    </div>`).join('') || '<div class="small">لا توجد بنود. أضف بنداً.</div>';
}

function renderDec() {
  $('#decRows').innerHTML = M.decisions.map((d, i) => `
    <tr data-id="${d.id}">
      <td class="n">${i + 1}</td>
      <td><textarea data-f="text" placeholder="القرار" style="min-height:44px">${esc(d.text)}</textarea></td>
      <td><select data-f="owner">${peopleOptions(d.owner, false)}</select></td>
      <td><input type="date" data-f="due" value="${esc(d.due)}"></td>
      <td><select data-f="item"><option value="">—</option>${M.agenda.map((g, j) => `<option value="${g.id}" ${g.id === d.item ? 'selected' : ''}>بند ${j + 1}</option>`).join('')}</select></td>
      <td><button type="button" class="x" data-act="del-dec" title="حذف">×</button></td>
    </tr>`).join('') || `<tr><td colspan="6" class="small" style="text-align:center;padding:14px">لا توجد قرارات بعد.</td></tr>`;
}

function quorum() {
  const total = M.attendees.length;
  const present = M.attendees.filter(a => a.status === 'present').length;
  const ok = total > 0 && present > total / 2 && M.chair && M.attendees.some(a => a.id === M.chair && a.status === 'present');
  return { total, present, ok };
}

function refresh() {
  // قوائم الرئيس والأمين
  if (M.chair && !M.attendees.some(a => a.id === M.chair && a.status === 'present')) M.chair = '';
  if (M.secretary && !M.attendees.some(a => a.id === M.secretary)) M.secretary = '';
  $('#chair').innerHTML = peopleOptions(M.chair, true);
  $('#secretary').innerHTML = peopleOptions(M.secretary, false);

  const q = quorum();
  $('#quorum').value = `حضر ${q.present} من ${q.total} · ${q.ok ? 'مكتمل' : 'غير مكتمل'}`;
  $('#quorum').style.color = q.ok ? 'var(--ok)' : 'var(--bad)';

  $('#sNo').textContent = M.no || '—';
  $('#sAtt').textContent = `${q.present} / ${q.total}`;
  $('#sAg').textContent = M.agenda.filter(g => g.text.trim()).length;
  $('#sDec').textContent = M.decisions.filter(d => d.text.trim()).length;
  $('#sSt').textContent = M.status === 'final' ? 'معتمد' : 'مسودة';

  $('#tpl').innerHTML = templateHtml(true);
}

/* نص المحضر بنفس صيغة النموذج الورقي */
function templateHtml(screen) {
  const B = (v, t) => v ? `<b>${esc(v)}</b>` : (screen ? blank(t) : '……………');
  const chair = M.attendees.find(a => a.id === M.chair);
  const present = M.attendees.filter(a => a.status === 'present' && a.id !== M.chair);
  const apol = M.attendees.filter(a => a.status !== 'present');
  const q = quorum();
  const month = M.date ? new Date(M.date + 'T00:00:00').toLocaleDateString('ar-EG', { month: 'long' }) : '';
  const year = M.date ? M.date.slice(0, 4) : '';
  const decs = M.decisions.filter(d => d.text.trim());

  let h = `في يوم ${B(dayName(M.date), 'اليوم')} الموافق ${M.date ? '<b>' + fmtDate(M.date) + '</b>' : (screen ? blank('التاريخ') : '……………')} من شهر ${B(month, 'الشهر')} لسنة ${B(year, 'السنة')}، في تمام الساعة ${B(fmtTime(M.tStart), 'الوقت')}، `;
  h += `انعقد ${B(M.type + (M.type === 'اجتماع قسم' && M.dept ? ' – ' + M.dept : ''), 'نوع الاجتماع')} رقم ${B(M.no, 'رقم المحضر')} بـ${B(M.place, 'المكان')}، `;
  h += `برئاسة السيد / ${B(chair ? personLabel(chair) : '', 'رئيس الاجتماع')}، وبحضور كل من السادة:`;
  h += present.length ? `<ol class="d-ol">${present.map(a => `<li>${esc(personLabel(a))}</li>`).join('')}</ol>` : ` ${screen ? blank('لم يُحدد الحاضرون') : '……………'}<br>`;
  h += apol.length ? `وتغيب عن الحضور: ${apol.map(a => esc(personLabel(a)) + (a.status === 'apology' ? ' (معتذر)' : '')).join('، ')}.<br>` : 'لم يتغيب أحد عن الحضور.<br>';
  h += `فقد حضر <b>${members(q.present)}</b> من إجمالي <b>${q.total}</b>، لذا ${q.ok ? 'كان الانعقاد صحيحاً' : (screen ? blank('لم يكتمل النصاب') : 'لم يكتمل النصاب')}، وبدأ النظر في جدول الأعمال والمناقشات، وتم اتخاذ القرارات التالية:`;
  h += decs.length ? `<ol class="d-ol">${decs.map(d => {
    const o = M.attendees.find(a => a.id === d.owner);
    return `<li>${esc(d.text)}${o ? ` — المسؤول: ${esc(personLabel(o))}` : ''}${d.due ? ` — الموعد: ${fmtDate(d.due)}` : ''}</li>`;
  }).join('')}</ol>` : ` ${screen ? blank('لا توجد قرارات') : '—'}<br>`;
  if (M.notes.trim()) h += `${esc(M.notes).replace(/\n/g, '<br>')}<br>`;
  h += `وأُقفل المحضر في تمام الساعة ${B(fmtTime(M.tEnd), 'وقت الإقفال')}.`;
  return h;
}

/* =========================================================
   الأحداث
   ========================================================= */
function bindTop() {
  ['no', 'place', 'dept', 'notes', 'nPlace'].forEach(k => $('#' + k).addEventListener('input', e => {
    M[k] = e.target.value; if (k === 'no') M.autoNo = false; refresh();
  }));
  ['tStart', 'tEnd', 'nDate', 'nTime'].forEach(k => $('#' + k).addEventListener('input', e => { M[k] = e.target.value; refresh(); }));
  $('#date').addEventListener('input', e => {
    M.date = e.target.value; $('#day').value = dayName(M.date);
    if (M.autoNo) { M.no = nextNo(M.type, M.date); $('#no').value = M.no; }
    refresh();
  });
  $('#type').addEventListener('change', e => {
    M.type = e.target.value;
    if (M.autoNo) { M.no = nextNo(M.type, M.date); $('#no').value = M.no; }
    refresh();
  });
  $('#chair').addEventListener('change', e => { M.chair = e.target.value; refresh(); });
  $('#secretary').addEventListener('change', e => { M.secretary = e.target.value; refresh(); });

  // جداول قابلة للتكرار
  const rowEdit = (sel, arr, after) => $(sel).addEventListener('input', e => {
    const host = e.target.closest('[data-id]'); const f = e.target.dataset.f;
    if (!host || !f) return;
    const it = M[arr].find(x => x.id === host.dataset.id); if (!it) return;
    it[f] = e.target.value;
    if (after) after(it, f, host);
    refresh();
  });
  rowEdit('#attRows', 'attendees', (it, f, host) => {
    if (f === 'role') { host.querySelector('[data-f=other]').hidden = it.role !== 'أخرى'; }
    if (['role', 'name', 'other', 'status'].includes(f)) renderDecOwners();
  });
  rowEdit('#agRows', 'agenda', () => {});
  rowEdit('#decRows', 'decisions', () => {});
}

function renderDecOwners() {
  document.querySelectorAll('#decRows tr[data-id]').forEach(tr => {
    const d = M.decisions.find(x => x.id === tr.dataset.id);
    const s = tr.querySelector('[data-f=owner]'); if (d && s) s.innerHTML = peopleOptions(d.owner, false);
  });
}


function validate(final) {
  const e = [];
  if (!M.no.trim()) e.push('رقم المحضر مطلوب');
  if (LIST.some(m => m.number === M.no && m.id !== M.id)) e.push(`رقم المحضر ${M.no} مستخدم في محضر آخر`);
  if (!M.date) e.push('التاريخ مطلوب');
  if (!M.place.trim()) e.push('مكان الانعقاد مطلوب');
  if (!final) return e;
  if (!M.tStart) e.push('وقت البدء مطلوب');
  if (!M.tEnd) e.push('وقت إقفال المحضر مطلوب');
  if (!M.attendees.length) e.push('أضف الحضور');
  M.attendees.forEach((a, i) => { if (a.role === 'أخرى' && !a.other.trim()) e.push(`اكتب وظيفة العضو رقم ${i + 1}`); });
  if (!M.chair) e.push('اختر رئيس الاجتماع (من الحاضرين)');
  if (!M.secretary) e.push('اختر أمين الاجتماع');
  if (!quorum().ok) e.push('النصاب غير مكتمل: يجب حضور أكثر من نصف الأعضاء ورئيس الاجتماع');
  if (!M.agenda.some(g => g.text.trim())) e.push('أضف بنداً واحداً على الأقل في جدول الأعمال');
  M.decisions.forEach((d, i) => {
    if (!d.text.trim()) e.push(`القرار رقم ${i + 1} فارغ: اكتبه أو احذفه`);
    if (!d.owner) e.push(`حدد المسؤول عن القرار رقم ${i + 1}`);
    if (!d.due) e.push(`حدد موعد تنفيذ القرار رقم ${i + 1}`);
  });
  return e;
}

function showMsg(errs, ok) {
  $('#msg').innerHTML = errs && errs.length ? `<div class="errors">لم يتم الحفظ:<ul>${errs.map(x => `<li>${esc(x)}</li>`).join('')}</ul></div>`
    : ok ? `<div class="okmsg">${esc(ok)}</div>` : '';
}

function recordTitle() {
  return M.type + (M.type === 'اجتماع قسم' && M.dept ? ' – ' + M.dept : '');
}

/* حفظ على الخادم · status: draft أو final */
async function persist(status) {
  const body = { number: M.no.trim(), title: recordTitle(), record_date: M.date || null, status, data: Object.assign({}, M, { status }) };
  const res = M.id ? await api('PUT', `${API}/${M.id}`, body) : await api('POST', API, body);
  loadRecord(res.record);
  await refreshList();
}

/* تحميل سجل من الخادم إلى الشاشة */
function loadRecord(rec) {
  M = Object.assign(blankMinutes(), rec.data || {});
  M.id = rec.id;
  M.no = rec.number;
  M.status = rec.status;
  LOGS = rec.logs || [];
  render();
}

document.addEventListener('click', async e => {
  const b = e.target.closest('[data-act]'); if (!b) return;
  const a = b.dataset.act;

  if (a === 'add-att') { M.attendees.push({ id: uid(), role: ROLES[1], other: '', name: '', status: 'present' }); renderAtt(); renderDecOwners(); refresh(); }
  if (a === 'del-att') { const id = b.closest('[data-id]').dataset.id; M.attendees = M.attendees.filter(x => x.id !== id); M.decisions.forEach(d => { if (d.owner === id) d.owner = ''; }); renderAtt(); renderDec(); refresh(); }
  if (a === 'add-ag') { M.agenda.push({ id: uid(), text: '', disc: '' }); renderAgenda(); renderDec(); refresh(); const ins = document.querySelectorAll('#agRows input'); if (ins.length) ins[ins.length - 1].focus(); }
  if (a === 'del-ag') { const id = b.closest('[data-id]').dataset.id; M.agenda = M.agenda.filter(x => x.id !== id); M.decisions.forEach(d => { if (d.item === id) d.item = ''; }); renderAgenda(); renderDec(); refresh(); }
  if (a === 'add-dec') { M.decisions.push({ id: uid(), text: '', owner: '', due: '', item: '' }); renderDec(); refresh(); }
  if (a === 'del-dec') { const id = b.closest('[data-id]').dataset.id; M.decisions = M.decisions.filter(x => x.id !== id); renderDec(); refresh(); }

  if (a === 'save') {
    const errs = validate(false); if (errs.length) return showMsg(errs);
    busy(true);
    try { await persist('draft'); showMsg(null, `تم حفظ المحضر ${M.no}`); }
    catch (err) { showMsg(err); }
    finally { busy(false); }
  }
  if (a === 'final') {
    const errs = validate(true); if (errs.length) return showMsg(errs);
    busy(true);
    try { await persist('final'); showMsg(null, `تم اعتماد المحضر ${M.no}. يمكنك الطباعة الآن.`); }
    catch (err) { showMsg(err); }
    finally { busy(false); }
  }
  if (a === 'reopen') {
    busy(true);
    try { const res = await api('POST', `${API}/${M.id}/reopen`); loadRecord(res.record); await refreshList(); showMsg(null, 'المحضر مفتوح للتعديل. اعتمده مرة أخرى بعد الانتهاء.'); }
    catch (err) { showMsg(err); }
    finally { busy(false); }
  }
  if (a === 'print') { buildDoc(); window.print(); }
  if (a === 'new') { M = blankMinutes(); LOGS = []; render(); showMsg(); window.scrollTo(0, 0); }

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
      if (M.id === id) { M = blankMinutes(); LOGS = []; }
      await refreshList(); render(); showMsg(null, 'تم حذف المسودة.');
    } catch (err) { showMsg(err); renderSaved(); }
    finally { busy(false); }
  }
  if (a === 'cancel-del') { pendingDelete = null; renderSaved(); }
});

function renderSaved() {
  $('#saved').innerHTML = LIST.length ? LIST.map(m => `
    <div class="row ${m.id === M.id ? 'cur' : ''}">
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
  const chair = M.attendees.find(a => a.id === M.chair);
  const sec = M.attendees.find(a => a.id === M.secretary);
  const ag = M.agenda.filter(g => g.text.trim());
  const decs = M.decisions.filter(d => d.text.trim());
  const logo = document.querySelector('header .brand svg').outerHTML.replace('width="34" height="37"', 'width="38" height="42"').replace('fill="#ffffff"', 'fill="#0f5e5a"');
  $('#doc').innerHTML = `
    <div class="d-head">
      <div class="d-brand">${logo}<div><div class="ar">إتقان</div><div class="sl">إتقان في كل خطوة</div></div></div>
      <div class="d-title"><div class="t1">محضر ${esc(M.type)}${M.type === 'اجتماع قسم' && M.dept ? ' – ' + esc(M.dept) : ''}</div><div class="t2">Meeting Minutes</div></div>
      <div class="d-tenant">${BOOT.tenant.logo ? `<img src="${esc(BOOT.tenant.logo)}" alt="">` : ''}<div><b>${esc(BOOT.tenant.name_ar)}</b><span>${esc(BOOT.tenant.name_en)}</span></div></div>
    </div>
    <div class="d-code" style="display:flex;gap:18px;justify-content:space-between;margin:-4px 0 8px">
      <span>رقم المحضر: <b>${esc(M.no)}</b></span><span>النموذج: <b>${FORM_CODE}</b></span><span>الحالة: ${M.status === 'final' ? 'معتمد' : 'مسودة'}</span>
    </div>

    <table class="d">
      <tr><td class="k">التاريخ</td><td>${dayName(M.date)} ${fmtDate(M.date)}</td><td class="k">المكان</td><td>${esc(M.place)}</td></tr>
      <tr><td class="k">وقت البدء</td><td>${fmtTime(M.tStart)}</td><td class="k">وقت الإقفال</td><td>${fmtTime(M.tEnd)}</td></tr>
      <tr><td class="k">رئيس الاجتماع</td><td>${esc(personLabel(chair))}</td><td class="k">أمين الاجتماع</td><td>${esc(personLabel(sec))}</td></tr>
    </table>

    <div class="d-h2">جدول الأعمال</div>
    <ol class="d-ol">${ag.map(g => `<li>${esc(g.text)}</li>`).join('') || '<li>—</li>'}</ol>

    <div class="d-h2">محضر الجلسة</div>
    <div class="d-p">${templateHtml(false)}</div>

    ${ag.some(g => g.disc.trim()) ? `
    <div class="d-h2">ملخص المناقشات</div>
    <table class="d"><tr><th style="width:5%">م</th><th style="width:30%">البند</th><th>ملخص المناقشة</th></tr>
      ${ag.map((g, i) => `<tr><td>${i + 1}</td><td>${esc(g.text)}</td><td>${esc(g.disc).replace(/\n/g, '<br>') || '—'}</td></tr>`).join('')}</table>` : ''}

    <div class="d-h2">القرارات</div>
    <table class="d"><tr><th style="width:5%">م</th><th>القرار</th><th style="width:22%">المسؤول</th><th style="width:14%">الموعد</th><th style="width:9%">البند</th></tr>
      ${decs.map((d, i) => { const o = M.attendees.find(a => a.id === d.owner); const j = M.agenda.findIndex(g => g.id === d.item);
        return `<tr><td>${i + 1}</td><td>${esc(d.text)}</td><td>${esc(personLabel(o))}</td><td>${fmtDate(d.due)}</td><td>${j >= 0 ? j + 1 : '—'}</td></tr>`; }).join('') || '<tr><td colspan="5">—</td></tr>'}</table>

    ${M.nDate ? `<p style="margin:10px 0 0;font-size:12.5px"><b>الاجتماع القادم:</b> ${dayName(M.nDate)} ${fmtDate(M.nDate)}${M.nTime ? ' – ' + fmtTime(M.nTime) : ''}${M.nPlace ? ' – ' + esc(M.nPlace) : ''}</p>` : ''}

    <div class="keep"><div class="d-h2">كشف الحضور والتوقيعات</div>
    <table class="d"><tr><th style="width:5%">م</th><th style="width:32%">الوظيفة</th><th style="width:25%">الاسم</th><th style="width:12%">الحضور</th><th>التوقيع</th></tr>
      ${M.attendees.map((a, i) => `<tr><td>${i + 1}</td><td>${esc(roleOf(a))}</td><td>${esc(a.name)}</td><td>${a.status === 'present' ? 'حاضر' : a.status === 'apology' ? 'معتذر' : 'غائب'}</td><td style="height:26px"></td></tr>`).join('')}</table></div>

    <div class="d-signs">
      <div>أمين الاجتماع<br>${esc(personLabel(sec))}</div>
      <div>رئيس الاجتماع<br>${esc(personLabel(chair))}</div>
      <div>التوزيع: الحاضرون · المعتذرون · المسؤولون عن القرارات</div>
    </div>
    <div class="d-foot">${esc(BOOT.tenant.name_ar)} · إتقان · نظام إدارة الجودة · ${FORM_CODE} · طبعه ${esc(BOOT.me.name)} في ${nowStr()}</div>`;
}

/* =========================================================
   البدء
   ========================================================= */
M = blankMinutes();
bindTop();
render();
DevLinks.mount({
  el: '#devLinks',
  ref: () => M.no,
  saved: () => !!M.id,
  prefill: () => ({ source: 'محضر اجتماع (SH-034)', sop: 'SH-034 الاجتماعات', dept: M.dept || '',
    what: `من ${recordTitle()} رقم ${M.no}${M.date ? ' بتاريخ ' + M.date : ''}: ` }),
});
refreshList().then(async () => {
  if (await openFromQuery(LIST, async id => { busy(true); try { const res = await api('GET', `${API}/${id}`); loadRecord(res.record); } catch (e) { showMsg(e); } finally { busy(false); } })) return;
  if (!M.id && M.autoNo) { M.no = nextNo(M.type, M.date); render(); }
});

/* اقتراح أسماء مستخدمي المخزن في خانة الاسم */
document.getElementById('peopleList').innerHTML = BOOT.users.map(u => `<option value="${esc(u.name)}">${esc(u.role || '')}</option>`).join('');
@endverbatim
</script>
@endpush