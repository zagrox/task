<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AboutController extends Controller
{
    /**
     * Display the About App page
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('about.index');
    }
} 