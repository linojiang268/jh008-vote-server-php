<?php
namespace Jihe\Jobs;

use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Jihe\Services\PushService;

class PushToTeamMessageJob extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * team id to push message to
     * @var string
     */
    protected $team;

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
     * @param int|array $team   topic to push message to
     * @param array        $message message to push
     * @param array        $options options to push
     *
     * @throws \Exception   exception will be thrown if push fails.
     */
    public function __construct($team, $message, array $options = [])
    {
        $this->team = $team;
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
        $pushService->pushToTeam($this->team, $this->message, $this->options);
    }

}
