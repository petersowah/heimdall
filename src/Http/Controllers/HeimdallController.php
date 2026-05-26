<?php

namespace PeterSowah\Heimdall\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class HeimdallController extends Controller
{
    public function __invoke(Request $request)
    {
        return view('heimdall::app');
    }
}
