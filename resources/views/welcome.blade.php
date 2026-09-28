<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BeautyQueenzy Apriori</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
</head>
<body class="min-h-screen bg-slate-100 text-slate-800">
    <main class="mx-auto flex min-h-screen max-w-3xl items-center px-4 py-10">
        <section class="w-full rounded-2xl bg-white p-8 shadow-sm">
            <div class="flex items-center gap-3">
                <img src="{{ asset('images/LOGO BQ.jpg') }}" alt="BeautyQueenzy" class="h-10 w-auto" />
                <p class="text-sm font-semibold tracking-wide text-slate-500">BEAUTYQUEENZY</p>
            </div>

            <h1 class="mt-4 text-3xl font-bold">Dashboard Analisis Pola Belanja</h1>
            <p class="mt-2 text-sm text-slate-600">
                Sistem analisis Apriori untuk melihat frequent itemset, association rule, support, dan confidence dari data transaksi.
            </p>

            <div class="mt-6 flex flex-wrap gap-2">
                <a href="{{ route('apriori.dashboard') }}" class="rounded-lg bg-sky-500 px-4 py-2 text-sm font-semibold text-white hover:bg-sky-600">
                    Buka Dashboard Apriori
                </a>
                <a href="{{ route('import.data') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                    Lihat Data Transaksi
                </a>
            </div>
        </section>
    </main>
</body>
</html>
