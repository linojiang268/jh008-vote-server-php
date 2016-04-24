<?php
namespace Jihe\Http\Controllers\Api;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Jihe\Http\Controllers\Controller;
use Jihe\Services\UserService;
use Jihe\Services\DeviceAuthService;
use Jihe\Services\VerificationService;
use Jihe\Services\TeamService;
use Jihe\Services\StorageService;
use Jihe\Exceptions\ExceptionCode;

class AuthController extends Controller
{
    /**
     * user registration
     */
    public function register(Request $request, UserService $userService,
                             VerificationService $verificationService,
                             StorageService $storageService)
    {
        $this->validate($request, [
            'code'      => 'required|string|size:4',
            'mobile'    => 'required|mobile',
            'password'  => 'required|between:6,32',
            'nick_name' => 'required|between:1,16',
            'gender'    => 'required|in:1,2',
            'birthday'  => 'required|date_format:Y-m-d',
            'tagIds'    => 'required|array',
            'avatar'    => 'required|mimes:jpeg,bmp,png',
        ], [
            'mobile.required'       => '手机号未填写',
            'mobile.mobile'         => '手机号格式错误',
            'code.required'         => '验证码未填写',
            'code.size'             => '验证码错误',
            'password.required'     => '密码未填写',
            'password.between'      => '密码错误',
            'nick_name.required'    => '昵称未填写',
            'nick_name.between'     => '昵称格式错误',
            'gender.required'       => '性别未填写',
            'gender.in'             => '性别格式错误',
            'birthday.required'     => '生日未填写',
            'birthday.date_format'  => '生日格式错误',
            'avatar.required'       => '未上传头像',
            'avatar.mimes'          => '头像类型错误',
            'tagIds.required'       => '未选择标签',
        ]);
        
        try {
            // check mobile verification code
            $verificationService->verify($request->input('mobile'),
                                         $request->input('code'));

            // save avatar
            $avatar = $storageService->storeAsImage($request->file('avatar'));

            // register the user
            $profile = [
                'avatar_url' => $avatar,
                'nick_name'  => $request->input('nick_name'),
                'gender'     => $request->input('gender'),
                'birthday'   => $request->input('birthday'),
                'tags'       => $request->input('tagIds'),
            ];
            $userService->register($request->input('mobile'),
                                   $request->input('password'),
                                   $profile);
        } catch (\Exception $ex) {
            // if register failed , delete avatar
            if (isset($avatar) && $avatar) {
                $storageService->remove($avatar);
            }
            return $this->jsonException($ex);
        }
        
        return $this->json('注册成功');
    }
    
    /**
     * send mobile verification to user for registration
     */
    public function sendVerifyCodeForRegistration(
        Request $request,
        VerificationService $verificationService
    ) {
        $this->validate($request, [
            'mobile' => 'required|mobile',
        ], [
            'mobile.required'   => '手机号未填写',
            'mobile.mobile'     => '手机号格式错误',
        ]);

        try {
            list($code, $sendInterval) = $verificationService->sendForRegistration($request->input('mobile'));
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
        
        return $this->json([
            'send_interval' => $sendInterval,
            'message'       => '发送成功',
        ]);
    }

    /**
     * send mobile verification to user for reset password
     */
    public function sendVerifyCodeForPasswordReset(
        Request $request,
        VerificationService $verificationService
    ) {
        $this->validate($request, [
            'mobile' => 'required|size:11',
        ], [
            'mobile.required'   => '手机号未填写',
            'mobile.size'       => '手机号格式错误',
        ]);

        try {
            list($code, $sendInterval) = $verificationService->sendForResetPassword($request->input('mobile'));
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        return $this->json([
            'send_interval' => $sendInterval,
            'message'       => '发送成功',
        ]);
    }

    /**
     * user login
     */
    public function login(Request $request,
                          Guard $auth,
                          UserService $userService,
                          TeamService $teamService,
                          DeviceAuthService $deviceAuthService
    ) {
        $this->validate($request, [
            'mobile'   => 'required|mobile',
            'password' => 'required|between:6,32',
        ], [
            'mobile.required'   => '手机号未填写',
            'mobile.mobile'     => '手机号格式错误',
            'password.required' => '密码未填写',
            'password.between'  => '密码错误',
        ]);

        try {
            if (!$userService->login($request->input('mobile'),
                                     $request->input('password'),
                                     $request->has('remember'))) {
                // hide the underlying errors so that malicious routines
                // won't know what the exact error is
                return $this->jsonException('密码错误');
            };
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        // handle device identifier for login user
        $identifier = $deviceAuthService->attachDeviceAfterLogin($request->input('mobile'));

        // response user profile after login
        $profile = $this->getProfile($auth->user()->getAuthIdentifier(), $userService,
                                    $teamService);
        if ( ! $profile) {
            return $this->json([
                'push_alias' => $identifier,
                'message' => '请先完善个人资料',
            ], ExceptionCode::USER_INFO_INCOMPLETE);
        }

        return $this->json(array_merge($profile, [
            'push_alias' => $identifier
        ]));
    }

    /**
     * Notify push alias aready bound
     */
    public function pushAliasBound(
        Guard $auth,
        DeviceAuthService $deviceAuthService
    ) {
        // close kick temporary
        //$deviceAuthService->checkDeviceAndKickUserOnOtherDevice($auth->user()->mobile);

        return $this->json();
    }

    /**
     * user logout
     */
    public function logout(
        Guard $auth,
        UserService $userService,
        DeviceAuthService $deviceAuthService
    ) {
        if ($auth->guest()) {
            return $this->json();
        }

        $mobile = $auth->user()->mobile;
        $userService->logout();

        $deviceAuthService->clearupAfterUserLogout($mobile);

        return $this->json();
    }


    /**
     * user reset password form
     */
    public function resetPasswordForm(Request $request)
    {
        return $this->json([
            '_token' => csrf_token(),
        ]);
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request, Guard $auth,
                                   UserService $userService
    ) {
        $this->validate($request, [
            'original_password' => 'required|between:6,32',
            'new_password'      => 'required|between:6,32',
        ], [
            'original_password.required'    => '当前密码未填写',
            'original_password.between'     => '当前密码格式错误',
            'new_password.required'         => '新密码未填写',
            'new_password.between'          => '新密码格式错误',
        ]);

        try {
            $userService->changePassword($auth->user()->getAuthIdentifier(),
                                         $request->input('original_password'),
                                         $request->input('new_password'));
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        return $this->json('修改密码成功');
    }

    /**
     * user reset password
     */
    public function resetPassword(Request $request, UserService $userService,
                                  VerificationService $verificationService)
    {
        $this->validate($request, [
            'code'     => 'required|size:4',
            'mobile'   => 'required|mobile',   // a 11-digit string
            'password' => 'required|between:6,32',
        ], [
            'code.required'      => '验证码未填写',
            'code.size'          => '验证码格式错误',
            'mobile.required'    => '手机号未填写',
            'mobile.mobile'      => '手机号格式错误',
            'password.required'  => '密码未填写',
            'password.between'   => '密码错误',
        ]);

        try {
            // check mobile verification code
            $verificationService->verify($request->input('mobile'),
                                         $request->input('code'));

            $userService->resetPassword($request->input('mobile'),
                                        $request->input('password'));
        } catch (\Exception $e) {
            return $this->jsonException($e);
        }

        return $this->json();
    }


    private function getProfile($user, UserService $userService,
                                TeamService $teamService)
    {
        $profile = $userService->fetchProfile($user);
        if ($profile->isNeedComplete()) {
            return [];
        }

        $avatarUrl = $profile->getAvatarUrl() ?: '';

        $tags = [];  // currently, tags are fixed, so we can only send their ids to client,
                     // and client knows how to render them
        foreach ($profile->getTags() as $tag) {
            /* @var $tag \Jihe\Entities\UserTag */
            $tags[] = $tag->getId();
        }

        $teams = $teamService->getTeamsByCreator($user);

        return [
            'user_id'       => $profile->getId(),
            'mobile'        => $profile->getMobile(),
            'identity'      => $profile->getIdentity(),
            'nick_name'     => $profile->getNickName(),
            'gender'        => $profile->getGender(),
            'birthday'      => $profile->getBirthday(),
            'avatar_url'    => $avatarUrl,
            'tag_ids'       => $tags,
            'is_team_owner' => ! empty($teams),
        ];
    }
}
