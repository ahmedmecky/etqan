@extends('layouts.app')

@section('title', 'تفتيش سريع')

@push('head')
<style>
@verbatim
  :root{--ink:#1c2826;--muted:#5f6f6c;--line:#dde3e1;--brand:#0f5e5a;--brand-soft:#e3f0ee;--gold:#c08a1e;--ok:#1e7a46;--ok-soft:#e4f4ea;--bad:#b3261e;--bad-soft:#fbe7e5;--warn:#9a6300;--warn-soft:#fdf1dc}
  [hidden]{display:none !important}
  .qi{max-width:560px;margin:0 auto;font-size:16px;color:var(--ink);padding-bottom:40px}
  .qi *{box-sizing:border-box}
  .hero{text-align:center;padding:6px 4px 14px}
  .hero h1{font-size:24px;margin:0 0 6px;color:var(--brand)}
  .hero p{margin:0 0 4px;color:var(--muted);font-size:15px;line-height:1.7}
  .box{background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px;margin-bottom:12px}
  .box h2{font-size:17px;margin:0 0 10px}
  .box h2 small{font-weight:400;color:var(--muted);font-size:13px}
  .muted{color:var(--muted)}
  .big-in{width:100%;padding:13px 14px;border:1px solid var(--line);border-radius:12px;font:inherit;font-size:16px;margin-bottom:10px}
  .procs{display:flex;flex-direction:column;gap:8px;max-height:360px;overflow:auto}
  .proc{display:flex;justify-content:space-between;align-items:center;gap:10px;text-align:start;width:100%;background:#fff;border:2px solid var(--line);border-radius:12px;padding:12px 14px;font:inherit;cursor:pointer}
  .proc b{font-size:15.5px}
  .proc span{color:var(--muted);font-size:12.5px;direction:ltr;white-space:nowrap}
  .proc.on{border-color:var(--brand);background:var(--brand-soft)}
  .chips{display:flex;flex-wrap:wrap;gap:8px}
  .chip{border:2px solid var(--line);background:#fff;border-radius:99px;padding:9px 14px;font:inherit;font-size:14.5px;font-weight:600;cursor:pointer}
  .chip.on{border-color:var(--brand);background:var(--brand);color:#fff}
  .tick{display:flex;gap:10px;align-items:center;font-size:15.5px;font-weight:600;margin-bottom:12px}
  .tick input{width:24px;height:24px;accent-color:var(--brand)}
  .big{display:block;width:100%;border:0;border-radius:14px;padding:16px;font:inherit;font-size:18px;font-weight:800;background:var(--brand);color:#fff;cursor:pointer}
  .big.go{background:var(--ok)}
  .big.ghost{background:#fff;color:var(--brand);border:2px solid var(--brand)}
  .big:disabled{opacity:.45;cursor:default}
  .link{background:none;border:0;color:var(--brand);font:inherit;font-weight:700;cursor:pointer;padding:8px}
  .link.center{display:block;margin:10px auto 0}
  .top{display:flex;justify-content:space-between;align-items:center;margin-bottom:6px}
  .count{font-weight:800;font-size:15px}
  .saved-st{font-size:12.5px;color:var(--muted);min-width:70px;text-align:end}
  .bar{height:8px;background:#e9eeec;border-radius:99px;overflow:hidden;margin-bottom:12px}
  .bar i{display:block;height:100%;background:var(--ok);width:0;transition:width .25s}
  .qcard{background:#fff;border:1px solid var(--line);border-radius:16px;padding:16px;margin-bottom:12px;min-height:150px}
  .sec{display:inline-block;background:var(--brand-soft);color:var(--brand);border-radius:99px;padding:2px 12px;font-size:12.5px;font-weight:700;margin-bottom:10px}
  .lbl{font-size:13px;color:var(--muted);font-weight:700}
  .qtext{font-size:19px;font-weight:700;line-height:1.8;margin:4px 0 8px}
  .meta{font-size:13px;color:var(--muted)}
  .answers{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:8px}
  .ans{border:3px solid var(--line);background:#fff;border-radius:16px;padding:14px 8px;font:inherit;font-size:17px;font-weight:800;cursor:pointer}
  .ans b{display:block;font-size:34px;line-height:1.1}
  .ans.ok{color:var(--ok)} .ans.nc{color:var(--bad)}
  .ans.ok.on{background:var(--ok);border-color:var(--ok);color:#fff}
  .ans.nc.on{background:var(--bad);border-color:var(--bad);color:#fff}
  .answers2{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:12px}
  .ans2{border:2px solid var(--line);background:#fff;border-radius:12px;padding:10px;font:inherit;font-size:15px;font-weight:700;cursor:pointer;color:var(--muted)}
  .ans2.on{border-color:var(--gold);background:var(--warn-soft);color:var(--warn)}
  .ans2[data-res=na].on{border-color:#8a9895;background:#eef0f0;color:#44524f}
  .ncbox,.evbox{background:#fff;border:1px solid var(--line);border-radius:14px;padding:12px;margin-bottom:12px}
  .lbl2{font-weight:800;margin-bottom:8px}
  .sev{display:grid;grid-template-columns:repeat(3,1fr);gap:8px}
  .sev button{border:2px solid var(--line);background:#fff;border-radius:12px;padding:10px 6px;font:inherit;cursor:pointer}
  .sev b{display:block;font-size:16px}
  .sev small{font-size:11.5px;color:var(--muted);line-height:1.4;display:block}
  .sev [data-cls=critical].on{border-color:var(--bad);background:var(--bad-soft);color:var(--bad)}
  .sev [data-cls=major].on{border-color:var(--warn);background:var(--warn-soft);color:var(--warn)}
  .sev [data-cls=minor].on{border-color:#8a9895;background:#eef0f0}
  .quick{display:flex;flex-wrap:wrap;gap:6px;margin-bottom:8px}
  .quick button{border:1px solid var(--line);background:#fafbfb;border-radius:99px;padding:6px 11px;font:inherit;font-size:13px;cursor:pointer}
  .qi textarea{width:100%;min-height:90px;border:1px solid var(--line);border-radius:12px;padding:11px;font:inherit;font-size:16px;resize:vertical}
  .nav{display:grid;grid-template-columns:1fr 1.6fr;gap:10px}
  .score{display:flex;gap:14px;align-items:center;background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px;margin-bottom:12px}
  .ring{width:110px;height:110px;border-radius:50%;border:8px solid var(--ok);display:flex;flex-direction:column;align-items:center;justify-content:center;flex:none}
  .ring.low{border-color:var(--bad)}
  .ring b{font-size:26px;direction:ltr}
  .ring span{font-size:11.5px;color:var(--muted)}
  .nums{display:grid;grid-template-columns:1fr 1fr;gap:8px;flex:1}
  .nums span{background:#fafbfb;border-radius:10px;padding:6px 10px;font-size:13px;color:var(--muted)}
  .nums b{display:block;font-size:20px;color:var(--ink)}
  .nums .r b{color:var(--bad)}
  .prob{display:block;width:100%;text-align:start;background:#fff;border:1px solid var(--line);border-inline-start:5px solid var(--line);border-radius:10px;padding:10px;margin-bottom:8px;font:inherit;font-size:14.5px;cursor:pointer;line-height:1.6}
  .prob small{display:block;color:var(--muted);font-size:13px}
  .prob.critical{border-inline-start-color:var(--bad)} .prob.major{border-inline-start-color:var(--warn)} .prob.opp{border-inline-start-color:var(--gold)}
  .prob .tag{display:inline-block;border-radius:99px;padding:0 8px;font-size:12px;font-weight:700;background:#eef0f0}
  .prob.critical .tag{background:var(--bad-soft);color:var(--bad)} .prob.major .tag{background:var(--warn-soft);color:var(--warn)}
  .hero.done .big-ok{width:80px;height:80px;border-radius:50%;background:var(--ok);color:#fff;font-size:44px;line-height:80px;margin:10px auto}
  .ltr{direction:ltr;unicode-bidi:isolate}
  .toast{position:fixed;bottom:18px;left:50%;transform:translateX(-50%);background:#1c2826;color:#fff;padding:12px 18px;border-radius:12px;font-weight:700;z-index:50;max-width:90vw;text-align:center}
  .toast.bad{background:var(--bad)}
  @media (max-width:600px){ main.wrap{padding:10px !important} }
  .sevtag{border-radius:12px;padding:10px 12px;margin-bottom:12px;background:#eef0f0;font-size:15px}
  .sevtag small{display:block;font-size:13px;color:var(--muted);margin-top:2px}
  .sevtag.critical{background:var(--bad-soft);color:var(--bad)} .sevtag.major{background:var(--warn-soft);color:var(--warn)}
@endverbatim
</style>
@endpush

@section('content')
@verbatim
<div class="qi" id="app">

  <!-- ===== الشاشة ١: البداية ===== -->
  <section class="scr" id="sStart">
    <div class="hero">
      <h1>تفتيش سريع</h1>
      <p>سؤال واحد في كل مرة. اضغط ✓ أو ✗ وانتقل للتالي. يُحفظ تلقائياً.</p>
    </div>

    <div class="box" id="resumeBox" hidden>
      <h2>أكمل تفتيشاً بدأته</h2>
      <div id="resumeList"></div>
    </div>

    <div class="box">
      <h2>١. أين ستفتش؟</h2>
      <div class="procs" id="procList"></div>
    </div>

    <div class="box" id="deptBox" hidden>
      <h2>٢. القسم</h2>
      <div class="chips" id="deptList"></div>
    </div>

    <div class="box" id="goBox" hidden>
      <label class="tick"><input type="checkbox" id="indep"> <span>لا أفتش على قسمي أو على عملي</span></label>
      <button type="button" class="big go" id="btnStart">ابدأ التفتيش ←</button>
    </div>
  </section>

  <!-- ===== الشاشة ٢: الأسئلة ===== -->
  <section class="scr" id="sAsk" hidden>
    <div class="top">
      <button type="button" class="link" id="btnExit">✕ خروج</button>
      <span class="count" id="count"></span>
      <span class="saved-st" id="saveSt"></span>
    </div>
    <div class="bar"><i id="bar"></i></div>

    <div class="qcard">
      <div class="sec" id="qSec"></div>
      <div class="lbl">تحقق من:</div>
      <div class="qtext" id="qText"></div>
      <div class="meta" id="qMeta"></div>
    </div>

    <div class="answers">
      <button type="button" class="ans ok" data-res="ok"><b>✓</b>مطابق</button>
      <button type="button" class="ans nc" data-res="nc"><b>✗</b>غير مطابق</button>
    </div>
    <div class="answers2">
      <button type="button" class="ans2" data-res="opp">💡 فرصة تحسين</button>
      <button type="button" class="ans2" data-res="na">— لا ينطبق</button>
    </div>

    <div class="sevtag" id="sevTag" hidden></div>
    <div class="evbox" id="evBox" hidden>
      <div class="lbl2" id="evLbl">ماذا رأيت؟</div>
      <div class="quick" id="quick"></div>
      <textarea id="ev" placeholder="اكتب باختصار: ماذا رأيت وأين (رقم سجل، صنف، مكان)…"></textarea>
    </div>

    <div class="nav">
      <button type="button" class="big ghost" id="btnPrev">→ السابق</button>
      <button type="button" class="big" id="btnNext">التالي ←</button>
    </div>
    <button type="button" class="link center" id="btnSkip">تخطي الآن والرجوع له لاحقاً</button>
  </section>

  <!-- ===== الشاشة ٣: الإنهاء ===== -->
  <section class="scr" id="sEnd" hidden>
    <div class="hero">
      <h1>ملخص التفتيش</h1>
      <p id="endProc"></p>
    </div>
    <div class="score" id="score"></div>
    <div class="box" id="todoBox" hidden>
      <h2>بنود لم تُجب عليها</h2>
      <div id="todoList"></div>
    </div>
    <div class="box" id="probBox" hidden>
      <h2>المشاكل التي وجدتها</h2>
      <div id="probList"></div>
    </div>
    <div class="box">
      <h2>ما الذي وجدته جيداً؟ <small>(اختياري)</small></h2>
      <textarea id="positives" placeholder="مثال: المنطقة نظيفة ومرتبة، السجلات مكتملة…"></textarea>
    </div>
    <button type="button" class="big go" id="btnSend">إرسال لإدارة الجودة ✓</button>
    <button type="button" class="link center" id="btnBack">→ رجوع للأسئلة</button>
  </section>

  <!-- ===== الشاشة ٤: تم ===== -->
  <section class="scr" id="sDone" hidden>
    <div class="hero done">
      <div class="big-ok">✓</div>
      <h1>تم إرسال التفتيش</h1>
      <p>رقم التفتيش <b id="doneNo" class="ltr"></b></p>
      <p>إدارة الجودة ستكمل الإجراءات التصحيحية وتصدر التقرير.</p>
    </div>
    <button type="button" class="big" id="btnNew">تفتيش جديد</button>
  </section>

  <div class="toast" id="toast" hidden></div>
</div>
@endverbatim
@endsection

@push('scripts')
<script>
const BOOT = {!! json_encode($boot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP) !!};
</script>
<script>
@verbatim
/* =========================================================
   التفتيش السريع (موبايل) · يحفظ في نفس سجل التفتيش الذاتي SH-012
   ========================================================= */
const API = BOOT.urls.records;
const QUICK = BOOT.urls.quick;
const FULL = BOOT.urls.full;
const DEPTS = ['المخزن', 'الاستلام', 'التوزيع والحركة', 'المبيعات', 'التجاري والمشتريات', 'إدخال البيانات', 'الحسابات', 'إدارة الجودة', 'المدير الصيدلي', 'الموارد البشرية', 'الحاسب والأنظمة', 'السلامة والأمن', 'الإدارة العليا'];
const QUICK_NC = ['غير مطبق', 'السجل غير موجود', 'السجل ناقص أو غير موقع', 'العامل لا يعرف الإجراء', 'المكان غير نظيف أو غير مرتب', 'متأخر عن موعده'];
const QUICK_OPP = ['يمكن تبسيط الخطوة', 'يحتاج تدريباً إضافياً', 'يحتاج أداة أو جهازاً'];
const CLS = { critical: 'حرجة', major: 'رئيسية', minor: 'ثانوية' };
const CLS_HINT = { critical: 'خطر مباشر على المنتج أو المريض: أبلغ المدير الصيدلي الآن', major: 'خلل قد يؤثر على الجودة', minor: 'خطأ بسيط في التوثيق أو الترتيب' };
const TAG_RUN = 'تفتيش سريع · جارٍ';
const TAG_DONE = 'تفتيش سريع';

/* ---------- أدوات ---------- */
const $ = s => document.querySelector(s);
const pad = (n, l = 2) => String(n).padStart(l, '0');
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const today = () => { const d = new Date(); return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`; };
const nowStr = () => { const d = new Date(); return `${today()} ${pad(d.getHours())}:${pad(d.getMinutes())}`; };
const uid = () => Math.random().toString(36).slice(2, 10);
const norm = s => String(s).replace(/[أإآ]/g, 'ا').replace(/ة/g, 'ه').replace(/ى/g, 'ي').toLowerCase();
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

function show(id) { ['sStart', 'sAsk', 'sEnd', 'sDone'].forEach(s => { $('#' + s).hidden = s !== id; }); window.scrollTo(0, 0); }

/* ---------- الحالة ---------- */
let AREAS = {};
let LIST = [];
let C = null;        // سجل التفتيش (نفس بنية الشاشة الكاملة)
let pos = 0;         // رقم السؤال الحالي
let pick = { area: '', dept: '' };

function nextNo() {
  const prefix = `SI-${today().slice(0, 4)}-`;
  const nums = LIST.filter(m => m.number && m.number.startsWith(prefix)).map(m => parseInt(m.number.slice(prefix.length), 10) || 0);
  return prefix + pad((nums.length ? Math.max(...nums) : 0) + 1, 3);
}

function newInsp(area, dept, items) {
  const A = AREAS[area];
  return {
    id: null, no: nextNo(), autoNo: false, type: 'surprise', date: today(), followOf: '',
    process: A.sop, processTitle: A.sop_title, dept, deptMgr: '', lead: BOOT.me.name, inspectors: '', independent: true,
    items: items.map(r => ({ id: uid(), sec: A.title, q: r[0], ref: A.sop, who: '', custom: false, cls0: r[1],
      res: '', ev: '', cls: '', action: '', owner: '', due: '', devNo: '', imm: '' })),
    sampleOn: false, orders: [], positives: '', closeDate: '', followDate: '', summary: '', issuedBy: '', issuedAt: '',
    quick: { stage: 'run', pos: 0, by: BOOT.me.name, started: nowStr(), area, areaTitle: A.title },
    status: 'draft'
  };
}

function totals() {
  const it = C.items, c = k => it.filter(x => x.res === k).length;
  const nc = it.filter(x => x.res === 'nc');
  const ok = c('ok');
  return { score: ok + nc.length ? Math.round(ok / (ok + nc.length) * 100) : null, ok, nc: nc.length, opp: c('opp'), na: c('na'),
    crit: nc.filter(x => x.cls === 'critical').length, maj: nc.filter(x => x.cls === 'major').length, min: nc.filter(x => x.cls === 'minor').length, total: it.length };
}

/* ---------- الحفظ التلقائي ---------- */
let saving = Promise.resolve();
function save(stage) {
  C.quick = Object.assign({}, C.quick, { pos, stage: stage || C.quick.stage });
  const done = C.quick.stage === 'done';
  const body = {
    number: C.no, record_date: C.date, status: 'draft',
    title: [C.process, (C.quick && C.quick.areaTitle) || C.processTitle, done ? TAG_DONE : TAG_RUN].join(' · '),
    data: Object.assign({}, C, { totals: totals() })
  };
  $('#saveSt').textContent = 'جارٍ الحفظ…';
  saving = saving.then(async () => {
    try {
      const res = C.id ? await api('PUT', `${API}/${C.id}`, body) : await api('POST', API, body);
      C.id = res.record.id; C.no = res.record.number;
      $('#saveSt').textContent = 'محفوظ ✓';
    } catch (e) {
      $('#saveSt').textContent = 'لم يُحفظ';
      // رقم مستخدم: نحدث القائمة ونأخذ رقماً جديداً
      if (!C.id && String(e).includes('مستخدم')) { await loadList(); C.no = nextNo(); }
      toast(e[0] || 'تعذر الحفظ', true);
      throw e;
    }
  });
  return saving;
}

/* ---------- الشاشة ١ ---------- */
function renderProcs() {
  $('#procList').innerHTML = Object.entries(AREAS)
    .map(([k, a]) => `<button type="button" class="proc ${pick.area === k ? 'on' : ''}" data-proc="${k}"><b>${esc(a.title)}</b><span>${a.count} بنود</span></button>`).join('');
}
function renderDepts() {
  $('#deptList').innerHTML = DEPTS.map(d => `<button type="button" class="chip ${pick.dept === d ? 'on' : ''}" data-dept="${esc(d)}">${esc(d)}</button>`).join('');
}
function renderResume() {
  const mine = LIST.filter(m => m.status !== 'final' && (m.title || '').includes(TAG_RUN) && m.created_by === BOOT.me.name);
  $('#resumeBox').hidden = !mine.length;
  $('#resumeList').innerHTML = mine.map(m => `<button type="button" class="proc" data-resume="${m.id}"><b>${esc((m.title || '').replace(' · ' + TAG_RUN, ''))}</b><span class="ltr">${esc(m.number)} · ${esc(m.record_date || '')}</span></button>`).join('');
}

/* ---------- الشاشة ٢ ---------- */
function renderQ() {
  const it = C.items[pos];
  const n = C.items.length, answered = C.items.filter(x => x.res).length;
  $('#count').textContent = `سؤال ${pos + 1} من ${n}`;
  $('#bar').style.width = Math.round(answered / n * 100) + '%';
  $('#qSec').textContent = it.sec;
  $('#qText').textContent = it.q;
  $('#qMeta').textContent = [it.who ? 'المسؤول: ' + it.who : '', it.ref ? 'السجل: ' + it.ref : ''].filter(Boolean).join(' · ');
  document.querySelectorAll('[data-res]').forEach(b => b.classList.toggle('on', b.dataset.res === it.res));
  $('#sevTag').hidden = it.res !== 'nc';
  $('#sevTag').className = 'sevtag ' + (it.cls || '');
  $('#sevTag').innerHTML = it.cls ? `التصنيف: <b>${CLS[it.cls]}</b><small>${CLS_HINT[it.cls]}</small>` : '';
  $('#evBox').hidden = !(it.res === 'nc' || it.res === 'opp');
  $('#evLbl').textContent = it.res === 'opp' ? 'ما اقتراحك؟' : 'ماذا رأيت؟';
  $('#quick').innerHTML = (it.res === 'opp' ? QUICK_OPP : QUICK_NC).map(t => `<button type="button" data-quick="${esc(t)}">${esc(t)}</button>`).join('');
  $('#ev').value = it.ev;
  $('#btnPrev').disabled = pos === 0;
  $('#btnNext').textContent = pos === n - 1 ? 'إنهاء ←' : 'التالي ←';
}

function checkCurrent() {
  const it = C.items[pos];
  if ((it.res === 'nc' || it.res === 'opp') && !it.ev.trim()) return it.res === 'nc' ? 'اكتب ماذا رأيت' : 'اكتب اقتراحك';
  return null;
}

function go(step) {
  const err = checkCurrent();
  if (err && step > 0) { toast(err, true); return; }
  const next = pos + step;
  if (next >= C.items.length) { save().catch(() => {}); return openEnd(); }
  if (next < 0) return;
  pos = next; renderQ(); save().catch(() => {});
}

/* ---------- الشاشة ٣ ---------- */
function openEnd() {
  const t = totals();
  $('#endProc').textContent = `${(C.quick && C.quick.areaTitle) || C.processTitle} · ${C.dept}`;
  $('#score').innerHTML = `<div class="ring ${t.score !== null && t.score < 80 ? 'low' : ''}"><b>${t.score === null ? '—' : t.score + '%'}</b><span>نسبة المطابقة</span></div>
    <div class="nums"><span><b>${t.ok}</b>مطابق</span><span class="r"><b>${t.nc}</b>غير مطابق</span><span><b>${t.opp}</b>تحسين</span><span><b>${t.na}</b>لا ينطبق</span></div>`;
  const todo = C.items.map((x, i) => ({ x, i })).filter(o => !o.x.res);
  $('#todoBox').hidden = !todo.length;
  $('#todoList').innerHTML = todo.map(o => `<button type="button" class="prob" data-jump="${o.i}"><b>${o.i + 1}.</b> ${esc(o.x.q)}</button>`).join('');
  const probs = C.items.map((x, i) => ({ x, i })).filter(o => o.x.res === 'nc' || o.x.res === 'opp');
  $('#probBox').hidden = !probs.length;
  $('#probList').innerHTML = probs.map(o => `<button type="button" class="prob ${o.x.cls || 'opp'}" data-jump="${o.i}">
      <span class="tag">${o.x.res === 'opp' ? 'تحسين' : CLS[o.x.cls]}</span> <b>${o.i + 1}.</b> ${esc(o.x.q)}<small>${esc(o.x.ev)}</small></button>`).join('');
  $('#positives').value = C.positives || '';
  $('#btnSend').disabled = !!todo.length;
  $('#btnSend').textContent = todo.length ? `أجب على ${todo.length} بنداً أولاً` : 'إرسال لإدارة الجودة ✓';
  show('sEnd');
}

/* ---------- الأحداث ---------- */
document.addEventListener('click', async e => {
  const t = e.target;
  const proc = t.closest('[data-proc]');
  if (proc) {
    pick.area = proc.dataset.proc; pick.dept = AREAS[pick.area].dept || pick.dept;
    renderProcs(); renderDepts(); $('#deptBox').hidden = false; $('#goBox').hidden = false;
    $('#deptBox').scrollIntoView({ behavior: 'smooth', block: 'start' });
    return;
  }
  const dept = t.closest('[data-dept]');
  if (dept) { pick.dept = dept.dataset.dept; renderDepts(); return; }

  const resume = t.closest('[data-resume]');
  if (resume) {
    try {
      const res = await api('GET', `${API}/${resume.dataset.resume}`);
      C = Object.assign({}, res.record.data, { id: res.record.id, no: res.record.number });
      pos = Math.min((C.quick && C.quick.pos) || 0, C.items.length - 1);
      show('sAsk'); renderQ();
    } catch (err) { toast(err[0], true); }
    return;
  }

  const r = t.closest('[data-res]');
  if (r && C) {
    const it = C.items[pos];
    it.res = r.dataset.res;
    it.cls = it.res === 'nc' ? (it.cls0 || 'major') : '';   // التصنيف ثابت لكل بند
    renderQ();
    if (it.res === 'ok' || it.res === 'na') setTimeout(() => go(1), 250);   // انتقال تلقائي
    else $('#ev').focus({ preventScroll: true });
    return;
  }
  const q = t.closest('[data-quick]');
  if (q && C) {
    const it = C.items[pos];
    it.ev = it.ev.trim() ? it.ev.trim() + '، ' + q.dataset.quick : q.dataset.quick;
    $('#ev').value = it.ev; $('#ev').focus();
    return;
  }
  const j = t.closest('[data-jump]');
  if (j) { pos = +j.dataset.jump; show('sAsk'); renderQ(); return; }
});

$('#ev').addEventListener('input', e => { C.items[pos].ev = e.target.value; });
$('#positives').addEventListener('input', e => { C.positives = e.target.value; });
$('#btnNext').addEventListener('click', () => go(1));
$('#btnPrev').addEventListener('click', () => go(-1));
$('#btnSkip').addEventListener('click', () => {
  const it = C.items[pos]; it.res = ''; it.cls = ''; it.ev = '';
  if (pos < C.items.length - 1) { pos++; renderQ(); } else openEnd();
});
$('#btnExit').addEventListener('click', async () => {
  try { await save(); } catch (e) {}
  await loadList(); renderResume(); show('sStart');
  toast('تم الحفظ. يمكنك الإكمال لاحقاً من «أكمل تفتيشاً بدأته».');
});
$('#btnBack').addEventListener('click', () => { show('sAsk'); renderQ(); });

$('#btnStart').addEventListener('click', async () => {
  if (!pick.area) return toast('اختر أين ستفتش', true);
  if (!pick.dept) return toast('اختر القسم', true);
  if (!$('#indep').checked) return toast('أكد أنك لا تفتش على قسمك', true);
  $('#btnStart').disabled = true;
  try {
    const res = await api('GET', `${QUICK}/${encodeURIComponent(pick.area)}`);
    await loadList();
    C = newInsp(pick.area, pick.dept, res.items || []);
    pos = 0;
    await save();
    show('sAsk'); renderQ();
  } catch (e) { toast((e && e[0]) || 'تعذر البدء', true); }
  finally { $('#btnStart').disabled = false; }
});

$('#btnSend').addEventListener('click', async () => {
  $('#btnSend').disabled = true;
  try {
    C.quick.ended = nowStr();
    await save('done');
    $('#doneNo').textContent = C.no;
    show('sDone');
  } catch (e) { $('#btnSend').disabled = false; }
});
$('#btnNew').addEventListener('click', async () => {
  C = null; pick = { area: '', dept: '' }; $('#indep').checked = false;
  $('#deptBox').hidden = true; $('#goBox').hidden = true;
  await loadList(); renderProcs(); renderResume(); show('sStart');
});

async function loadList() {
  try { LIST = (await api('GET', API)).records || []; } catch (e) { LIST = []; }
}

/* ---------- البدء ---------- */
(async function () {
  try { AREAS = (await api('GET', QUICK)).areas || {}; } catch (e) { toast('تعذر تحميل قائمة المناطق', true); }
  await loadList();
  renderProcs(); renderDepts(); renderResume();
})();
@endverbatim
</script>
@endpush