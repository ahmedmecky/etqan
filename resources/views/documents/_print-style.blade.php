{{-- تنسيق طباعة وثائق مكتبة إتقان (إجراء · مؤشرات) بشعار المخزن: A4 --}}
<style>
  .pd{max-width:960px;margin:0 auto;background:#fff;border:1px solid var(--line);border-radius:12px;padding:24px 28px;font-size:13.5px;line-height:1.8;position:relative}
  .pd .hd{display:grid;grid-template-columns:1.25fr 1.3fr .85fr;align-items:center;gap:12px;border-bottom:3px solid #c08a1e;padding-bottom:8px;margin-bottom:10px}
  .pd .hd .tn{display:flex;align-items:center;gap:10px}
  .pd .hd .tn img{max-height:52px;max-width:90px;object-fit:contain}
  .pd .hd .tn b{display:block;font-size:14.5px;color:#0f5e5a;line-height:1.35}
  .pd .hd .tn small{display:block;font-size:10.5px;color:#5f6f6c}
  .pd .hd .tt{text-align:center;line-height:1.45}
  .pd .hd .tt span{display:block;font-size:12px;color:#0f5e5a;font-weight:700}
  .pd .hd .tt b{display:block;font-size:17px}
  .pd .hd .tt small{display:block;font-size:11px;color:#5f6f6c;direction:ltr}
  .pd .hd .mt{font-size:11.5px;line-height:1.7;justify-self:end;text-align:start}
  .pd .hd .mt b{direction:ltr;display:inline-block}
  .pd .dates{width:100%;border-collapse:collapse;font-size:12px;margin-bottom:6px}
  .pd .dates td{border:1px solid #b9cbc7;padding:4px 8px}
  .pd .dates td.k{background:#e3f0ee;color:#0f5e5a;font-weight:700;width:17%}
  .pd h3{font-size:14.5px;color:#0f5e5a;border-bottom:1.5px solid #0f5e5a;padding-bottom:2px;margin:16px 0 7px;break-after:avoid}
  .pd h3 i{font-style:normal;color:#c08a1e;margin-inline-end:4px}
  .pd h4{font-size:13.5px;margin:8px 0 4px;color:#0f5e5a}
  .pd p{margin:3px 0}
  .pd p.mut{color:#5f6f6c;font-size:12.5px}
  .pd ul{margin:3px 0 6px;padding-inline-start:22px}
  .pd li{margin-bottom:2px}
  .pd table.t2{width:100%;border-collapse:collapse;font-size:12.5px;margin:4px 0 8px}
  .pd .t2 th,.pd .t2 td{border:1px solid #9fb3b0;padding:4px 7px;text-align:start;vertical-align:top}
  .pd .t2 th{background:#0f5e5a;color:#fff;font-weight:700}
  .pd .t2 td.k{background:#e3f0ee;font-weight:700;color:#0f5e5a;width:24%}
  .pd .t2 td.c,.pd .t2 th.c{text-align:center}
  .pd .t2 tr.g td{background:#e3f0ee;color:#0f5e5a;font-weight:700}
  .pd .t2 tr{break-inside:avoid}
  .pd .sg td{height:30px}
  .pd .sg .ok{color:#1e7a46;font-weight:700}
  .pd .fm{break-before:page;page-break-before:always;margin-top:26px;padding-top:12px;border-top:2px dashed #c9d3d1}
  .pd .fm.first-only{break-before:auto;page-break-before:auto;border-top:0;margin-top:0;padding-top:0}
  .pd .fh{display:flex;justify-content:space-between;align-items:center;gap:10px;border-bottom:2px solid #0f5e5a;padding-bottom:5px;margin-bottom:8px}
  .pd .fh b{font-size:15px;color:#0f5e5a}
  .pd .fh b span{color:#c08a1e;direction:ltr;display:inline-block;margin-inline-end:6px}
  .pd .fh small{font-size:11px;color:#5f6f6c;text-align:left}
  .pd .fh img{max-height:34px;max-width:70px;object-fit:contain}
  .pd .grid td{height:26px}
  .pd .foot{margin-top:18px;border-top:1px solid #0f5e5a;padding-top:6px;font-size:10.5px;color:#5f6f6c;text-align:center}
  .pd .wm{position:absolute;inset:0;pointer-events:none;overflow:hidden}
  .pd .wm span{position:absolute;top:30%;left:50%;transform:translate(-50%,-50%) rotate(-28deg);font-size:72px;font-weight:700;color:rgba(179,38,30,.10);white-space:nowrap}
  .ptools{max-width:960px;margin:0 auto 12px;display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap;align-items:center}
  .ptools .fl{display:flex;gap:6px;flex-wrap:wrap;align-items:center;font-size:13px}
  .ptools .fl a{border:1px solid var(--line);border-radius:99px;padding:2px 10px;text-decoration:none;font-size:12.5px;background:#fff}
  .ptools .warn{background:#fdf1dc;color:#7a4f00;border-radius:8px;padding:4px 10px;font-size:13px;font-weight:700}
  @page{size:A4;margin:11mm}
  @media print{
    header.top,footer.foot,.ptools,main.wrap>.alert{display:none !important}
    main.wrap{padding:0 !important;max-width:none !important}
    body{background:#fff !important}
    .pd{border:0;padding:0;max-width:none;font-size:11.5px;line-height:1.65;border-radius:0}
    .pd .wm{position:fixed}
    .pd .fm{border-top:0;margin-top:0;padding-top:0}
    *{-webkit-print-color-adjust:exact;print-color-adjust:exact}
  }
</style>