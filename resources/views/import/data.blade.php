@extends('layouts.app')

@section('content')
<div class="app-content min-h-screen bg-slate-100">
    <div class="container-fluid py-4 space-y-4">
        <!-- Header -->
        <section class="rounded-xl border border-rose-200 bg-gradient-to-r from-rose-400 to-rose-500 p-4 text-white shadow">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-xl font-bold md:text-2xl">Data Transaksi (Hasil Cleaning KDD)</h1>
                    <p class="mt-1 text-xs opacity-90 md:text-sm">Transaksi bersih dengan format item siap analisis Apriori.</p>
                </div>
                <a href="{{ route('apriori.dashboard') }}#parameter" class="rounded-lg bg-white/20 px-4 py-1.5 text-sm font-semibold backdrop-blur transition hover:bg-white/30">
                    ← Kembali ke Dashboard
                </a>
            </div>
        </section>

        <!-- Main Content -->
        <div class="grid gap-4 lg:grid-cols-3">
            <div class="lg:col-span-2 rounded-2xl bg-white p-5 shadow-sm border border-slate-200">
                <div class="flex items-center gap-2 mb-4">
                    <i class="bi bi-list-ul text-lg text-slate-600"></i>
                    <h2 class="text-xl font-bold text-slate-800">Daftar Transaksi</h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b-2 border-slate-300 bg-slate-50">
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">Kode Transaksi</th>
                                <th class="px-4 py-3 text-left font-semibold text-slate-700">Produk (Brand Jenis)</th>
                                <th class="px-4 py-3 text-center font-semibold text-slate-700 w-20">Qtty</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            @forelse($transactions as $transaction)
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-4 py-3">
                                        <span class="inline-block rounded-md bg-sky-100 px-3 py-1 text-xs font-bold text-sky-700">
                                            {{ $transaction->kode_transaksi }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-slate-700">
                                        <span class="text-sm">{{ $transaction->daftar_barang }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-block rounded-full bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-700">
                                            {{ $transaction->jumlah_barang }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-4 py-6 text-center text-slate-500">
                                        <i class="bi bi-inbox text-2xl block mb-2 opacity-50"></i>
                                        Belum ada data transaksi
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if ($transactions->hasPages())
                    <div class="mt-4 border-t border-slate-200 pt-4">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-xs text-slate-600">
                                <span class="font-semibold">{{ $transactions->firstItem() ?? 0 }} - {{ $transactions->lastItem() ?? 0 }}</span>
                                dari
                                <span class="font-semibold">{{ number_format($transactions->total()) }}</span>
                                hasil
                            </p>
                            <div class="text-sm">
                                {{ $transactions->links() }}
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="space-y-4">
                <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-200">
                    <div class="flex items-center gap-2 mb-4">
                        <i class="bi bi-tags text-lg text-violet-600"></i>
                        <h2 class="text-lg font-bold text-slate-800">Jenis Produk</h2>
                        <span class="ml-auto rounded-full bg-violet-100 px-2 py-1 text-xs font-bold text-violet-700">
                            {{ $productTypes->count() }}
                        </span>
                    </div>

                    <div class="max-h-96 overflow-y-auto space-y-2">
                        @forelse($productTypes as $item)
                            <div class="flex items-center justify-between rounded-lg border border-violet-100 bg-gradient-to-r from-violet-50 to-white p-3 hover:shadow-sm transition">
                                <span class="font-medium text-slate-700 text-sm">{{ $item['jenis'] }}</span>
                            </div>
                        @empty
                            <p class="text-center text-slate-500 py-4 text-sm">Belum ada data</p>
                        @endforelse
                    </div>
                </div>

                <div class="rounded-2xl bg-white p-5 shadow-sm border border-slate-200">
                    <div class="flex items-center gap-2 mb-4">
                        <i class="bi bi-bag-check text-lg text-rose-600"></i>
                        <h2 class="text-lg font-bold text-slate-800">Brand</h2>
                        <span class="ml-auto rounded-full bg-rose-100 px-2 py-1 text-xs font-bold text-rose-700">
                            {{ $brandStats->count() }}
                        </span>
                    </div>

                    <div class="max-h-96 overflow-y-auto space-y-2">
                        @forelse($brandStats as $item)
                            <div class="flex items-center justify-between rounded-lg border border-rose-100 bg-gradient-to-r from-rose-50 to-white p-3 hover:shadow-sm transition">
                                <span class="font-medium text-slate-700 text-sm">{{ $item['brand'] }}</span>
                            </div>
                        @empty
                            <p class="text-center text-slate-500 py-4 text-sm">Belum ada data</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
