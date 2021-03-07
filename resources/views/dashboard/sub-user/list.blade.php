@extends('layouts.app')

@push('scripts_before')
   
@endpush

@section('content')

	<div class="py-3 container">
        <h2>Total of {{ucfirst(auth()->user()->name)}} users </h2>
       <div class="py-3 container">
        <div class="text-right">
          <div class="form-group">
          <a class="btn btn-md btn-success" href="{{url('dashboard/add-user')}}" title="Add new user details"> Add New User</a>
        </div>
        </div>
        @if(count($userListing) == 0)
          <div class="text-center alert alert-danger" style="font-size: 18px"> No Records Found ! </div>
        @else
        <table class="table">
          <thead>
            <tr>
              <th>Name</th>
              <th>Email</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
              @forelse($userListing as $user)
              <tr>
                <td>{{$user->name}}</td>
                <td>{{$user->email}}</td>
                <td>
                  @if($user->is_disabled == 0)
                   <a href="javascript:void(0)" title="User status is active" class="btn btn-sm btn-success">Active</a> 
                  @else
                    <a href="javascript:void(0)" title="User status is Inactive" class="btn btn-sm btn-danger">InActive</a>
                  @endif
                </td>
                <td>
                  <a href="{{url('dashboard/edit-user/'.$user->id)}}" title="Click to edit Profile" class="btn btn-sm btn-primary">Edit</a>
                </td>
              </tr>
              @empty
              @endforelse
          </tbody>
        </table>
           
       
        @endif
       </div>
	</div>
    

@endsection

