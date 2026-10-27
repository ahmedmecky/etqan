<?php

namespace App\Http\Controllers;

use App\Support\GlobalSearch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * البحث العام (شريط البحث في الرئيسية) · دليل الوثائق (زر المعلومات في الشريط العلوي)
 */
class SearchController extends Controller
{
    /** GET /search?q= : JSON للبحث الفوري، أو صفحة نتائج عند الضغط على Enter */
    public function index(Request $request): JsonResponse|View
    {
        $res = GlobalSearch::run($request->user(), (string) $request->query('q', ''));

        return $request->wantsJson() ? response()->json($res) : view('search.index', ['res' => $res]);
    }

    /** GET /library : الإجراءات والمؤشرات والأوصاف الوظيفية بأكوادها */
    public function library(Request $request): JsonResponse
    {
        return response()->json(GlobalSearch::library($request->user()));
    }
}