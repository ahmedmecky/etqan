<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

/** مخططات سير العمل (الاستلام SH-001 · التحضير والتوزيع SH-004): للعرض والطباعة */
class FlowChartController extends Controller
{
    /** المفتاح ← [العنوان, الإجراء, الملف في public/flows, ارتفاع المخطط بالبكسل (العرض ١٤٤٠)] */
    public const FLOWS = [
        'receiving' => ['مخطط استلام المستحضرات الصيدلية', 'SH-001', 'flows/receiving.html', 2580],
        'dispatch'  => ['مخطط تحضير الطلبيات وتوزيعها', 'SH-004', 'flows/dispatch.html', 3040],
    ];

    public function __invoke(Request $request): View
    {
        $key = array_key_exists((string) $request->query('f'), self::FLOWS) ? (string) $request->query('f') : 'receiving';

        return view('qms.flows', ['flows' => self::FLOWS, 'key' => $key]);
    }
}