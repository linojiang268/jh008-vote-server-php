<?php
namespace Jihe\Http\Controllers\Api;

use Illuminate\Http\Request;
use Jihe\Http\Controllers\Controller;
use Validator;
use \Illuminate\Contracts\Auth\Guard;
use Jihe\Services\MessageService;
use Jihe\Entities\Message;

class MessageController extends Controller
{
    /**
     * list of messages
     */
    public function listMessages(Request $request, Guard $auth, MessageService $messageService)
    {
        $this->validate($request, [
            'type'                => 'required|integer|min:1|max:2',
            'last_requested_time' => 'required|date',
        ], [
            'type.required'                => 'type未填写',
            'type.integer'                 => 'type错误',
            'type.min'                     => 'type错误',
            'type.max'                     => 'type错误',
            'last_requested_time.required' => '最后时间未指定',
            'last_requested_time.date'     => '最后时间错误',
        ]);
        
        try {
            $lastRequestedTime = date('Y-m-d H:i:s');
            
            list($total, $messages) = 1 == $request->input('type') ? 
                                               $messageService->getSystemMessagesOf(
                                                                $auth->user()->toEntity(), 
                                                                null, null, $request->input('last_requested_time'))
                                               :
                                               $messageService->getNoticesOf(
                                                                $auth->user()->toEntity(), 
                                                                null, null, $request->input('last_requested_time'));
            
            return $this->json([
                                'last_requested_time' => $lastRequestedTime,
                                'total_num'           => $total,
                                'messages'            => array_map([$this, 'morphToMessageArray'], $messages),
                              ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * list new messages
     */
    public function listNewMessages(Request $request, Guard $auth, MessageService $messageService)
    {
        $this->validate($request, [
            'last_requested_time' => 'required|date',
        ], [
            'last_requested_time.required' => '最后时间未指定',
            'last_requested_time.date'     => '最后时间错误',
        ]);

        try {
            $lastRequestedTime = date('Y-m-d H:i:s');

            list($total, $messages) = $messageService->getMessagesOf($auth->user()->toEntity(), $request->input('last_requested_time'), false);

            return $this->json([
                'last_requested_time' => $lastRequestedTime,
                'new_messages'        => array_map([$this, 'morphToMessageArray'], $messages),
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * count new messages
     */
    public function checkNew(Request $request, Guard $auth, MessageService $messageService)
    {
        $this->validate($request, [
            'last_requested_time' => 'required|date',
        ], [
            'last_requested_time.required' => '最后时间未指定',
            'last_requested_time.date'     => '最后时间错误',
        ]);

        try {
            $lastRequestedTime = date('Y-m-d H:i:s');

            $total = $messageService->getMessagesOf($auth->user()->toEntity(), $request->input('last_requested_time'));

            return $this->json([
                'last_requested_time' => $lastRequestedTime,
                'new_messages'        => $total,
            ]);
        } catch (\Exception $ex) {
            return $this->jsonException($ex);
        }
    }

    /**
     * 
     * @param Message $message     \Jihe\Entities\Message
     * @return array
     */
    private function morphToMessageArray(Message $message)
    {
        $messageArr = [
            'id'         => $message->getId(),
            'content'    => $message->getContent(),
            'type'       => $message->getType(),
            'attributes' => $message->getAttributes() ?: null,
            'created_at' => $message->getCreatedAt(),
        ];
        
        if ($message->getTeam()) {
            $messageArr['team_name'] = $message->getTeam()->getName();
        }
        
        return $messageArr;
    }
}