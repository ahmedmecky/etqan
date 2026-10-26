@extends('layouts.app')

@section('title', $doc->code . ' · ' . $doc->title)

@push('head')
<style>
  .head-actions{display:flex;justify-content:space-between;align-items:flex-start;gap:10px;flex-wrap:wrap;margin-bottom:14px}
  .code{font-weight:700;color:var(--brand);direction:ltr;display:inline-block;white-space:nowrap}
  .by{font-size:12px;color:var(--muted)}
  .err{color:var(--bad);font-size:13px;margin-top:6px}
  .flow{display:flex;gap:0;align-items:stretch;flex-wrap:wrap;margin:4px 0 14px}
  .flow .st{flex:1;min-width:120px;text-align:center;padding:9px 6px;background:#eef0f0;color:var(--muted);font-size:13px;font-weight:700;position:relative;border-left:2px solid #fff}
  .flow .st small{display:block;font-weight:400;font-size:11px;margin-top:2px}
  .flow .st.done{background:#e3f4ea;color:var(--ok)}
  .flow .st.now{background:var(--brand);color:#fff}
  .flow .st.ret{background:#fde8e6;color:var(--bad)}
  .flow .st:first-child{border-radius:0 8px 8px 0} .flow .st:last-child{border-radius:8px 0 0 8px;border-left:0}
  .grid2{display:grid;grid-template-columns:1fr 1fr;gap:16px}
  @media (max-width:900px){.grid2{grid-template-columns:1fr}}
  .grid2 .card{margin:0 0 16px}
  .file{display:flex;align-items:center;gap:10px;border:1px dashed var(--line);border-radius:10px;padding:10px 12px;background:#fafbfb;margin:8px 0}
  .file b{direction:ltr}
  .note{background:#fde8e6;color:var(--bad);border-radius:8px;padding:8px 12px;font-size:13.5px;margin:8px 0}
  .okbox{background:#e3f4ea;color:var(--ok);border-radius:8px;padding:8px 12px;font-size:13.5px;margin:8px 0}
  .box{border:1px solid var(--line);border-radius:10px;padding:12px;margin-top:12px}
  .box h3{font-size:14px;margin:0 0 8px}
  .box.approve{border-color:#b9e2c9;background:#f6fcf8}
  .box.ret{border-color:#f3c2bd;background:#fffafa}
  .box.edit{border-color:#e6d6ad;background:#fffdf7}
  .row{display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin-top:8px}
  .appr{font-size:13px;padding:6px 0;border-bottom:1px solid var(--line)}
  .appr:last-child{border:0}
  .pill{display:inline-block;border-radius:99px;padding:2px 10px;font-size:12px;font-weight:700;white-space:nowrap;background:#eef0f0;color:var(--muted)}
  .pill.effective{background:#e3f4ea;color:var(--ok)} .pill.returned{background:#fde8e6;color:var(--bad)}
  .pill.qa,.pill.hr,.pill.ceo{background:#fdf1dc;color:#9a6300} .pill.superseded{text-decoration:line-through}
  .meta{display:grid;grid-template-columns:120px 1fr;gap:6px 10px;font-size:13.5px}
  .meta span{color:var(--muted)}
  textarea{min-height:70px}
  details summary{cursor:pointer;font-weight:700;font-size:14px}
  .lib{display:flex;align-items:center;gap:10px;flex-wrap:wrap;border:1px solid #cfe3df;border-radius:10px;padding:10px 12px;background:#f3f9f8;margin:8px 0}
  .lib b{color:var(--brand)}
  .lib .bl{font-size:12.5px;border-radius:99px;padding:2px 10px;font-weight:700;background:#e3f4ea;color:var(--ok)}
  .lib .bl.e{background:#fdf1dc;color:#9a6300}
  .start{border:1.5px solid #cfe3df;border-radius:12px;padding:14px;background:#f8fcfb;margin-bottom:12px}
  .start h3{margin:0 0 4px;font-size:15px;color:var(--brand)}
</style>
@endpush

@section('content')
  @php
    $w = $working;
    $cur = $doc->current;
    $nxt = $w && $canAct ? \App\Support\DocFlow::next($w->stage, $doc->type, auth()->user()->tenant_id) : null;
    // مراحل المسار للعرض
    $flow = array_merge(['prep' => 'المُعد'], array_intersect_key($labels, array_flip($steps)), ['eff' => 'ساري']);
    $passed = collect($w?->approvals ?? [])->pluck('step')->all();
  @endphp

  <div class="head-actions">
    <div>
      <h1 class="page-title"><span class="code">{{ $doc->code }}</span> · {{ $doc->title }}</h1>
      <p class="page-sub" style="margin:0">{{ $doc->typeLabel() }}@if ($doc->dept) · {{ $doc->dept }}@endif
        @unless ($doc->is_active) · <b style="color:var(--bad)">ملغاة</b>@endunless</p>
    </div>
    <div class="actions">
      @if ($hasLib)<a href="{{ route('documents.print', $doc) }}" class="btn gold" target="_blank">طباعة{{ $cur ? ' الساري' : '' }}</a>@endif
      <a href="{{ route('documents.queue') }}" class="btn ghost">قائمة الانتظار</a>
      <a href="{{ route('documents.index') }}" class="btn ghost">سجل الوثائق</a>
    </div>
  </div>


  {{-- ===== الإصدار الجاري ===== --}}
  <div class="card">
    @if ($w)
      <h2 style="font-size:16px;margin:0 0 8px">الإصدار <span class="ltr">{{ $w->version }}</span> <span class="pill {{ $w->stage }}">{{ $w->stageLabel() }}</span></h2>
      <div class="flow">
        @foreach ($flow as $k => $label)
          @php
            $cls = '';
            if ($k === 'prep') $cls = in_array($w->stage, ['draft', 'returned']) ? ($w->stage === 'returned' ? 'ret' : 'now') : 'done';
            elseif ($k === 'eff') $cls = '';
            elseif ($w->stage === $k) $cls = 'now';
            elseif (in_array($k, $passed)) $cls = 'done';
          @endphp
          <div class="st {{ $cls }}">{{ $label }}
            @if ($k === 'prep')<small>{{ $w->preparer?->displayName() ?? '—' }}</small>@endif
            @if ($k === 'hr')<small>للأوصاف والمؤشرات</small>@endif
          </div>
        @endforeach
      </div>

      @if ($w->stage === 'returned' && $w->return_note)
        <div class="note"><b>أُعيدت للتعديل:</b> {{ $w->return_note }}</div>
      @endif

      <div class="meta">
        <span>ملخص التعديل</span><div>{!! nl2br(e($w->change_summary)) !!}</div>
        <span>أعده</span><div>{{ $w->preparer?->displayName() ?? '—' }}</div>
        @if ($w->submitted_at)<span>أُرسل للمراجعة</span><div class="ltr" style="text-align:right">{{ $w->submitted_at->timezone('Africa/Cairo')->format('Y-m-d H:i') }}</div>@endif
      </div>

      @if ($w->isLibrary())
        <div class="lib">📘 <b>نص مكتبة إتقان</b> <span class="by">إصدار المكتبة {{ $w->lib_version }}</span>
          @if ($blanks)
            <span class="bl {{ $blanks['empty'] ? 'e' : '' }}">{{ $blanks['empty'] ? $blanks['empty'] . ' فراغ لم يُملأ من ' . $blanks['total'] : 'كل الفراغات مملوءة (' . $blanks['total'] . ')' }}</span>
          @endif
          <span style="flex:1"></span>
          @if ($doc->type === 'sop' && $hasBlanks)<a href="{{ route('documents.fills', [$doc, $w]) }}" class="btn small {{ ($canPrepare || $canAct) ? '' : 'ghost' }}">{{ ($canPrepare || $canAct) ? 'ملء الفراغات' : 'عرض الفراغات' }}</a>@endif
          @if ($doc->type === 'jd' && \App\Http\Controllers\JobController::canManage(auth()->user()))<a href="{{ route('jobs.edit', $doc->code) }}" class="btn small ghost">فراغات الوصف (الهيكل الوظيفي)</a>@endif
          <a href="{{ route('documents.print', [$doc, 'v' => $w->id]) }}" class="btn small ghost" target="_blank">معاينة الطباعة</a>
        </div>
      @elseif ($w->file_path)
        <div class="file">📄 <b>{{ $w->file_name }}</b> <span class="by">{{ number_format(($w->file_size ?? 0) / 1024, 0) }} KB</span>
          <span style="flex:1"></span><a href="{{ route('documents.download', [$doc, $w]) }}" class="btn small">تنزيل</a></div>
      @else
        <div class="note">لم يُرفع ملف لهذا الإصدار بعد.</div>
      @endif

      @if (! empty($w->approvals))
        <div class="box">
          <h3>الاعتمادات حتى الآن</h3>
          @foreach ($w->approvals as $a)
            <div class="appr">✓ <b>{{ $labels[$a['step']] ?? $a['step'] }}</b> · {{ $a['name'] }} <span class="by">· <span class="ltr">{{ $a['at'] }}</span></span>
              @if (! empty($a['comment']))<div class="by">{{ $a['comment'] }}</div>@endif</div>
          @endforeach
        </div>
      @endif

      {{-- المُعد: مسودة أو معادة --}}
      @if ($canPrepare)
        <div class="box edit">
          <h3>{{ $w->stage === 'returned' ? ($w->isLibrary() ? 'عدّل حسب الملاحظات ثم أعد الإرسال' : 'عدّل الملف حسب الملاحظات ثم أعد الإرسال') : 'المسودة' }}</h3>
          @if ($w->isLibrary())
            <div class="by">النص من مكتبة إتقان ولا يحتاج ملفاً.{{ $doc->type === 'sop' ? ' املأ الفراغات وراجع المعاينة ثم أرسل.' : ' راجع المعاينة ثم أرسل.' }}</div>
            <details style="margin-top:8px">
              <summary>تعديل أكبر من الفراغات؟ ارفع ملفاً معدلاً بدلاً من نص المكتبة</summary>
          @endif
          <form method="POST" action="{{ route('documents.upload', [$doc, $w]) }}" enctype="multipart/form-data">
            @csrf
            <label class="f" for="up_file">{{ $w->file_path ? 'رفع ملف معدل (يحل محل الملف الحالي)' : 'ملف الوثيقة' }}</label>
            <input type="file" id="up_file" name="file" accept=".docx,.doc,.pdf,.xlsx,.xls" required>
            <label class="f" for="up_sum" style="margin-top:8px">ملخص التعديل</label>
            <textarea id="up_sum" name="change_summary">{{ old('change_summary', $w->change_summary) }}</textarea>
            <div class="row"><button type="submit" class="btn ghost">رفع الملف</button></div>
          </form>
          @if ($w->isLibrary())
            </details>
          @endif
          <div class="row">
            <form method="POST" action="{{ route('documents.submit', [$doc, $w]) }}" style="margin:0">
              @csrf
              <button type="submit" class="btn" {{ $w->file_path || $w->isLibrary() ? '' : 'disabled' }}>إرسال لمدير إدارة الجودة</button>
            </form>
            @if ($w->stage === 'draft')
              <form method="POST" action="{{ route('documents.version.destroy', [$doc, $w]) }}" onsubmit="return confirm('حذف هذه المسودة وملفها؟')" style="margin:0">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn danger">حذف المسودة</button>
              </form>
            @endif
          </div>
        </div>
      @elseif (in_array($w->stage, ['draft', 'returned']))
        <div class="okbox" style="background:#eef0f0;color:var(--muted)">بانتظار المُعد ({{ $w->preparer?->displayName() ?? '—' }}).</div>
      @endif

      {{-- المراجع / المعتمد --}}
      @if ($canAct)
        <div class="grid2" style="margin-top:12px">
          <div class="box approve" style="margin:0">
            <h3>✓ اعتماد</h3>
            <div class="by">{{ $nxt ? 'تنتقل الوثيقة بعد اعتمادك إلى: ' . $labels[$nxt] : 'اعتمادك نهائي: يصبح الإصدار سارياً من اليوم، ويُلغى الإصدار السابق، وموعد المراجعة بعد سنتين.' }}</div>
            <form method="POST" action="{{ route('documents.approve', [$doc, $w]) }}" onsubmit="return confirm('{{ $nxt ? 'اعتماد وإرسال؟' : 'اعتماد نهائي وسريان الإصدار؟' }}')">
              @csrf
              <textarea name="comment" placeholder="تعليق (اختياري)" style="margin-top:8px"></textarea>
              <div class="row"><button type="submit" class="btn">{{ $nxt ? 'اعتماد وإرسال' : 'اعتماد نهائي' }}</button></div>
            </form>
          </div>
          <div class="box ret" style="margin:0">
            <h3>↩ إعادة للمُعد</h3>
            <div class="by">ترجع الوثيقة إلى {{ $w->preparer?->displayName() ?? 'المُعد' }} مع ملاحظاتك، ثم تبدأ المراجعة من جديد.</div>
            <form method="POST" action="{{ route('documents.return', [$doc, $w]) }}">
              @csrf
              <textarea name="comment" placeholder="سبب الإعادة والمطلوب تعديله" required style="margin-top:8px"></textarea>
              <div class="row"><button type="submit" class="btn danger">إعادة للمُعد</button></div>
            </form>
          </div>
        </div>
        @if ($w->isLibrary() && $doc->type === 'sop' && $hasBlanks)
          <div class="by" style="margin-top:8px">✎ لتعديل قيمة فراغ بنفسك دون إعادة: <a href="{{ route('documents.fills', [$doc, $w]) }}">ملء الفراغات</a> (يُسجل التعديل باسمك).</div>
        @endif
        <div class="box edit">
          <details>
            <summary>✎ تعديل بنفسي (رفع ملف معدل دون إعادة)</summary>
            <div class="by" style="margin-top:6px">للتعديلات البسيطة: نزّل الملف، عدّله، وارفعه هنا. يُسجل التعديل باسمك وتبقى الوثيقة في مرحلتك حتى تعتمدها.</div>
            <form method="POST" action="{{ route('documents.upload', [$doc, $w]) }}" enctype="multipart/form-data" style="margin-top:8px">
              @csrf
              <input type="file" name="file" accept=".docx,.doc,.pdf,.xlsx,.xls" required>
              <input type="text" name="edit_note" placeholder="ما الذي عدلته؟" required style="margin-top:8px">
              <div class="row"><button type="submit" class="btn ghost">رفع الملف المعدل</button></div>
            </form>
          </details>
        </div>
      @elseif (in_array($w->stage, ['qa', 'hr', 'ceo']))
        <div class="okbox" style="background:#fdf1dc;color:#9a6300">بانتظار {{ $labels[$w->stage] }}.</div>
      @endif

    @elseif ($doc->is_active)
      <h2 style="font-size:16px;margin:0 0 8px">{{ $cur ? 'إصدار جديد (مراجعة / تعديل)' : 'الإصدار الأول' }}</h2>
      <div class="by" style="margin-bottom:8px">المسار: المُعد ← {{ collect($steps)->map(fn ($s) => $labels[$s])->implode(' ← ') }} ← ساري.
        @if ($cur) الإصدار الساري {{ $cur->version }} يبقى سارياً حتى يُعتمد الجديد.@endif</div>
      @if ($hasLib)
        <div class="start">
          <h3>📘 من نص مكتبة إتقان (بدون رفع ملف)</h3>
          <div class="by">النص الجاهز للإصدار {{ $libVer }}{{ $doc->type === 'sop' ? ' بنماذجه' : '' }}. {{ $doc->type === 'sop' ? 'تملأ الفراغات فقط (مدد · حدود · أوقات)، ويُطبع بشعار المخزن.' : 'يُطبع بشعار المخزن.' }}
            @if ($cur && $cur->isLibrary()) تُنقل فراغات الإصدار السابق تلقائياً.@endif</div>
          <form method="POST" action="{{ route('documents.library', $doc) }}" style="margin-top:8px">
            @csrf
            <div class="grid2" style="grid-template-columns:140px 1fr;gap:10px">
              <div><label class="f req" for="lver">رقم الإصدار</label><input type="text" id="lver" name="version" value="{{ old('version', $cur ? $nextVer : $libVer) }}" class="ltr-in" required></div>
              <div><label class="f req" for="lsum">ملخص التعديل</label><input type="text" id="lsum" name="change_summary" value="{{ old('change_summary', $cur ? 'مراجعة دورية: بدون تغيير في النص' : 'إصدار أول في المخزن من نص مكتبة إتقان') }}" required></div>
            </div>
            <div class="row"><button type="submit" class="btn">إنشاء الإصدار من المكتبة</button>
              <a href="{{ route('documents.print', $doc) }}" target="_blank" class="btn ghost">معاينة النص</a></div>
          </form>
        </div>
        <details>
          <summary>أو ارفع ملفاً خاصاً بالمخزن</summary>
      @endif
      <form method="POST" action="{{ route('documents.version.start', $doc) }}" enctype="multipart/form-data">
        @csrf
        <div class="grid2" style="grid-template-columns:140px 1fr">
          <div><label class="f req" for="ver">رقم الإصدار</label><input type="text" id="ver" name="version" value="{{ old('version', $nextVer) }}" class="ltr-in" required></div>
          <div><label class="f req" for="file">ملف الوثيقة (Word / PDF / Excel · حتى ٢٠ ميجا)</label><input type="file" id="file" name="file" accept=".docx,.doc,.pdf,.xlsx,.xls" required></div>
        </div>
        <label class="f req" for="sum">ملخص التعديل</label>
        <textarea id="sum" name="change_summary" required placeholder="{{ $cur ? 'ما الذي تغير عن الإصدار ' . $cur->version . '؟' : 'إصدار أول' }}">{{ old('change_summary', $cur ? '' : 'إصدار أول') }}</textarea>
        <div class="row"><button type="submit" class="btn">إنشاء الإصدار</button></div>
      </form>
      @if ($hasLib)
        </details>
      @endif
    @else
      <div class="by">الوثيقة ملغاة. أعد تفعيلها لإصدار جديد.</div>
    @endif
  </div>

  <div class="grid2">
    {{-- ===== الساري ===== --}}
    <div class="card">
      <h2 style="font-size:16px;margin:0 0 8px">الإصدار الساري</h2>
      @if ($cur)
        <div class="meta">
          <span>الإصدار</span><div class="ltr" style="text-align:right">{{ $cur->version }}</div>
          <span>تاريخ السريان</span><div class="ltr" style="text-align:right">{{ $cur->effective_date?->format('Y-m-d') }}</div>
          <span>موعد المراجعة</span><div class="ltr" style="text-align:right;{{ $cur->review_due && $cur->review_due->isPast() ? 'color:var(--bad);font-weight:700' : '' }}">{{ $cur->review_due?->format('Y-m-d') }}</div>
        </div>
        @foreach ($cur->approvals ?? [] as $a)
          <div class="appr">✓ {{ $labels[$a['step']] ?? $a['step'] }} · {{ $a['name'] }} <span class="by ltr">{{ $a['at'] }}</span></div>
        @endforeach
        @if ($cur->isLibrary())<div class="row"><a href="{{ route('documents.print', $doc) }}" class="btn small gold" target="_blank">طباعة النسخة السارية</a></div>
        @elseif ($cur->file_path)<div class="row"><a href="{{ route('documents.download', [$doc, $cur]) }}" class="btn small">تنزيل النسخة السارية</a></div>@endif
      @else
        <div class="by">لا يوجد إصدار ساري بعد.</div>
      @endif
    </div>

    {{-- ===== البيانات ===== --}}
    <div class="card">
      <h2 style="font-size:16px;margin:0 0 8px">بيانات الوثيقة</h2>
      @if ($canManage)
        <form method="POST" action="{{ route('documents.update', $doc) }}">
          @csrf
          @method('PUT')
          <div class="grid2" style="gap:8px">
            <div><label class="f">الكود</label><input type="text" name="code" value="{{ old('code', $doc->code) }}" class="ltr-in" required></div>
            <div><label class="f">النوع</label><select name="type">@foreach ($types as $k => $t)<option value="{{ $k }}" {{ $doc->type === $k ? 'selected' : '' }}>{{ $t }}</option>@endforeach</select></div>
          </div>
          <label class="f" style="margin-top:8px">العنوان</label><input type="text" name="title" value="{{ old('title', $doc->title) }}" required>
          <label class="f" style="margin-top:8px">الإدارة / القسم</label><input type="text" name="dept" value="{{ old('dept', $doc->dept) }}">
          <div class="row"><button type="submit" class="btn ghost">حفظ البيانات</button></div>
        </form>
        <form method="POST" action="{{ route('documents.toggle', $doc) }}" style="margin-top:10px"
              onsubmit="return confirm('{{ $doc->is_active ? 'إلغاء الوثيقة؟ تبقى في السجل للرجوع إليها.' : 'إعادة تفعيل الوثيقة؟' }}')">
          @csrf
          @if ($doc->is_active)<input type="text" name="reason" placeholder="سبب الإلغاء">@endif
          <div class="row"><button type="submit" class="btn {{ $doc->is_active ? 'danger' : 'ghost' }} small">{{ $doc->is_active ? 'إلغاء الوثيقة' : 'إعادة تفعيل' }}</button></div>
        </form>
      @else
        <div class="meta">
          <span>الكود</span><div class="ltr" style="text-align:right">{{ $doc->code }}</div>
          <span>النوع</span><div>{{ $doc->typeLabel() }}</div>
          <span>الإدارة</span><div>{{ $doc->dept ?: '—' }}</div>
        </div>
      @endif
    </div>
  </div>

  {{-- ===== الإصدارات ===== --}}
  <div class="card">
    <h2 style="font-size:16px;margin:0 0 8px">الإصدارات</h2>
    <div class="tbl-wrap">
      <table class="t">
        <thead><tr><th>الإصدار</th><th>الحالة</th><th>ملخص التعديل</th><th>أعده</th><th>السريان</th><th>الاعتمادات</th><th></th></tr></thead>
        <tbody>
          @forelse ($doc->versions as $v)
            <tr>
              <td class="ltr"><b>{{ $v->version }}</b></td>
              <td><span class="pill {{ $v->stage }}">{{ $v->stageLabel() }}</span></td>
              <td class="by" style="max-width:320px">{{ \Illuminate\Support\Str::limit($v->change_summary, 120) }}</td>
              <td>{{ $v->preparer?->displayName() ?? '—' }}</td>
              <td class="ltr">{{ $v->effective_date?->format('Y-m-d') ?? '—' }}</td>
              <td class="by">{{ collect($v->approvals ?? [])->map(fn ($a) => ($labels[$a['step']] ?? $a['step']) . ': ' . $a['name'])->implode(' · ') ?: '—' }}</td>
              <td>@if ($v->isLibrary())<a href="{{ route('documents.print', [$doc, 'v' => $v->id]) }}" class="btn ghost small" target="_blank">طباعة</a>@elseif ($v->file_path)<a href="{{ route('documents.download', [$doc, $v]) }}" class="btn ghost small">تنزيل</a>@endif</td>
            </tr>
          @empty
            <tr><td colspan="7" style="text-align:center;color:var(--muted);padding:18px">لا توجد إصدارات بعد.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>

  {{-- ===== السجل ===== --}}
  <div class="card">
    <h2 style="font-size:16px;margin:0 0 8px">سجل الحركات</h2>
    <div class="tbl-wrap">
      <table class="t">
        <thead><tr><th>الوقت</th><th>المستخدم</th><th>الدور</th><th>الإجراء</th><th>الإصدار</th><th>التفاصيل</th></tr></thead>
        <tbody>
          @forelse ($doc->logs as $l)
            <tr>
              <td class="ltr" style="white-space:nowrap">{{ $l->created_at?->timezone('Africa/Cairo')->format('Y-m-d H:i') }}</td>
              <td>{{ $l->user?->displayName() ?? '—' }}</td>
              <td class="by">{{ $l->role ?: '—' }}</td>
              <td><b>{{ $l->action }}</b></td>
              <td class="ltr">{{ $l->version?->version ?? '—' }}</td>
              <td class="by">{{ $l->comment }}</td>
            </tr>
          @empty
            <tr><td colspan="6" style="text-align:center;color:var(--muted);padding:18px">لا توجد حركات.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
@endsection