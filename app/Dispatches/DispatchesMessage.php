<?php
namespace Jihe\Dispatches;

use Jihe\Jobs\SendMessageToUserJob;
use Jihe\Jobs\SendMessageToTeamMemberJob;
use Jihe\Jobs\SendMessageToActivityMemberJob;
use Jihe\Entities\Team;
use Jihe\Entities\Activity;

trait DispatchesMessage
{
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
    protected function sendToUsers(array $phones = null, array $message, array $option = [])
    {
        $this->dispatch(new SendMessageToUserJob($phones, $message, $option));
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
    protected function sendToTeamMembers(Team $team, array $phones = null, array $message, array $option = [])
    {
        $this->dispatch(new SendMessageToTeamMemberJob($team, $phones, $message, $option));
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
    protected function sendToActivityMembers(Activity $activity, array $phones = null, array $message, array $option = [])
    {
        $this->dispatch(new SendMessageToActivityMemberJob($activity, $phones, $message, $option));
    }
}