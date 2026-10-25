<!doctype html>
<html lang="ar" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ $title ?? 'شكوى' }} · {{ $tenant->name_ar }}</title>
<style>
  :root{--bg:#f4f6f5;--card:#fff;--ink:#1c2826;--muted:#5f6f6c;--line:#dde3e1;--brand:#0f5e5a;--brand-soft:#e3f0ee;--gold:#c08a1e;--ok:#1e7a46;--ok-soft:#e4f4ea;--bad:#b3261e;--bad-soft:#fbe7e5}
  *{box-sizing:border-box}
  body{margin:0;background:var(--bg);color:var(--ink);font-family:"Cairo","Segoe UI",Tahoma,sans-serif;font-size:15px;line-height:1.6}
  .top{background:#fff;border-bottom:3px solid var(--gold);padding:14px 16px}
  .top .in{max-width:640px;margin:0 auto;display:flex;align-items:center;gap:12px}
  .top img{height:46px;width:auto;max-width:120px;object-fit:contain}
  .top b{display:block;font-size:17px;color:var(--brand)}
  .top span{font-size:13px;color:var(--muted)}
  main{max-width:640px;margin:0 auto;padding:16px}
  .card{background:var(--card);border:1px solid var(--line);border-radius:12px;padding:16px;margin-bottom:14px}
  h1{font-size:20px;margin:0 0 4px}
  h2{font-size:16px;margin:0 0 12px;color:var(--brand)}
  .sub{color:var(--muted);font-size:13.5px;margin:0 0 6px}
  label.f{display:block;font-size:13.5px;font-weight:700;margin:12px 0 5px}
  .req::after{content:" *";color:var(--bad)}
  input[type=text],input[type=tel],input[type=number],select,textarea{width:100%;padding:11px 12px;border:1px solid var(--line);border-radius:10px;font:inherit;font-size:16px;background:#fff;color:var(--ink)}
  input:focus,select:focus,textarea:focus{outline:2px solid var(--brand-soft);border-color:var(--brand)}
  textarea{min-height:100px;resize:vertical}
  .ltr{direction:ltr;text-align:right}
  .btn{display:inline-block;background:var(--brand);color:#fff;border:0;border-radius:10px;padding:13px 20px;font:inherit;font-weight:700;font-size:16px;cursor:pointer;text-decoration:none}
  .btn.block{width:100%}
  .btn.ghost{background:#fff;color:var(--brand);border:1px solid var(--brand);padding:9px 14px;font-size:14px}
  .choice{display:grid;grid-template-columns:1fr 1fr;gap:10px}
  .choice label{border:2px solid var(--line);border-radius:12px;padding:14px 10px;text-align:center;font-weight:700;cursor:pointer;background:#fff}
  .choice input{display:none}
  .choice label:has(input:checked){border-color:var(--brand);background:var(--brand-soft);color:var(--brand)}
  .chips{display:flex;flex-wrap:wrap;gap:8px}
  .chips label{border:1px solid var(--line);border-radius:99px;padding:8px 14px;cursor:pointer;background:#fff;font-weight:600;font-size:14px}
  .chips input{display:none}
  .chips label:has(input:checked){border-color:var(--brand);background:var(--brand);color:#fff}
  .ph{margin-top:6px;font-size:14px;border-radius:8px;padding:8px 10px}
  .ph.ok{background:var(--ok-soft);color:var(--ok);font-weight:700}
  .ph.bad{background:var(--bad-soft);color:var(--bad)}
  .errors{background:var(--bad-soft);color:var(--bad);border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:14px}
  .errors ul{margin:4px 0 0;padding-inline-start:18px}
  .item{border:1px solid var(--line);border-radius:12px;padding:12px;margin-bottom:10px;background:#fbfcfc;position:relative}
  .item .x{position:absolute;top:6px;left:8px;background:none;border:0;color:var(--bad);font-size:22px;cursor:pointer}
  .pick{position:relative}
  .pick .list{position:absolute;inset-inline:0;top:100%;z-index:5;background:#fff;border:1px solid var(--line);border-radius:10px;max-height:260px;overflow:auto;box-shadow:0 8px 20px rgba(0,0,0,.08)}
  .pick .list div{padding:10px 12px;border-bottom:1px solid #eef1f0;cursor:pointer;display:flex;justify-content:space-between;gap:10px}
  .pick .list div:hover,.pick .list div.on{background:var(--brand-soft)}
  .pick .list .pr{color:var(--muted);direction:ltr;white-space:nowrap}
  .chosen{margin-top:6px;font-weight:700;color:var(--brand);font-size:14px}
  .row2{display:grid;grid-template-columns:2fr 1fr;gap:10px}
  .hp{position:absolute;width:1px;height:1px;overflow:hidden;clip-path:inset(50%);opacity:0;pointer-events:none}
  .foot{text-align:center;color:var(--muted);font-size:12px;padding:10px 0 30px}
  .foot b{color:var(--brand)}
  [hidden]{display:none !important}
</style>
</head>
<body>
<div class="top"><div class="in">
  @if ($tenant->logoUrl())<img src="{{ $tenant->logoUrl() }}" alt="">@endif
  <div><b>{{ $tenant->name_ar }}</b><span>{{ $title ?? '' }}</span></div>
</div></div>