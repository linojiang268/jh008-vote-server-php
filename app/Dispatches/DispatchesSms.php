<?php
namespace Jihe\Dispatches;

use Jihe\Jobs\SendSms;

trait DispatchesSms
{
    /**
     * send message
     * @param array|string  $subscriber   subscriber to send message to
     * @param string        $message      message to send
     *
     * @return boolean      true if message will be sent, false if message 
     *                      sending will be skipped.
     * 
     * @throws \Exception   exception will be thrown if sending fails.
     */
    protected function dispatchSms($subscriber, $message)
    {
        $config = app('config')['sms.config'];
        $shouldDispatch = !array_get($config, 'no_sending', false);
        
        if ($shouldDispatch) {
            $this->dispatch(new SendSms($subscriber, $message));
        }
        
        return $shouldDispatch;
    }
}