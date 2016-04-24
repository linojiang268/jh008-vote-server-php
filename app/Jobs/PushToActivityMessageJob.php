<?php
namespace Jihe\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Jihe\Services\PushService;

class PushToActivityMessageJob extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * activity id to push message to
     * @var string
     */
    protected $activity;

    /**
     * message to push
     *
     * @var array
     */
    protected $message;

    /**
     * options to push
     *
     * @var array
     */
    protected $options;


    /**
     * push message
     *
     * @param int|array $activity activity id to push message to
     * @param array     $message  message to push
     * @param array     $options  options to push
     *
     * @throws \Exception   exception will be thrown if push fails.
     */
    public function __construct($activity, $message, array $options = [])
    {
        $this->activity = $activity;
        $this->message = $message;
        $this->options = $options;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(PushService $pushService)
    {
        $pushService->pushToActivity($this->activity, $this->message, $this->options);
    }

}