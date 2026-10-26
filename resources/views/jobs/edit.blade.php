@extends('layouts.app')

@section('title', $job['title'])

@push('head')
<style>
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:14px}
  .code{font-weight:700;color:var(--brand);direction:ltr;display:inline-block}
  .modes{display:grid;grid-template-columns:repeat(3,1fr);gap:10px}
  @media (max-width:700px){.modes{grid-template-columns:1fr}}
  .modes label{border:2px solid var(--line);border-radius:10px;padding:10px 12px;cursor:pointer;font-weight:700;background:#fff}
  .modes label small{display:block;font-weight:400;color:var(--muted);font-size:12px}
  .modes input{display:none}
  .modes label:has(input:checked){border-color:var(--brand);background:#eef6f5}
  .ph{display:grid;grid-template-columns:1fr 220px;gap:10px;align-items:center;padding:7px 0;border-bottom:1px solid #eef1f0}
  .ph:last-child{border:0}
  .ph .c{font-size:13px} .ph .c span{color:var(--muted);font-size:11.5px;display:block}
  .grp{border:1px solid var(--line);border-radius:10px;padding:10px 12px;margin-bottom:10px}
  .grp .h{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
  .grp ul{margin:6px 0 0;padding-inline-start:20px;font-size:12.5px;color:var(--muted)}
  .grp select{width:auto;min-width:260px}
  .warnbox{background:#fff6e8;border:1px solid #f6e2bf;color:#7a5200;border-radius:10px;padding:10px 14px;font-size:13.5px;margin-bottom:12px}
</style>
@endpush

@section('content')
  <div class="head-actions">
    <div>
      <h1 class="page-title"><span class="code">{{ $code }}</span> · {{ $job['title'] }}</h1>
      <p class="page-sub" style="margin:0">{{ $job['dept'] }} · المدير المباشر: {{ $job['manager'] }} · الشاغلون حالياً: <b>{{ $count }}</b></p>
    </div>
    <div class="actions">
      <a href="{{ route('jobs.print', $code) }}" class="btn ghost" target="_blank">معاينة الطباعة</a>
      <a href="{{ route('jobs.index') }}" class="btn ghost">الهيكل الوظيفي</a>
    </div>
  </div>

  <form method="POST" action="{{ route('jobs.update', $code) }}">
    @csrf
    @method('PUT')
    <div class="card">
      <h2 style="font-size:16px;margin:0 0 10px">الوظيفة في مخزنك</h2>
      <div class="modes">
        @foreach ($modes as $k => $l)
          <label><input type="radio" name="mode" value="{{ $k }}" {{ old('mode', $t->mode) === $k ? 'checked' : '' }}>{{ $l }}
            <small>{{ ['staffed' => 'موظف أو أكثر يشغلها بهذا الوصف', 'covered' => 'لا يوجد موظف لها، ومهامها يقوم بها زملاء', 'na' => 'لا تُطبع ولا تُحسب (مثل: لا يوجد بيع قطاعي)'][$k] }}</small></label>
        @endforeach
      </div>
      <div class="grid" style="margin-top:12px;max-width:640px">
        <div><label class="f" for="planned">العدد المخطط</label><input type="number" id="planned" name="planned" min="0" value="{{ old('planned', $t->planned) }}" class="ltr-in"></div>
        <div style="grid-column:span 2"><label class="f" for="note">ملاحظة محلية (تُطبع في الوصف)</label><input type="text" id="note" name="note" value="{{ old('note', $t->note) }}"></div>
      </div>
    </div>

    <div class="card">
      <h2 style="font-size:16px;margin:0 0 4px">توزيع المهام عند الشغور</h2>
      @if ($needs)
        <div class="warnbox">هذه الوظيفة بدون شاغل حالياً. حدد من يقوم بكل مجموعة من مهامها؛ تُضاف تلقائياً للوصف الوظيفي المطبوع لكل من يشغل الوظيفة المختارة، تحت «مهام إضافية مكلف بها».</div>
      @else
        <p class="page-sub">الوظيفة لها شاغل حالياً. التوزيع يُطبق فقط إذا أصبحت شاغرة أو اخترت «بدون موظف».</p>
      @endif
      @foreach ($groups as $g)
        <div class="grp">
          <div class="h">
            <b>{{ $g['key'] }}. {{ $g['title'] }}</b>
            <select name="reassign[{{ $g['key'] }}]">
              <option value="">— لم تُوزع —</option>
              @foreach ($targets as $c => $label)
                <option value="{{ $c }}" {{ old('reassign.' . $g['key'], $t->reassign[$g['key']] ?? '') === $c ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <ul>@foreach ($g['items'] as $it)<li>{{ $it }}</li>@endforeach</ul>
        </div>
      @endforeach
      @if (! count($targets))<p class="page-sub">لا توجد وظائف لها موظفون فعالون بعد. حدد وظائف المستخدمين أولاً.</p>@endif
    </div>

    <div class="card">
      <h2 style="font-size:16px;margin:0 0 4px">الفراغات في نص الوصف</h2>
      <p class="page-sub">نص الوصف معتمد ولا يُعدل. اكتب هنا قيم الفراغات الخاصة بمخزنك؛ الفارغ يُطبع خطاً للكتابة اليدوية.</p>
      @foreach ($placeholders as $i => $p)
        <div class="ph">
          <div class="c">{{ $p['ctx'] ?: '—' }} <b style="direction:ltr;display:inline-block">{{ $p['raw'] }}</b><span>{{ $p['sec'] }}</span></div>
          <input type="text" name="blanks[{{ $i }}]" value="{{ old('blanks.' . $i, $t->blanks[$i] ?? \App\Support\JobLibrary::defaultFor($p['raw'], $tenant)) }}">
        </div>
      @endforeach
    </div>

    <div class="actions"><button type="submit" class="btn">حفظ</button><a href="{{ route('jobs.index') }}" class="btn ghost">إلغاء</a></div>
  </form>
@endsection