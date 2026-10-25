@include('portal._head', ['title' => 'شكاوى الصيدليات'])
<main>
  <div class="card" style="text-align:center">
    <h1>الرابط متوقف حالياً</h1>
    <p class="sub">للشكاوى تواصل مع {{ $tenant->name_ar }}@if ($tenant->phone) على <span class="ltr" style="display:inline-block">{{ $tenant->phone }}</span>@endif.</p>
  </div>
</main>
@include('portal._foot')