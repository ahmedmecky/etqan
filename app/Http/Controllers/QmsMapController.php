<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/** خريطة نظام إدارة الجودة ومصفوفة المسؤوليات (RACI) مع حالة كل إجراء في سجل الوثائق */
class QmsMapController extends Controller
{
    public function __invoke(): View
    {
        $today = Carbon::today('Africa/Cairo')->toDateString();
        $docs = Document::query()->with(['current', 'versions'])->where('type', 'sop')->where('is_active', true)->get()
            ->mapWithKeys(fn (Document $d) => [$d->code => [
                'url'       => route('documents.show', $d),
                'version'   => $d->current?->version,
                'effective' => $d->current?->effective_date?->format('Y-m-d'),
                'review'    => (bool) ($d->current?->review_due && $d->current->review_due->toDateString() < $today),
                'pending'   => (bool) $d->working(),
            ]]);

        return view('qms.map', ['docs' => $docs]);
    }
}