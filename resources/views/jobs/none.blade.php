@extends('layouts.app')

@section('title', 'الوصف الوظيفي')

@section('content')
  <div class="card" style="max-width:620px">
    <h1 class="page-title">لم يُحدد وصف وظيفي</h1>
    <p class="page-sub">لم تُحدد وظيفة من مكتبة الأوصاف الوظيفية للمستخدم {{ $user->displayName() }}.</p>
    @if ($canManage && auth()->user()->isClientAdmin() && ! $user->isClientAdmin())
      <a href="{{ route('client.users.edit', $user) }}" class="btn">تحديد الوظيفة</a>
    @else
      <p class="page-sub">اطلب من مدير المخزن أو الموارد البشرية تحديد وظيفتك.</p>
    @endif
  </div>
@endsection