<?php
namespace Jihe\Contracts\Services\Sms;

/**
 * contract for all short message based services
 *
 */
interface SmsService
{
    /**
     * send message
     * @param array|string  $subscriber   subscriber to send message to
     * @param string        $message      message to send
     * @param array         $options      possible options for each implementation
     *
     * @throws \Exception   exception will be thrown if sending fails.
     */
    public function send($subscriber, $message, array $options = []);
    
    /**
     * find quota like number of short messages that can be sent,
     *                 balance, etc
     *
     * @return mixed|array
     */
    public function queryQuota();
}
