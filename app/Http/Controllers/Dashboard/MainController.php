<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MainController extends Controller
{
    public function index()
    {
        return redirect()->route('dashboard.ub_pages');
    }

    public function getCode()
    {
        return view('dashboard.get_code');
    }
}
