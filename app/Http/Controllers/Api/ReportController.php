<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ReportStatements as Report;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    /**
     * POST /api/reports
     * Insert scam report (statement only)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'statement' => ['required', 'string', 'min:5', 'max:5000'],
            'locale'    => ['nullable', 'string', 'max:16'],
        ]);

        $report = Report::create([
            'statement' => $validated['statement'],
            'locale'    => $validated['locale'] ?? 'en_PH',
        ]);

        return response()->json([
            'message' => 'Report saved',
            'data'    => [
                'id' => $report->id,
                'statement' => $report->statement,
                'locale' => $report->locale,
                'created_at' => $report->created_at,
            ],
        ], 201);
    }

    /**
     * GET /api/patterns
     * Returns detected scam patterns based on keywords in the DB reports table.
     *
     * NOTE: This is a simple "rule-to-insights" extractor.
     * It counts how many reports mention each pattern.
     */
    public function patterns(Request $request)
    {
        $limit = (int)($request->query('limit', 10));
        $limit = max(1, min($limit, 50));

        // Define your known patterns (you can expand anytime)
        $patterns = [
            [
                'id' => 'UPFRONT_PAYMENT',
                'name' => 'Asks for upfront payment / deposit',
                'keywords' => [
                    'deposit', 'downpayment', 'dp', 'pay first', 'send money',
                    'gcash', 'maya', 'bank transfer'
                ],
            ],
            [
                'id' => 'OFF_PLATFORM',
                'name' => 'Moves conversation off-platform',
                'keywords' => [
                    'telegram', 'whatsapp', 'viber', 'messenger', 'pm me', 'text me'
                ],
            ],
            [
                'id' => 'FAKE_COURIER',
                'name' => 'Fake courier / rider / delivery proof',
                'keywords' => [
                    'courier', 'rider', 'delivery', 'tracking', 'waybill', 'awb',
                    'lalamove', 'grab', 'j&t', 'jnt', 'ninjavan', 'flash'
                ],
            ],
            [
                'id' => 'URGENCY_PRESSURE',
                'name' => 'Uses urgency / pressure tactics',
                'keywords' => [
                    'urgent', 'asap', 'rush', 'today only', 'last chance',
                    'bilis', 'madali', 'ngayon'
                ],
            ],
            [
                'id' => 'MEETUP_AVOIDANCE',
                'name' => 'Avoids meetup/inspection',
                'keywords' => [
                    'no meetup', "can't meet", 'deliver only', 'no cod', 'cod not allowed',
                    'padala'
                ],
            ],
        ];

        // Pull all statements (simple approach).
        // If your dataset grows, we can optimize with FULLTEXT or a pattern_hits table.
        $statements = Report::query()
            ->select('statement')
            ->orderByDesc('id')
            ->limit(5000) // safety cap to avoid huge RAM usage
            ->pluck('statement')
            ->map(fn ($s) => mb_strtolower($s ?? ''))
            ->all();

        $results = [];

        foreach ($patterns as $p) {
            $count = 0;
            $examples = [];

            foreach ($statements as $st) {
                $matched = false;

                foreach ($p['keywords'] as $kw) {
                    $kwLower = mb_strtolower($kw);

                    if (str_contains($st, $kwLower)) {
                        $matched = true;
                        break;
                    }
                }

                if ($matched) {
                    $count++;

                    if (count($examples) < 3) {
                        // store small excerpt for user guidance
                        $examples[] = mb_substr($st, 0, 160) . (mb_strlen($st) > 160 ? '…' : '');
                    }
                }
            }

            if ($count > 0) {
                $results[] = [
                    'pattern_id' => $p['id'],
                    'name' => $p['name'],
                    'mentions' => $count,
                    'examples' => $examples,
                ];
            }
        }

        // Sort by most mentioned
        usort($results, fn ($a, $b) => $b['mentions'] <=> $a['mentions']);

        return response()->json([
            'data' => array_slice($results, 0, $limit),
            'meta' => [
                'total_reports_scanned' => count($statements),
                'returned' => min($limit, count($results)),
            ],
        ]);
    }
}
