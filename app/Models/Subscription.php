<?php

namespace App\Models;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Subscription extends Model
{
	protected $table = 'user_subscription';

    protected $fillable = [
        'userId',
        'orderID',
        'subscriptionID',
        'start_date',
        'end_date'
    ];

}
