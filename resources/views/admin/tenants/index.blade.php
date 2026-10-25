@extends('layouts.app')

@section('title', 'العملاء')

@push('head')
<style>
  .logo-thumb{width:56px;height:40px;object-fit:contain;background:#fff;border:1px solid var(--line);border-radius:6px;padding:2px}
  .logo-none{width:56px;height:40px;border:1px dashed var(--line);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:11px;color:var(--muted)}
  .search{display:flex;gap:8px;max-width:640px;flex:1;flex-wrap:wrap}
  .search input[type=text]{flex:1;min-width:200px}
  .search select{max-width:180px}
  .t-code{font-weight:700;color:var(--brand)}
  .t-en{font-size:12.5px;color:var(--muted)}
  .t-zones{font-size:12px;color:var(--muted);max-width:260px}
  .row-actions{display:flex;gap:6px;flex-wrap:wrap}
  .row-actions .btn{padding:4px 12px;font-size:13px}
</style>
@endpush

@section('content')
  <div class="actions" style="justify-content:space-between;margin-bottom:14px">
    <div>
      <h1 class="page-title">العملاء (الشركات والمخازن)</h1>
      <p class="page-sub" style="margin:0">{{ $tenants->count() }} عميل</p>
    </div>
    <a href="{{ route('admin.tenants.create') }}" class="btn">+ عميل جديد</a>
  </div>

  <div class="card">
    <form method="GET" action="{{ route('admin.tenants.index') }}" class="search" style="margin-bottom:14px">
      <input type="text" name="q" value="{{ $q }}" placeholder="بحث بالكود أو الاسم أو GLN أو منطقة التغطية…">
      <select name="city">
        <option value="">كل المدن</option>
        @foreach ($cities as $c)
          <option value="{{ $c }}" {{ $city === $c ? 'selected' : '' }}>{{ $c }}</option>
        @endforeach
      </select>
      <button type="submit" class="btn ghost">بحث</button>
      @if ($q !== '' || $city !== '')
        <a href="{{ route('admin.tenants.index') }}" class="btn ghost">مسح</a>
      @endif
    </form>

    <div class="tbl-wrap">
      <table class="t">
        <thead>
          <tr><th>الشعار</th><th>الكود</th><th>اسم العميل</th><th>المدينة · التغطية</th><th>GLN</th><th>الهاتف</th><th>المستخدمون</th><th>الحالة</th><th></th></tr>
        </thead>
        <tbody>
          @forelse ($tenants as $t)
            <tr>
              <td>
                @if ($t->logoUrl())
                  <img src="{{ $t->logoUrl() }}" alt="" class="logo-thumb">
                @else
                  <div class="logo-none">بدون</div>
                @endif
              </td>
              <td><span class="t-code ltr">{{ $t->code }}</span></td>
              <td><b>{{ $t->name_ar }}</b><div class="t-en ltr">{{ $t->name_en }}</div></td>
              <td>
                {{ $t->city ?? '—' }}
                @if ($t->coverage_zones)
                  <div class="t-zones">{{ $t->zonesText() }}</div>
                @endif
              </td>
              <td><span class="ltr">{{ $t->gln ?? '—' }}</span></td>
              <td><span class="ltr">{{ $t->phone }}</span></td>
              <td>{{ $t->users_count }}</td>
              <td>
                @if ($t->is_active)
                  <span class="badge b-ok">فعال</span>
                @else
                  <span class="badge b-bad">موقوف</span>
                @endif
              </td>
              <td>
                <div class="row-actions">
                  <a href="{{ route('admin.tenants.edit', $t) }}" class="btn ghost">تعديل · المدير</a>
                  <form method="POST" action="{{ route('admin.tenants.toggle', $t) }}" style="margin:0">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="btn {{ $t->is_active ? 'danger' : 'ghost' }}">{{ $t->is_active ? 'إيقاف' : 'تفعيل' }}</button>
                  </form>
                </div>
              </td>
            </tr>
          @empty
            <tr><td colspan="9" style="text-align:center;color:var(--muted);padding:24px">
              {{ $q !== '' || $city !== '' ? 'لا توجد نتائج مطابقة.' : 'لا يوجد عملاء بعد. اضغط «عميل جديد».' }}
            </td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection