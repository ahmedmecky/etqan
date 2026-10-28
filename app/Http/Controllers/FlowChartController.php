<?php

namespace App\Http\Controllers;

use App\Support\SopLibrary;
use App\Support\WorkCards;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * مخططات سير العمل:
 *   - مخططان تفصيليان مرسومان (الاستلام SH-001 · التحضير والتوزيع SH-004)
 *   - مخطط تلقائي لكل إجراء من الـ ٣٤ من خطواته (المراحل · الخطوات · المسؤول · السجل) + «عند حدوث مشكلة»
 */
class FlowChartController extends Controller
{
    /** المفتاح ← [العنوان, الإجراء, الملف في public/flows, ارتفاع المخطط بالبكسل (العرض ١٤٤٠)] */
    public const FLOWS = [
        'receiving' => ['مخطط استلام المستحضرات الصيدلية', 'SH-001', 'flows/receiving.html', 2580],
        'dispatch'  => ['مخطط تحضير الطلبيات وتوزيعها', 'SH-004', 'flows/dispatch.html', 3040],
    ];

    /** ألوان المسؤولين (بالترتيب) */
    private const COLORS = ['#0f6e64', '#b45309', '#1d4ed8', '#7c3aed', '#be123c', '#047857', '#a16207', '#0e7490', '#9333ea', '#4d7c0f', '#c2410c', '#334155'];

    public function __invoke(Request $request): View
    {
        $f = strtoupper((string) $request->query('f', 'receiving'));
        $sops = SopLibrary::all()->map(fn ($s, $c) => ['code' => $c, 'title' => $s['title']])->values();

        // مخطط مرسوم: بمفتاحه أو بكود إجرائه (ما لم يُطلب التلقائي)
        $hand = collect(self::FLOWS)->search(fn ($x) => $x[1] === $f);
        $key = array_key_exists(strtolower($f), self::FLOWS) ? strtolower($f) : (($hand !== false && ! $request->boolean('auto')) ? $hand : null);
        if ($key) {
            return view('qms.flows', ['flows' => self::FLOWS, 'key' => $key, 'flow' => null, 'sops' => $sops, 'code' => self::FLOWS[$key][1]]);
        }

        $sop = preg_match('/^SH-\d{3}$/', $f) ? SopLibrary::get($f) : null;
        abort_unless($sop, 404);

        return view('qms.flows', ['flows' => self::FLOWS, 'key' => null, 'flow' => self::build($sop), 'sops' => $sops, 'code' => $f,
            'hand' => $hand !== false ? $hand : null]);
    }

    /** بيانات المخطط من الإجراء */
    public static function build(array $sop): array
    {
        $phases = [];
        $owners = [];
        $records = [];
        $n = 0;
        foreach ($sop['sections'] ?? [] as $sec) {
            foreach ($sec['blocks'] ?? [] as $b) {
                if (($b['type'] ?? '') !== 'steps') {
                    continue;
                }
                foreach ($b['groups'] ?? [] as $g) {
                    $rows = [];
                    foreach ($g['rows'] ?? [] as $r) {
                        $who = array_values(array_filter(array_map('trim', preg_split('/\s*[·،,\/]\s*/u', (string) ($r[1] ?? '')))));
                        foreach ($who as $w) {
                            $owners[$w] ??= self::COLORS[count($owners) % count(self::COLORS)];
                        }
                        $rec = trim((string) ($r[2] ?? ''));
                        $rec = in_array($rec, ['—', '-', ''], true) ? null : $rec;
                        if ($rec) {
                            foreach (preg_split('/\s*[·،,]\s*/u', $rec) as $x) {
                                if (preg_match('/^SH-\d{3}-F\d{2}$/', trim($x))) {
                                    $records[trim($x)] = true;
                                }
                            }
                        }
                        $rows[] = ['n' => ++$n, 'text' => WorkCards::clean((string) ($r[0] ?? '')), 'who' => $who, 'rec' => $rec];
                    }
                    $phases[] = ['title' => trim((string) ($g['title'] ?? '')), 'rows' => $rows];
                }
            }
        }

        $problems = [];
        foreach ($sop['sections'] ?? [] as $sec) {
            if (! str_contains((string) ($sec['title'] ?? ''), 'الانحرافات')) {
                continue;
            }
            foreach ($sec['blocks'] ?? [] as $b) {
                foreach (($b['type'] ?? '') === 'table' ? ($b['rows'] ?? []) : [] as $r) {
                    $problems[] = ['if' => WorkCards::clean((string) ($r[0] ?? '')), 'do' => WorkCards::clean((string) ($r[1] ?? '')), 'who' => trim((string) ($r[2] ?? ''))];
                }
            }
        }

        $forms = collect($sop['forms'] ?? [])->mapWithKeys(fn ($f) => [$f['code'] => $f['title']]);

        return [
            'code' => $sop['code'], 'title' => $sop['title'], 'version' => $sop['version'] ?? '01',
            'phases' => $phases, 'owners' => $owners, 'problems' => $problems, 'steps' => $n,
            'records' => collect(array_keys($records))->map(fn ($c) => [$c, $forms[$c] ?? ''])->all(),
        ];
    }
}