<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class KddPreparationService
{
    /**
     * Kamus brand untuk membantu deteksi nama brand yang sering muncul.
     * Ini dipakai sebagai hint normalisasi, bukan filter data.
     *
     * @var array<int, string>
     */
    private const BRAND_HINTS = [
        'hanasui',
        'skintific',
        'dazzle me',
        'glad2glow',
        'garnier',
        'wardah',
        'azarine',
        'facetology',
        'omg',
        'make over',
        'emina',
        'bioaqua',
        'luxcrime',
        'purbasari',
        'harlette',
        'madame gie',
        'focallure',
        'scarlett',
        'esqa',
        'azzura',
    ];

    /**
     * @var array<string, array<int, string>>
     */
    private const PRODUCT_RULES_REGEX = [
        'parfume' => [
            '/\bparfume\b/u', '/\bperfume\b/u', '/\bedp\b/u', '/\bedt\b/u',
        ],
        'sunscreen' => [
            '/\bsunscreen\b/u', '/\bspf\s*50\b/u', '/\bspf50\b/u', '/\bspf\s*45\b/u', '/\bspf45\b/u',
            '/\bspf\s*35\b/u', '/\bspf35\b/u', '/\bspf\b/u', '/\bss\b/u',
        ],
        'lip glasting' => [
            '/\bglasting\b/u', '/\bglazed\s*lip\b/u',
        ],
        'lip gloss' => [
            '/\blip\s*gloss\b/u', '/\bgloss\b/u',
        ],
        'lipmatte' => [
            '/\bmattelite\s*lipstick\b/u', '/\blip\s*matte\b/u', '/\blipmatte\b/u',
        ],
        'lip cream' => [
            '/\blip\s*cream\b/u', '/\blipcream\b/u', '/\blipstick\b/u', '/\bboba\s*0?2\b/u',
            '/\bboba\s*0?3\b/u', '/\bboba\s*0?5\b/u', '/\blip\s*mouse\s*0?8\b/u',
        ],
        'lip liner' => [
            '/\blip\s*liner\b/u',
        ],
        'lipstain' => [
            '/\blip\s*stain\b/u', '/\blipstain\b/u',
        ],
        'lip tint' => [
            '/\blip\s*tint\b/u', '/\btint\b/u',
        ],
        'lip mask' => [
            '/\blip\s*mask\b/u', '/\blipmask\b/u',
        ],
        'lip scrub' => [
            '/\blipscrub\b/u',
        ],
        'lip balm' => [
            '/\blip\s*balm\b/u',
        ],
        'bronzer' => [
            '/\bbronzer\b/u',
        ],
        'highlighter' => [
            '/\bhighlighter\b/u',
        ],
        'blush on' => [
            '/\bblush\b/u', '/\bcheeklit\b/u', '/\bcheek\s*blush\b/u', '/\bbounchy\s*tint\b/u',
        ],
        'skin tint' => [
            '/\bskin\s*tint\b/u',
        ],
        'eyeshadow' => [
            '/\beye\s*palette\b/u', '/\beyeshadow\b/u', '/\beye\s*shadow\b/u',
        ],
        'eyeliner' => [
            '/\beye\s*liner\b/u', '/\beyeliner\b/u',
        ],
        'mascara' => [
            '/\bmascara\b/u',
        ],
        'eyebrow' => [
            '/\bbrow\b/u', '/\beyebrow\b/u', '/\bbrowcara\b/u',
        ],
        'matte powder' => [
            '/\bmatte\s*powder\b/u',
        ],
        'powder' => [
            '/\bpowder\b/u', '/\btwc\b/u', '/\bhoneycomb\b/u',
        ],
        'foundation' => [
            '/\bfoundation\b/u',
        ],
        'cushion' => [
            '/\bcushion\b/u',
        ],
        'concealer' => [
            '/\bconceal\w*\b/u', '/\bcorrector\b/u', '/\bcorecting\b/u',
        ],
        'primer' => [
            '/\bprimer\b/u',
        ],
        'setting spray' => [
            '/\bsetting\s*spray\b/u', '/\bfixing\s*spray\b/u',
        ],
        'micellar water' => [
            '/\bmicellar\b/u', '/\bglow\s*expert\s*cleanser\b/u',
        ],
        'facial wash' => [
            '/\bfacial\s*wash\b/u', '/\bfw\b/u', '/\bface\s*wash\b/u', '/\bcleanser\b/u', '/\bcleansing\s*foam\b/u',
        ],
        'sheetmask' => [
            '/\bsheet\s*mask\b/u', '/\bsheetmask\b/u',
        ],
        'exfo toner' => [
            '/\bexfo\s*toner\b/u', '/\bexfol?\b.*\btoner\b/u', '/\baha\b/u', '/\bbha\b/u', '/\bpha\b/u',
        ],
        'toner' => [
            '/\btoner\b/u',
        ],
        'serum' => [
            '/\bserum\b/u', '/\bazarine\s*niacinamide\s*5\s*%?\b/u',
        ],
        'essence' => [
            '/\bessence\b/u',
        ],
        'retinol' => [
            '/\bretinol\b/u',
        ],
        'face mist' => [
            '/\bface\s*mist\b/u',
        ],
        'acne patch' => [
            '/\bacne\s*patch\b/u',
        ],
        'eye cream' => [
            '/\beye\s*cream\b/u', '/\bundereye\b/u', '/\bunder\s*eye\b/u',
        ],
        'masker' => [
            '/\bmask\b/u', '/\bmasker\b/u', '/\bmugwort\s*stick\b/u', '/\bclay\s*stick\b/u', '/\bstick\b.*\bmask\b/u', '/\bclaymask\b/u',
        ],
        'lulur mandi' => [
            '/\blulur\b/u', '/\bbody\s*scrub\b/u', '/\blulur\s*mandi\b/u',
        ],
        'moisturizer' => [
            '/\bmoist\b/u', '/\bmoistur\w*\b/u', '/\btone\s*up\b/u', '/\bbarrier\b/u', '/\bpanthenol\b/u',
            '/\bsecret\s*day\b/u', '/\bsecret\s*night\b/u', '/\bday\s*cream\b/u', '/\bday\s*creme\b/u', '/\bnight\s*cream\b/u',
            '/\bgel\b/u', '/\blotion\b/u', '/\bcream\b/u', '/\bday\s*acne\b/u', '/\bnight\s*acne\b/u',
        ],
    ];

    public function prepare(Collection $itemRows): array
    {
        $selectionRows = $itemRows
            ->map(function ($row) {
                return [
                    'kode_transaksi' => trim((string) ($row->kode_transaksi ?? '')),
                    'nama_barang' => trim((string) ($row->nama_barang ?? '')),
                ];
            })
            ->filter(fn ($row) => $row['kode_transaksi'] !== '' && $row['nama_barang'] !== '')
            ->values();

        $preparedRows = $selectionRows
            ->map(function ($row) {
                $brand = $this->detectBrand((string) ($row['nama_barang'] ?? ''));

                return [
                    'kode_transaksi' => (string) ($row['kode_transaksi'] ?? ''),
                    'nama_barang' => (string) ($row['nama_barang'] ?? ''),
                    'brand' => $brand,
                ];
            })
            ->values();

        $selectionGrouped = $preparedRows
            ->groupBy('kode_transaksi')
            ->map(function (Collection $rows, string $kodeTransaksi) {
                $items = $rows
                    ->pluck('nama_barang')
                    ->flatMap(fn ($name) => $this->cleanSplit((string) $name))
                    ->filter(fn ($name) => $name !== '')
                    ->unique(fn ($name) => mb_strtolower((string) $name))
                    ->sort()
                    ->values();

                return [
                    'kode_transaksi' => $kodeTransaksi,
                    'nama_barang' => $items->implode(', '),
                    'jumlah_item' => $items->count(),
                ];
            })
            ->values();

        // Proses seluruh transaksi hasil cleaning, lalu buang transaksi dengan item <= 1.
        $selectedTransactions = $selectionGrouped
            ->values();

        $preprocessedTopTransactions = $selectedTransactions
            ->filter(fn ($row) => (int) ($row['jumlah_item'] ?? 0) > 1)
            ->values();

        $explodedRows = $preprocessedTopTransactions
            ->flatMap(function ($row) {
                $items = $this->cleanSplit((string) ($row['nama_barang'] ?? ''));
                $kodeTransaksi = (string) ($row['kode_transaksi'] ?? '');

                return collect($items)
                    ->map(fn ($item) => [
                        'kode_transaksi' => $kodeTransaksi,
                        'nama_barang' => $item,
                    ])
                    ->values();
            })
            ->values();

        $classifiedRows = $explodedRows
            ->map(function ($row) {
                $name = (string) ($row['nama_barang'] ?? '');
                $brand = $this->detectBrand($name);
                $productType = $this->getProductType($name);

                return [
                    'kode_transaksi' => (string) ($row['kode_transaksi'] ?? ''),
                    'nama_barang' => $name,
                    'brand' => $brand,
                    'product_type' => $productType,
                ];
            })
            ->values();

        $finalRows = $classifiedRows
            ->filter(fn ($row) => filled($row['nama_barang']))
            ->map(function ($row) {
                $brand = trim((string) ($row['brand'] ?? ''));
                $productType = trim((string) ($row['product_type'] ?? 'other'));
                $cleanName = $this->cleanProductName((string) ($row['nama_barang'] ?? ''));

                $itemName = $cleanName;
                if ($productType !== 'other' && $brand !== '') {
                    $itemName = $this->composeBrandProductTypeLabel($brand, $productType);
                }

                return [
                    'kode_transaksi' => (string) ($row['kode_transaksi'] ?? ''),
                    'brand' => $brand,
                    'product_type' => $productType,
                    'brand_jenis' => $itemName,
                ];
            })
            ->values();

        $finalRows = $this->mergeSimilarProductNames($finalRows);

        $cleanTransactionsByCode = $finalRows
            ->groupBy('kode_transaksi')
            ->map(function (Collection $rows) {
                return $rows
                    ->pluck('brand_jenis')
                    ->filter(fn ($value) => filled($value))
                    ->unique(fn ($value) => mb_strtolower((string) $value))
                    ->sort()
                    ->values();
            })
            ->filter(fn (Collection $items) => $items->isNotEmpty());

        $transactionsForApriori = $cleanTransactionsByCode
            ->values();

        $uniqueFinalItems = $transactionsForApriori
            ->flatten()
            ->unique(fn ($value) => mb_strtolower((string) $value))
            ->values();

        $droppedOther = $classifiedRows->where('product_type', 'other')->count();
        $droppedUnknownBrand = $classifiedRows
            ->where('product_type', '!=', 'other')
            ->filter(fn ($row) => ! filled($row['brand']))
            ->count();

        return [
            'transactionsForApriori' => $transactionsForApriori,
            'cleanTransactionsByCode' => $cleanTransactionsByCode,
            'finalRows' => $finalRows,
            'kddSummary' => [
                'selection_row_count' => $selectionRows->count(),
                'selection_transaction_count' => $selectionGrouped->count(),
                'brand_filtered_row_count' => $preparedRows->filter(fn ($row) => filled($row['brand']))->count(),
                'top_transaction_limit' => null,
                'top_transaction_count' => $selectedTransactions->count(),
                'top_transaction_after_single_item_removed' => $preprocessedTopTransactions->count(),
                'exploded_item_count' => $explodedRows->count(),
                'classified_item_count' => $classifiedRows->count(),
                'dropped_other_count' => $droppedOther,
                'dropped_unknown_brand_count' => $droppedUnknownBrand,
                'final_row_count' => $finalRows->count(),
                'final_transaction_count' => $transactionsForApriori->count(),
                'final_unique_item_count' => $uniqueFinalItems->count(),
            ],
        ];
    }

    /**
     * Mirroring notebook clean_split(): bersihkan karakter noise lalu split per koma.
     *
     * @return array<int, string>
     */
    private function cleanSplit(string $value): array
    {
        $text = str_replace(['[', ']', '\\'], '', (string) $value);
        $rawItems = explode(',', $text);

        $items = [];
        foreach ($rawItems as $rawItem) {
            $item = trim((string) $rawItem);

            while (Str::startsWith($item, ['"', "'"])) {
                $item = mb_substr($item, 1);
            }

            while (Str::endsWith($item, ['"', "'"])) {
                $item = mb_substr($item, 0, mb_strlen($item) - 1);
            }

            $item = $this->cleanProductName($item);

            if ($item !== '') {
                $items[] = $item;
            }
        }

        return array_values(array_unique($items, SORT_STRING));
    }

    private function detectBrand(string $name): ?string
    {
        $normalizedName = $this->normalize($name);
        if ($normalizedName === '') {
            return null;
        }

        $brandCandidates = collect(self::BRAND_HINTS)
            ->filter(fn ($brand) => trim((string) $brand) !== '')
            ->sortByDesc(fn ($brand) => mb_strlen((string) $brand))
            ->values();

        foreach ($brandCandidates as $brand) {
            $normalizedBrand = $this->normalize((string) $brand);
            if ($normalizedBrand !== '' && Str::contains($normalizedName, $normalizedBrand)) {
                return Str::title($normalizedBrand);
            }
        }

        // Fallback umum untuk data non-beauty: ambil token awal yang bukan ukuran/angka.
        $tokens = collect(explode(' ', $normalizedName))
            ->map(fn ($token) => trim((string) $token))
            ->filter(fn ($token) => $token !== '')
            ->values();

        $fallback = $tokens
            ->take(2)
            ->filter(function ($token) {
                if (preg_match('/^\d+(?:[\.,]\d+)?$/', $token) === 1) {
                    return false;
                }

                return ! in_array($token, ['kg', 'g', 'gr', 'gram', 'mg', 'ml', 'l', 'lt', 'liter', 'pcs', 'pc', 'pack', 'isi', 'netto'], true);
            })
            ->implode(' ');

        if ($fallback !== '') {
            return Str::title($fallback);
        }

        return null;
    }

    private function getProductType(string $name): string
    {
        $normalized = $this->normalize($name);
        if ($normalized === '') {
            return 'other';
        }

        foreach (self::PRODUCT_RULES_REGEX as $label => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $normalized) === 1) {
                    return $label;
                }
            }
        }

        return 'other';
    }

    private function normalize(string $text): string
    {
        $text = mb_strtolower((string) $text);
        $text = (string) preg_replace('/[^a-z0-9\s]/', ' ', $text);
        $text = (string) preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    private function cleanProductName(string $name): string
    {
        $name = trim((string) $name);
        $name = trim($name, " \t\n\r\0\x0B\"'[]()");
        // Remove common size/measurement tokens so variants like 50g vs 100g collapse.
        $name = (string) preg_replace('/\b(netto|isi)\b[:\s-]*/iu', ' ', $name);
        $name = (string) preg_replace('/\b\d+(?:[\.,]\d+)?\s*(kg|g|gr|gram|mg|ml|l|lt|liter|pcs|pc|sheet|sachet|pack)\b/iu', ' ', $name);

        // Normalize frequent aliases/typos that split the same product into multiple itemsets.
        $aliases = [
            '/\bgokyujun\b/iu' => 'gokujun',
            '/\bgokujyun\b/iu' => 'gokujun',
            '/\bfw\b/iu' => 'facial wash',
        ];

        foreach ($aliases as $pattern => $replacement) {
            $name = (string) preg_replace($pattern, $replacement, $name);
        }

        $name = (string) preg_replace('/\s+/', ' ', $name);

        return trim((string) $name);
    }

    private function composeBrandProductTypeLabel(string $brand, string $productType): string
    {
        $brand = trim($brand);
        $normalizedBrand = $this->normalize($brand);
        $normalizedProductType = $this->normalize($productType);

        if ($normalizedBrand === '' || $normalizedProductType === '') {
            return $brand;
        }

        $brandTokens = array_values(array_filter(explode(' ', $normalizedBrand), fn ($token) => $token !== ''));
        $typeTokens = array_values(array_filter(explode(' ', $normalizedProductType), fn ($token) => $token !== ''));

        if (empty($brandTokens) || empty($typeTokens)) {
            return $brand;
        }

        // Find largest suffix(prefix) overlap, e.g. "scora facial" + "facial wash" => overlap "facial".
        $maxOverlap = min(count($brandTokens), count($typeTokens));
        $overlapSize = 0;

        for ($size = $maxOverlap; $size >= 1; $size--) {
            $brandSuffix = array_slice($brandTokens, -$size);
            $typePrefix = array_slice($typeTokens, 0, $size);

            if ($brandSuffix === $typePrefix) {
                $overlapSize = $size;
                break;
            }
        }

        $remainingTypeTokens = array_slice($typeTokens, $overlapSize);
        if (empty($remainingTypeTokens)) {
            return $brand;
        }

        return trim($brand . ' ' . implode(' ', $remainingTypeTokens));
    }

    private function mergeSimilarProductNames(Collection $rows): Collection
    {
        $canonicalByKey = [];

        $lookupCache = []; // Quick O(1) hash-based lookup untuk exact matches
        $brandIndex = [];  // Index entries by brand untuk faster similarity filtering
        
        return $rows->map(function ($row) use (&$canonicalByKey, &$lookupCache, &$brandIndex) {
            $itemName = trim((string) ($row['brand_jenis'] ?? ''));
            if ($itemName === '') {
                return $row;
            }

            $brand = trim((string) ($row['brand'] ?? ''));
            $normalized = $this->normalize($itemName);
            $tokens = $this->tokenizeForSimilarity($normalized);

            if (empty($tokens)) {
                return $row;
            }

            // Quick hash lookup dulu - O(1) - untuk exact match
            $hashKey = md5($brand . '|' . $normalized);
            if (isset($lookupCache[$hashKey])) {
                $row['brand_jenis'] = $lookupCache[$hashKey];
                return $row;
            }

            $brandKey = mb_strtolower($brand);
            $entriesToCheck = $brandIndex[$brandKey] ?? [];

            // Similarity check hanya di entries dengan brand yang sama - O(m) bukan O(n)
            foreach ($entriesToCheck as $idx) {
                $entry = $canonicalByKey[$idx];
                if ($this->areSimilarTokens($tokens, $entry['tokens'])) {
                    $row['brand_jenis'] = $entry['label'];
                    $lookupCache[$hashKey] = $entry['label'];
                    return $row;
                }
            }

            // No match found - add new canonical entry
            $idx = count($canonicalByKey);
            $canonicalByKey[] = [
                'brand' => $brand,
                'label' => $itemName,
                'tokens' => $tokens,
            ];
            $lookupCache[$hashKey] = $itemName;
            
            // Index by brand untuk faster filtering di iterasi berikutnya
            if (!isset($brandIndex[$brandKey])) {
                $brandIndex[$brandKey] = [];
            }
            $brandIndex[$brandKey][] = $idx;

            return $row;
        })->values();
    }

    /**
     * @return array<int, string>
     */
    private function tokenizeForSimilarity(string $normalized): array
    {
        $tokens = collect(explode(' ', $normalized))
            ->map(fn ($token) => trim((string) $token))
            ->filter(fn ($token) => $token !== '' && mb_strlen($token) > 1)
            ->values()
            ->all();

        return array_values(array_unique($tokens, SORT_STRING));
    }

    /**
     * @param array<int, string> $left
     * @param array<int, string> $right
     */
    private function areSimilarTokens(array $left, array $right): bool
    {
        if ($left === $right) {
            return true;
        }

        $leftCount = count($left);
        $rightCount = count($right);

        if ($leftCount === 0 || $rightCount === 0) {
            return false;
        }

        $intersection = count(array_intersect($left, $right));
        $maxCount = max($leftCount, $rightCount);
        $overlapRatio = $intersection / $maxCount;

        // Consider names equivalent when they mostly share tokens and differ by at most one token.
        if ($overlapRatio >= 0.75 && abs($leftCount - $rightCount) <= 1) {
            return true;
        }

        return false;
    }
}
