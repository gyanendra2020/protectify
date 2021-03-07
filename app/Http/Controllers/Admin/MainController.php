<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class MainController extends Controller
{
    public function index()
    {
        return redirect()->route('admin.users');
    }

    public function users()
    {
        $users = User::orderBy('id', 'desc')->paginate(50);

        return view('admin.users', [
            'users' => $users,
        ]);
    }
}
