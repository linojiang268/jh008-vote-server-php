<?php
namespace Jihe\Jobs;

use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Jihe\Services\PushService;

class PushToTopicMessageJob extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * topic id to push message to
     * @var string
     */
    protected $topic;

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
     * @param string $topic   topic to push message to
     * @param array  $message message to push
     * @param array  $options options to push
     *
     * @throws \Exception   exception will be thrown if push fails.
     */
    public function __construct($topic, $message, array $options = [])
    {
        $this->topic = $topic;
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
        $pushService->pushToTopic($this->topic, $this->message, $this->options);
    }

}