@extends('layouts.app')

@section('content')
    <div class="py-3 container">
        <h2>Get Code</h2>
        <div class="mb-2">Put the following HTML code before <b>&lt;/body&gt;</b>:</div>
        <textarea class="form-control" rows="5">&lt;!-- UB Recorder --&gt;
&lt;script&gt;var ubUserId = {{ auth()->user()->id }};&lt;/script&gt;
&lt;script src=&quot;{{ url('js/ub-recorder.js') }}&quot;&gt;&lt;/script&gt;
&lt;!-- /UB Recorder --&gt;</textarea>
    </div>
@endsection
