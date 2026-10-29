{{-- تنسيق طباعة الوصف الوظيفي (A4) --}}
<style>
  .jd{max-width:900px;margin:0 auto;background:#fff;border:1px solid var(--line);border-radius:12px;padding:26px 30px;font-size:14px;line-height:1.85}
  .jd .hd{display:grid;grid-template-columns:1fr auto 1fr;align-items:center;border-bottom:3px solid #c08a1e;padding-bottom:8px;margin-bottom:12px;gap:10px}
  .jd .hd .tn{font-weight:700;color:#0f5e5a;font-size:15px}
  .jd .hd .tt{text-align:center}
  .jd .hd .tt b{display:block;font-size:20px;color:#0f5e5a}
  .jd .hd .tt span{font-size:12px;color:#5f6f6c;direction:ltr;display:inline-block}
  .jd .hd img{max-height:46px;justify-self:end}
  .jd h3{font-size:15px;color:#0f5e5a;border-bottom:1.5px solid #0f5e5a;padding-bottom:2px;margin:18px 0 8px}
  .jd h4{font-size:14px;margin:10px 0 4px;color:#24514d}
  .jd ul{margin:4px 0 6px;padding-inline-start:22px}
  .jd li{margin-bottom:2px}
  .jd table{width:100%;border-collapse:collapse;font-size:13px;margin:4px 0 8px}
  .jd th,.jd td{border:1px solid #9fb3b0;padding:5px 8px;text-align:start;vertical-align:top}
  .jd th{background:#e3f0ee;color:#0f5e5a}
  .jd td.k{background:#f2f7f6;font-weight:700;color:#24514d;width:26%}
  .jd .emp{background:#f6faf9;border:1px solid #d3e8e3;border-radius:10px;padding:10px 14px;margin-bottom:6px;display:grid;grid-template-columns:repeat(2,1fr);gap:4px 18px;font-size:13.5px}
  .jd .emp b{color:#0f5e5a}
  .jd .xtra{border:1.5px dashed #c08a1e;border-radius:10px;padding:8px 14px;margin-top:10px;background:#fffdf7}
  .jd .xtra h4{color:#9a6300}
  .jd .src{font-size:11.5px;color:#9a6300;font-weight:700}
  .jd .note{background:#f6f8f7;border-radius:8px;padding:6px 10px;font-size:13px}
  .tools{max-width:900px;margin:0 auto 12px;display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center}
  .tools form{display:flex;gap:6px;align-items:center;margin:0}
  .tools input[type=date]{width:auto;direction:ltr}
  .ack{color:var(--ok);font-weight:700}
  @page{size:A4;margin:12mm}
  @media print{
    header.top,footer.foot,.tools,main.wrap>.alert{display:none !important}
    main.wrap{padding:0 !important;max-width:none !important}
    body{background:#fff !important}
    .jd{border:0;padding:0;max-width:none;font-size:12px;line-height:1.7}
    .jd h3{break-after:avoid}
    .jd table{break-inside:avoid}
    .jd .xtra h4{break-after:avoid}
    *{-webkit-print-color-adjust:exact;print-color-adjust:exact}
  }
</style>