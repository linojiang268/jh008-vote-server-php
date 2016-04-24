<?php
namespace Jihe\Services;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Jihe\Contracts\Repositories\LoginDeviceRepository;
use Jihe\Dispatches\DispatchesSms;
use Jihe\Contracts\Repositories\MessageRepository;
use Jihe\Entities\Team;
use Jihe\Entities\Activity;
use Jihe\Entities\User;
use Jihe\Entities\Message;
use Jihe\Utils\PaginationUtil;
use Jihe\Dispatches\DispatchesPushMessage;
use Jihe\Entities\TeamMember;

class MessageService
{
    use DispatchesJobs, DispatchesSms, DispatchesPushMessage;

    const SEND_BY_SYSTEM = 'system';

    const SEND_BY_TEAM = 'team';

    const SEND_BY_ACTIVITY = 'system';

    const PUSH_SUBSECTION_COUNT = 50;

    const SMS_SUBSECTION_COUNT = 50;

    const ACTIVITY_SEND_NOTICES_MAX_TIMES = 3;

    /**
     *
     * @var \Jihe\Contracts\Repositories\MessageRepository
     */
    private $messages;

    /**
     *
     * @var \Jihe\Services\TeamMemberService
     */
    private $teamMemberService;

    /**
     *
     * @var \Jihe\Services\ActivityMemberService
     */
    private $activityMemberService;

    /**
     *
     * @var \Jihe\Services\UserService
     */
    private $userService;

    /**
     * @var \Jihe\Contracts\Repositories\LoginDeviceRepository
     */
    private $loginDeviceRepository;

    /**
     *
     * @var \Jihe\Services\TeamService
     */
    private $teamService;

    public function __construct(MessageRepository $messageRepository,
                                TeamMemberService $teamMemberService,
                                ActivityMemberService $activityMemberService,
                                UserService $userService,
                                LoginDeviceRepository $loginDeviceRepository,
                                TeamService $teamService)
    {
        $this->messages              = $messageRepository;
        $this->teamMemberService     = $teamMemberService;
        $this->activityMemberService = $activityMemberService;
        $this->userService           = $userService;
        $this->loginDeviceRepository = $loginDeviceRepository;
        $this->teamService           = $teamService;
    }

    /**
     * get notices relate to given user
     *
     * @param User   $user
     * @param string $lastCreatedAt
     * @param boolean $onlyCount
     * @return array
     */
    public function getMessagesOf(User $user, $lastCreatedAt, $onlyCount = true)
    {
        $teams = $this->teamMemberService->listEnrolledTeams($user->getId());
        $activities = $this->activityMemberService->getNotOverActivitiesOfUser($user->getId());
        return $this->messages->findMessagesOfUser($user->getId(), $teams, $activities, $lastCreatedAt, $onlyCount);
    }
    
    /**
     * get system messages
     *
     * @param int $page
     * @param int $size
     * @return array
     */
    public function getSystemMessages($page, $size)
    {
        return $this->messages->findSystemMessages($page, $size);
    }
    
    /**
     * get given team notices
     *
     * @param Team $team
     * @param int  $page
     * @param int  $size
     * @return array
     */
    public function getTeamNotices(Team $team, $page, $size)
    {
        return $this->messages->findTeamMessages($team->getId(), $page, $size);
    }
    
    /**
     * get given activity notices
     *
     * @param Activity $activity
     * @param int      $page
     * @param int      $size
     * @return array
     */
    public function getActivityNotices(Activity $activity, $page, $size)
    {
        return $this->messages->findActivityMessages($activity->getId(), $page, $size);
    }

    /**
     * count notices of activity that sended to mass
     *
     * @param $activity  entity of activity
     * @return int
     */
    public function countActivityNoticesToMass(Activity $activity)
    {
        return $this->messages->countActivityNotices($activity->getId(), ['only_mass' => true]);
    }

    /**
     * count notices of activity that sended to mass
     *
     * @param $activity  entity of activity
     * @return int
     */
    public function restActivityNoticesToMass(Activity $activity)
    {
        $count = $this->countActivityNoticesToMass($activity);
        return ($count >= self::ACTIVITY_SEND_NOTICES_MAX_TIMES) ?
            0 : self::ACTIVITY_SEND_NOTICES_MAX_TIMES - $count;
    }
    
    /**
     * get system messages relate to given user
     *
     * @param User   $user
     * @param int    $page
     * @param int    $size
     * @param string $lastCreatedAt            
     * @return array
     */
    public function getSystemMessagesOf(User $user, $page, $size, $lastCreatedAt = null)
    {
        return $this->messages->findSystemMessagesOf($user->getId(), $page, $size, $lastCreatedAt);
    }

    /**
     * get given team notices relate to given user
     *
     * @param User   $user
     * @param Team   $team
     * @param int    $page
     * @param int    $size
     * @param string $lastCreatedAt            
     * @return array
     */
    public function getTeamNoticesOf(User $user, Team $team, $page, $size, $lastCreatedAt = null)
    {
        $activities = $this->activityMemberService->getNotOverActivitiesOfUser($user->getId(), $team->getId());
        
        return $this->messages->findUserMessagesOf($team->getId(), $activities, $user->getId(), $page, $size, $lastCreatedAt);
    }

    /**
     * get notices relate to given user
     *
     * @param User   $user
     * @param int    $page
     * @param int    $size
     * @param string $lastCreatedAt            
     * @return array
     */
    public function getNoticesOf(User $user, $page, $size, $lastCreatedAt = null)
    {
        $teams = $this->teamMemberService->listEnrolledTeams($user->getId());
        $activities = $this->activityMemberService->getNotOverActivitiesOfUser($user->getId());
        return $this->messages->findUserMessages($teams, $activities, $user->getId(), $page, $size, $lastCreatedAt);
    }

    /**
     * send message to users
     *
     * @param array|null $phones  target phones, send to all if given null
     * @param array $message      message need sent, keys taken:
     *                             - content               (string)content of message
     *                             - type                  (string)type given messag relate to,
     *                                                      - text(default or when sms sent)
     *                                                      - url
     *                                                      - team
     *                                                      - activity
     *                             - attributes            (array)attributes given messag relate to, keys taken,
     *                                                      - null enable when type is text
     *                                                      - team_id     morph when type is team
     *                                                      - activity_id morph when type is activity
     *                                                      - url         morph when type is url
     * @param array $option       option of sent, keys taken:
     *                             - record                (boolean)whether message need to record,
     *                                                     false(default)
     *                             - record_attributes     attributes also need to record,
     *                                                      rules:
     *                                                       - attributes is null when message is system message
     *                                                       - team must not null when message is team notice
     *                                                       - team and activity must not null when message activity notice
     *                                                      keys taken:
     *                                                       - team     (int)team of message sent by
     *                                                       - activity (int)activity of message sent by
     *                             - push                  (boolean)whether message need to push,
     *                                                     false(default)
     *                             - sms                   (boolean)whether message need to send by sms,
     *                                                     false(default)
     * @return boolean
     */
    public function sendToUsers(array $phones = null, array $message, array $option = [])
    {
        $this->ensureParamsAvailable($message, $option);

        if (array_get($option, 'record', false)) {
            $this->handlerRecord($phones, $message, array_get($option, 'record_attributes', []));
        }

        // send to all when given phones is null
        if (array_get($option, 'push', false)) {
            $this->handlerPushToUsers($phones, $message);
        }

        // send to all when given phones is null
        if (array_get($option, 'sms', false)) {
            $this->handlerSms(array_get($message, 'content'), $phones);
        }
    }

    /**
     * send message to team members
     *
     * @param \Jihe\Entities\Team $team
     * @param array|null $phones  target phones, send to all members of given team if given null
     * @param array $message      message need sent, keys taken:
     *                             - content               (string)content of message
     *                             - type                  (string)type given messag relate to,
     *                                                      - team(default)
     *                                                      - text
     *                                                      - url
     *                                                      - activity
     *                             - attributes            (array)attributes given messag relate to, keys taken,
     *                                                      - null enable when type is text or team
     *                                                      - url         morph when type is url
     *                                                      - activity_id morph when type is activity
     * @param array $option       option of sent, keys taken:
     *                             - record                (boolean)whether message need to record,
     *                                                     false(default)
     *                             - push                  (boolean)whether message need to push,
     *                                                     false(default)
     *                             - sms                   (boolean)whether message need to send by sms,
     *                                                     false(default)
     * @return boolean
     */
    public function sendToTeamMembers(Team $team, array $phones = null, array $message, array $option = [])
    {
        if (is_null($team)) {
            throw new \Exception('社团未指定');
        }

        $this->ensureParamsAvailable($message, $option, $team);

        if (array_get($option, 'record', false)) {
            $this->handlerRecord($phones, $message, array_get($option, 'record_attributes', []));
        }

        // send to all when given phones is null
        if (array_get($option, 'push', false)) {
            if (empty($phones)) {
                $this->pushTeamTopicMessage($team->getId(), $message);
            } else {
                $this->handlerPushToUsers($phones, $message);
            }
        }

        // send to all when given phones is null
        if (array_get($option, 'sms', false)) {
            $this->handlerSms(array_get($message, 'content'), empty($phones) ? $team : $phones);
        }
    }

    /**
     * send message to activity members
     *
     * @param \Jihe\Entities\Activity $activity
     * @param array|null $phones  target phones, send to all members of given team if given null
     * @param array $message      message need sent, keys taken:
     *                             - content               (string)content of message
     *                             - type                  (string)type given messag relate to,
     *                                                      - activity(default)
     *                                                      - text
     *                                                      - url
     *                                                      - team
     *                             - attributes            (array)attributes given messag relate to, keys taken,
     *                                                      - null enable when type is text or activity
     *                                                      - url         morph when type is url
     *                                                      - team_id     morph when type is team
     * @param array $option       option of sent, keys taken:
     *                             - record                (boolean)whether message need to record,
     *                                                     false(default)
     *                             - push                  (boolean)whether message need to push,
     *                                                     false(default)
     *                             - sms                   (boolean)whether message need to send by sms,
     *                                                     false(default)
     * @return boolean
     */
    public function sendToActivityMembers(Activity $activity, array $phones = null, array $message, array $option = [])
    {
        if (is_null($activity)) {
            throw new \Exception('活动未指定');
        }

        $this->ensureParamsAvailable($message, $option, $activity);

        if (array_get($option, 'record', false)) {
            $this->handlerRecord($phones, $message, array_get($option, 'record_attributes'));
        }

        // send to all when given phones is null
        if (array_get($option, 'push', false)) {
        if (empty($phones)) {
                $this->pushActivityTopicMessage($activity->getId(), $message);
            } else {
                $this->handlerPushToUsers($phones, $message);
            }
        }

        // send to all when given phones is null
        if (array_get($option, 'sms', false)) {
            $this->handlerSms(array_get($message, 'content'), empty($phones) ? $activity : $phones);
        }
    }

    /**
     *
     * @param array $message            
     * @param array $option            
     * @param null|Team|Activity
     */
    private function ensureParamsAvailable(array &$message, array &$option)
    {
        $target = null;
        if (count(func_get_args()) > 2) {
            $target = func_get_args()[2];
        }
        
        $this->fillDefaultParams($message, $option, $target);
        
        $this->ensureMessageAvailable($message);
        
        if (array_get($option, 'record', false)) {
            $this->ensureRecordAttributesAvailable(array_get($option, 'attributes', []));
        }
    }

    /**
     * fill default params into message array and option array
     * according to the necessary params
     *
     * @param array $message            
     * @param array $option            
     */
    private function fillDefaultParams(array &$message, array &$option, $target)
    {
        if (array_get($option, 'sms', false)) {
            array_set($message, 'notified_type', Message::NOTIFIED_TYPE_SMS);
        } elseif (array_get($option, 'push', false)) {
            array_set($message, 'notified_type', Message::NOTIFIED_TYPE_PUSH);
        }

        if (! array_key_exists('type', $message)) {
            if ($target instanceof Team) {
                array_set($message, 'type', 'team');

                $attributes = array_get($message, 'attributes', []);
                if (! array_key_exists('team_id', $attributes)) {
                    array_set($attributes, 'team_id', $target->getId());
                    array_set($message, 'attributes', $attributes);
                }
            } elseif ($target instanceof Activity) {
                array_set($message, 'type', 'activity');

                $attributes = array_get($message, 'attributes', []);
                if (! array_key_exists('activity_id', $attributes)) {
                    array_set($attributes, 'activity_id', $target->getId());
                    array_set($message, 'attributes', $attributes);
                }
            } else {
                array_set($message, 'type', 'text');
            }
        }
        
        if (array_get($option, 'record', false)) {
            if ($target instanceof Team) {
                $recordAttributes = array_get($option, 'record_attributes', []);
                if (! array_key_exists('team', $recordAttributes)) {
                    array_set($recordAttributes, 'team', $target->getId());
                    array_set($option, 'record_attributes', $recordAttributes);
                }
            } elseif ($target instanceof Activity) {
                if (array_get($option, 'record', false)) {
                    $recordAttributes = array_get($option, 'record_attributes', []);
                    if (! array_key_exists('activity', $recordAttributes)) {
                        array_set($recordAttributes, 'team', $target->getTeam()->getId());
                        array_set($recordAttributes, 'activity', $target->getId());
                        array_set($option, 'record_attributes', $recordAttributes);
                    }
                }
            }
        }
    }

    /**
     * ensure message is not invalid
     *
     * @param array $message            
     * @throws \Exception
     */
    private function ensureMessageAvailable(array $message)
    {
        if (is_null($message)) {
            return;
        }
        
        if (! array_key_exists('content', $message)) {
            throw new \Exception('message invalid: content is not exists.');
        }
        
        $type = array_get($message, 'type', 'text');
        $attributes = array_get($message, 'attributes', []);
        
        // team_id and activity_id must be int
        if ('team' == $type) {
            if (! is_int(array_get($attributes, 'team_id'))) {
                throw new \Exception('message invalid: team_id is not int');
            }
        } elseif ('activity' == $type) {
            if (! is_int(array_get($attributes, 'activity_id'))) {
                throw new \Exception('message invalid: activity_id is not int');
            }
        } elseif ('url' == $type) {
            if (! is_string(array_get($attributes, 'url'))) {
                throw new \Exception('message invalid: url is not string');
            }
        }
    }

    /**
     * ensure record attributes is not invalid
     *
     * @param array $recordAttributes            
     * @throws \Exception
     */
    private function ensureRecordAttributesAvailable(array $recordAttributes)
    {
        if (is_null($recordAttributes)) {
            return;
        }
        
        // team and activity must be int
        if (array_key_exists('team', $recordAttributes)) {
            if (! is_int(array_get($recordAttributes, 'team'))) {
                throw new \Exception('record_attributes invalid: team is not int');
            }
        }
        
        // team must exists when activity is exists
        if (array_key_exists('activity', $recordAttributes)) {
            if (! is_int(array_get($recordAttributes, 'activity'))) {
                throw new \Exception('record_attributes invalid: activity is not int');
            }
            
            if (! array_key_exists('team', $recordAttributes)) {
                throw new \Exception('record_attributes invalid: team is not exists when activity is exists');
            }
        }
    }

    private function handlerRecord(array $phones = null, array $message, array $attributes = [])
    {
        $messages = $this->record(
                                  array_get($message, 'content'), 
                                  array_get($message, 'type'), 
                                  array_get($message, 'attributes'),
                                  array_get($message, 'notified_type', Message::NOTIFIED_TYPE_PUSH),
                                  array_get($attributes, 'team'), 
                                  array_get($attributes, 'activity'), 
                                  is_null($phones) ? null : array_values($this->userService->fetchUsersForMessagePush($phones)));
        return ! empty($messages);
    }

    /**
     * storage given message
     *
     * @param string $content            
     * @param string $type            
     * @param array  $attributes
     * @param string $notifiedType
     * @param int    $team
     * @param int    $activity
     * @param int    $users
     * @return \Jihe\Entities\Message|multitype
     */
    public function record($content, $type = null, array $attributes = null,
                           $notifiedType = null, $team = null, $activity = null, $users = null)
    {
        $messages = null;

        if (empty($users)) {
            $messages = $this->messages->add($this->morphToMessage($content, $type, $attributes,
                                                                   $notifiedType, $team, $activity, null));
        } else {
            $messages = [];
            foreach ($users as $user) {
                array_push($messages,
                           $this->messages->add($this->morphToMessage($content, $type, $attributes,
                                                                      $notifiedType, $team, $activity, $user)));
            }
        }

        if (!empty($messages) && !empty($team)) {
            $this->notifyTeam($team);
        }

        return $messages;
    }

    private function notifyTeam($team)
    {
        return $this->teamService->notify($team, ['notices']);
    }

    private function morphToMessage($content, $type = null, array $attributes = null, $notifiedType = null, $team = null, $activity = null, $user = null)
    {
        $messsage = (new Message())->setContent($content);
        
        if (is_null($type) || 'text' == $type) {
            $messsage->setType('text');
        } else {
            $messsage->setType($type);
        }
        
        if (! empty($attributes)) {
            $messsage->setAttributes($attributes);
        }

        if ($notifiedType) {
            $messsage->setNotifiedType($notifiedType);
        }
        
        if ($team) {
            $messsage->setTeam(((new Team())->setId($team)));
        }
        
        if ($activity) {
            $messsage->setActivity(((new Activity())->setId($activity)));
        }
        
        if ($user) {
            $messsage->setUser(((new User())->setId($user)));
        }
        
        return $messsage;
    }

    private function handlerPushToUsers(array $phones = null, array $message)
    {
        if (empty($phones)) {
            $this->pushAllTopicMessage($message);
            return;
        }

        $alias = array_values($this->loginDeviceRepository->findClientIdentifiers($phones));
        
        $pages = ceil(count($alias) / self::PUSH_SUBSECTION_COUNT);
        
        for ($page = 0; $page < $pages; $page ++) {
            $subsectionPhones = array_slice($alias, $page, self::PUSH_SUBSECTION_COUNT);
            
            $this->pushAliasMessage($subsectionPhones, $message);
        }
    }

    /**
     *
     * @param string $content            
     * @param null|array|Team|Activity
     */
    private function handlerSms($content)
    {
        $target = null;
        if (count($args = func_get_args()) > 1) {
            $target = $args[1];
        }
        
        if (! empty($target) && is_array($target)) {
            $this->sendSms($target, $content);
            return;
        } elseif (! empty($target) && $target instanceof Team) {
            $this->sendSmsToTeamMembers($target, $content);
            return;
        } elseif (! empty($target) && $target instanceof Activity) {
            $this->sendSmsToActivityMembers($target, $content);
            return;
        }
        
        $this->sendSmsToAll($content);
    }

    private function sendSmsToAll($content)
    {
        // send sms to all users by subsection
        list ($total, $users) = $this->userService->listUsers(1, self::SMS_SUBSECTION_COUNT);
        if ($total <= 0 || empty($users)) {
            return;
        }
        $pages = PaginationUtil::count2Pages($total, self::SMS_SUBSECTION_COUNT);
        
        for ($page = 1; $page <= $pages; $page ++) {
            $users = (1 == $page) ? $users : $this->userService->listUsers($page, self::SMS_SUBSECTION_COUNT)[1];
            
            $this->sendSms(array_map(function (User $user) {
                return $user->getMobile();
            }, $users), $content);
        }
    }

    private function sendSmsToTeamMembers(Team $team, $content)
    {
        // send sms to team members by subsection
        list ($total, $members) = $this->teamMemberService->listMembers($team->getId(), 1, self::SMS_SUBSECTION_COUNT);
        if ($total <= 0 || empty($members)) {
            return;
        }
        $pages = PaginationUtil::count2Pages($total, self::SMS_SUBSECTION_COUNT);
        
        for ($page = 1; $page <= $pages; $page ++) {
            $members = (1 == $page) ? $members : $this->teamMemberService->listMembers($team->getId(), $page, self::SMS_SUBSECTION_COUNT)[1];
            
            $this->sendSms(array_map(function (TeamMember $member) {
                return $member->getUser()
                    ->getMobile();
            }, $members), $content);
        }
    }

    private function sendSmsToActivityMembers(Activity $activity, $content)
    {
        // send sms to activity members by subsection
        list ($total, $members) = $this->activityMemberService->getActivityMemberList($activity->getId(), 1, self::SMS_SUBSECTION_COUNT);
        if ($total <= 0 || empty($members)) {
            return;
        }
        $pages = PaginationUtil::count2Pages($total, self::SMS_SUBSECTION_COUNT);
        
        for ($page = 1; $page <= $pages; $page ++) {
            $members = (1 == $page) ? $members : $this->activityMemberService->getActivityMemberList($activity->getId(), $page, self::SMS_SUBSECTION_COUNT)[1];
            
            $this->sendSms(array_map(function ($member) {
                return $member['mobile'];
            }, $members), $content);
        }
    }

    /**
     *
     * @param array $message            
     */
    private function pushAllTopicMessage($message)
    {
        $this->dispatchPushToTopicMessage(PushService::TO_ALL_TOPIC, $message);
    }

    /**
     *
     * @param int   $team
     * @param array $message            
     */
    private function pushTeamTopicMessage($team, $message)
    {
        $this->dispatchPushToTeamMessage($team, $message);
    }

    /**
     *
     * @param int   $activity
     * @param array $message            
     */
    private function pushActivityTopicMessage($activity, $message)
    {
        $this->dispatchPushToActivityMessage($activity, $message);
    }

    /**
     *
     * @param string|array $alias            
     * @param array        $message
     */
    private function pushAliasMessage($alias, $message)
    {
        $this->dispatchPushToUserMessage($alias, $message);
    }

    /**
     *
     * @param string|array $phones
     * @param string       $content
     */
    public function sendSms($phone, $content)
    {
        $this->dispatchSms($phone, $content);
    }
}