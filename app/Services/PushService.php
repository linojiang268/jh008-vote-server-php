<?php
namespace Jihe\Services;

use Jihe\Contracts\Services\Push\PushService as PushServiceContract;
use Jihe\Entities\Message;

class PushService
{
    /**
     * @var PushServiceContract
     */
    private $service;

    const TOPIC_TYPE = ['team', 'activity'];

    const TO_ALL_TOPIC = 'topic_static_all';

    const TO_IOS_TOPIC = 'topic_static_ios';

    const TO_ANDROID_TOPIC = 'topic_static_android';

    public function __construct(PushServiceContract $service)
    {
        $this->service = $service;
    }

    /**
     * @param int|array $team team id
     * @param array     $message
     * @param array     $options
     */
    public function pushToTeam($team, array $message, array $options = [])
    {
        $message = $this->makeMessage($message);
        $options = $this->makeOptions($options, $message);
        $this->pushToTopic($this->makeTopic('team', $team), $message, $options);
    }

    /**
     * @param int|array $activity activity id
     * @param array     $message  push message
     * @param array     $options  push options
     */
    public function pushToActivity($activity, array $message, array $options = [])
    {
        $message = $this->makeMessage($message);
        $options = $this->makeOptions($options, $message);
        $this->pushToTopic($this->makeTopic('activity', $activity), $message, $options);
    }

    /**
     * @param string|array $alias   push to alias
     * @param array        $message push message
     * @param array        $options push options
     */
    public function pushToAlias($alias, array $message, array $options = [])
    {
        $message = $this->makeMessage($message);
        $options = $this->makeOptions($options, $message);
        $this->service->pushAlias($alias, $message, $options);
    }

    /**
     * @param int|array $team
     *
     * @return array|string
     * @throws \Exception
     */
    public function getTeamTopics($team)
    {
        return $this->makeTopic('team', $team);
    }

    /**
     * @param int|array $activity
     *
     * @return array|string
     * @throws \Exception
     */
    public function getActivityTopics($activity)
    {
        return $this->makeTopic('activity', $activity);
    }


    /**
     * @param string|array $topics  push to topic
     * @param array        $message push message
     * @param array        $options push options
     */
    public function pushToTopic($topics, array $message, array $options = [])
    {
        if (empty($topics)) {
            return;
        }

        if (!is_array($topics)) {
            $topics = [$topics];
        }
        $message = $this->makeMessage($message);
        $options = $this->makeOptions($options, $message);
        foreach ($topics as $topic) {
            $this->service->pushTopic($topic, $message, $options);
        }
    }


    /**
     * @param string    $type
     * @param int|array $ids
     *
     * @return array|string
     * @throws \Exception
     */
    private function makeTopic($type, $ids)
    {
        $topics = [];
        if (!in_array($type, self::TOPIC_TYPE)) {
            throw new \Exception('Topic类型错误');
        }
        if (!empty($ids) && is_array($ids)) {
            foreach ($ids as $id) {
                $topics[] = 'topic_' . $type . '_' . $id;
            }
        } else {
            $topics = 'topic_' . $type . '_' . $ids;
        }

        return $topics;
    }

    private function makeMessage($message)
    {
        if (!isset($message['content']) || empty($message['content'])) {
            throw new \Exception('推送文本不能为空');
        }
        $message['title'] = array_get($message, 'title', '集合');
        $message['type'] = array_get($message, 'type', Message::TYPE_TEXT);
        $message['attributes'] = array_get($message, 'attributes', []);
        if (empty($message['attributes'])) {
            unset($message['attributes']);
        }

        return $message;
    }

    private function makeOptions($options, $message)
    {
        if (!isset($options['apn_json'])) {
            $options['apn_json'] = ['aps' => [
                'alert' => $message['content'],
                'sound' => 'default',
                'type'  => $message['type'],
                'title' => $message['title'],
            ]];
            if (isset($message['attributes'])) {
                $options['apn_json']['ops']['attributes'] = $message['attributes'];
            }
        }
        return $options;
    }
}