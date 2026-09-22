<div>
    <p class="text-sm font-extrabold uppercase tracking-[0.18em] text-indigo-600">Content Platform</p>
    <h1 class="mt-2 text-4xl font-extrabold tracking-tight">Administrare e-test.ro</h1>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @foreach([
            ['Verticale', $stats['verticals']],
            ['Întrebări', $stats['questions']],
            ['Teste', $stats['tests']],
            ['Importuri', $stats['imports']],
            ['Întrebări în review', $stats['reviewQuestions']],
            ['Teste în review', $stats['reviewTests']],
            ['Raportări deschise', $stats['openReports']],
            ['Campanii sponsor active', $stats['activeSponsors']],
            ['Lead-uri noi', $stats['newLeads']],
            ['Resurse afiliere active', $stats['affiliateResources']],
            ['Newsletter active', $stats['newsletterActive']],
            ['White-label tenants', $stats['tenants']],
            ['Clienți API activi', $stats['apiClients']],
            ['API requests · 30 zile', $stats['apiRequests30d']],
        ] as [$label, $value])
            <div class="rounded-3xl border border-slate-200 bg-white p-6 dark:border-white/10 dark:bg-white/5">
                <p class="text-sm text-slate-500">{{ $label }}</p>
                <p class="mt-2 text-4xl font-extrabold">{{ $value }}</p>
            </div>
        @endforeach
    </div>
</div>
