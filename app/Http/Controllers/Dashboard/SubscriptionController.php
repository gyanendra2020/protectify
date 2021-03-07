<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {   
        $userId = auth()->user()->id;
        $result = User::find($userId)->getSubscription()->orderBy('id', 'desc')->first();
        return View('dashboard.subscription', compact('result'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create( Request $request)
    {   
        $userId = auth()->user()->id;
        $orderID = $request->orderID;
        $subscriptionID = $request->subscriptionID;
        $start_date = date('d-m-Y H:i');
        $end_date =   date('d-m-Y H:i', strtotime("+30 days"));

        $insert = Subscription::Create([
            'userId' => $userId,
            'orderID' => $orderID,
            'subscriptionID' => $subscriptionID,
            'start_date' => $start_date,
            'end_date' => $end_date, 
        ]);

        if($insert){
            $data = ['status' => 'success', 'msg' => 'Subscribe successfully !'];
            echo json_encode($data);
            exit();
        }

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Subscription  $subscription
     * @return \Illuminate\Http\Response
     */
    public function show(Subscription $subscription)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Subscription  $subscription
     * @return \Illuminate\Http\Response
     */
    public function edit(Subscription $subscription)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Subscription  $subscription
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Subscription $subscription)
    {
        //
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
