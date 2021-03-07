@extends('layouts.app')

@push('scripts_before')
    <script>
        window.users = @json($users->items());
    </script>
@endpush

@section('content')
	<div class="py-3 container">
        <h2>Users</h2>
        <user-table>
        {{ $users->links() }}
	</div>
@endsection
