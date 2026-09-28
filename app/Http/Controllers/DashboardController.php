<?php

namespace App\Http\Controllers;

use App\Services\AprioriService;
use App\Services\KddPreparationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        private AprioriService $aprioriService,
        private KddPreparationService $kddPreparationService
    )
    {
    }

    public function index(Request $request)
    {
        return view('apriori.dashboard', $this->buildDashboardData($request));
    }

    public function downloadRecommendationPdf(Request $request)
    {
        $dashboardData = $this->buildDashboardData($request);

        $supportEpsilon = 1.0E-6;

        $isSupportPassed = static function (?float $supportValue, ?float $minSupportValue) use ($supportEpsilon): bool {
            if ($minSupportValue === null) {
                return true;
            }

            $support = (float) ($supportValue ?? 0);
            $minSupportThreshold = (float) $minSupportValue;

            return $support >= ($minSupportThreshold - $supportEpsilon);
        };

        $isConfidencePassed = static function (?float $confidenceValue, ?float $minConfidenceValue) use ($supportEpsilon): bool {
            if ($minConfidenceValue === null) {
                return true;
            }

            $confidence = (float) ($confidenceValue ?? 0);
            $minConfidenceThreshold = (float) $minConfidenceValue;

            return $confidence >= ($minConfidenceThreshold - $supportEpsilon);
        };

        $rulesForDisplay = $dashboardData['hasProcessed']
            ? $dashboardData['rulesData']
                ->filter(function ($rule) use ($dashboardData, $isSupportPassed, $isConfidencePassed) {
                    return $isSupportPassed((float) ($rule['support'] ?? 0), $dashboardData['minSupport'])
                        && $isConfidencePassed((float) ($rule['confidence'] ?? 0), $dashboardData['minConfidence'])
                        && (float) ($rule['lift'] ?? 0) > 1;
                })
                ->sortByDesc(fn ($rule) => (float) ($rule['lift'] ?? 0))
                ->values()
            : collect();

        $rulesByItemsetSize = $rulesForDisplay
            ->groupBy(function ($rule) {
                $antecedentItems = collect(explode(',', (string) ($rule['antecedent'] ?? '')))
                    ->map(fn ($item) => trim($item))
                    ->filter();
                $consequentItems = collect(explode(',', (string) ($rule['consequent'] ?? '')))
                    ->map(fn ($item) => trim($item))
                    ->filter();

                return $antecedentItems->count() + $consequentItems->count();
            })
            ->filter(fn ($_, $itemsetSize) => (int) $itemsetSize >= 2)
            ->sortKeys();

        $pdf = Pdf::loadView('apriori.recommendation-pdf', [
            'minSupport' => $dashboardData['minSupport'],
            'minConfidence' => $dashboardData['minConfidence'],
            'hasProcessed' => $dashboardData['hasProcessed'],
            'hasData' => $dashboardData['hasData'],
            'rulesByItemsetSize' => $rulesByItemsetSize,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return $pdf->download('rekomendasi-pola-belanja-' . now()->format('Ymd_His') . '.pdf');
    }

    public function filter(Request $request)
    {
        $validated = $request->validate([
            'min_support' => ['required', 'numeric', 'min:0', 'max:100'],
            'min_confidence' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $request->session()->put([
            'apriori_processed' => true,
            'apriori_min_support' => (float) $validated['min_support'],
            'apriori_min_confidence' => (float) $validated['min_confidence'],
        ]);

        return redirect()->route('apriori.dashboard');
    }

    private function buildDashboardData(Request $request): array
    {
        $hasProcessed = false;
        $minSupport = null;
        $minConfidence = null;

        if ($request->has(['min_support', 'min_confidence'])) {
            $hasProcessed = true;
            $minSupport = (float) $request->query('min_support');
            $minConfidence = (float) $request->query('min_confidence');
        } elseif ((bool) $request->session()->get('apriori_processed', false)) {
            $hasProcessed = true;
            $minSupport = (float) $request->session()->get('apriori_min_support', 20);
            $minConfidence = (float) $request->session()->get('apriori_min_confidence', 60);
        }

        if ($hasProcessed) {
            $minSupport = max(0, min(100, (float) $minSupport));
            $minConfidence = max(0, min(100, (float) $minConfidence));
        }

        $itemRows = DB::table('transactions')
            ->select('kode_transaksi', 'nama_barang')
            ->whereNotNull('nama_barang')
            ->where('nama_barang', '!=', '')
            ->cursor()
            ->collect();

        $kddResult = $this->kddPreparationService->prepare($itemRows);
        $transactionsForApriori = $kddResult['transactionsForApriori'] ?? collect();
        $kddSummary = $kddResult['kddSummary'] ?? [];

        $totalTransactions = (int) ($kddSummary['final_transaction_count'] ?? 0);
        $totalProducts = (int) ($kddSummary['final_unique_item_count'] ?? 0);

        $hasData = $totalTransactions > 0 && $totalProducts > 0;

        $analysis = ($hasProcessed && $hasData)
            ? $this->aprioriService->analyze(
                $transactionsForApriori,
                $minSupport,
                $minConfidence
            )
            : [
                'singleProducts' => collect(),
                'frequentLevels' => collect(),
                'frequentLevel1' => collect(),
                'frequentLevel2' => collect(),
                'frequentLevel3' => collect(),
                'rules' => collect(),
            ];

        if ($hasProcessed && $hasData) {
            $this->syncFrequentItemsets($analysis['frequentLevels'] ?? collect());
            $this->syncAssociationRules($analysis['rules'] ?? collect());
        }

        $allProducts = $analysis['singleProducts'];

        $frequentLevel1 = $analysis['frequentLevel1'];
        $frequentLevel2 = $analysis['frequentLevel2'];
        $frequentLevel3 = $analysis['frequentLevel3'];
        $frequentLevels = collect($analysis['frequentLevels'] ?? collect())
            ->sortKeys();

        $rulesData = $analysis['rules']
            ->sortByDesc(fn ($rule) => (float) ($rule['lift'] ?? 0))
            ->values();

        $totalRulesCount = $rulesData->count();
        
        $supportEpsilon = 1.0E-6;
        $validRulesCount = 0;
        if ($hasProcessed && $hasData) {
            $validRulesCount = $rulesData->filter(function ($rule) use ($minSupport, $minConfidence, $supportEpsilon) {
                $support = (float) ($rule['support'] ?? 0);
                $minSupportThreshold = (float) $minSupport;
                $supportPassed = $support >= ($minSupportThreshold - $supportEpsilon);
                
                $confidence = (float) ($rule['confidence'] ?? 0);
                $minConfidenceThreshold = (float) $minConfidence;
                $confidencePassed = $confidence >= ($minConfidenceThreshold - $supportEpsilon);
                
                $lift = (float) ($rule['lift'] ?? 0);
                $liftValid = $lift > 1;
                
                return $supportPassed && $confidencePassed && $liftValid;
            })->count();
        }

        $topRules = $rulesData->take(10)->values();
        $ruleChartLabels = $topRules
            ->map(fn ($rule) => $rule['antecedent'] . ' → ' . $rule['consequent'])
            ->values()
            ->all();
        $ruleChartConfidence = $topRules->pluck('confidence')->map(fn ($value) => round((float) $value, 2))->values()->all();
        $ruleChartSupport = $topRules->pluck('support')->map(fn ($value) => round((float) $value, 2))->values()->all();

        $displayTotalTransactions = null;
        $displayTotalProducts = null;

        if ($hasData) {
            $displayTotalTransactions = $totalTransactions;
            $displayTotalProducts = max($totalProducts, $allProducts->count());
        }

        return [
            'minSupport' => $minSupport,
            'minConfidence' => $minConfidence,
            'hasProcessed' => $hasProcessed,
            'hasData' => $hasData,
            'totalTransactions' => $totalTransactions,
            'totalProducts' => $totalProducts,
            'displayTotalTransactions' => $displayTotalTransactions,
            'displayTotalProducts' => $displayTotalProducts,
            'frequentLevel1' => $frequentLevel1,
            'frequentLevel2' => $frequentLevel2,
            'frequentLevel3' => $frequentLevel3,
            'frequentLevels' => $frequentLevels,
            'rulesData' => $rulesData,
            'totalRulesCount' => $totalRulesCount,
            'validRulesCount' => $validRulesCount,
            'ruleChartLabels' => $ruleChartLabels,
            'ruleChartConfidence' => $ruleChartConfidence,
            'ruleChartSupport' => $ruleChartSupport,
            'kddSummary' => $kddSummary,
        ];
    }

    private function syncFrequentItemsets(Collection $frequentLevels): void
    {
        $rows = collect($frequentLevels)
            ->flatten(1)
            ->map(function ($row) {
                return [
                    'level' => (int) ($row['level'] ?? 0),
                    'itemset' => (string) ($row['itemset'] ?? ''),
                    'count' => (int) ($row['count'] ?? 0),
                    'support' => (float) ($row['support'] ?? 0),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })
            ->filter(fn ($row) => $row['level'] > 0 && $row['itemset'] !== '')
            ->values()
            ->all();

        DB::transaction(function () use ($rows) {
            DB::table('frequent_itemsets')->delete();

            if (! empty($rows)) {
                DB::table('frequent_itemsets')->insert($rows);
            }
        });
    }

    private function syncAssociationRules(Collection $rules): void
    {
        $rows = $rules
            ->map(function ($rule) {
                return [
                    'antecedent' => (string) ($rule['antecedent'] ?? ''),
                    'consequent' => (string) ($rule['consequent'] ?? ''),
                    'support' => (float) ($rule['support'] ?? 0),
                    'confidence' => (float) ($rule['confidence'] ?? 0),
                    'lift' => (float) ($rule['lift'] ?? 0),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })
            ->filter(fn ($row) => $row['antecedent'] !== '' && $row['consequent'] !== '')
            ->values()
            ->all();

        DB::transaction(function () use ($rows) {
            DB::table('association_rules')->delete();

            if (! empty($rows)) {
                DB::table('association_rules')->insert($rows);
            }
        });
    }
}
