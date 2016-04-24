<?php
namespace Jihe\Jobs;

use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Bus\SelfHandling;
use Illuminate\Contracts\Queue\ShouldQueue;
use Jihe\Services\PushService;

class PushToAliasMessageJob extends Job implements SelfHandling, ShouldQueue
{
    use InteractsWithQueue, SerializesModels;

    /**
     * topic to push message to
     * @var string|array
     */
    protected $alias;

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
     * @param string|array $alias   alias to push message to
     * @param array        $message message to push
     * @param array        $options options to push
     *
     * @throws \Exception   exception will be thrown if push fails.
     */
    public function __construct($alias, array $message, array $options = [])
    {
        $this->alias = $alias;
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
        try{
            $pushService->pushToAlias($this->alias, $this->message, $this->options);
        }catch (\Exception $e) {
            //code=5 未知的别名
            if($e->getCode() != 5){
                throw new \Exception($e->getMessage());
            }
        }
    }
}
