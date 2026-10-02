@if(auth()->user()->hasPermission('reports.finance.export') && !($forPdf ?? false))
    <p>
        <a href="{{ route($routeName, array_merge($routeParameters ?? [], request()->except('format'), ['format' => 'csv'])) }}">Export CSV</a>
        <a href="{{ route($routeName, array_merge($routeParameters ?? [], request()->except('format'), ['format' => 'pdf'])) }}">Export PDF</a>
        <a href="{{ route($routeName, array_merge($routeParameters ?? [], request()->except('format'), ['format' => 'excel'])) }}">Export Excel</a>
    </p>
@endif
