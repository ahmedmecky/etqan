@extends('layouts.app')

@section('title', 'لا توجد صلاحية')

@section('content')
  <div class="card" style="max-width:560px;margin:30px auto;text-align:center">
    <div style="font-size:42px;line-height:1">🔒</div>
    <h1 class="page-title" style="margin-top:8px">لا توجد صلاحية</h1>
    <p class="page-sub">{{ $message }}</p>
    <a href="{{ route('home') }}" class="btn">العودة للرئيسية</a>
  </div>
@endsection