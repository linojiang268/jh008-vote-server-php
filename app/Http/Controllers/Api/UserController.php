<?php
namespace Jihe\Http\Controllers\Api;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Jihe\Http\Controllers\Controller;
use Jihe\Services\UserService;
use Jihe\Services\TeamService;

class UserController extends Controller
{
    /**
     * show user (user himself/herself or other's) profile
     */
    public function showProfile(Request $request, Guard $auth,
                                UserService $userService,
                                TeamService $teamService
    ) {
        $this->validate($request, [
            'user_id' => 'integer',
        ], [
            'user_id.integer' => '非法用户'
        ]);

        $user = $request->input('user_id') ?: $auth->user()->getAuthIdentifier();
        // we don't need verify input user id, as there will be no profile got
        // in that case

        $profile = $userService->fetchProfile($user);
        if (!$profile) { // no profile found
            return $this->json();
        }

        $tags = []; // collect tag's id as current tags are fixed, and as long as tag id is
                    // provided, client knows how to render them
                    // TODO: this logic is duplicated as what in AuthController while collecting
                    // tag identifiers
        foreach ($profile->getTags() as $tag) {
            /* @var $tag \Jihe\Entities\UserTag */
            $tags[] = $tag->getId();
        }

        $teams = $teamService->getTeamsByCreator($profile->getId());

        return $this->json([
            'user_id'    => $profile->getId(),
            'mobile'     => $profile->getMobile(),
            'identity'   => $profile->getIdentity(),
            'nick_name'  => $profile->getNickName(),
            'gender'     => $profile->getGender(),
            'birthday'   => $profile->getBirthday(),
            'avatar_url' => $profile->getAvatarUrl() ?: '',
            'tag_ids'    => $tags,
            'is_team_owner' => ! empty($teams),
        ]);
    }

    /**
     * user gets his/her profile updated
     */
    public function updateProfile(Request $request, Guard $auth,
                                  UserService $userService)
    {
        $profile = $this->validateRequestAndLoadProfile($request);

        // TODO: do we need old password, so that user can change his/her profile?
        //
        // $this->validate($request, [
        //     'password'  => 'between:6,32',
        // ], [
        //     'password.between'  => '密码错误',
        //]);

        try {
            $userService->updateProfile($auth->user()->getAuthIdentifier(), $profile);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        return $this->json('更新成功');
    }

    /**
     * user completes his/her profile
     */
    public function completeProfile(Request $request, Guard $auth,
                                    UserService $userService)
    {
        $profile = $this->validateRequestAndLoadProfile($request);

        try {
            $userService->completeProfile($auth->user()->getAuthIdentifier(), $profile);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }

        return $this->json('完善资料成功');
    }

    /**
     * @param Request $request
     * @return array
     */
    private function validateRequestAndLoadProfile(Request $request)
    {
        $this->validate($request, [
            'nick_name' => 'required|between:1,16',
            'gender'    => 'required|in:1,2',
            'birthday'  => 'required|date_format:Y-m-d',
            'tagIds'    => 'required|array',
            'avatar'    => 'mimes:jpeg,bmp,png',
        ], [
            'nick_name.required'   => '昵称未填写',
            'nick_name.between'    => '昵称格式错误',
            'gender.required'      => '性别未填写',
            'gender.in'            => '性别格式错误',
            'birthday.required'    => '生日未填写',
            'birthday.date_format' => '生日格式错误',
            'avatar.required'      => '未上传头像',
            'avatar.mimes'         => '头像类型错误',
            'avatar.size'          => '头像文件太大了',
            'tagIds.required'      => '未选择标签',
        ]);

        // collect profile from request
        $profile = [
            'nick_name' => $request->input('nick_name'),
            'gender'    => $request->input('gender'),
            'birthday'  => $request->input('birthday'),
            'tags'      => $request->input('tagIds'),
        ];

        if (null != $avatar = $request->file('avatar')) {
            $profile['avatar'] = [
                'path' => strval($avatar),
                'ext'  => $avatar->getClientOriginalExtension()
            ];
        }

        return $profile;
    }

    /**
     * reset identity of given user
     */
    public function resetIdentity(Guard $auth, UserService $userService)
    {
        try {
            $identity = $userService->resetIdentity($auth->user()->toEntity());

            if (!$identity) {
                return $this->json('刷新失败');
            }
            $user =$userService->findUserById($auth->user()->getAuthIdentifier());
            $user->setIdentitySalt('1234567890123456');

            return $this->json(['identity' => $identity]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
}
