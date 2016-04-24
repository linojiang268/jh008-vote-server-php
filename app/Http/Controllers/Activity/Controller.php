<?php
namespace Jihe\Http\Controllers\Activity;

use Illuminate\Http\Request;
use Jihe\Http\Controllers\Controller as BaseController;

abstract class Controller extends BaseController
{

    protected function checkAuth(Request $request)
    {
        $session = $request->session();
        $user = $session->get('user');
        if ( ! $user ||
            $user['activityName'] != $request['activityName'] ||
            $user['expireAt'] < time()
        ) {
            return false;
        }

        $user['expireAt'] = time() + 3600;
        $session->set('user', $user);

        return true;
    }

    protected function redirectToLoginForm(Request $request)
    {
        return redirect("/act/{$request['activityName']}/login");
    }
}
