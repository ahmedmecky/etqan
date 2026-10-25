@extends('layouts.app')

@section('title', $t['title'])

@push('head')
<style>
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:14px}
  .two{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:16px}
  @media (max-width:900px){.two{grid-template-columns:1fr}}
  .two .card{margin:0}
  .hint{font-size:12.5px;color:var(--muted);line-height:1.8}
  .up{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:10px}
  .warn-box{background:#fdf1dc;color:#9a6300;border-radius:8px;padding:8px 12px;font-size:13px;margin-top:10px}
  .add{display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:8px;align-items:end}
  @media (max-width:700px){.add{grid-template-columns:1fr}}
  .search{display:flex;gap:8px;max-width:420px;margin-bottom:12px}
  table.t td{vertical-align:middle}
  table.t td input{padding:5px 7px;font-size:13px}
  .row-form{display:contents}
  .num{direction:ltr;text-align:right}
  .acts{display:flex;gap:4px}
  .acts .btn{padding:4px 10px;font-size:12.5px}
  .err{color:var(--bad);font-size:13px;margin-top:6px}
  .meta{font-size:13px;color:var(--muted)}
</style>
@endpush

@section('content')
  <div class="head-actions">
    <div>
      <h1 class="page-title">{{ $t['title'] }}</h1>
      <p class="page-sub" style="margin:0">{{ auth()->user()->tenant->name_ar }} · {{ number_format($total) }} {{ $t['single'] }}
        @if ($updated) · آخر تحديث: <span class="ltr">{{ \Illuminate\Support\Carbon::parse($updated)->timezone('Africa/Cairo')->format('Y-m-d H:i') }}</span>@endif</p>
    </div>
    <div class="actions">
      <a href="{{ route('client.data.index', $type === 'products' ? 'pharmacies' : 'products') }}" class="btn ghost">{{ $type === 'products' ? 'الصيدليات' : 'الأصناف' }}</a>
      <a href="{{ route('client.settings.edit') }}" class="btn ghost">رابط الشكاوى</a>
    </div>
  </div>

  <div class="two">
    <div class="card">
      <h2 style="font-size:16px;margin:0 0 6px">رفع ملف Excel</h2>
      <div class="hint">{{ $t['hint'] }} الصف الأول عناوين.</div>
      <form method="POST" action="{{ route('client.data.upload', $type) }}" enctype="multipart/form-data" class="up"
            onsubmit="return {{ $total ? 'confirm(\'سيتم حذف القائمة الحالية (' . $total . ') واستبدالها بالملف الجديد. متابعة؟\')' : 'true' }}">
        @csrf
        <input type="file" name="file" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" required>
        <button type="submit" class="btn">رفع واستبدال</button>
      </form>
      @error('file')<div class="err">{{ $message }}</div>@enderror
      @if ($total)<div class="warn-box">الرفع يستبدل القائمة الحالية بالكامل.</div>@endif
      <div class="up">
        <a href="{{ route('client.data.template', $type) }}" class="btn ghost">تنزيل قالب Excel</a>
        @if ($total)
          <a href="{{ route('client.data.export', $type) }}" class="btn ghost">تنزيل القائمة الحالية</a>
          <form method="POST" action="{{ route('client.data.clear', $type) }}" onsubmit="return confirm('حذف القائمة كلها؟')" style="margin:0">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn danger">حذف القائمة</button>
          </form>
        @endif
      </div>
    </div>

    <div class="card">
      <h2 style="font-size:16px;margin:0 0 10px">إضافة {{ $t['single'] }}</h2>
      <form method="POST" action="{{ route('client.data.store', $type) }}" class="add">
        @csrf
        @foreach ($t['cols'] as $col => $label)
          <div>
            <label class="f" for="add_{{ $col }}">{{ $label }}</label>
            <input type="{{ $col === 'price' ? 'number' : 'text' }}" id="add_{{ $col }}" name="{{ $col }}" value="{{ old($col) }}"
                   @if ($col === 'price') step="0.01" min="0" class="num" @endif
                   @if (in_array($col, ['name', 'code'])) required @endif>
          </div>
        @endforeach
        <button type="submit" class="btn">إضافة</button>
      </form>
      @foreach (array_keys($t['cols']) as $col)
        @error($col)<div class="err">{{ $message }}</div>@enderror
      @endforeach
    </div>
  </div>

  <div class="card">
    <form method="GET" action="{{ route('client.data.index', $type) }}" class="search">
      <input type="text" name="q" value="{{ $q }}" placeholder="بحث…">
      <button type="submit" class="btn ghost">بحث</button>
      @if ($q !== '')<a href="{{ route('client.data.index', $type) }}" class="btn ghost">مسح</a>@endif
    </form>

    <div class="tbl-wrap">
      <table class="t">
        <thead><tr>@foreach ($t['cols'] as $label)<th>{{ $label }}</th>@endforeach<th></th></tr></thead>
        <tbody>
          @forelse ($rows as $r)
            <tr>
              @foreach ($t['cols'] as $col => $label)
                <td><input form="f{{ $r->id }}" type="{{ $col === 'price' ? 'number' : 'text' }}" name="{{ $col }}" value="{{ $r->$col }}"
                           @if ($col === 'price') step="0.01" min="0" class="num" style="max-width:120px" @endif></td>
              @endforeach
              <td>
                <div class="acts">
                  <form id="f{{ $r->id }}" method="POST" action="{{ route('client.data.update', [$type, $r->id]) }}" style="margin:0">
                    @csrf
                    @method('PUT')
                    <button type="submit" class="btn ghost">حفظ</button>
                  </form>
                  <form method="POST" action="{{ route('client.data.destroy', [$type, $r->id]) }}" onsubmit="return confirm('حذف؟')" style="margin:0">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn danger">×</button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="{{ count($t['cols']) + 1 }}" style="text-align:center;color:var(--muted);padding:24px">
              {{ $q !== '' ? 'لا توجد نتائج.' : 'القائمة فارغة. ارفع ملف Excel أو أضف يدوياً.' }}
            </td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
    @if ($rows->hasPages())
      <div class="actions" style="margin-top:12px;justify-content:center">
        @if ($rows->onFirstPage())<span class="btn ghost" style="opacity:.4">السابق</span>@else<a href="{{ $rows->previousPageUrl() }}" class="btn ghost">السابق</a>@endif
        <span class="meta">صفحة {{ $rows->currentPage() }} من {{ $rows->lastPage() }} · {{ number_format($rows->total()) }} نتيجة</span>
        @if ($rows->hasMorePages())<a href="{{ $rows->nextPageUrl() }}" class="btn ghost">التالي</a>@else<span class="btn ghost" style="opacity:.4">التالي</span>@endif
      </div>
    @endif
  </div>
@endsection