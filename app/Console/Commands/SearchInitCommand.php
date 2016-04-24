<?php
namespace Jihe\Console\Commands;

use Illuminate\Console\Command;
use Jihe\Contracts\Services\Search\SearchService;
use Symfony\Component\Console\Input\InputOption;

class SearchInitCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'search:init';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'init the search engin';

    /**
     * Execute the console command.
     *
     * @param SearchService $searchService
     */
    public function handle(SearchService $searchService)
    {
        $deleteIfExists   = $this->option('delete') === true ||
                            $this->option('delete') === 'Y' ||
                            $this->option('delete') === 'y' ||
                            $this->option('delete') === 'true';
        $numberOfShards   = $this->option('shards');
        $numberOfReplicas = $this->option('replicas');

        $searchService->init($deleteIfExists, $numberOfShards, $numberOfReplicas);

        $this->info("Init the search engin successfully.");
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['delete',   null, InputOption::VALUE_OPTIONAL, 'Delete if already exists', false],
            ['shards',   null, InputOption::VALUE_OPTIONAL, 'Number of shards',         1],
            ['replicas', null, InputOption::VALUE_OPTIONAL, 'Number of replicas',       0],
        ];
    }
}
