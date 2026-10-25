@extends('layouts.app')

@section('title', 'المستخدمون')

@push('head')
<style>
  .filters{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px}
  .filters input[type=text]{max-width:300px}
  .filters select{max-width:240px}
  .u-en{font-size:12.5px;color:var(--muted)}
  .row-actions{display:flex;gap:6px;flex-wrap:wrap}
  .row-actions .btn{padding:4px 12px;font-size:13px}
  tr.is-admin td{background:#f6faf9}
</style>
@endpush

@section('content')
  <div class="actions" style="justify-content:space-between;margin-bottom:14px">
    <div>
      <h1 class="page-title">مستخدمو {{ auth()->user()->tenant->name_ar }}</h1>
      <p class="page-sub" style="margin:0">{{ $users->count() }} مستخدم · {{ $users->where('is_active', true)->count() }} فعال</p>
    </div>
    <a href="{{ route('client.users.create') }}" class="btn">+ مستخدم جديد</a>
  </div>

  <div class="card">
    <form method="GET" action="{{ route('client.users.index') }}" class="filters">
      <input type="text" name="q" value="{{ $q }}" placeholder="بحث بالاسم أو البريد أو الهاتف…">
      <select name="role">
        <option value="">كل الأدوار</option>
        @foreach ($roles as $r)
          <option value="{{ $r->id }}" {{ (string) $roleId === (string) $r->id ? 'selected' : '' }}>{{ $r->name_ar }}</option>
        @endforeach
      </select>
      <button type="submit" class="btn ghost">بحث</button>
      @if ($q !== '' || $roleId)
        <a href="{{ route('client.users.index') }}" class="btn ghost">مسح</a>
      @endif
    </form>

    <div class="tbl-wrap">
      <table class="t">
        <thead>
          <tr><th>الاسم</th><th>البريد الإلكتروني</th><th>الهاتف</th><th>الدور الوظيفي</th><th>آخر دخول</th><th>الحالة</th><th></th></tr>
        </thead>
        <tbody>
          @forelse ($users as $u)
            @php($isAdmin = $u->role?->level !== \App\Models\Role::LEVEL_USER)
            <tr class="{{ $isAdmin ? 'is-admin' : '' }}">
              <td>
                <b>{{ $u->displayName() }}</b>
                @if ($u->name_ar) <div class="u-en ltr">{{ $u->name }}</div> @endif
              </td>
              <td><span class="ltr">{{ $u->email }}</span></td>
              <td><span class="ltr">{{ $u->phone }}</span></td>
              <td>{{ $u->role?->name_ar }}</td>
              <td><span class="ltr">{{ $u->last_login_at?->timezone('Africa/Cairo')->format('Y-m-d H:i') ?? '—' }}</span></td>
              <td>
                @if ($u->is_active)
                  <span class="badge b-ok">فعال</span>
                @else
                  <span class="badge b-bad">موقوف</span>
                @endif
              </td>
              <td>
                @if ($isAdmin)
                  <span class="badge b-mute">{{ $u->id === auth()->id() ? 'أنت' : 'مدير' }}</span>
                @else
                  <div class="row-actions">
                    <a href="{{ route('client.users.edit', $u) }}" class="btn ghost">تعديل</a>
                    <form method="POST" action="{{ route('client.users.toggle', $u) }}" style="margin:0">
                      @csrf
                      @method('PATCH')
                      <button type="submit" class="btn {{ $u->is_active ? 'danger' : 'ghost' }}">{{ $u->is_active ? 'إيقاف' : 'تفعيل' }}</button>
                    </form>
                  </div>
                @endif
              </td>
            </tr>
          @empty
            <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:24px">
              {{ ($q !== '' || $roleId) ? 'لا توجد نتائج مطابقة.' : 'لا يوجد مستخدمون بعد. اضغط «مستخدم جديد».' }}
            </td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection