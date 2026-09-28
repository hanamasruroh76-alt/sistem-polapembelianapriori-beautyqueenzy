<?php

namespace App\Imports;

use Illuminate\Support\Facades\DB;

class TransactionsImport
{
    /**
     * Import rows ke tabel transactions (kode_transaksi, nama_barang).
     *
     * @param array<int, array<string, mixed>> $rows
     * @return array{success:int, failed:int}
     */
    public function importRows(array $rows): array
    {
        $successCount = 0;
        $failCount    = 0;

        foreach (array_chunk($rows, 1000) as $batch) {
            $inserts = [];
            $now = now();

            foreach ($batch as $row) {
                $kodeTransaksi = $this->extractField($row, [
                    'kode_transaksi', 'Kode Transaksi', 'kode transaksi',
                    'transaction_id', 'id transaksi', 'ID Transaksi',
                    'id trx', 'no transaksi', 'nomor transaksi',
                    'trx', 'invoice', 'invoice no', 'no invoice',
                ]);

                $namaBarang = $this->extractField($row, [
                    'nama_barang', 'Nama Barang', 'nama barang',
                    'item_name', 'produk', 'Produk', 'product', 'Product',
                    'nama_produk', 'Nama Produk', 'barang', 'item',
                    'nama item', 'produk dibeli',
                ]);

                // Fallback ke dua kolom pertama jika header tidak dikenali
                if ($kodeTransaksi === '' || $namaBarang === '') {
                    $values        = array_values($row);
                    $kodeTransaksi = $kodeTransaksi !== '' ? $kodeTransaksi : trim((string) ($values[0] ?? ''));
                    $namaBarang    = $namaBarang    !== '' ? $namaBarang    : trim((string) ($values[1] ?? ''));
                }

                if ($kodeTransaksi === '' || $namaBarang === '') {
                    $failCount++;
                    continue;
                }

                // Satu nama barang bisa berisi beberapa produk yang dipisah koma/baris
                foreach ($this->splitProductNames($namaBarang) as $produk) {
                    $inserts[] = [
                        'kode_transaksi' => $kodeTransaksi,
                        'nama_barang'    => $produk,
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ];
                }

                $successCount++;
            }

            if (!empty($inserts)) {
                DB::table('transactions')->insert($inserts);
            }
        }

        return [
            'success' => $successCount,
            'failed'  => $failCount,
        ];
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function extractField(array $row, array $possibleKeys): string
    {
        $normalizedRow = [];
        foreach ($row as $key => $value) {
            $normalizedRow[mb_strtolower(trim((string) $key))] = $value;
        }

        foreach ($possibleKeys as $candidate) {
            $normalized = mb_strtolower(trim($candidate));
            if (isset($normalizedRow[$normalized]) && trim((string) $normalizedRow[$normalized]) !== '') {
                return trim((string) $normalizedRow[$normalized]);
            }
        }

        return '';
    }

    private function splitProductNames(string $value): array
    {
        $parts = preg_split('/[\r\n,;]+/', $value);
        $result = [];

        foreach ($parts as $part) {
            $name = $this->cleanName($part);
            if ($name !== '') {
                $result[mb_strtolower($name)] = $name;
            }
        }

        return array_values($result);
    }

    private function cleanName(string $name): string
    {
        $name = trim($name);
        $name = trim($name, " \t\n\r\0\x0B\"'[]()");
        $name = (string) preg_replace('/\s+/', ' ', $name);

        return trim($name);
    }
}
