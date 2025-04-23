<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DocumentationController extends Controller
{
    /**
     * Display the documentation home page.
     */
    public function index()
    {
        return view('documentation.index');
    }

    /**
     * Show the getting started page.
     */
    public function gettingStarted()
    {
        return view('documentation.getting-started');
    }
    
    /**
     * Display basic tutorials documentation.
     */
    public function basicTutorials()
    {
        return view('documentation.basic-tutorials');
    }
    
    /**
     * Display advanced tutorials documentation.
     */
    public function advancedTutorials()
    {
        return view('documentation.advanced-tutorials');
    }
    
    /**
     * Show the API documentation page.
     */
    public function api()
    {
        return view('documentation.api');
    }
    
    /**
     * Show the integration guide page.
     */
    public function integration()
    {
        return view('documentation.integration');
    }
    
    /**
     * Display the GitHub integration documentation.
     */
    public function github()
    {
        return view('documentation.github');
    }
    
    /**
     * Show the FAQ page.
     */
    public function faq()
    {
        return view('documentation.faq');
    }

    /**
     * Show the user guide page.
     */
    public function userGuide()
    {
        return view('documentation.user-guide');
    }
} 