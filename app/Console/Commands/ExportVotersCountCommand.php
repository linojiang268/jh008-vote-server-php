<?php
namespace Jihe\Console\Commands;

use Illuminate\Console\Command;
use Jihe\Services\Excel\ExcelWriter;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Support\Facades\Mail;

class ExportVotersCountCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'vote:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export excel of voters count.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $titles = [
            'votes' => '空乘投票统计',
            'plane_model_votes' => '电商模特投票统计',
            'shiyang_votes' => '缘聚石羊相亲投票统计',
            'gaoxin_votes' => '缘聚高新相亲投票统计',
        ];

        $table = $this->input->hasOption('table') ? $this->option('table') : 'votes';
        $to = $this->input->hasOption('to') ? $this->option('to') : 'file';
        $path = $this->input->hasOption('path') ? $this->option('path') : sys_get_temp_dir() . '/voters_' . date('YmdHis') . '.xls';
        $title = array_get($titles, $table, '投票统计');

        try {
            $file = $this->exportVoters($table, $path);

            if ('file' == $to) {
                echo "export votes count to " . $file . " successfully\n";
            } else {

                Mail::send([], [], function ($m) use ($to, $title, $file) {
                    $m->to($to)->subject($title)->attach($file, []);
                });

                unlink($file);

                echo "export votes count and send to " . $to . " successfully\n";
            }
        } catch (\Exception $e) {
            echo "export votes count failed: " . $e->getTraceAsString() . "\n";
        }
    }

    /**
     * export voters in given table of voters as excel
     *
     * @param int $team        team id
     * @return mixed
     */
    public function exportVoters($table, $path)
    {
        $writer = ExcelWriter::fromScratch();
        $count = \DB::select('SELECT DATE(created_at) AS vote_date,
                               COUNT(1),
                               COUNT(if(type=2,1,null)) AS wechat,
                               COUNT(if(type=1,1,null)) AS app,
                               COUNT(DISTINCT(if(type=1,voter,null))) AS mobile
                               FROM '.
                               'votes' .
                               ' GROUP BY vote_date');

        $writer->writeHeader(['日期', '微信投票数', '集合app投票数', 'app投票用户数', '投票总数']);
        $writer->write($this->morphCount($count));
        $writer->save(['file' => $path]);
        return $path;
    }

    private function morphCount(array $count)
    {
        $data = array_map(function ($c) {
            return [
                $c->vote_date,
                $c->wechat,
                $c->app,
                $c->mobile,
                $c->wechat + $c->app,
            ];
        }, $count);

        return $data;
    }

    /**
     * Get the console command options.
     *
     * @return array
     */
    protected function getOptions()
    {
        return [
            ['table', 'l', InputOption::VALUE_OPTIONAL, 'table of vote.',  'votes'],
            ['path', 'p', InputOption::VALUE_OPTIONAL, 'path will save file.',  sys_get_temp_dir() . '/voters_' . date('YmdHis') . '.xls'],
            ['to', 't', InputOption::VALUE_OPTIONAL, 'where to send. (email)',  'file'],
        ];
    }
}