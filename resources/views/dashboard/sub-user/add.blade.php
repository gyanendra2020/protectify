@extends('layouts.app')

@push('scripts_before')
   
@endpush

@section('content')

	<div class="py-3 container">
      <h2>Add user details </h2>
         @foreach ($errors->all() as $error)
            <li class="alert alert-danger">{{ $error }}</li>
        @endforeach

        @if (session('msg'))
            <div class="alert alert-success">
                {{ session('msg') }}
            </div>
        @endif

       <div class="text-right">
          <div class="form-group">
          <a class="btn btn-md btn-success" href="{{url('dashboard/user-list')}}"> Users Listing</a>
        </div>
        </div>  

      <form id="userform" action="{{url('dashboard/add-user')}}" method="post">
        {{ csrf_field() }}
        <div class="row">
          <div class="col-md-12">
            <div class="form-group">
            <label>Username </label>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="Enter username ">
          </div>
          </div>

          <div class="col-md-12">
            <div class="form-group">
            <label>Email </label>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="Enter Email">
          </div>
          </div> 

          <div class="col-md-12">
            <div class="form-group">
            <label>Password </label>
            <input type="password" name="password" class="form-control" value="{{ old('password') }}" placeholder="Password">
          </div>
          </div>

           <div class="col-md-12">
            <div class="form-group">
            <input type="submit" name="submit" class="btn btn-md btn-success">
          </div>
          </div>
        
        </div>


      </form>

	</div>
    

@endsection

