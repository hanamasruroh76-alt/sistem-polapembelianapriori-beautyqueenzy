<?php

namespace App\Http\Controllers;

use App\Imports\TransactionsImport;
use App\Services\KddPreparationService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Collection;

class ImportController extends Controller
{
    public function __construct(private KddPreparationService $kddPreparationService)
    {
    }

    /**
     * Handle file upload
     */
    public function store(Request $request)
    {
        // Large imports can legitimately take longer than default 60 seconds.
        @set_time_limit(300);

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240', // Max 10MB
        ], [
            'file.required' => 'Silakan pilih file yang akan diunggah.',
            'file.mimes' => 'File harus berformat Excel atau CSV (xlsx, xls, csv).',
            'file.max' => 'Ukuran file tidak boleh lebih dari 10MB.',
        ]);

        try {
            $file = $request->file('file');
            
            \Log::info('File upload started', [
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime' => $file->getMimeType(),
                'extension' => $file->getClientOriginalExtension(),
            ]);
            
            // Store file temporarily - use proper path handling
            $tempDir = storage_path('app' . DIRECTORY_SEPARATOR . 'temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0777, true);
            }
            
            $filename = uniqid() . '.' . strtolower($file->getClientOriginalExtension());
            $fullPath = $tempDir . DIRECTORY_SEPARATOR . $filename;
            
            // Move uploaded file
            if (!$file->move($tempDir, $filename)) {
                throw new \Exception('Gagal memindahkan file yang diunggah.');
            }
            
            \Log::info('File stored', [
                'full_path' => $fullPath,
                'exists' => file_exists($fullPath),
                'size' => file_exists($fullPath) ? filesize($fullPath) : 'N/A',
            ]);
            
            // Verify file exists
            if (!file_exists($fullPath)) {
                throw new \Exception('File tidak ditemukan setelah proses unggah.');
            }
            
            $extension = strtolower($file->getClientOriginalExtension());
            $rowNum = 0;
            $rowsData = [];
            
            // Handle different file formats
            if ($extension === 'csv') {
                \Log::info('Processing as CSV');
                // Read CSV file
                $handle = fopen($fullPath, 'r');
                if (!$handle) {
                    throw new \Exception('File CSV tidak dapat dibuka.');
                }
                
                $headers = fgetcsv($handle);
                $headers = array_map('trim', $headers);
                
                \Log::info('CSV Headers found', ['headers' => $headers]);
                
                while (($data = fgetcsv($handle)) !== false) {
                    if (empty(array_filter($data))) {
                        continue;
                    }
                    
                    $rowData = [];
                    foreach ($headers as $index => $header) {
                        $rowData[$header] = isset($data[$index]) ? trim($data[$index]) : '';
                    }
                    
                    $rowsData[] = $rowData;
                    $rowNum++;
                }
                fclose($handle);
                
            } else if ($extension === 'xlsx') {
                \Log::info('Processing as XLSX');

                $xlsxRows = $this->readXlsxRows($fullPath);

                if (empty($xlsxRows)) {
                    throw new \Exception('XLSX tidak berisi data yang bisa dibaca.');
                }

                $headers = array_map(fn ($value) => trim((string) $value), array_shift($xlsxRows));
                \Log::info('XLSX Headers', ['headers' => $headers]);

                if (count(array_filter($headers, fn ($header) => $header !== '')) === 0) {
                    throw new \Exception('Header XLSX kosong/tidak valid. Pastikan baris pertama berisi judul kolom.');
                }

                foreach ($xlsxRows as $values) {
                    if (empty(array_filter($values, fn ($value) => trim((string) $value) !== ''))) {
                        continue;
                    }

                    $rowData = [];
                    foreach ($headers as $index => $header) {
                        if ($header === '') {
                            continue;
                        }

                        $rowData[$header] = isset($values[$index]) ? trim((string) $values[$index]) : '';
                    }

                    if (!empty($rowData)) {
                        $rowsData[] = $rowData;
                        $rowNum++;
                    }
                }
                
            } else {
                throw new \Exception('Format XLS tidak didukung. Gunakan format XLSX atau CSV.');
            }
            
            \Log::info('Rows extracted from file', ['count' => $rowNum]);
            
            // Import the data
            $importer = new TransactionsImport();
            $importResult = $importer->importRows($rowsData);
            $successCount = (int) ($importResult['success'] ?? 0);
            $failCount = (int) ($importResult['failed'] ?? 0);
            
            // Clean up - remove temp file
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            
            // Get updated stats
            $transactionCount = \DB::table('transactions')->distinct('kode_transaksi')->count('kode_transaksi');
            $totalItems = \DB::table('transactions')->distinct('nama_barang')->count('nama_barang');

            if ($successCount === 0 || $totalItems === 0) {
                return Redirect::route('apriori.dashboard')
                    ->with('error', '❌ Upload gagal diproses. Kolom produk tidak terbaca. Gunakan header: Kode Transaksi & Nama Barang.');
            }

            \Log::info('Import completed', [
                'success' => $successCount, 
                'failed' => $failCount,
                'total_transactions' => $transactionCount,
                'total_items' => $totalItems,
            ]);

            return Redirect::route('apriori.dashboard')
                ->with('success', "✅ Import selesai! {$successCount} baris diproses, {$failCount} gagal. Total: {$transactionCount} transaksi, {$totalItems} produk unik")
                ->with('uploaded', true)
                ->with('uploaded_total_transactions', $transactionCount)
                ->with('uploaded_total_items', $totalItems);
        } catch (\Exception $e) {
            \Log::error('Import fatal error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            
            return Redirect::back()
                ->with('error', '❌ Import gagal: ' . $e->getMessage())
                ->withInput();
        }
    }

    private function readXlsxRows(string $fullPath): array
    {
        $zip = new \ZipArchive();
        $zipOpen = $zip->open($fullPath);

        if ($zipOpen !== true) {
            throw new \Exception("File XLSX tidak dapat dibuka sebagai ZIP (Kode error: $zipOpen).");
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        $zip->close();

        if ($sheetXml === false) {
            throw new \Exception('Tidak menemukan worksheet utama di file XLSX.');
        }

        $namespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $sharedStrings = $this->parseSharedStrings($sharedStringsXml, $namespace);

        $sheet = simplexml_load_string($sheetXml);
        if (!$sheet) {
            throw new \Exception('Gagal membaca struktur XML worksheet XLSX.');
        }

        $sheetChildren = $sheet->children($namespace);
        if (!isset($sheetChildren->sheetData)) {
            return [];
        }

        $rows = [];
        foreach ($sheetChildren->sheetData->row as $row) {
            $cellsByIndex = [];

            foreach ($row->c as $cell) {
                $attributes = $cell->attributes();
                $reference = (string) ($attributes['r'] ?? '');
                $columnIndex = $this->columnIndexFromReference($reference);
                $value = $this->extractXlsxCellValue($cell, $sharedStrings, $namespace);

                $cellsByIndex[$columnIndex] = $value;
            }

            if (empty($cellsByIndex)) {
                continue;
            }

            ksort($cellsByIndex);
            $maxColumn = max(array_keys($cellsByIndex));

            $normalized = [];
            for ($index = 0; $index <= $maxColumn; $index++) {
                $normalized[] = $cellsByIndex[$index] ?? '';
            }

            $rows[] = $normalized;
        }

        return $rows;
    }

    private function parseSharedStrings($sharedStringsXml, string $namespace): array
    {
        if ($sharedStringsXml === false) {
            return [];
        }

        $shared = simplexml_load_string($sharedStringsXml);
        if (!$shared) {
            return [];
        }

        $sharedChildren = $shared->children($namespace);
        if (!isset($sharedChildren->si)) {
            $sharedChildren = $shared->children();
        }
        $values = [];

        foreach (($sharedChildren->si ?? []) as $item) {
            $itemChildren = $item->children($namespace);
            if (!isset($itemChildren->t) && !isset($itemChildren->r)) {
                $itemChildren = $item->children();
            }

            if (isset($itemChildren->t)) {
                $values[] = (string) $itemChildren->t;
                continue;
            }

            $richText = '';
            foreach (($itemChildren->r ?? []) as $run) {
                $runChildren = $run->children($namespace);
                if (!isset($runChildren->t)) {
                    $runChildren = $run->children();
                }
                $richText .= (string) ($runChildren->t ?? '');
            }
            $values[] = $richText;
        }

        return $values;
    }

    private function extractXlsxCellValue(\SimpleXMLElement $cell, array $sharedStrings, string $namespace): string
    {
        $attributes = $cell->attributes();
        $type = (string) ($attributes['t'] ?? '');
        $children = $cell->children($namespace);
        if (!isset($children->v) && !isset($children->is)) {
            $children = $cell->children();
        }

        if ($type === 's') {
            $index = (int) ($children->v ?? 0);
            return (string) ($sharedStrings[$index] ?? '');
        }

        if ($type === 'inlineStr') {
            if (!isset($children->is)) {
                return (string) ($children->v ?? '');
            }

            $inlineChildren = $children->is->children($namespace);
            if (!isset($inlineChildren->t) && !isset($inlineChildren->r)) {
                $inlineChildren = $children->is->children();
            }
            if (isset($inlineChildren->t)) {
                return (string) $inlineChildren->t;
            }

            $text = '';
            foreach (($inlineChildren->r ?? []) as $run) {
                $runChildren = $run->children($namespace);
                if (!isset($runChildren->t)) {
                    $runChildren = $run->children();
                }
                $text .= (string) ($runChildren->t ?? '');
            }

            return $text;
        }

        return (string) ($children->v ?? '');
    }

    private function columnIndexFromReference(string $reference): int
    {
        if ($reference === '') {
            return 0;
        }

        preg_match('/[A-Z]+/i', $reference, $matches);
        $columnLetters = strtoupper($matches[0] ?? 'A');

        $index = 0;
        $length = strlen($columnLetters);
        for ($i = 0; $i < $length; $i++) {
            $index = ($index * 26) + (ord($columnLetters[$i]) - 64);
        }

        return max($index - 1, 0);
    }

    /**
     * Clear all imported data
     */
    public function clear()
    {
        try {
            \DB::table('transactions')->delete();

            session()->forget([
                'uploaded',
                'uploaded_total_transactions',
                'uploaded_total_items',
                'apriori_processed',
                'apriori_min_support',
                'apriori_min_confidence',
                'apriori_processing_mode',
            ]);

            return Redirect::route('apriori.dashboard')
                ->with('success', '✅ Data berhasil dibersihkan. Semua output dashboard telah direset.');
        } catch (\Exception $e) {
            return Redirect::back()
                ->with('error', '❌ Gagal membersihkan data: ' . $e->getMessage());
        }
    }

    /**
     * Show imported data preview
     */
    public function data()
    {
        $itemRows = \DB::table('transactions')
            ->select('kode_transaksi', 'nama_barang')
            ->whereNotNull('nama_barang')
            ->where('nama_barang', '!=', '')
            ->cursor()
            ->collect();

        $kddResult = $this->kddPreparationService->prepare($itemRows);

        $cleanTransactionsByCode = $kddResult['cleanTransactionsByCode'] ?? collect();
        $transactionsCollection = collect($cleanTransactionsByCode)
            ->map(function (Collection $items, string $kodeTransaksi) {
                return (object) [
                    'kode_transaksi' => $kodeTransaksi,
                    'daftar_barang' => $items->implode(', '),
                    'jumlah_barang' => $items->count(),
                ];
            })
            ->sortBy('kode_transaksi', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $transactions = $this->paginateCollection($transactionsCollection, 25);

        $uniqueProducts = collect($cleanTransactionsByCode)
            ->flatten()
            ->map(fn ($item) => trim((string) $item))
            ->filter(fn ($item) => $item !== '')
            ->unique(fn ($item) => mb_strtolower((string) $item))
            ->sort()
            ->values()
            ->all();

        $finalRows = collect($kddResult['finalRows'] ?? collect());

        $productTypes = $finalRows
            ->pluck('product_type')
            ->map(fn ($type) => trim((string) $type))
            ->filter(fn ($type) => $type !== '' && $type !== 'other')
            ->countBy()
            ->sortDesc()
            ->map(fn ($count, $jenis) => [
                'jenis' => $jenis,
                'count' => $count,
            ])
            ->values();

        $brandStats = $finalRows
            ->pluck('brand')
            ->map(fn ($brand) => trim((string) $brand))
            ->filter(fn ($brand) => $brand !== '')
            ->countBy()
            ->sortDesc()
            ->map(fn ($count, $brand) => [
                'brand' => $brand,
                'count' => $count,
            ])
            ->values();

        $kddSummary = $kddResult['kddSummary'] ?? [];
        $uniqueProductCount = (int) ($kddSummary['final_unique_item_count'] ?? count($uniqueProducts));
        $totalRows = (int) ($kddSummary['final_row_count'] ?? 0);

        return view('import.data', [
            'transactions' => $transactions,
            'uniqueProductCount' => $uniqueProductCount,
            'totalRowItems' => $totalRows,
            'uniqueProducts' => $uniqueProducts,
            'productTypes' => $productTypes,
            'brandStats' => $brandStats,
        ]);
    }

    private function paginateCollection(Collection $items, int $perPage): LengthAwarePaginator
    {
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $total = $items->count();
        $currentItems = $items
            ->forPage($currentPage, $perPage)
            ->values();

        return new LengthAwarePaginator(
            $currentItems,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }

    private function splitProductNames(string $value): array
    {
        $parts = preg_split('/[\r\n,;]+/', $value);
        $result = [];

        foreach ($parts as $part) {
            $name = $this->cleanProductName((string) $part);
            if ($name === '') {
                continue;
            }

            $result[$this->normalizeProductKey($name)] = $name;
        }

        return array_values($result);
    }

    private function cleanProductName(string $name): string
    {
        $name = trim($name);
        $name = trim($name, " \t\n\r\0\x0B\"'[]()");
        $name = preg_replace('/\s+/', ' ', $name);

        return trim((string) $name);
    }

    private function normalizeProductKey(string $name): string
    {
        $name = mb_strtolower($this->cleanProductName($name));

        return trim((string) $name);
    }

    /**
     * Download sample Excel template
     */
    public function downloadTemplate()
    {
        $templatePath = public_path('templates/template_transaksi_apriori.xlsx');

        if (!file_exists($templatePath)) {
            return Redirect::back()->with('error', 'Template Excel tidak ditemukan. Silakan hubungi admin.');
        }

        return response()->download(
            $templatePath,
            'template_transaksi_apriori.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }
}
