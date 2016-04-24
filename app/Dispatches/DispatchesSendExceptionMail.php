<?php
namespace Jihe\Dispatches;

use Jihe\Jobs\SendExceptionMail;

trait DispatchesSendExceptionMail
{
    /**
     * send exception mail
     *
     * @param \Exception $exception
     * @param string     $queue
     */
    protected function dispatchSendMail(\Exception $exception, $queue = 'mail')
    {
        $errorMailTo = app('config')['mail.errorMailTo'];
        if(empty($errorMailTo)){
            return;
        }
        $this->dispatch((new SendExceptionMail($exception, $errorMailTo, $queue))->onQueue($queue));
    }
}