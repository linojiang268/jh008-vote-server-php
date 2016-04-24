<?php
namespace Jihe\Console\Commands;

use Illuminate\Console\Command;
use Jihe\Repositories\VoteRepository;
use Symfony\Component\Console\Input\InputOption;
use Cache;


class VoteCacheClearCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'vote:cache-clear';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'clear vote cache';

    /**
     * Execute the console command.
     */
    public function handle(VoteRepository $voteRepository)
    {
        $cacheKey = $this->option('key');
        if($cacheKey){
            if (Cache::has($cacheKey)) {
                Cache::forget($cacheKey);
            }
        }else{
            $groupKey = md5(VoteRepository::GROUP_BY_USER_COUNT_SORT_KEY);
            if (Cache::has($groupKey)) {
                Cache::forget($groupKey);
            }
            $return = $voteRepository->findVoteCountByUser();
            if($return){
                foreach($return as $index => $ret){
                    $key = md5(VoteRepository::USER_SORT_KEY). '_' . $index;
                    Cache::forever($key, json_encode($ret));
                }
            }
        }

    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['key',   null, InputOption::VALUE_OPTIONAL, 'Delete cache key', null],
        ];
    }
}
