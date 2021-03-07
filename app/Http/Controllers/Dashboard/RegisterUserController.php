<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
class RegisterUserController extends Controller
{
    public function index(){   
        $userId = auth()->user()->id;
        $userListing = User::where(['created_by' => $userId])->paginate(15);
        return View('dashboard.sub-user.list', compact('userListing'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function create( Request $request)
    {   
       	return View('dashboard.sub-user.add');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreUserRequest $request)
    {

    	$input = $request->validated();

    	$userId = auth()->user()->id;
    	$result = User::find($userId)->getSubscription()->orderBy('id', 'desc')->first();
    	$insert =  User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role'  => 'user',
            'created_by' => $userId,
            'is_disabled' => (!empty($result) && strtotime($result->end_date) > time()) ? 0 : 1,
        ]);

        if($insert){
        	return redirect('dashboard/add-user')->with('msg', 'User Added successfully !');
        }
	}

    /**
     * Display the specified resource.
     *
     * @param  \App\Subscription  $subscription
     * @return \Illuminate\Http\Response
     */

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Subscription  $subscription
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
     	$getDetails = User::where(['id' => $id])->select('id', 'name', 'email', 'is_disabled')->first();
     	return View('dashboard.sub-user.edit', compact('getDetails'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Subscription  $subscription
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request){
        $name = $request->name;
        $email = $request->email;
        $status = $request->status;
        $id = $request->id;

        $updateArr = [
        	'name'	=> $name,
        	'email'	=> $email,
        	'is_disabled'	=> $status
        ];
        $update = User::where(['id' => $id])->update($updateArr);
        if($update){
        	return redirect('dashboard/edit-user/'.$id)->with('msg', 'User details updated successfully !');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Subscription  $subscription
     * @return \Illuminate\Http\Response
     */
    public function destroy(Subscription $subscription)
    {
        //
    }
}
