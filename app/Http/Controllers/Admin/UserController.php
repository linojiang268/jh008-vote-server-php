<?php
namespace Jihe\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Jihe\Http\Controllers\Controller;
use Jihe\Services\Admin\UserService;
use Jihe\Entities\Admin\User;
use Auth;
use Jihe\Entities\Message;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Jihe\Dispatches\DispatchesMessage;
use Jihe\Services\MessageService;
use Jihe\Utils\PaginationUtil;

class UserController extends Controller
{
    use DispatchesJobs, DispatchesMessage;
    
    /**
     * user login
     */
    public function login(Request $request, UserService $userService)
    {
        $this->validate($request, [
            'username' => 'required|max:32',
            'password'  => 'required|between:6,32',
        ], [
            'username.required' => '用户名未填写',
            'username.max'      => '用户名格式错误',
            'password.required'  => '密码未填写',
            'password.between'   => '密码错误',
        ]);
        
        try {
            if (!$userService->login(
                                    $request->input('username'), 
                                    $request->input('password'), 
                                    $request->has('remember'))) {
                return redirect('/admin')->withErrors('密码错误')->withInput();
            }
            
            $role = Auth::user()->role;
            
            if (User::ROLE_ADMIN == $role) {
                return redirect('/admin/user');
            } elseif (User::ROLE_ACCOUNTANT == $role) {
                return redirect('/admin/accountant');
            } else {
                return redirect('/admin/teams');
            }
        } catch (\Exception $ex) {
            return redirect('/admin')->withErrors($ex->getMessage())->withInput();
        }
    }
    
    /**
     * user logout
     */
    public function logout(UserService $userService)
    {
        $userService->logout();

        return redirect()->guest('admin');
    }
    
    /**
     * reset user password
     */
    public function resetPassword(Request $request, UserService $userService)
    {
        $this->validate($request, [
            'user'      => 'required|integer',
            'password'  => 'required|between:6,32',
        ], [
            'user.required'     => '用户未指定',
            'user.integer'      => '用户错误',
            'password.required' => '密码未填写',
            'password.between'  => '密码错误',
        ]);
        
        try {
            $user = $userService->getUser($request->input('user'));
            if (null == $user || User::ROLE_ADMIN == $user->getRole()) {
                return $this->jsonException('没有权限修改此用户');
            }
            
            $userService->resetPassword($user->getId(), $request->input('password'));
            
            return $this->json('修改密码成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
    
    /**
     * user reset self password
     */
    public function resetSelfPassword(Request $request, UserService $userService)
    {
        $this->validate($request, [
            'password'     => 'required|between:6,32',
            'old_password' => 'required|between:6,32',
        ], [
            'password.required'     => '密码未填写',
            'password.between'      => '密码错误',
            'old_password.required' => '旧密码未填写',
            'old_password.between'  => '旧密码错误',
        ]);
        
        try {
            $userService->resetPassword(
                                        Auth::user()->id, 
                                        $request->input('password'), 
                                        $request->input('old_password'));
        
            return $this->json('修改密码成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
    
    /**
     * list users
     */
    public function listUsers(Request $request, UserService $userService)
    {
        $this->validate($request, [
            'page'         => 'integer|min:1',
            'size'         => 'integer|min:1',
        ], [
            'page.integer' => '分页page错误',
            'page.min'     => '分页page错误',
            'size.integer' => '分页size错误',
            'size.min'     => '分页size错误',
        ]);
        
        list($pageIndex, $pageSize) = $this->sanePageAndSize($request->input('page'), $request->input('size'));
        
        try {
            list($pages, $users) = $userService->getUsers($pageIndex, $pageSize);
            
            return $this->json([
                'pages' => $pages,
                'users' => array_map(function (User $user) {
                                return [
                                    'id'        => $user->getId(),
                                    'user_name' => $user->getUserName(),
                                    'role'      => $user->getRole(),
                                    'status'    => $user->getStatus(),
                                ];
                            }, $users),
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
    
    /**
     * add user
     */
    public function add(Request $request, UserService $userService)
    {
        $this->validate($request, [
            'user_name' => 'required|max:32',
            'password'  => 'required|between:6,32',
        ], [
            'password.required'     => '密码未填写',
            'password.between'      => '密码错误',
            'old_password.required' => '旧密码未填写',
            'old_password.between'  => '旧密码错误',
        ]);
    
        try {
            if (!in_array($request->input('role'), [User::ROLE_OPERATOR, User::ROLE_ACCOUNTANT])) {
                return $this->jsonException('您不能进行此操作');
            }
            
            $userService->add(
                              $request->input('user_name'), 
                              $request->input('password'), 
                              $request->input('role'));
    
            return $this->json('添加管理员成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
    
    /**
     * remove user
     */
    public function remove(Request $request, UserService $userService)
    {
        $this->validate($request, [
            'user' => 'required|integer',
        ], [
            'user.required' => '用户未指定',
            'user.integer'  => '用户错误',
        ]);
    
        try {
            $user = $userService->getUser($request->input('user'));
            
            if (!in_array($user->getRole(), [User::ROLE_OPERATOR, User::ROLE_ACCOUNTANT])) {
                return $this->jsonException('您不能进行此操作');
            }
    
            $userService->remove($user->getId());
    
            return $this->json('删除管理员成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
    
    /**
     * send system notice
     */
    public function sendNotice(Request $request)
    {
        $this->validate($request, [
            'to_all'   => 'required|boolean',
            'phones'    => 'array',
            'send_way' => 'required|min:3|max:4',
            'content'  => 'required|min:1|max:128',
        ], [
            'to_all.required'   => '群发设置未指定',
            'to_all.boolean'    => '群发设置错误',
            'phones.array'      => '用户设置错误',
            'send_way.required' => '发送方式未指定',
            'send_way.min'      => '发送方式错误',
            'send_way.max'      => '发送方式错误',
            'content.required'  => '内容未指定',
            'content.min'       => '内容错误',
            'content.max'       => '内容错误',
        ]);
    
        try {
            $phones = null;
            if (!$request->input('to_all')) {
                if (empty($request->input('phones'))) {
                    throw new \Exception('用户未指定');
                }
                $phones = $request->input('phones');
            }
    
            $message = [];
            $message['content'] = $request->input('content');
    
            $option = [];
            $option['record'] = true;
            if ('sms' == $request->input('send_way')) {
                $option['sms'] = true;
            } else {
                $option['push'] = true;
            }
    
            $this->sendToUsers($phones, $message, $option);
            return $this->json('发送成功');
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }
    
    /**
     * list activity notices
     */
    public function listNotices(Request $request, MessageService $messageService)
    {
        $this->validate($request, [
            'page' => 'integer',
            'size' => 'integer',
        ], [
            'page.integer'  => '分页page错误',
            'size.integer'  => '分页size错误',
        ]);
    
        try {
            list($page, $size) = $this->sanePageAndSize(
                                        $request->input('page'),
                                        $request->input('size'));
    
            list($total, $messages) = $messageService->getSystemMessages(
                                                       $page, $size);
    
            return $this->json([
                'pages'    => PaginationUtil::count2Pages($total, $size),
                'messages' => array_map(function (Message $message) {
                    return [
                        'id' => $message->getId(),
                        'content' => $message->getContent(),
                        'notified_type' => $message->getNotifiedType(),
                        'created_at' => $message->getCreatedAt(),
                    ];
                }, $messages),
                ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * render user update password page
     */
    public function renderUpdatePassword()
    {   
        return view('admin.user.updatePassword', [
            'key' => 'updatePassword'
        ]);
    }

    public function renderUserList()
    {
        return view('admin.user.account', [
            'key' => 'userList'
        ]);
    }

    public function renderCreateUser()
    {
        return view('admin.user.createAccount', [
            'key' => 'createUser'
        ]);
    }

}
