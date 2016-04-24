<?php
namespace Jihe\Http\Controllers\Backstage;

use Jihe\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function login()
    {
        return view('backstage.home.login');
    }

    public function complete()
    {
        return view('backstage.home.complete');
    }
}
