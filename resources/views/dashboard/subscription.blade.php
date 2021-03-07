@extends('layouts.app')

@push('scripts_before')
   
@endpush

@section('content')

	<div class="py-3 container">
        <h2>Subscribe Subscription Plan</h2>
       <div class="py-3 container">
        @if($result && (strtotime($result->end_date) > time() ))
            <div class="text-center alert alert-success" style="font-size: 20px"> You have subscribe Monthly Package !</div>
        @else
            <div id="paypal-button-container"></div>
        @endif
        
       </div>
	</div>
    

@endsection

