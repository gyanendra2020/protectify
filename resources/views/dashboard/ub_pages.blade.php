@extends('layouts.app')

@push('scripts_before')
    <script>
        window.ubPages = @json($ubPages->items());
    </script>
@endpush

@section('content')
	<div class="py-3 container">
        <h2>Pages</h2>
        <ub-page-filter-form
            :filter='@json($filter)'
            :do-allow-to-filter-by-user='@json($doAllowToFilterByUser)'
            :per-page='@json($perPage)'
        ></ub-page-filter-form>
        <ub-page-table :do-allow-to-delete='@json($doAllowToDelete)' :do-show-user-column='@json($doShowUserColumn)'></ub-page-table>
        {{ $ubPages->links() }}
	</div>
@endsection
