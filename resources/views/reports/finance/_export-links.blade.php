@if(auth()->user()->hasPermission('reports.finance.export') && !($forPdf ?? false))
    <nav class="flex flex-wrap gap-2" aria-label="Report exports">
        <a class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" href="{{ route($routeName, array_merge($routeParameters ?? [], request()->except('format'), ['format' => 'csv'])) }}"><x-icon name="arrow-down-tray" size="sm" /> Export CSV</a>
        <a class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" href="{{ route($routeName, array_merge($routeParameters ?? [], request()->except('format'), ['format' => 'pdf'])) }}"><x-icon name="arrow-down-tray" size="sm" /> Export PDF</a>
        <a class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" href="{{ route($routeName, array_merge($routeParameters ?? [], request()->except('format'), ['format' => 'excel'])) }}"><x-icon name="arrow-down-tray" size="sm" /> Export Excel</a>
    </nav>
@endif
