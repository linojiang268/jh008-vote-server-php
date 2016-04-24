<?php
namespace Jihe\Contracts\Services\Push;

/**
 * contract for all short message based services
 *
 */
interface PushService
{
    /**
     * push message to topic
     * 
     * @param  string     $topic      topic to push message to
     * @param  array      $message    message to push
     * @param  array      $options    possible options for each implementation
     *
     * 
     * @return bool            
     */
    public function pushTopic($topic, $message, array $options = []);

    /**
     * push message to alias
     * 
     * @param  string|array    $alias      alias to push message to
     * @param  array           $message    message to push
     * @param  array           $options    possible options for each implementation
     * 
     * @return bool
     */
    public function pushAlias($alias, $message, array $options = []);

}
