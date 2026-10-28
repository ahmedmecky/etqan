{{-- تنسيق شاشات السحب --}}
<style>
  .rc{max-width:1060px;margin:0 auto}
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:12px}
  .box{background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px;margin-bottom:12px}
  .box h2{font-size:15.5px;margin:0 0 10px}
  .mf{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
  .mf label{display:flex;flex-direction:column;gap:4px;font-size:12.5px;font-weight:700;color:var(--muted)}
  .mf label input,.mf label select,.mf label textarea{font-weight:400;color:var(--ink,#1c2826)}
  .mf .full{grid-column:1 / -1}
  @media (max-width:760px){ .mf{grid-template-columns:1fr 1fr} }
  @media (max-width:480px){ .mf{grid-template-columns:1fr} }
  .new{background:#fff;border:1px dashed #b9d8d3;border-radius:12px;margin-bottom:12px}
  .new > summary{list-style:none;cursor:pointer;padding:11px 14px;font-weight:700;color:var(--brand)}
  .new > summary::-webkit-details-marker{display:none}
  .new .body{padding:0 14px 14px}
  .st{display:inline-block;font-size:11.5px;font-weight:700;border-radius:99px;padding:2px 9px;background:#f1f3f3;color:#5f6f6c;white-space:nowrap}
  .st.ok{background:#e5f4ea;color:#1e7a3c} .st.warn{background:#fdf3dc;color:#8a5a00} .st.bad{background:#fbe9e7;color:#b3261e} .st.c1{background:#b3261e;color:#fff}
  .chips{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:12px}
  .chips div{background:#fff;border:1px solid var(--line);border-radius:12px;padding:8px 12px;font-size:12.5px;color:var(--muted)}
  .chips div b{display:block;font-size:18px;color:var(--ink,#1c2826)}
  .chips div.bad b{color:var(--bad)} .chips div.ok b{color:var(--ok)}
  .row{background:#fff;border:1px solid var(--line);border-inline-start:5px solid #d5dedb;border-radius:12px;margin-bottom:8px}
  .row.none,.row.has{border-inline-start-color:var(--ok)} .row.pending{border-inline-start-color:#d9b25a} .row.late{border-inline-start-color:var(--bad)}
  .row > summary{list-style:none;cursor:pointer;display:grid;grid-template-columns:minmax(0,2.2fr) minmax(0,1fr) minmax(0,1.4fr) minmax(0,1.6fr) 18px;gap:10px;align-items:center;padding:10px 14px}
  .row > summary::-webkit-details-marker{display:none}
  .row summary b{display:block;font-size:14.5px;line-height:1.45}
  .row summary small{display:block;font-size:12px;color:var(--muted);line-height:1.6}
  .row .ar{color:var(--muted);transition:transform .15s}
  .row[open] .ar{transform:rotate(180deg)}
  .row .body{border-top:1px solid #eef1f0;padding:12px 14px}
  @media (max-width:760px){
    .row > summary{grid-template-columns:1fr auto;row-gap:4px}
    .row > summary > :nth-child(2),.row > summary > :nth-child(3),.row > summary > :nth-child(4){grid-column:1 / 2}
    .row > summary .ar{grid-row:1;grid-column:2}
  }
  .quick{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:10px;align-items:center}
  .dec{display:flex;gap:6px}
  .dec label{flex:1;flex-direction:row !important;justify-content:center;align-items:center;border:1px solid var(--line);border-radius:10px;padding:10px 6px;cursor:pointer;font-size:13.5px !important;color:var(--ink,#1c2826) !important;background:#fff}
  .dec input{position:absolute;opacity:0;pointer-events:none}
  .dec label:has(input[value=none]:checked){background:var(--ok);border-color:var(--ok);color:#fff !important}
  .dec label:has(input[value=has]:checked){background:#0f6e64;border-color:#0f6e64;color:#fff !important}
  .dec label:has(input[value=pending]:checked){background:#d9b25a;border-color:#d9b25a;color:#fff !important}
  .kv{display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:6px 14px;font-size:13.5px}
  .kv div span{display:block;font-size:12px;color:var(--muted)}
  .hint{font-size:12.5px;color:var(--muted);line-height:1.8}
  .empty{background:#fff;border:1px dashed var(--line);border-radius:12px;padding:22px;text-align:center;color:var(--muted)}
  a.rl{display:grid;grid-template-columns:minmax(0,1.1fr) minmax(0,2.2fr) minmax(0,1.2fr) minmax(0,1.4fr) minmax(0,1fr);gap:10px;align-items:center;background:#fff;border:1px solid var(--line);border-inline-start:5px solid #d9b25a;border-radius:12px;padding:11px 14px;margin-bottom:8px;color:inherit;text-decoration:none}
  a.rl.closed{border-inline-start-color:var(--ok);opacity:.8} a.rl.late{border-inline-start-color:var(--bad)}
  a.rl:hover{background:#f7faf9}
  a.rl b{display:block;font-size:14.5px} a.rl small{display:block;font-size:12px;color:var(--muted)}
  @media (max-width:760px){ a.rl{grid-template-columns:1fr 1fr} }
</style>