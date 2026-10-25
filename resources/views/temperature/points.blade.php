@extends('layouts.app')

@section('title', 'نقاط مراقبة الحرارة')

@push('head')
<style>
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:14px}
  .hint{font-size:12.5px;color:var(--muted);line-height:1.8}
  .err{color:var(--bad);font-size:13px;margin-top:6px}
  table.t td{vertical-align:middle}
  table.t td input,table.t td select{padding:5px 7px;font-size:13px}
  table.t td input.n{width:70px;direction:ltr;text-align:center}
  .acts{display:flex;gap:4px}
  .acts .btn{padding:4px 10px;font-size:12.5px}
  tr.off td{opacity:.5}
  .add{display:grid;grid-template-columns:1.6fr 1.2fr 1fr repeat(5,.7fr) auto;gap:8px;align-items:end}
  @media (max-width:1000px){.add{grid-template-columns:1fr 1fr}}
  .add input.n{direction:ltr;text-align:center}
</style>
@endpush

@section('content')
  <div class="head-actions">
    <div>
      <h1 class="page-title">نقاط مراقبة الحرارة والرطوبة</h1>
      <p class="page-sub" style="margin:0">مناطق التخزين والثلاجات وغرف التبريد وحدود كل منها · <span class="ltr">SH-030</span></p>
    </div>
    <div class="actions">
      <a href="{{ route('temperature.round') }}" class="btn">تسجيل القراءات</a>
      <a href="{{ route('temperature.report') }}" class="btn ghost">التقرير الشهري</a>
    </div>
  </div>

  <div class="card">
    <div class="hint">
      الحدود الافتراضية من الإجراء: مناطق الغرفة <b>١٥–٢٥°م</b> ورطوبة لا تزيد عن <b>٦٥٪</b> (إنذار مبكر ٢٤°م) · الثلاجات وغرف التبريد <b>٢–٨°م</b> (إنذار مبكر ٣ و٧°م).
      أي قراءة (الحالية أو الأعلى أو الأدنى) خارج الحدود = انحراف حرارة يحتاج تقرير F03، حتى لو عادت الحرارة تلقائياً.
      اترك حقل الرطوبة فارغاً إذا كانت النقطة لا تُقاس رطوبتها.
    </div>
  </div>

  @if ($canManage)
    <div class="card">
      <h2 style="font-size:16px;margin:0 0 10px">إضافة نقطة</h2>
      <form method="POST" action="{{ route('temperature.points.store') }}" class="add" id="addForm">
        @csrf
        <div><label class="f req">الاسم</label><input type="text" name="name" value="{{ old('name') }}" placeholder="مثال: الثلاجة ٢" required></div>
        <div><label class="f req">النوع</label>
          <select name="type" id="addType">@foreach ($types as $k => $t)<option value="{{ $k }}" {{ old('type') === $k ? 'selected' : '' }}>{{ $t }}</option>@endforeach</select></div>
        <div><label class="f">رقم الجهاز</label><input type="text" name="device_no" value="{{ old('device_no') }}" class="ltr-in"></div>
        <div><label class="f req">الأدنى °م</label><input type="number" step="0.1" name="t_min" value="{{ old('t_min', 15) }}" class="n" required></div>
        <div><label class="f req">الأعلى °م</label><input type="number" step="0.1" name="t_max" value="{{ old('t_max', 25) }}" class="n" required></div>
        <div><label class="f">إنذار أدنى</label><input type="number" step="0.1" name="warn_low" value="{{ old('warn_low') }}" class="n"></div>
        <div><label class="f">إنذار أعلى</label><input type="number" step="0.1" name="warn_high" value="{{ old('warn_high', 24) }}" class="n"></div>
        <div><label class="f">رطوبة ≤ ٪</label><input type="number" step="1" name="h_max" value="{{ old('h_max', 65) }}" class="n"></div>
        <button type="submit" class="btn">إضافة</button>
      </form>
      @if ($errors->any())@foreach ($errors->all() as $e)<div class="err">{{ $e }}</div>@endforeach @endif
      @if ($points->isEmpty())
        <form method="POST" action="{{ route('temperature.points.seed') }}" style="margin-top:12px">
          @csrf
          <span class="hint">أو ابدأ بنقاط جاهزة (منطقة التخزين العامة · منطقة الاستلام والحجر · الثلاجة ١) ثم عدّلها:</span>
          <button type="submit" class="btn ghost small">إضافة نقاط البداية</button>
        </form>
      @endif
    </div>
  @endif

  <div class="card">
    <div class="tbl-wrap">
      <table class="t" style="min-width:980px">
        <thead><tr><th>ترتيب</th><th>الاسم</th><th>النوع</th><th>رقم الجهاز</th><th>الأدنى</th><th>الأعلى</th><th>إنذار أدنى</th><th>إنذار أعلى</th><th>رطوبة ≤</th><th></th></tr></thead>
        <tbody>
          @forelse ($points as $p)
            <tr class="{{ $p->is_active ? '' : 'off' }}">
              @if ($canManage)
                <td><input form="p{{ $p->id }}" type="number" name="sort" value="{{ $p->sort }}" class="n" style="width:56px"></td>
                <td><input form="p{{ $p->id }}" type="text" name="name" value="{{ $p->name }}" required></td>
                <td><select form="p{{ $p->id }}" name="type">@foreach ($types as $k => $t)<option value="{{ $k }}" {{ $p->type === $k ? 'selected' : '' }}>{{ $t }}</option>@endforeach</select></td>
                <td><input form="p{{ $p->id }}" type="text" name="device_no" value="{{ $p->device_no }}" class="ltr-in" style="width:110px"></td>
                @foreach (['t_min', 't_max', 'warn_low', 'warn_high'] as $k)
                  <td><input form="p{{ $p->id }}" type="number" step="0.1" name="{{ $k }}" value="{{ \App\Models\TempPoint::n($p->$k) }}" class="n"></td>
                @endforeach
                <td><input form="p{{ $p->id }}" type="number" step="1" name="h_max" value="{{ $p->h_max }}" class="n"></td>
                <td>
                  <div class="acts">
                    <form id="p{{ $p->id }}" method="POST" action="{{ route('temperature.points.update', $p) }}" style="margin:0">
                      @csrf
                      @method('PUT')
                      <button type="submit" class="btn ghost">حفظ</button>
                    </form>
                    <form method="POST" action="{{ route('temperature.points.toggle', $p) }}" style="margin:0">
                      @csrf
                      <button type="submit" class="btn {{ $p->is_active ? 'danger' : 'ghost' }}">{{ $p->is_active ? 'إيقاف' : 'تفعيل' }}</button>
                    </form>
                  </div>
                </td>
              @else
                <td>{{ $p->sort }}</td><td><b>{{ $p->name }}</b></td><td>{{ $p->typeLabel() }}</td><td class="ltr">{{ $p->device_no ?: '—' }}</td>
                <td class="ltr">{{ \App\Models\TempPoint::n($p->t_min) }}</td><td class="ltr">{{ \App\Models\TempPoint::n($p->t_max) }}</td>
                <td class="ltr">{{ \App\Models\TempPoint::n($p->warn_low) ?: '—' }}</td><td class="ltr">{{ \App\Models\TempPoint::n($p->warn_high) ?: '—' }}</td>
                <td class="ltr">{{ $p->h_max ? $p->h_max . '%' : '—' }}</td><td>{{ $p->is_active ? '' : 'موقوفة' }}</td>
              @endif
            </tr>
          @empty
            <tr><td colspan="10" style="text-align:center;color:var(--muted);padding:24px">لا توجد نقاط مراقبة بعد.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection

@push('scripts')
<script>
(function () {
  const D = @json($defaults);
  const t = document.getElementById('addType'); if (!t) return;
  const f = document.getElementById('addForm');
  t.addEventListener('change', () => {
    const d = D[t.value] || {};
    ['t_min', 't_max', 'warn_low', 'warn_high', 'h_max'].forEach(k => { f.elements[k].value = d[k] ?? ''; });
  });
})();
</script>
@endpush