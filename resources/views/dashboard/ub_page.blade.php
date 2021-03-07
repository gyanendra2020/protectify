@extends('layouts.app')

@push('scripts_after')
    <script>
		window.page = @json($ubPage);
    </script>
    <script src="/js/ub-player.js?{{ time() }}"></script>
	<script>
		let iframe = document.querySelector('iframe');
		iframe.src = '/storage/ub-pages/' + page.storage_path + 'snapshots/0.html';

		iframe.addEventListener('load', function(event) {
			ub.player.initialize(page);
		});
	</script>
@endpush

@section('content')
	<div class="py-3 container-fluid">
        <a href="{{ route('dashboard.ub_pages') }}" onclick="event.preventDefault(); window.history.back();">&lt; Back to records</a>
        <div class="container mt-3">
            <h3>Page Information</h3>
            <table class="table">
                <tr>
                    <td>Date</td>
                    <td>{{ $ubPage->created_at->isoFormat('LLLL') }}</td>
                </tr>
                <tr>
                    <td>ID</td>
                    <td>{{ $ubPage->id }}</td>
                </tr>
                <tr>
                    <td>URL</td>
                    <td>{{ $ubPage->url }}</td>
                </tr>
            </table>
        </div>
        <div class="container mt-3">
            <h3>Visitor Information</h3>
            <table class="table">
                <tr>
                    <td>IP</td>
                    <td>{{ $ubPage->visitor_ip }}</td>
                </tr>
                <tr>
                    <td>User-Agent</td>
                    <td>{{ $ubPage->visitor->user_agent }}</td>
                </tr>
            </table>
        </div>
		<div class="container mt-3">
            <h3>Recording</h3>
			<div class="ub-player mx-auto">
				<div class="ub-player-container">
					<iframe style="
						width: {{ $ubPage['initial_state']['size']['width'] }}px;
						height: {{ $ubPage['initial_state']['size']['height'] }}px;
						transform: translateX(-50%) translateY(-50%) scale(0.5);
					" src="about:blank" class="ub-player-iframe" onload=""></iframe>
					<div class="ub-player-overlay"></div>
					<div class="ub-player-container-button is-play"></div>
					<div class="ub-player-container-button is-pause"></div>
				</div>
				<div class="ub-player-controls">
					<div class="ub-player-controls-buttons">
						<div class="ub-player-controls-button is-play">&#9658;</div>
						<div class="ub-player-controls-button is-pause is-hidden">&#10074;&#10074;</div>
					</div>
					<div class="ub-player-controls-timeline">
						<div></div>
					</div>
					<div class="ub-player-controls-time">
						<span class="ub-player-controls-current-time">0:00:00</span>
						/
						<span class="ub-player-controls-total-time">0:00:00</span>
					</div>
				</div>
			</div>
        </div>
        @if (count($ubPage->forms) > 0)
            <div class="container mt-5">
                <h2>Forms ({{ count($ubPage->forms) }})</h2>
                @foreach ($ubPage->forms as $ubFormIndex => $ubForm)
                    <div class="mt-3">
                        <h4>Form #{{ $ubFormIndex + 1 }} (ID: {{ $ubForm->id }})</h4>
                        <table class="table w-100">
                            <thead>
                                <th scope="col">Title</th>
                                <th scope="col">Value</th>
                                <th scope="col">Name</th>
                            </thead>
                            <tbody>
                                @foreach ($ubForm->inputs as $ubFormInput)
                                    <tr>
                                        <td>{{ $ubFormInput->title }}</td>
                                        <td>{{ $ubFormInput->value }}</td>
                                        <td>{{ $ubFormInput->name }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </div>
        @endif
	</div>
@endsection
