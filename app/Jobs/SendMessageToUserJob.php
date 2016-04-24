<?php
namespace Jihe\Jobs;

use Jihe\Jobs\Job;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Jihe\Services\MessageService;

class SendMessageToUserJob extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    protected $phones;
    protected $message;
    protected $option;
    
    public function __construct(array $phones = null, array $message, array $option = [])
    {
        $this->phones = $phones;
        $this->message = $message;
        $this->option = $option;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(MessageService $service)
    {
        $service->sendToUsers($this->phones, $this->message, $this->option);
    }
    
    public function failed()
    {
        $this->release();
    }
}
