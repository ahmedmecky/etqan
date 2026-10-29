{{-- قائمة ملاحظات: $list · $a · $write · $G --}}
@forelse ($list as $f)
  <details class="fd" id="f{{ $f->id }}" style="border-inline-start-color:{{ $G[$f->grade][1] }}">
    <summary>
      <span class="no">{{ $f->no }}</span>
      <span class="tx"><span class="gr" style="background:{{ $G[$f->grade][1] }}">{{ $f->gradeLabel() }}</span> {{ $f->text }}
        @if ($f->ref || $f->evidence)<small>@if ($f->ref)المرجع: {{ $f->ref }}@endif @if ($f->evidence) · الدليل: {{ \Illuminate\Support\Str::limit($f->evidence, 120) }}@endif</small>@endif
      </span>
    </summary>
    <div class="body">
      @if ($write)
        <form method="POST" action="{{ route('audits.findings.update', [$a, $f]) }}">
          @csrf
          @method('PUT')
          <div class="grades" style="margin-bottom:8px">
            @foreach ($G as $k => $g)<label title="{{ $g[3] }}"><input type="radio" name="grade" value="{{ $k }}" @checked($f->grade === $k)><i style="background:{{ $g[1] }}"></i>{{ $g[0] }}</label>@endforeach
          </div>
          <div class="mf">
            <label class="full">الملاحظة <textarea name="text" rows="2" maxlength="4000" required>{{ $f->text }}</textarea></label>
            <label class="two">الدليل الموضوعي <textarea name="evidence" rows="2" maxlength="4000">{{ $f->evidence }}</textarea></label>
            <label>المرجع (بند GDP / الإجراء) <input type="text" name="ref" maxlength="160" list="sopList" value="{{ $f->ref }}"></label>
          </div>
          <div class="actions" style="margin-top:8px">
            <button type="submit" class="btn small">حفظ</button>
            <button type="submit" form="delF{{ $f->id }}" class="btn ghost small">حذف</button>
          </div>
        </form>
        <form method="POST" action="{{ route('audits.findings.destroy', [$a, $f]) }}" id="delF{{ $f->id }}" hidden onsubmit="return confirm('حذف الملاحظة {{ $f->no }}؟')">@csrf @method('DELETE')</form>
      @else
        @if ($f->evidence)<p style="margin:0 0 6px;font-size:13.5px"><b>الدليل:</b> {{ $f->evidence }}</p>@endif
        @if ($f->action)<p style="margin:0;font-size:13.5px"><b>الإجراء:</b> {{ $f->action }}</p>@endif
      @endif
    </div>
  </details>
@empty
  <div class="empty" style="padding:14px">لا توجد ملاحظات.</div>
@endforelse