@extends('layouts.app')

@section('title', 'خريطة نظام الجودة')

@push('head')
<style>
  main.wrap{max-width:1560px !important}
  .qm{
    --bg:#f4f6f5; --card:#ffffff; --ink:#1c2826; --muted:#5f6f6c; --line:#dde3e1;
    --brand:#0f5e5a; --brand-soft:#e3f0ee; --gold:#c08a1e; --gold-soft:#f8efdc;
    --mgmt:#0f5e5a; --mgmt-soft:#e3f0ee;
    --core:#1d4f86; --core-soft:#e4edf8;
    --loop:#9a5b00; --loop-soft:#fbefdc;
    --sup:#4f5d5b; --sup-soft:#eef1f0;
    --R:#1e7a46; --A:#b3261e; --C:#1d4f86; --I:#7a8785;
    --radius:10px;
  }
  .qm .card{background:var(--card);border:1px solid var(--line);border-radius:var(--radius);padding:18px 20px;margin-bottom:18px}
  .qm .card h2{font-size:18px;margin:0 0 4px;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
  .qm .sub{font-size:13px;color:var(--muted);margin:0 0 14px}
  .qm .btn{background:var(--brand);color:#fff;border:1px solid var(--brand);border-radius:8px;padding:7px 16px;font:inherit;font-weight:600;cursor:pointer;font-size:14px}
  .qm .btn.ghost{background:#fff;color:var(--brand)}
  .qm .btn.gold{background:var(--gold);border-color:var(--gold)}
  .qm .bar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-bottom:16px}
  .qm .bar select{padding:7px 9px;border:1px solid var(--line);border-radius:8px;font:inherit;background:#fff;min-width:220px}
  .qm .bar .hint{font-size:13px;color:var(--muted)}
  .qm /* ===== الخريطة ===== */
  .layer{border-radius:12px;padding:12px 14px 14px;margin-bottom:12px;position:relative}
  .qm .layer h3{margin:0 0 10px;font-size:14.5px;display:flex;align-items:center;gap:8px}
  .qm .layer h3 .tag{font-size:11.5px;font-weight:700;padding:1px 10px;border-radius:99px;color:#fff}
  .qm .l-mgmt{background:var(--mgmt-soft);border:1.5px solid #b9d8d3}
  .qm .l-mgmt h3{color:var(--mgmt)}
  .qm .l-mgmt .tag{background:var(--mgmt)}
  .qm .l-core{background:var(--core-soft);border:1.5px solid #bfd2ea}
  .qm .l-core h3{color:var(--core)}
  .qm .l-core .tag{background:var(--core)}
  .qm .l-sup{background:var(--sup-soft);border:1.5px solid #d3dad8}
  .qm .l-sup h3{color:var(--sup)}
  .qm .l-sup .tag{background:var(--sup)}
  .qm .row{display:flex;flex-wrap:wrap;gap:8px}
  .qm .box{background:#fff;border:1.5px solid var(--line);border-radius:9px;padding:6px 10px;cursor:pointer;min-width:120px;flex:1;transition:transform .08s,box-shadow .12s,opacity .15s;position:relative}
  .qm .box:hover{box-shadow:0 2px 8px rgba(0,0,0,.08)}
  .qm .box .c{font-size:11.5px;font-weight:700;direction:ltr;display:inline-block;color:var(--muted)}
  .qm .box .t{font-size:13.5px;font-weight:600;line-height:1.35}
  .qm .box .who{font-size:11px;margin-top:2px;color:var(--muted)}
  .qm .l-mgmt .box{border-color:#b9d8d3}
  .qm .l-mgmt .box .c{color:var(--mgmt)}
  .qm .l-core .box{border-color:#bfd2ea}
  .qm .l-core .box .c{color:var(--core)}
  .qm .l-sup .box{border-color:#d3dad8}
  .qm .box.loop{border-color:#ecd2a6;background:#fffaf1}
  .qm .box.loop .c{color:var(--loop)}
  .qm .box.sel{outline:3px solid var(--gold);outline-offset:1px}
  .qm .box.dim{opacity:.25}
  .qm .box.hlR{box-shadow:0 0 0 3px var(--R) inset}
  .qm .box.hlA{box-shadow:0 0 0 3px var(--A) inset}
  .qm /* السلسلة الأساسية */
  .chain{display:flex;align-items:stretch;gap:0;flex-wrap:nowrap;overflow-x:auto;padding-bottom:4px}
  .qm .stage{flex:1;min-width:150px;display:flex;flex-direction:column;gap:6px}
  .qm .stage .sh{font-size:12px;font-weight:700;color:var(--core);text-align:center;padding:2px 0}
  .qm .stage .box{flex:none}
  .qm .arrow{flex:none;width:26px;display:flex;align-items:center;justify-content:center;color:var(--core);font-size:22px;font-weight:700;padding-top:20px}
  .qm .ends{flex:none;width:92px;display:flex;align-items:center;justify-content:center;text-align:center;font-size:12.5px;font-weight:700;color:#fff;background:var(--core);border-radius:10px;padding:8px;line-height:1.4}
  .qm .loopbar{margin-top:12px;border:1.5px dashed #e0bd7f;border-radius:10px;padding:10px 12px;background:#fffaf1}
  .qm .loopbar .lh{font-size:13px;font-weight:700;color:var(--loop);margin-bottom:8px;display:flex;align-items:center;gap:8px}
  .qm .sup-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
  @media (max-width:1000px){.qm .sup-grid{grid-template-columns:repeat(2,1fr)}}
  .qm .sup-grid .grp{background:#fff;border:1px solid #d3dad8;border-radius:10px;padding:8px}
  .qm .sup-grid .gh{font-size:12.5px;font-weight:700;color:var(--sup);margin-bottom:6px}
  .qm .sup-grid .row{flex-direction:column}
  .qm .policy{display:flex;gap:10px;align-items:center;justify-content:center;background:linear-gradient(90deg,var(--brand),#1c7a74);color:#fff;border-radius:12px;padding:10px 14px;margin-bottom:12px;font-weight:700;text-align:center;flex-wrap:wrap}
  .qm .policy span{font-size:12.5px;font-weight:600;color:#d7efe9}
  .qm /* لوحة التفاصيل */
  .detail{border:1.5px solid var(--gold);background:#fffdf7;border-radius:10px;padding:12px 14px;margin-top:4px}
  .qm .detail h4{margin:0 0 6px;font-size:15px}
  .qm .detail .kv{display:grid;grid-template-columns:repeat(4,1fr);gap:8px}
  @media (max-width:900px){.qm .detail .kv{grid-template-columns:1fr 1fr}}
  .qm .detail .kv div{font-size:13px}
  .qm .detail .kv b{display:block;font-size:12px;margin-bottom:2px}
  .qm .chip{display:inline-block;font-size:12px;padding:1px 8px;border-radius:99px;margin:2px 0 2px 4px;border:1px solid var(--line);background:#fff}
  .qm /* ===== المصفوفة ===== */
  .tbl-wrap{overflow:auto;max-height:78vh;border:1px solid var(--line);border-radius:10px}
  .qm table.m{border-collapse:separate;border-spacing:0;font-size:13px;width:100%;min-width:1180px}
  .qm table.m th,.qm table.m td{border-bottom:1px solid var(--line);border-inline-start:1px solid var(--line);padding:5px 6px;text-align:center;background:#fff}
  .qm table.m thead th{position:sticky;top:0;z-index:3;background:#f6f8f7;font-size:12px;color:#33413f;font-weight:700;vertical-align:bottom;cursor:pointer;line-height:1.3}
  .qm table.m thead th.dep:hover{background:var(--brand-soft)}
  .qm table.m thead th.on{background:var(--brand);color:#fff}
  .qm table.m th.sop,.qm table.m td.sop{position:sticky;right:0;z-index:2;text-align:start;min-width:270px;background:#fff;border-inline-start:0}
  .qm table.m thead th.sop{z-index:4;background:#f6f8f7}
  .qm table.m td.sop{cursor:pointer}
  .qm table.m td.sop .c{font-weight:700;direction:ltr;display:inline-block;color:var(--brand);font-size:12px;min-width:56px}
  .qm table.m tr.grp td{background:#eef3f2;font-weight:700;color:var(--brand);text-align:start;font-size:12.5px;position:static}
  .qm table.m tr.grp td.sop{background:#eef3f2}
  .qm table.m tr.sel td{background:#fff8e6}
  .qm table.m tr.sel td.sop{background:#fff8e6}
  .qm table.m td.col-on{background:#f1f8f6}
  .qm table.m tr.sel td.col-on{background:#fbefcf}
  .qm .L{display:inline-flex;align-items:center;justify-content:center;width:24px;height:24px;border-radius:6px;font-weight:700;font-size:12.5px;color:#fff;direction:ltr}
  .qm .L.R{background:var(--R)}
  .qm .L.A{background:var(--A)}
  .qm .L.C{background:#fff;color:var(--C);border:1.5px solid var(--C)}
  .qm .L.I{background:#fff;color:var(--I);border:1.5px dashed var(--I)}
  .qm table.m tfoot td{position:sticky;bottom:0;background:#f6f8f7;font-weight:700;font-size:12px;z-index:2}
  .qm table.m tfoot td.sop{z-index:3}
  .qm .legend{display:flex;gap:16px;flex-wrap:wrap;font-size:13px;margin:0 0 12px;align-items:center}
  .qm .legend span{display:inline-flex;align-items:center;gap:6px}
  .qm .checks{font-size:13px;margin-top:12px}
  .qm .checks .ok{color:var(--R);font-weight:600}
  .qm .checks .bad{color:var(--A);font-weight:600}
  .qm /* ===== الطباعة A3 أفقي ===== */
  @page{size:A3 landscape;margin:10mm}
  @media print{.qm .brand-en,.qm .bar,.qm .no-print,.qm .detail{display:none !important}
  .qm .card{border:0;padding:0;margin:0 0 6px}
  .qm .card.page2{break-before:page}
  .qm .box{break-inside:avoid}
  .qm .box.dim{opacity:1}
  .qm .box.sel{outline:0}
  .qm .tbl-wrap{max-height:none;overflow:visible;border:0}
  .qm table.m{min-width:0;font-size:10.5px}
  .qm table.m th,.qm table.m td{padding:2px 3px}
  .qm table.m thead th,.qm table.m tfoot td,.qm table.m th.sop,.qm table.m td.sop{position:static}
  .qm .L{width:18px;height:18px;font-size:10.5px}
  .qm *{-webkit-print-color-adjust:exact;print-color-adjust:exact}
  .qm .card{zoom:.86}
  .qm .sub{margin-bottom:6px}
  .qm .layer{margin-bottom:8px;padding:8px 10px 10px}}
  .qm .ds{font-size:10.5px;font-weight:700;margin-top:3px;border-radius:99px;padding:0 7px;display:inline-block}
  .qm .ds-ok{background:#e4f4ea;color:#1e7a46} .qm .ds-wait{background:#fdf1dc;color:#9a6300} .qm .ds-none{background:#f0f2f1;color:#8a9895}
  .qm .detail .dl{margin-top:10px;font-size:13.5px;border-top:1px dashed #e6d8b4;padding-top:8px}
  .qm .detail .dl a{color:var(--brand);font-weight:700}
  .qm .ptitle{display:none}
  @media print{
    header.top,footer.foot,main.wrap>.alert{display:none !important}
    main.wrap{padding:0 !important;max-width:none !important}
    body{background:#fff !important}
    .qm .ptitle{display:flex;justify-content:space-between;align-items:center;border-bottom:3px solid #c08a1e;padding-bottom:6px;margin-bottom:8px}
    .qm .ds{display:none}
  }
</style>
@endpush

@section('content')
<div class="qm">
  <div class="ptitle"><b style="font-size:18px;color:#0f5e5a">{{ auth()->user()->tenant?->name_ar }} · خريطة نظام إدارة الجودة ومصفوفة المسؤوليات</b><span style="direction:ltr;font-size:11px">QMS-MAP · v01 · SH-001 → SH-034</span></div>
  <div class="no-print" style="display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:12px">
    <div>
      <h1 class="page-title" style="margin:0">خريطة نظام إدارة الجودة ومصفوفة المسؤوليات</h1>
      <p class="page-sub" style="margin:0">الإجراءات الـ ٣٤ وترابطها · من يعتمد ومن ينفذ · حالة كل إجراء في سجل الوثائق</p>
    </div>
    <a href="{{ route('documents.index', ['type' => 'sop']) }}" class="btn ghost">سجل الوثائق</a>
  </div>
@verbatim


  <div class="bar no-print">
    <label for="dep" class="hint">عرض مسؤوليات:</label>
    <select id="dep"></select>
    <button type="button" class="btn ghost" id="clear">مسح التحديد</button>
    <span style="flex:1"></span>
    <span class="hint">اضغط على أي إجراء لعرض تفاصيله، أو على أي قسم لعرض مسؤولياته.</span>
    <button type="button" class="btn gold" onclick="window.print()">طباعة (A3 أفقي)</button>
  </div>

  <!-- ١. الخريطة -->
  <section class="card">
    <h2>١. خريطة نظام إدارة الجودة</h2>
    <p class="sub">مسار المستحضر من المورد المرخص إلى العميل المرخص، تحكمه عمليات الإدارة من أعلى، وتدعمه عمليات الموارد والمرافق من أسفل. كل مربع إجراء تشغيل قياسي.</p>

    <div class="policy">سياسة الجودة · ممارسات التخزين والتوزيع الجيد (EDA GSDP) <span>مستحضر صالح · في الظروف الصحيحة · لعميل مرخص · في الوقت المناسب</span></div>

    <div class="layer l-mgmt">
      <h3><span class="tag">إدارة</span> عمليات الإدارة والتحسين المستمر</h3>
      <div class="row" id="mgmt"></div>
    </div>

    <div class="layer l-core">
      <h3><span class="tag">أساسية</span> سلسلة القيمة: من المورد إلى العميل</h3>
      <div class="chain" id="chain"></div>
      <div class="loopbar">
        <div class="lh">↺ التغذية العكسية من السوق والمستحضرات غير الصالحة</div>
        <div class="row" id="loop"></div>
      </div>
    </div>

    <div class="layer l-sup">
      <h3><span class="tag">داعمة</span> عمليات الدعم: الأفراد والمرافق والأنظمة</h3>
      <div class="sup-grid" id="sup"></div>
    </div>

    <div id="detail" class="detail" hidden></div>
  </section>

  <!-- ٢. المصفوفة -->
  <section class="card page2">
    <h2>٢. مصفوفة المسؤوليات (RACI)</h2>
    <p class="sub">لكل إجراء: من ينفذ، ومن يُسأل ويعتمد، ومن يُستشار، ومن يُبلغ. مأخوذة من قسم المسؤوليات والاعتماد في كل إجراء.</p>
    <div class="legend">
      <span><i class="L R">R</i> ينفذ (Responsible)</span>
      <span><i class="L A">A</i> مسؤول ومعتمد (Accountable) · واحد فقط لكل إجراء</span>
      <span><i class="L C">C</i> يُستشار (Consulted)</span>
      <span><i class="L I">I</i> يُبلغ (Informed)</span>
    </div>
    <div class="tbl-wrap"><table class="m" id="mx"></table></div>
    <div class="checks" id="checks"></div>
  </section>


@endverbatim
</div>
@endsection

@push('scripts')
<script>
const DOCS = @json($docs);
</script>
<script>
@verbatim

/* =========================================================
   الأقسام
   ========================================================= */
const DEPTS = [
  ['CEO', 'الرئيس التنفيذي'],
  ['PH', 'المدير الصيدلي'],
  ['QA', 'إدارة الجودة'],
  ['WH', 'المخزن (المدير · الأمناء · المحضرون · الكنترول)'],
  ['RC', 'الاستلام'],
  ['DS', 'التوزيع والحركة'],
  ['SL', 'المبيعات'],
  ['CM', 'التجاري والمشتريات وإدخال البيانات'],
  ['FN', 'الحسابات'],
  ['HR', 'الموارد البشرية'],
  ['IT', 'الحاسب والأنظمة'],
  ['SF', 'السلامة والأمن']
];
const SHORT = { CEO: 'الرئيس التنفيذي', PH: 'المدير الصيدلي', QA: 'الجودة', WH: 'المخزن', RC: 'الاستلام', DS: 'التوزيع والحركة', SL: 'المبيعات', CM: 'التجاري والمشتريات', FN: 'الحسابات', HR: 'الموارد البشرية', IT: 'الحاسب', SF: 'السلامة والأمن' };
const ORDER = DEPTS.map(d => d[0]);

/* =========================================================
   الإجراءات · الترتيب: CEO PH QA WH RC DS SL CM FN HR IT SF
   ========================================================= */
const SOPS = [
  // عمليات الإدارة
  ['SH-009', 'إدارة مخاطر الجودة', 'mgmt', 'I R A C C C C C C C C C'],
  ['SH-013', 'مراجعة الإدارة', 'mgmt', 'A R R C - I C C C C I I'],
  ['SH-012', 'التفتيش الذاتي', 'mgmt', 'I R A C C C C C C C C C'],
  ['SH-008', 'الإجراءات التصحيحية والوقائية', 'mgmt', 'I C A R R R R R C C C C'],
  ['SH-027', 'التحكم في التغيير', 'mgmt', 'C R A C C C C C C C C C'],
  ['SH-014', 'التوثيق والتحكم في الوثائق', 'mgmt', 'C R A I I I I I I I R I'],
  ['SH-034', 'الاجتماعات ومتابعة القرارات', 'mgmt', 'R C A R I R R R C R I I'],
  // السلسلة الأساسية
  ['SH-031', 'تأهيل الموردين والعملاء', 'core:1', '- A C - I - R R I - C -'],
  ['SH-001', 'الاستلام', 'core:2', 'I A C R R - - C I - - R'],
  ['SH-005', 'التخزين', 'core:3', '- A C R R - - - - - R -'],
  ['SH-030', 'الحرارة وسلسلة التبريد', 'core:3', '- A C R R R - - - - I -'],
  ['SH-016', 'جرد المخزون', 'core:3', 'I R C R C - - - A - R -'],
  ['SH-028', 'سياسة الصرف (FEFO)', 'core:4', '- A C R - - R R - - R -'],
  ['SH-004', 'التجهيز والصرف', 'core:4', '- A C R - R R R - - I -'],
  ['SH-006', 'النقل والتسليم', 'core:5', '- A C C - R R - - - - R'],
  // التغذية العكسية
  ['SH-002', 'المرتجعات', 'loop', 'I A C R - R R - R - - -'],
  ['SH-007', 'الشكاوى', 'loop', 'I R A C - C R C - C - -'],
  ['SH-010', 'الاستدعاء والسحب', 'loop', 'I A R R R R R R R - R -'],
  ['SH-011', 'المستحضرات المغشوشة', 'loop', 'I A C R R - R R - - - I'],
  ['SH-020', 'المنتهي والتالف والإعدام', 'loop', 'C A R R - - - R R - - -'],
  ['SH-019', 'الكسر والانسكاب', 'loop', '- A C R R R - - - - - -'],
  ['SH-032', 'المستحضرات الخاضعة للرقابة', 'loop', 'I A C C - R R R - - - C'],
  // الدعم
  ['SH-015', 'تدريب العاملين', 'sup:people', 'I R R R R R R I I A I I'],
  ['SH-021', 'التعيين والتوظيف', 'sup:people', 'C C I C C C C C C A R -'],
  ['SH-022', 'الصحة والنظافة الشخصية', 'sup:people', '- C C R R R - - - A - -'],
  ['SH-018', 'سلامة العاملين', 'sup:safety', 'I C C A I C I I I R I R'],
  ['SH-017', 'مكافحة الحريق', 'sup:safety', 'I C C A I I I I I R I R'],
  ['SH-029', 'نظام الأمن', 'sup:safety', '- C I A - R - - - - R R'],
  ['SH-033', 'الطوارئ واستمرارية العمل', 'sup:safety', 'C R C A - R I - - C R R'],
  ['SH-003', 'نظافة المخزن', 'sup:fac', '- A C R R R - - - - - -'],
  ['SH-026', 'مكافحة الآفات', 'sup:fac', '- C C A R - - - - - - -'],
  ['SH-023', 'التأهيل والمعايرة', 'sup:fac', 'I R A R - - - - - - - -'],
  ['SH-024', 'الأنظمة الإلكترونية وسلامة البيانات', 'sup:sys', '- C A C - - C C C - R -'],
  ['SH-025', 'الجهات الخارجية', 'sup:sys', 'C C A R - C - - - C C C']
];
const SUP_GROUPS = [['people', 'الأفراد'], ['safety', 'السلامة والأمن والطوارئ'], ['fac', 'المرافق والمعدات'], ['sys', 'الأنظمة والجهات الخارجية']];
const STAGES = [[1, 'التأهيل'], [2, 'الاستلام'], [3, 'التخزين'], [4, 'التجهيز والصرف'], [5, 'النقل والتسليم']];
const GROUP_TITLES = { mgmt: 'عمليات الإدارة والتحسين', core: 'سلسلة القيمة', loop: 'التغذية العكسية والمستحضرات غير الصالحة', sup: 'عمليات الدعم' };

/* تحويل النص إلى خريطة */
const DATA = SOPS.map(([code, title, place, raci]) => {
  const v = raci.trim().split(/\s+/);
  const map = {}; ORDER.forEach((d, i) => { map[d] = v[i] && v[i] !== '-' ? v[i] : ''; });
  return { code, title, place, grp: place.split(':')[0], map };
});

/* =========================================================
   أدوات
   ========================================================= */
const $ = s => document.querySelector(s);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const by = code => DATA.find(d => d.code === code);
const who = (d, L) => ORDER.filter(k => d.map[k] === L);
const state = { sop: null, dep: '' };

/* =========================================================
   الخريطة
   ========================================================= */
/* حالة وثيقة الإجراء في سجل الوثائق لهذا المخزن */
function docBadge(code) {
  const x = DOCS[code];
  if (!x) return '<div class="ds ds-none">غير مسجل</div>';
  if (x.version) return `<div class="ds ds-ok">ساري · إصدار ${esc(x.version)}${x.review ? ' · مراجعة متأخرة' : ''}</div>`;
  return `<div class="ds ${x.pending ? 'ds-wait' : 'ds-none'}">${x.pending ? 'قيد المراجعة' : 'لم يُعتمد'}</div>`;
}

function box(d, extra = '') {
  const a = who(d, 'A')[0];
  return `<div class="box ${extra}" data-sop="${d.code}" tabindex="0">
    <span class="c">${d.code}</span>
    <div class="t">${esc(d.title)}</div>
    <div class="who">يعتمد: ${a ? SHORT[a] : '—'}</div>
    ${docBadge(d.code)}
  </div>`;
}

function renderMap() {
  $('#mgmt').innerHTML = DATA.filter(d => d.grp === 'mgmt').map(d => box(d)).join('');
  let chain = `<div class="ends">مورد<br>مرخص</div>`;
  STAGES.forEach(([n, t], i) => {
    chain += `<div class="arrow">←</div><div class="stage"><div class="sh">${t}</div>${DATA.filter(d => d.place === 'core:' + n).map(d => box(d)).join('')}</div>`;
  });
  chain += `<div class="arrow">←</div><div class="ends">عميل<br>مرخص</div>`;
  $('#chain').innerHTML = chain;
  $('#loop').innerHTML = DATA.filter(d => d.grp === 'loop').map(d => box(d, 'loop')).join('');
  $('#sup').innerHTML = SUP_GROUPS.map(([k, t]) => `<div class="grp"><div class="gh">${t}</div><div class="row">${DATA.filter(d => d.place === 'sup:' + k).map(d => box(d)).join('')}</div></div>`).join('');
}

/* =========================================================
   المصفوفة
   ========================================================= */
function renderMatrix() {
  const groups = [['mgmt', DATA.filter(d => d.grp === 'mgmt')], ['core', DATA.filter(d => d.grp === 'core')], ['loop', DATA.filter(d => d.grp === 'loop')], ['sup', DATA.filter(d => d.grp === 'sup')]];
  let h = `<thead><tr><th class="sop">الإجراء</th>${DEPTS.map(([k, t]) => `<th class="dep" data-dep="${k}">${esc(t)}</th>`).join('')}</tr></thead><tbody>`;
  groups.forEach(([g, list]) => {
    h += `<tr class="grp"><td class="sop">${GROUP_TITLES[g]}</td><td colspan="${DEPTS.length}"></td></tr>`;
    list.forEach(d => {
      h += `<tr data-sop="${d.code}"><td class="sop"><span class="c">${d.code}</span> ${esc(d.title)}</td>${ORDER.map(k => `<td data-dep="${k}">${d.map[k] ? `<i class="L ${d.map[k]}">${d.map[k]}</i>` : ''}</td>`).join('')}</tr>`;
    });
  });
  h += `</tbody><tfoot><tr><td class="sop">عدد الإجراءات (ينفذ R / يعتمد A)</td>${ORDER.map(k => `<td data-dep="${k}">${DATA.filter(d => d.map[k] === 'R').length} / ${DATA.filter(d => d.map[k] === 'A').length}</td>`).join('')}</tr></tfoot>`;
  $('#mx').innerHTML = h;

  // فحص السلامة
  const noA = DATA.filter(d => who(d, 'A').length !== 1);
  const noR = DATA.filter(d => !who(d, 'R').length);
  $('#checks').innerHTML =
    (noA.length ? `<div class="bad">إجراءات بدون مسؤول اعتماد واحد: ${noA.map(d => d.code).join('، ')}</div>` : `<div class="ok">✓ كل إجراء من ${DATA.length} إجراء له مسؤول اعتماد واحد (A).</div>`) +
    (noR.length ? `<div class="bad">إجراءات بدون منفذ: ${noR.map(d => d.code).join('، ')}</div>` : `<div class="ok">✓ كل إجراء له منفذ واحد على الأقل (R).</div>`);
}

/* =========================================================
   التفاعل
   ========================================================= */
function apply() {
  // الخريطة
  document.querySelectorAll('.box').forEach(b => {
    const d = by(b.dataset.sop);
    b.classList.toggle('sel', state.sop === d.code);
    b.classList.remove('hlR', 'hlA', 'dim');
    if (state.dep) {
      const L = d.map[state.dep];
      if (L === 'A') b.classList.add('hlA');
      else if (L === 'R') b.classList.add('hlR');
      else if (!L) b.classList.add('dim');
    }
  });
  // المصفوفة
  document.querySelectorAll('#mx tr[data-sop]').forEach(tr => tr.classList.toggle('sel', tr.dataset.sop === state.sop));
  document.querySelectorAll('#mx [data-dep]').forEach(c => {
    const on = c.dataset.dep === state.dep;
    if (c.tagName === 'TH') c.classList.toggle('on', on); else c.classList.toggle('col-on', on);
  });
  $('#dep').value = state.dep;

  // التفاصيل
  const det = $('#detail');
  if (!state.sop) { det.hidden = true; return; }
  const d = by(state.sop);
  const list = L => who(d, L).map(k => `<span class="chip">${SHORT[k]}</span>`).join('') || '<span class="chip">—</span>';
  det.hidden = false;
  det.innerHTML = `<h4><span style="direction:ltr;display:inline-block;color:var(--brand)">${d.code}</span> · ${esc(d.title)}</h4>
    <div class="kv">
      <div><b style="color:var(--A)">يعتمد (A)</b>${list('A')}</div>
      <div><b style="color:var(--R)">ينفذ (R)</b>${list('R')}</div>
      <div><b style="color:var(--C)">يُستشار (C)</b>${list('C')}</div>
      <div><b style="color:var(--I)">يُبلغ (I)</b>${list('I')}</div>
    </div>
    <div class="dl">${(() => { const x = DOCS[d.code]; if (!x) return 'الإجراء غير مسجل في سجل الوثائق لهذا المخزن.';
      return (x.version ? `الإصدار الساري <b>${esc(x.version)}</b> منذ <span style="direction:ltr;display:inline-block">${esc(x.effective)}</span>` : (x.pending ? 'إصدار قيد المراجعة' : 'لم يُعتمد إصدار بعد'))
        + ` · <a href="${esc(x.url)}">فتح الوثيقة ←</a>`; })()}</div>`;
}

document.addEventListener('click', e => {
  const b = e.target.closest('.box, #mx tr[data-sop] td.sop');
  if (b) {
    const code = b.dataset.sop || b.closest('tr').dataset.sop;
    state.sop = state.sop === code ? null : code; apply(); return;
  }
  const th = e.target.closest('#mx th.dep');
  if (th) { state.dep = state.dep === th.dataset.dep ? '' : th.dataset.dep; apply(); }
});
document.addEventListener('keydown', e => {
  if ((e.key === 'Enter' || e.key === ' ') && e.target.classList.contains('box')) { e.preventDefault(); e.target.click(); }
});
$('#clear').addEventListener('click', () => { state.sop = null; state.dep = ''; apply(); });
$('#dep').addEventListener('change', e => { state.dep = e.target.value; apply(); });

/* =========================================================
   البدء
   ========================================================= */
$('#dep').innerHTML = `<option value="">— كل الأقسام —</option>` + DEPTS.map(([k, t]) => `<option value="${k}">${esc(t)}</option>`).join('');
renderMap();
renderMatrix();
apply();

@endverbatim
</script>
@endpush