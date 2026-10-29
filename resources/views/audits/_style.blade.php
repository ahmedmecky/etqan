{{-- تنسيق شاشات المراجعة الخارجية --}}
<style>
  .au{max-width:1100px;margin:0 auto}
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:12px}
  .box{background:#fff;border:1px solid var(--line);border-radius:14px;padding:14px;margin-bottom:12px}
  .box h2{font-size:15.5px;margin:0 0 10px}
  .mf{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:10px}
  .mf label{display:flex;flex-direction:column;gap:4px;font-size:12.5px;font-weight:700;color:var(--muted)}
  .mf label input,.mf label select,.mf label textarea{font-weight:400;color:var(--ink,#1c2826)}
  .mf .full{grid-column:1 / -1} .mf .two{grid-column:span 2}
  @media (max-width:760px){ .mf{grid-template-columns:1fr 1fr} .mf .two{grid-column:1 / -1} }
  @media (max-width:480px){ .mf{grid-template-columns:1fr} }
  .new{background:#fff;border:1px dashed #b9d8d3;border-radius:12px;margin-bottom:12px}
  .new > summary{list-style:none;cursor:pointer;padding:11px 14px;font-weight:700;color:var(--brand)}
  .new > summary::-webkit-details-marker{display:none}
  .new .body{padding:0 14px 14px}
  .st{display:inline-block;font-size:11.5px;font-weight:700;border-radius:99px;padding:2px 9px;background:#f1f3f3;color:#5f6f6c;white-space:nowrap}
  .st.ok{background:#e5f4ea;color:#1e7a3c} .st.warn{background:#fdf3dc;color:#8a5a00} .st.bad{background:#fbe9e7;color:#b3261e}
  .gr{display:inline-block;font-size:11.5px;font-weight:700;border-radius:6px;padding:1px 8px;color:#fff;white-space:nowrap}
  .flow{display:flex;gap:4px;flex-wrap:wrap;margin:0 0 12px}
  .flow span{flex:1;min-width:110px;text-align:center;font-size:12.5px;font-weight:700;padding:7px 6px;border-radius:8px;background:#eef2f1;color:#7a8a87}
  .flow span.done{background:#d9efe9;color:#0f5e5a} .flow span.on{background:#0f5e5a;color:#fff}
  .lay{display:grid;grid-template-columns:240px minmax(0,1fr);gap:14px;align-items:start}
  @media (max-width:860px){ .lay{grid-template-columns:1fr} }
  .nav{background:#fff;border:1px solid var(--line);border-radius:14px;padding:6px;position:sticky;top:8px}
  .nav a{display:flex;align-items:center;gap:8px;padding:9px 10px;border-radius:9px;text-decoration:none;color:var(--ink,#1c2826);font-size:14px;font-weight:600}
  .nav a:hover{background:#f3f7f6}
  .nav a.on{background:#0f5e5a;color:#fff}
  .nav a .c{margin-inline-start:auto;font-size:11.5px;background:#eef2f1;color:#4d5f5b;border-radius:99px;padding:0 7px}
  .nav a.on .c{background:rgba(255,255,255,.2);color:#fff}
  .nav a .ok{color:var(--ok);font-weight:700}
  .nav a.on .ok{color:#bff0d6}
  .nav hr{border:0;border-top:1px solid var(--line);margin:6px 4px}
  @media (max-width:860px){ .nav{position:static;display:flex;flex-wrap:nowrap;overflow-x:auto;gap:4px} .nav a{white-space:nowrap} .nav hr{display:none} }
  .fd{background:#fff;border:1px solid var(--line);border-inline-start:5px solid #9fb3b0;border-radius:12px;margin-bottom:8px}
  .fd > summary{list-style:none;cursor:pointer;display:flex;gap:10px;align-items:flex-start;padding:10px 14px}
  .fd > summary::-webkit-details-marker{display:none}
  .fd .no{font-weight:700;color:var(--muted);min-width:26px}
  .fd .tx{flex:1;min-width:0;font-size:14px;line-height:1.7}
  .fd .tx small{display:block;font-size:12px;color:var(--muted)}
  .fd .body{border-top:1px solid #eef1f0;padding:12px 14px}
  .grades{display:flex;flex-wrap:wrap;gap:6px}
  .grades label{display:flex !important;flex-direction:row !important;align-items:center;gap:6px;border:1.5px solid var(--line);border-radius:10px;padding:7px 10px;cursor:pointer;font-size:13px !important;color:var(--ink,#1c2826) !important;background:#fff}
  .grades input{accent-color:var(--brand)}
  .grades label:has(input:checked){border-color:var(--brand);background:#f0f8f6}
  .grades i{width:12px;height:12px;border-radius:3px;display:inline-block}
  .mx{width:100%;border-collapse:collapse;font-size:13px}
  .mx th,.mx td{border:1px solid var(--line);padding:6px 8px;text-align:center}
  .mx th{background:#f3f7f6}
  .mx td:first-child,.mx th:first-child{text-align:start}
  .hint{font-size:12.5px;color:var(--muted);line-height:1.8}
  .empty{background:#fff;border:1px dashed var(--line);border-radius:12px;padding:22px;text-align:center;color:var(--muted)}
  a.rl{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,2.4fr) minmax(0,1.3fr) minmax(0,1.6fr) minmax(0,1fr);gap:10px;align-items:center;background:#fff;border:1px solid var(--line);border-inline-start:5px solid #d9b25a;border-radius:12px;padding:11px 14px;margin-bottom:8px;color:inherit;text-decoration:none}
  a.rl.closed{border-inline-start-color:var(--ok);opacity:.85}
  a.rl:hover{background:#f7faf9}
  a.rl b{display:block;font-size:14.5px} a.rl small{display:block;font-size:12px;color:var(--muted)}
  @media (max-width:760px){ a.rl{grid-template-columns:1fr 1fr} }
</style>