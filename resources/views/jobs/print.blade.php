@extends('layouts.app')

@section('title', 'الوصف الوظيفي · ' . ($user ? $user->displayName() : $job['title']))

@push('head')
@include('jobs._jd-style')
@endpush

@section('content')
  @php
    $esc = fn ($s) => e($s);
    $sections = $job['sections'];
  @endphp

  <div class="tools">
    <div>
      @if ($user)
        <b>{{ $user->displayName() }}</b> ·
        @if ($user->jd_ack_at)<span class="ack">✓ وقّع بالاستلام {{ $user->jd_ack_at->format('Y-m-d') }}</span>@else<span style="color:var(--bad)">لم يُسجل إقرار الاستلام</span>@endif
      @else
        <b>نسخة فارغة للتعيين</b>
      @endif
    </div>
    <div class="actions" style="gap:6px">
      @if ($user && $canManage && ! $user->jd_ack_at)
        <form method="POST" action="{{ route('jobs.ack', $user) }}">
          @csrf
          <input type="date" name="date" value="{{ $today }}" max="{{ $today }}">
          <button type="submit" class="btn ghost small">تسجيل توقيع الموظف</button>
        </form>
      @endif
      @if (isset(\App\Support\WorkCards::CARDS[$job['code']]))
        <a href="{{ route('workcards.show', [$job['code']] + ($user ? ['user' => $user->id] : [])) }}" class="btn ghost small" target="_blank">طباعة البطاقة المبسطة</a>
      @endif
      <button type="button" class="btn gold" onclick="window.print()">طباعة الوصف</button>
    </div>
  </div>

  @include('jobs._jd-body')
@endsection