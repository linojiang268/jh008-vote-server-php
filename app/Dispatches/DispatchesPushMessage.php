<?php
namespace Jihe\Dispatches;

use Jihe\Jobs\PushToAliasMessageJob;
use Jihe\Jobs\PushToTeamMessageJob;
use Jihe\Jobs\PushToActivityMessageJob;
use Jihe\Jobs\PushToTopicMessageJob;

trait DispatchesPushMessage
{
    /**
     * send message
     *
     * @param string|array $mobile  subscriber to send message to
     * @param array        $message message to send
     *
     * @return boolean      true if message will be sent, false if message
     *                      sending will be skipped.
     *
     * @throws \Exception   exception will be thrown if sending fails.
     */
    protected function dispatchPushToUserMessage($mobile, $message)
    {
        $config = app('config')['push.config'];
        $shouldDispatch = !array_get($config, 'no_push', false);
        if ($shouldDispatch) {
            try{
                $this->dispatch(new PushToAliasMessageJob($mobile, $message));
            }catch (\Exception $e) {
                //code=5 未知的别名
                if($e->getCode() != 5){
                    throw new \Exception($e->getMessage());
                }
            }
        }

        return $shouldDispatch;
    }

    /**
     * send message
     *
     * @param int|array $team    subscriber to send message to
     * @param array     $message message to send
     *
     * @return boolean      true if message will be sent, false if message
     *                      sending will be skipped.
     *
     * @throws \Exception   exception will be thrown if sending fails.
     */
    protected function dispatchPushToTeamMessage($team, $message)
    {
        $config = app('config')['push.config'];
        $shouldDispatch = !array_get($config, 'no_push', false);

        if ($shouldDispatch) {
            $this->dispatch(new PushToTeamMessageJob($team, $message));
        }

        return $shouldDispatch;
    }

    /**
     * send message
     *
     * @param int|array $activity activity id to send message to
     * @param array     $message  message to send
     *
     * @return boolean      true if message will be sent, false if message
     *                      sending will be skipped.
     *
     * @throws \Exception   exception will be thrown if sending fails.
     */
    protected function dispatchPushToActivityMessage($activity, $message)
    {
        $config = app('config')['push.config'];
        $shouldDispatch = !array_get($config, 'no_push', false);

        if ($shouldDispatch) {
            $this->dispatch(new PushToActivityMessageJob($activity, $message));
        }

        return $shouldDispatch;
    }

    /**
     * send message
     *
     * @param string $topic   topic id to send message to
     * @param array  $message message to send
     *
     * @return boolean      true if message will be sent, false if message
     *                      sending will be skipped.
     *
     * @throws \Exception   exception will be thrown if sending fails.
     */
    protected function dispatchPushToTopicMessage($topic, $message)
    {
        $config = app('config')['push.config'];
        $shouldDispatch = !array_get($config, 'no_push', false);

        if ($shouldDispatch) {
            $this->dispatch(new PushToTopicMessageJob($topic, $message));
        }

        return $shouldDispatch;
    }

}


