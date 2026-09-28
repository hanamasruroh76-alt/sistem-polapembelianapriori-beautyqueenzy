<?php

namespace App\Services;

use Illuminate\Support\Collection;

class AprioriService
{
    private const SUPPORT_EPSILON = 1.0E-6;

    public function analyze(Collection $transactions, float $minSupportPercent, float $minConfidencePercent): array
    {
        $totalTransactions = $transactions->count();

        if ($totalTransactions === 0) {
            return [
                'totalTransactions' => 0,
                'singleProducts' => collect(),
                'frequentLevels' => collect(),
                'frequentLevel1' => collect(),
                'frequentLevel2' => collect(),
                'frequentLevel3' => collect(),
                'rules' => collect(),
            ];
        }

        $minSupportRatio = max(0, min(100, $minSupportPercent)) / 100;
        $minConfidenceRatio = max(0, min(100, $minConfidencePercent)) / 100;

        $normalizedTransactions = $transactions
            ->map(function ($items) {
                return collect($items)
                    ->filter(fn ($item) => filled($item))
                    ->map(fn ($item) => trim((string) $item))
                    ->filter()
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();
            })
            ->values();

        $singleItemCounts = [];
        foreach ($normalizedTransactions as $basket) {
            foreach ($basket as $item) {
                if (! isset($singleItemCounts[$item])) {
                    $singleItemCounts[$item] = 0;
                }
                $singleItemCounts[$item]++;
            }
        }

        $frequentItemsets = [];
        $currentLevel = [];

        foreach ($singleItemCounts as $item => $count) {
            $support = $count / $totalTransactions;
            if ($this->meetsMinSupport($support, $minSupportRatio)) {
                $itemset = [$item];
                $key = $this->itemsetKey($itemset);

                $frequentItemsets[$key] = [
                    'items' => $itemset,
                    'count' => $count,
                    'support' => $support,
                ];

                $currentLevel[] = [
                    'items' => $itemset,
                    'count' => $count,
                    'support' => $support,
                ];
            }
        }

        $frequentSingleLookup = [];
        foreach ($currentLevel as $row) {
            $frequentSingleLookup[$row['items'][0]] = true;
        }

        $transactionsForCandidates = $normalizedTransactions
            ->map(function (array $basket) use ($frequentSingleLookup) {
                return array_values(array_filter($basket, fn ($item) => isset($frequentSingleLookup[$item])));
            })
            ->filter(fn (array $basket) => ! empty($basket))
            ->values();

        $k = 2;
        while (! empty($currentLevel)) {
            $candidates = $this->generateCandidates($currentLevel, $k);
            if (empty($candidates)) {
                break;
            }

            $candidateCounts = array_fill_keys(array_keys($candidates), 0);

            foreach ($transactionsForCandidates as $basket) {
                $this->countCandidateMatchesInBasket($basket, $k, $candidates, $candidateCounts);
            }

            $nextLevel = [];
            foreach ($candidates as $candidateKey => $candidate) {
                $count = $candidateCounts[$candidateKey];
                $support = $count / $totalTransactions;

                if ($this->meetsMinSupport($support, $minSupportRatio)) {
                    $record = [
                        'items' => $candidate,
                        'count' => $count,
                        'support' => $support,
                    ];

                    $frequentItemsets[$candidateKey] = $record;
                    $nextLevel[] = $record;
                }
            }

            $currentLevel = $nextLevel;
            $k++;
        }

        $singleProducts = collect($singleItemCounts)
            ->map(function ($count, $item) use ($totalTransactions) {
                return [
                    'item_name' => $item,
                    'count' => $count,
                    'support' => ($count / $totalTransactions) * 100,
                ];
            })
            ->sortByDesc('support')
            ->values();

        $rules = $this->generateRules($frequentItemsets, $minConfidenceRatio)
            ->sortByDesc(function ($rule) {
                return sprintf('%09.5f-%09.5f', $rule['confidence'], $rule['support']);
            })
            ->values();

        $frequentLevels = collect($frequentItemsets)
            ->map(function ($row) {
                return [
                    'items' => $row['items'],
                    'itemset' => implode(', ', $row['items']),
                    'count' => $row['count'],
                    'support' => $row['support'] * 100,
                    'level' => count($row['items']),
                ];
            })
            ->groupBy('level');

        return [
            'totalTransactions' => $totalTransactions,
            'singleProducts' => $singleProducts,
            'frequentLevels' => $frequentLevels,
            'frequentLevel1' => $frequentLevels->get(1, collect())->sortByDesc('support')->values(),
            'frequentLevel2' => $frequentLevels->get(2, collect())->sortByDesc('support')->values(),
            'frequentLevel3' => $frequentLevels->get(3, collect())->sortByDesc('support')->values(),
            'rules' => $rules,
        ];
    }

    private function generateCandidates(array $currentLevel, int $k): array
    {
        $candidateMap = [];
        $itemsets = array_map(fn ($row) => $row['items'], $currentLevel);
        $itemsetLookup = array_flip(array_map(fn ($set) => $this->itemsetKey($set), $itemsets));

        $totalSets = count($itemsets);

        for ($left = 0; $left < $totalSets; $left++) {
            for ($right = $left + 1; $right < $totalSets; $right++) {
                $merged = array_values(array_unique(array_merge($itemsets[$left], $itemsets[$right])));
                sort($merged);

                if (count($merged) !== $k) {
                    continue;
                }

                if (! $this->allSubsetsFrequent($merged, $itemsetLookup)) {
                    continue;
                }

                $candidateMap[$this->itemsetKey($merged)] = $merged;
            }
        }

        return $candidateMap;
    }

    private function allSubsetsFrequent(array $candidate, array $itemsetLookup): bool
    {
        foreach ($this->subsetsOfSize($candidate, count($candidate) - 1) as $subset) {
            if (! isset($itemsetLookup[$this->itemsetKey($subset)])) {
                return false;
            }
        }

        return true;
    }

    private function subsetsOfSize(array $items, int $size): array
    {
        $result = [];
        $this->buildCombinations($items, $size, 0, [], $result);

        return $result;
    }

    private function buildCombinations(array $items, int $size, int $start, array $path, array &$result): void
    {
        if (count($path) === $size) {
            $result[] = $path;

            return;
        }

        for ($index = $start; $index < count($items); $index++) {
            $path[] = $items[$index];
            $this->buildCombinations($items, $size, $index + 1, $path, $result);
            array_pop($path);
        }
    }

    private function generateRules(array $frequentItemsets, float $minConfidenceRatio): Collection
    {
        $rules = collect();

        foreach ($frequentItemsets as $itemsetData) {
            $items = $itemsetData['items'];
            $itemCount = count($items);

            if ($itemCount < 2) {
                continue;
            }

            for ($mask = 1; $mask < (1 << $itemCount) - 1; $mask++) {
                $antecedent = [];
                $consequent = [];

                for ($bit = 0; $bit < $itemCount; $bit++) {
                    if (($mask >> $bit) & 1) {
                        $antecedent[] = $items[$bit];
                    } else {
                        $consequent[] = $items[$bit];
                    }
                }

                $antecedentKey = $this->itemsetKey($antecedent);
                if (! isset($frequentItemsets[$antecedentKey])) {
                    continue;
                }

                $consequentKey = $this->itemsetKey($consequent);
                if (! isset($frequentItemsets[$consequentKey])) {
                    continue;
                }

                $confidence = $itemsetData['support'] / $frequentItemsets[$antecedentKey]['support'];
                if (! $this->meetsMinSupport($confidence, $minConfidenceRatio)) {
                    continue;
                }

                $consequentSupport = $frequentItemsets[$consequentKey]['support'];
                if ($consequentSupport <= 0) {
                    continue;
                }

                $liftRatio = $confidence / $consequentSupport;

                $rules->push([
                    'antecedent' => implode(', ', $antecedent),
                    'consequent' => implode(', ', $consequent),
                    'support' => $itemsetData['support'] * 100,
                    'confidence' => $confidence * 100,
                    'lift' => $liftRatio,
                ]);
            }
        }

        return $rules;
    }

    private function countCandidateMatchesInBasket(array $basket, int $k, array $candidateLookup, array &$candidateCounts): void
    {
        if (count($basket) < $k) {
            return;
        }

        $this->scanBasketCombinations($basket, $k, 0, [], $candidateLookup, $candidateCounts);
    }

    private function scanBasketCombinations(
        array $basket,
        int $k,
        int $start,
        array $path,
        array $candidateLookup,
        array &$candidateCounts
    ): void
    {
        if (count($path) === $k) {
            $key = $this->itemsetKey($path);
            if (isset($candidateLookup[$key])) {
                $candidateCounts[$key]++;
            }

            return;
        }

        $remainingNeeded = $k - count($path);
        $lastStart = count($basket) - $remainingNeeded;

        for ($index = $start; $index <= $lastStart; $index++) {
            $path[] = $basket[$index];
            $this->scanBasketCombinations($basket, $k, $index + 1, $path, $candidateLookup, $candidateCounts);
            array_pop($path);
        }
    }

    private function meetsMinSupport(float $support, float $minSupportRatio): bool
    {
        return $support >= ($minSupportRatio - self::SUPPORT_EPSILON);
    }

    private function itemsetKey(array $items): string
    {
        $normalized = $items;
        sort($normalized);

        return implode('||', $normalized);
    }
}
