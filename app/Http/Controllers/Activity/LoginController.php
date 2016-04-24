<?php
namespace Jihe\Http\Controllers\Activity;

use Illuminate\Http\Request;
use Jihe\Http\Controllers\Activity\Controller;
use Jihe\Contracts\Repositories\PromotionActivityManagerRepository;

class LoginController extends Controller
{
    public function loginForm(Request $request)
    {
        return view("backstage.wap.{$request['templateSegment']}.login");
    }

    public function login(
        Request $request,
        PromotionActivityManagerRepository $promotionActivityManagerRepository
    ) {
        $this->validate($request, [
            'username'  => 'required|string|min:4|max:20',
            'password'  => 'required|string|min:6|max:20',
        ], [
            'username.required'     => '用户名未填写',
            'username.min'          => '用户名最少4个字符',
            'username.max'          => '用户名最多20个字符',
            'password.required'     => '密码未填写',
            'password.min'          => '密码最少4个字符',
            'password.max'          => '密码最多20个字符',
        ]);

        // Check manager
        $manager = $promotionActivityManagerRepository->findByName(
            $request->input('username')
        );
        if ( ! $manager || md5($request->input('password')) != $manager->getPassword())
        {
            return $this->redirectToLoginForm($request);
        }
       
        // Set session
        $request->session()->set('user', [
            'activityName'  => $request['activityName'],
            'username'      => $request->input('username'),
            'expireAt'      => time() + 3600,
        ]);

        return redirect("/act/{$request['activityName']}/list/all");
    }

    public function logout(
        Request $request
    ) {
        $request->session()->remove('user');
        return $this->redirectToLoginForm($request);
    }
}
