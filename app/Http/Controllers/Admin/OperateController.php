<?php
namespace Jihe\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Jihe\Http\Controllers\Controller;
use Jihe\Services\UserService;
use Jihe\Entities\User;

class OperateController extends Controller
{
    public function teams()
    {
        return view('admin.operate.teams', [
            'key' => 'teams'
        ]);
    }

    public function teamVerify()
    {
        return view('admin.operate.teamVerify', [
            'key' => 'teamVerify'
        ]);
    }

    public function teamAuthentication()
    {
        return view('admin.operate.teamAuthentication', [
            'key' => 'teamAuthentication'
        ]);
    }

    public function activities()
    {
        return view('admin.operate.activities', [
            'key' => 'activities'
        ]);
    }

    public function members()
    {
        return view('admin.operate.members', [
            'key' => 'members'
        ]);
    }    

    public function notices()
    {
        return view('admin.operate.notices', [
            'key' => 'notices'
        ]);
    }

    public function noticesList()
    {
        return view('admin.operate.noticesList', [
            'key' => 'notices'
        ]);
    }  

    public function systemNotices()
    {
        return view('admin.operate.systemNotices', [
            'key' => 'systemNotices'
        ]);
    }

    public function tags()
    {
        return view('admin.operate.tags', [
            'key' => 'tags'
        ]);
    }

    /**
     * List client user
     */
    public function listClientUser(Request $request, UserService $userService)
    {
        $this->validate($request, [
            'mobile'    => 'string|size:11',
            'nick_name' => 'string|between:1,16',
            'page'          => 'integer',
            'size'          => 'integer',
        ], [
            'mobile.size'           => '手机号格式错误',
            'nick_name.between'     => '昵称格式错误',
            'page.integer'              => '分页page错误',
            'size.integer'              => '分页size错误',
        ]);

        list($page, $pageSize) = $this->sanePageAndSize($request->input('page'),
                                            $request->input('size'));
        list($total, $users) = $userService->listUsers(
                                            $page, $pageSize,
                                            $request->input('mobile'),
                                            $request->input('nick_name'));

        return $this->json(['total' => $total,
                            'users' => array_map([$this, 'userToArray'], $users)]);
    }

    /**
     * show a specific client user profile
     */
    public function showClientUserDetail(Request $request, UserService $userService)
    {
        $this->validate($request, [
            'user_id' => 'required|integer',
        ], [
            'user_id.required'  => '未指定用户',
            'user_id.integer'   => '非法用户'
        ]);

        $profile = $userService->fetchProfile($request->input('user_id'));

        return $this->json($this->userToArray($profile));
    }

    /**
     * convert user Entity to Array
     *
     * @param \Jihe\Entities\User|null
     */
    private function userToArray(User $user)
    {
        return $user ? [
            'id'                => $user->getId(),
            'mobile'            => $user->getMobile(),
            'nick_name'         => $user->getNickName(),
            'register_time'     => $user->getRegisterAt()->format('Y-m-d H:i:s'),
            'status'            => $user->getStatus(),
            'status_desc'       => $user->getStatusDesc(),
            'tag_ids'           => array_map(function ($item) {
                                        return $item->getId();
                                    }, $user->getTags()->all()),
        ] : [];
    }
}
