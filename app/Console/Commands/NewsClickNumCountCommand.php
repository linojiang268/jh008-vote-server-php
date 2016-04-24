<?php
namespace Jihe\Console\Commands;

use Illuminate\Console\Command;
use Jihe\Contracts\Repositories\NewsRepository;
use Jihe\Entities\News;
use Symfony\Component\Console\Input\InputOption;
use Cache;


class NewsClickNumCountCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'news:click-num-count';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'count news click num ';

    /**
     * Execute the console command.
     */
    public function handle(NewsRepository $newsRepository)
    {
        $newsId = $this->option('id');
        if($newsId){
            $news = $newsRepository->findNewsById($newsId);
            $this->updateNewsClickNum($news, $newsRepository);
        }else{
            $newses = $newsRepository->findAll();
            if($newses){
                foreach($newses as $news){
                    $this->updateNewsClickNum($news, $newsRepository);
                }
            }
        }
    }

    private function updateNewsClickNum(News $news, NewsRepository $newsRepository)
    {
        $cacheKey = md5(News::CLICK_NUM_CACHE_KEY.$news->getId());
        if (Cache::has($cacheKey)) {
            $count = Cache::get($cacheKey);
            if($count > 0){
                $news->setClickNum($news->getClickNum() + $count);
                $newsRepository->update($news);
            }
            Cache::forget($cacheKey);
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
            ['id',   null, InputOption::VALUE_OPTIONAL, 'count news id', null],
        ];
    }
}
