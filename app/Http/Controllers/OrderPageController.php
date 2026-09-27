<?php

namespace App\Http\Controllers;

class OrderPageController extends Controller
{
    public function create()
    {
        return view('orders.create');
    }

    public function history()
    {
        return view('orders.history');
    }
}
