<?php
namespace Jihe\Services\Push;

use Jihe\Contracts\Services\Push\PushService as PushServiceContract;

class CompoundPushService implements PushServiceContract
{
    private $pushServices;

    public function __construct(array $pushServices) {
        $this->pushServices = $pushServices;
    }

    /**
     * push message to topic
     *
     * @param  string     $topic      topic to push message to
     * @param  array      $message    message to push
     * @param  array      $options    possible options for each implementation
     *
     * @return bool
     * @throws \Exception
     * @throws null
     */
    public function pushTopic($topic, $message, array $options = []) {
        $lastException = null;
        foreach ($this->pushServices as $pushService) {
            try {
                $pushService->pushTopic($topic, $message, $options);
            } catch (\Exception $ex) {
                $lastException = $ex;
            }
        }

        if ($lastException) {
            throw $lastException;
        }

        return true;
    }

    /**
     * push message to alias
     *
     * @param  string|array    $alias      alias to push message to
     * @param  array           $message    message to push
     * @param  array           $options    possible options for each implementation
     *
     * @return bool
     * @throws \Exception
     * @throws null
     */
    public function pushAlias($alias, $message, array $options = []) {
        $lastException = null;
        foreach ($this->pushServices as $pushService) {
            try {
                $pushService->pushAlias($alias, $message, $options);
            } catch (\Exception $ex) {
                $lastException = $ex;
            }
        }

        if ($lastException) {
            throw $lastException;
        }

        return true;
    }
}
