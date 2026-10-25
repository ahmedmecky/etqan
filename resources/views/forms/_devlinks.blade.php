{{--
  ربط الانحرافات (SH-008) بأي مستند: قائمة الانحرافات المرتبطة برقم المستند + زر «تسجيل انحراف»
  يفتح شاشة الانحراف في تبويب جديد بالبيانات معبأة (المصدر · الإجراء · القسم · الوصف · رقم المستند)
  ورابط رجوع للمستند. الاستخدام داخل @push('scripts') بعد تعريف BOOT:
    @include('forms._devlinks')
    DevLinks.mount({ el: '#devLinks', ref: () => C.no, saved: () => !!C.id, prefill: () => ({...}), onLinks: list => {} });
    DevLinks.refresh();   // بعد فتح سجل أو حفظه أو سجل جديد
--}}
<style>
@verbatim
  .dl{border:1px dashed #c9d3d1;border-radius:10px;padding:10px 12px;margin-top:14px;background:#fbfcfc}
  .dl-h{display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap}
  .dl-h b{font-size:14px;color:#0f5e5a}
  .dl-add{background:#b3261e;color:#fff;border:1px solid #b3261e;border-radius:8px;padding:5px 12px;font-size:13px;font-weight:700;text-decoration:none;white-space:nowrap}
  .dl-add.off{background:#e9eeec;color:#8a9895;border-color:#e9eeec;pointer-events:none}
  .dl-list{margin-top:8px;display:flex;flex-direction:column;gap:6px}
  .dl-row{display:flex;gap:8px;align-items:center;flex-wrap:wrap;font-size:13px;text-decoration:none;color:inherit;padding:6px 8px;border-radius:8px;background:#fff;border:1px solid #e3e8e6}
  .dl-row:hover{border-color:#0f5e5a}
  .dl-row .n{font-weight:700;direction:ltr;color:#0f5e5a}
  .dl-row .st{border-radius:99px;padding:1px 9px;font-size:11.5px;font-weight:700;background:#fdf1dc;color:#9a6300}
  .dl-row .st.closed{background:#e4f4ea;color:#1e7a46}
  .dl-row .st.late{background:#fbe7e5;color:#b3261e}
  .dl-row .w{color:#5f6f6c;font-size:12px;flex:1;min-width:160px}
  .dl-none{font-size:12.5px;color:#5f6f6c;margin-top:6px}
  @media print{.dl{display:none !important}}
@endverbatim
</style>
<script>
@verbatim
window.DevLinks = (function () {
  let O = null, LINKS = [], lastRef = null;
  const e = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

  function addUrl() {
    const ref = (O.ref() || '').trim();
    const p = Object.assign({}, O.prefill ? O.prefill() : {});
    const q = new URLSearchParams();
    q.set('ref', ref);
    ['source', 'sop', 'dept', 'what'].forEach(k => { if (p[k]) q.set(k, String(p[k]).slice(0, 1500)); });
    q.set('back', location.pathname + '?open=' + encodeURIComponent(ref));
    return (BOOT.urls.deviation || '#') + '?' + q.toString();
  }

  function paint() {
    const box = document.querySelector(O.el); if (!box) return;
    const ref = (O.ref() || '').trim();
    const ok = !!ref && O.saved();
    box.innerHTML = `
      <div class="dl">
        <div class="dl-h"><b>الانحرافات المرتبطة (SH-008)${LINKS.length ? ' · ' + LINKS.length : ''}</b>
          <a class="dl-add ${ok ? '' : 'off'}" href="${ok ? e(addUrl()) : '#'}" target="_blank" rel="noopener" data-dl="add">+ تسجيل انحراف</a></div>
        ${LINKS.length ? `<div class="dl-list">${LINKS.map(l => `
          <a class="dl-row" href="${e((BOOT.urls.deviation || '#') + '?open=' + encodeURIComponent(l.number))}" target="_blank" rel="noopener">
            <span class="n">${e(l.number)}</span>
            <span class="st ${l.stage === 'closed' ? 'closed' : l.late ? 'late' : ''}">${e(l.label)}${l.late && l.stage !== 'closed' ? ' · متأخر' : ''}</span>
            ${l.cls ? `<span class="st">${e(l.cls)}</span>` : ''}
            <span class="w">${e(l.what)}</span></a>`).join('')}</div>`
        : `<div class="dl-none">${ok ? 'لا يوجد انحراف مسجل لهذا المستند. سجل انحرافاً لأي عدم مطابقة تحتاج تحقيقاً وإجراءً تصحيحياً.' : 'احفظ المستند أولاً لتسجيل انحراف مرتبط به.'}</div>`}
      </div>`;
  }

  async function refresh() {
    if (!O) return;
    const ref = (O.ref() || '').trim();
    lastRef = ref;
    if (!ref || !O.saved() || !BOOT.urls.devlinks) { LINKS = []; paint(); return; }
    try {
      const res = await fetch(BOOT.urls.devlinks + '?ref=' + encodeURIComponent(ref), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
      const json = res.ok ? await res.json() : { links: [] };
      if (ref !== lastRef) return;
      LINKS = json.links || [];
    } catch (err) { LINKS = []; }
    paint();
    if (O.onLinks) O.onLinks(LINKS);
  }

  function mount(opts) {
    O = opts;
    paint();
    // عند الرجوع من تبويب الانحراف: تحديث القائمة
    document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') refresh(); });
    window.addEventListener('focus', () => refresh());
  }

  return { mount, refresh, links: () => LINKS };
})();

/* فتح سجل برقمه من الرابط (?open=RCV-2026-001) — للرجوع من شاشة الانحراف */
window.openFromQuery = async function (list, openFn) {
  const v = new URLSearchParams(location.search).get('open');
  if (!v) return false;
  const m = list.find(x => x.number === v || String(x.id) === v);
  if (!m) return false;
  await openFn(m.id);
  return true;
};
@endverbatim
</script>