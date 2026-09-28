@extends('layouts.app')

@section('content')
<div class="app-content min-h-screen bg-slate-100">
  <div class="container-fluid py-4 space-y-4">
    <section class="rounded-2xl border border-rose-200 bg-rose-400 p-4 text-white shadow md:p-5">
      <div class="flex items-center gap-3">
        <div class="rounded-xl bg-white/90 p-2">
          <img src="{{ asset('images/LOGO BQ.jpg') }}" alt="BeautyQueenzy" class="h-10 w-auto rounded-md" />
        </div>
        <div>
          <p class="text-xs font-semibold tracking-wider text-rose-100">BEAUTYQUEENZY</p>
          <h1 class="text-xl font-bold md:text-2xl">Dashboard Analisis Pola Belanja</h1>
        </div>
      </div>
    </section>

    <section class="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
      <div class="relative overflow-hidden rounded-xl border border-rose-200 bg-rose-50 p-4 shadow">
        <p class="text-xs font-semibold uppercase tracking-wide text-rose-400">Total Transaksi</p>
        <p class="mt-1 text-3xl font-bold text-rose-700">{{ $displayTotalTransactions === null ? '-' : number_format($displayTotalTransactions) }}</p>
        <i class="bi bi-receipt absolute right-4 bottom-2 text-5xl text-rose-200 opacity-60 select-none"></i>
      </div>
      <div class="relative overflow-hidden rounded-xl border border-rose-200 bg-rose-50 p-4 shadow">
        <p class="text-xs font-semibold uppercase tracking-wide text-rose-400">Total Produk</p>
        <p class="mt-1 text-3xl font-bold text-rose-700">{{ $displayTotalProducts === null ? '-' : number_format($displayTotalProducts) }}</p>
        <i class="bi bi-box-seam absolute right-4 bottom-2 text-5xl text-rose-200 opacity-60 select-none"></i>
      </div>
      <div class="relative overflow-hidden rounded-xl border border-rose-200 bg-rose-50 p-4 shadow">
        <p class="text-xs font-semibold uppercase tracking-wide text-rose-400">Aturan Valid (Lift > 1)</p>
        <p class="mt-1 text-3xl font-bold text-rose-700">{{ ($hasProcessed && $hasData) ? ($validRulesCount ?? 0) : '-' }}</p>
        <i class="bi bi-check-circle absolute right-4 bottom-2 text-5xl text-rose-200 opacity-60 select-none"></i>
      </div>
      <div class="relative overflow-hidden rounded-xl border border-rose-200 bg-rose-50 p-4 shadow">
        <p class="text-xs font-semibold uppercase tracking-wide text-rose-400">Total Aturan</p>
        <p class="mt-1 text-3xl font-bold text-rose-700">{{ ($hasProcessed && $hasData) ? ($totalRulesCount ?? 0) : '-' }}</p>
        <i class="bi bi-diagram-3 absolute right-4 bottom-2 text-5xl text-rose-200 opacity-60 select-none"></i>
      </div>
    </section>

    <section id="parameter" class="rounded-2xl bg-white p-5 shadow-sm">
      <h2 class="text-xl font-bold text-slate-700">Unggah Data & Pengaturan Parameter</h2>

      @if (session('success'))
        <div class="mt-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-700">
          {{ session('success') }}
        </div>
      @endif

      @if (session('error'))
        <div class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
          {{ session('error') }}
        </div>
      @endif

      @if ($errors->any())
        <div class="mt-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-700">
          {{ $errors->first() }}
        </div>
      @endif

      <div class="mt-4 grid gap-3 lg:grid-cols-3">
        <div class="grid gap-3 lg:col-span-2">
          <form id="upload-form" method="POST" action="{{ route('import.store') }}" enctype="multipart/form-data" class="grid gap-2 rounded-xl border border-slate-200 p-3">
            @csrf
            <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Upload Excel/CSV</label>
            <input type="file" name="file" accept=".xlsx,.xls,.csv" class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 file:mr-4 file:rounded-md file:border-0 file:bg-sky-50 file:px-3 file:py-2 file:text-sm file:font-semibold file:text-sky-700 hover:file:bg-sky-100" required>
            <div class="flex flex-wrap gap-2">
              <button type="submit" class="rounded-lg bg-sky-500 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-600">
                Upload File
              </button>
              <a href="{{ route('import.downloadTemplate') }}" class="rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
                Download Template Excel
              </a>
              <a href="{{ route('import.data') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                Lihat Data Transaksi
              </a> 
              <button
                type="submit"
                formaction="{{ route('import.clear') }}"
                formmethod="POST"
                formnovalidate
                onclick="return confirm('Yakin ingin cleaning data? Semua output dashboard akan direset.');"
                class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100"
              >
                Cleaning History
              </button>
            </div>
          </form>

          <form method="POST" action="{{ route('apriori.filter') }}" class="grid gap-2 md:grid-cols-2">
            @csrf
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Min. Support (%)</label>
              <input type="number" name="min_support" min="0" max="100" step="0.1" value="{{ old('min_support', $minSupport ?? '') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-sky-400 focus:outline-none" required oninvalid="this.setCustomValidity('Silakan isi nilai minimum support terlebih dahulu.')" oninput="this.setCustomValidity('')">
            </div>
            <div>
              <label class="mb-1 block text-xs font-semibold uppercase text-slate-600">Min. Confidence (%)</label>
              <input type="number" name="min_confidence" min="0" max="100" step="0.1" value="{{ old('min_confidence', $minConfidence ?? '') }}" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-sky-400 focus:outline-none" required oninvalid="this.setCustomValidity('Silakan isi nilai minimum confidence terlebih dahulu.')" oninput="this.setCustomValidity('')">
            </div>
            <button type="submit" class="md:col-span-2 rounded-lg border border-emerald-300 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 hover:bg-emerald-100">
              Proses Apriori
            </button>
          </form>
        </div>

        <aside class="rounded-xl border border-slate-200 bg-slate-50 p-3">
          <div class="hidden lg:block">
            <h3 class="text-sm font-bold text-slate-700">Alur Penggunaan</h3>
            <div class="mt-3 grid gap-2 text-sm text-slate-600">
              <div class="rounded-lg bg-white px-3 py-2"><span class="font-semibold text-slate-800">1.</span> Upload file transaksi di dashboard ini.</div>
              <div class="rounded-lg bg-white px-3 py-2"><span class="font-semibold text-slate-800">2.</span> Cek data lewat tombol <span class="font-semibold">Lihat Data Transaksi</span>.</div>
              <div class="rounded-lg bg-white px-3 py-2"><span class="font-semibold text-slate-800">3.</span> Isi Min Support dan Min Confidence.</div>
              <div class="rounded-lg bg-white px-3 py-2"><span class="font-semibold text-slate-800">4.</span> Klik <span class="font-semibold">Proses Apriori</span> untuk isi tabel hasil.</div>
            </div>
          </div>

          <details class="lg:hidden">
            <summary class="cursor-pointer list-none text-sm font-bold text-slate-700">
              Alur Penggunaan
            </summary>
            <div class="mt-3 grid gap-2 text-sm text-slate-600">
              <div class="rounded-lg bg-white px-3 py-2"><span class="font-semibold text-slate-800">1.</span> Upload file transaksi di dashboard ini.</div>
              <div class="rounded-lg bg-white px-3 py-2"><span class="font-semibold text-slate-800">2.</span> Cek data lewat tombol <span class="font-semibold">Lihat Data Transaksi</span>.</div>
              <div class="rounded-lg bg-white px-3 py-2"><span class="font-semibold text-slate-800">3.</span> Isi Min Support dan Min Confidence.</div>
              <div class="rounded-lg bg-white px-3 py-2"><span class="font-semibold text-slate-800">4.</span> Klik <span class="font-semibold">Proses Apriori</span> untuk isi tabel hasil.</div>
            </div>
          </details>

        </aside>
      </div>

      <p class="mt-3 text-xs text-slate-500">
        Rumus: support(A→B) = jumlah transaksi mengandung A∪B / total transaksi, confidence(A→B) = support(A∪B) / support(A), lift(A→B) = confidence(A→B) / support(B).
      </p>
    </section>

    @php
      $supportEpsilon = 1.0E-6;
      $isSupportPassed = static function (?float $supportValue, ?float $minSupportValue) use ($supportEpsilon): bool {
        if ($minSupportValue === null) {
          return true;
        }

        $support = (float) ($supportValue ?? 0);
        $minSupportThreshold = (float) $minSupportValue;

        return $support >= ($minSupportThreshold - $supportEpsilon);
      };
    @endphp

    <section class="rounded-2xl bg-white p-5 shadow-sm">
      <h2 class="text-xl font-bold text-slate-700">Frekuensi Itemset</h2>
      @if($hasProcessed)
        <div class="mt-4 space-y-5">
          @forelse(collect($frequentLevels ?? collect())->sortKeys() as $level => $rows)
            @php
              $levelRows = collect($rows)->values();
              $currentMinSupport = $minSupport ?? null;
              $passedLevelRows = $levelRows
                ->filter(function ($row) use ($isSupportPassed, $currentMinSupport) {
                  return $isSupportPassed((float) ($row['support'] ?? 0), $currentMinSupport);
                })
                ->values();
            @endphp

            <div class="space-y-4 rounded-2xl border border-slate-200 bg-slate-50/60 p-3">
              <div class="flex items-center gap-2 text-sm font-semibold text-rose-600">
                <i class="bi bi-grid-3x3-gap-fill"></i>
                <span>Itemset {{ $level }}</span>
              </div>

              <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white">
                <table class="min-w-full divide-y divide-slate-200 text-sm">
                  <thead class="bg-rose-50 text-rose-700">
                    <tr>
                      <th class="px-3 py-2 text-center">No</th>
                      <th class="px-3 py-2 text-left">Item</th>
                      <th class="px-3 py-2 text-center">Jumlah</th>
                      <th class="px-3 py-2 text-center">Support</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($passedLevelRows as $index => $row)
                      <tr>
                        <td class="px-3 py-2 text-center">{{ $index + 1 }}</td>
                        <td class="px-3 py-2">{{ $row['itemset'] }}</td>
                        <td class="px-3 py-2 text-center">{{ $row['count'] }}</td>
                        <td class="px-3 py-2 text-center">{{ number_format((float) ($row['support'] ?? 0), 2, ',', '.') }}</td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="4" class="px-3 py-3 text-center text-slate-500">Belum ada itemset {{ $level }} yang memenuhi minimum support.</td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          @empty
            <p class="text-sm text-slate-500">Belum ada frequent itemset yang memenuhi minimum support.</p>
          @endforelse
        </div>
      @else
        <p class="mt-3 text-sm text-slate-500">Belum diproses. Isi parameter lalu klik Proses Apriori untuk melihat frequent itemset.</p>
      @endif
    </section>

    <section class="rounded-2xl bg-white p-5 shadow-sm">
      <div class="flex flex-wrap items-center justify-between gap-2">
        <h2 class="text-xl font-bold text-slate-700">Tabel Hasil Rekomendasi Pola Belanja</h2>
        @if ($hasProcessed)
          <a
            href="{{ route('apriori.recommendation.pdf', ['min_support' => $minSupport, 'min_confidence' => $minConfidence]) }}"
            class="inline-flex items-center gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100"
          >
            <i class="bi bi-file-earmark-pdf"></i>
            Download / Cetak PDF
          </a>
        @endif
      </div>
      @php
        $isConfidencePassed = static function (?float $confidenceValue, ?float $minConfidenceValue) use ($supportEpsilon): bool {
          if ($minConfidenceValue === null) {
            return true;
          }

          $confidence = (float) ($confidenceValue ?? 0);
          $minConfidenceThreshold = (float) $minConfidenceValue;

          return $confidence >= ($minConfidenceThreshold - $supportEpsilon);
        };

        $rulesForDisplay = $hasProcessed
          ? $rulesData->filter(function ($rule) use ($minSupport, $minConfidence, $isSupportPassed, $isConfidencePassed) {
              return $isSupportPassed((float) ($rule['support'] ?? 0), $minSupport)
                && $isConfidencePassed((float) ($rule['confidence'] ?? 0), $minConfidence);
            })->values()
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
      @endphp

      @if (! $hasProcessed)
        <p class="mt-3 text-sm text-slate-500">Belum diproses. Isi parameter lalu klik Proses Apriori.</p>
      @elseif ($rulesByItemsetSize->isEmpty())
        <p class="mt-3 text-sm text-slate-500">Belum ada aturan yang memenuhi parameter minimum.</p>
      @else
        <div class="mt-3 space-y-4">
          @foreach ($rulesByItemsetSize as $itemsetSize => $rules)
            <div class="overflow-x-auto rounded-xl border border-slate-200">
              <div class="bg-slate-100 px-3 py-2 text-sm font-semibold text-slate-700">
                Aturan Asosiasi Itemset {{ $itemsetSize }}
              </div>
              <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-rose-50 text-rose-700">
                  <tr>
                    <th class="px-3 py-2 text-left">No</th>
                    <th class="px-3 py-2 text-left">Rekomendasi Pola Belanja</th>
                    <th class="px-3 py-2 text-left">Support</th>
                    <th class="px-3 py-2 text-left">Confidence</th>
                    <th class="px-3 py-2 text-left">Lift Ratio</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white text-slate-700">
                  @foreach ($rules->values() as $index => $rule)
                    <tr>
                      <td class="px-3 py-2">{{ $index + 1 }}</td>
                      <td class="px-3 py-2">
                        Jika Konsumen Membeli {{ $rule['antecedent'] }}, Maka Konsumen Juga Akan Membeli {{ $rule['consequent'] }}
                      </td>
                      <td class="px-3 py-2">{{ number_format($rule['support'], 2) }}%</td>
                      <td class="px-3 py-2">{{ number_format($rule['confidence'], 2) }}%</td>
                      <td class="px-3 py-2">{{ number_format((float) ($rule['lift'] ?? 0), 3) }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endforeach
        </div>
      @endif
    </section>

  </div>
</div>
@endsection
