<?php
namespace Jihe\Jobs;

use Log;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Jihe\Contracts\Services\Sms\SmsService;

class SendSms extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * subscriber to send message to
     * @var array|string
     */
    protected $subscriber;
    
    /**
     * message to send
     * 
     * @var string
     */
    protected $message;
    
    /**
     * send message
     * @param array|string  $subscriber   subscriber to send message to
     * @param string        $message      message to send
     * 
     * @throws \Exception   exception will be thrown if sending fails.
     */
    public function __construct($subscriber, $message)
    {
        $this->subscriber = $subscriber;
        $this->message = $message;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(SmsService $service)
    {
        try {
            $tag = uniqid('sms', true);

            \Log::info($tag . ' send to: ' . implode(',', (array)$this->subscriber));
            \Log::info($tag . ' send content: ' . $this->message);

            $service->send($this->subscriber, $this->message);

            \Log::info($tag . ' send response successfully');
        } catch (\Exception $ex) {
            \Log::error($tag . $ex->getMessage());
        }
    }
    
    public function failed()
    {
        // TODO: handle message sending errors
        // $this->release();
    }
}
