<?php
namespace Jihe\Console\Commands;

use Illuminate\Console\Command;
use Jihe\Services\Excel\ExcelWriter;
use Symfony\Component\Console\Input\InputOption;
use Illuminate\Support\Facades\Mail;

class ExportAttendantsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $name = 'attendant:export';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Export excel of attendants.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $titles = [
//            'attendants' => '空乘参选人员表',
//            'plane_model_attendants' => '电商模特参选人员表',
            'shiyang_attendants' => '缘聚石羊相亲参选人员表',
            'gaoxin_attendants' => '缘聚高新相亲参选人员表',
        ];

        $commands = [
            'shiyang_attendants' => 'exportShiyangAttendants',
            'gaoxin_attendants' => 'exportGaoxinAttendants',
        ];

        $table = $this->input->hasOption('table') ? $this->option('table') : 'attendants';
        $to = $this->input->hasOption('to') ? $this->option('to') : 'file';
        $path = $this->input->hasOption('path') ? $this->option('path') : sys_get_temp_dir() . '/attendants_' . date('YmdHis') . '.xls';
        $title = array_get($titles, $table, '参选人员表');

        try {
            if (!array_key_exists($table, $titles)) {
                throw new \Exception('table is error.');
            }

            $file = call_user_func([$this, $commands[$table]], $table, $path);

            if ('file' == $to) {
                echo "export attendants to " . $file . " successfully\n";
            } else {

                Mail::send([], [], function ($m) use ($to, $title, $file) {
                    $m->to($to)->subject($title)->attach($file, []);
                });

                unlink($file);

                echo "export attendants and send to " . $to . " successfully\n";
            }
        } catch (\Exception $e) {
            echo "export attendants failed: " . $e->getTraceAsString() . "\n";
        }
    }

    public function exportShiyangAttendants($table, $path)
    {
        $writer = ExcelWriter::fromScratch();
        $attendants = \DB::select('SELECT * FROM ' . $table . ' where status=1');

        $writer->writeHeader(['编号', '姓名', '性别', '年龄', '手机号', '工作单位', '年薪', '微信号', '才艺', '申请上台嘉宾', '报名时间']);
        $writer->write($this->morphShiyangAttendant($attendants));
        $writer->save(['file' => $path]);
        return $path;
    }

    private function morphShiyangAttendant(array $attendants)
    {
        $data = array_map(function ($attendant) {
            return [
                $attendant->id,
                $attendant->name,
                1 == $attendant->gender ? '男' : '女',
                $attendant->age,
                $attendant->mobile,
                $attendant->work_unit,
                $attendant->yearly_salary,
                $attendant->wechat_id,
                $attendant->talent,
                1 == $attendant->guest_apply ? '是' : '否',
                $attendant->created_at,
            ];
        }, $attendants);

        return $data;
    }

    public function exportGaoxinAttendants($table, $path)
    {
        $writer = ExcelWriter::fromScratch();
        $attendants = \DB::select('SELECT * FROM ' . $table . ' where status=1');

        $writer->writeHeader(['编号', '姓名', '性别', '年龄', '手机号', '工作单位', '年薪', '微信号', '才艺', '申请上台嘉宾', '乘坐爱情大巴', '报名时间']);
        $writer->write($this->morphGaoxinAttendant($attendants));
        $writer->save(['file' => $path]);
        return $path;
    }

    private function morphGaoxinAttendant(array $attendants)
    {
        $data = array_map(function ($attendant) {
            return [
                $attendant->id,
                $attendant->name,
                1 == $attendant->gender ? '男' : '女',
                $attendant->age,
                $attendant->mobile,
                $attendant->work_unit,
                $attendant->yearly_salary,
                $attendant->wechat_id,
                $attendant->talent,
                1 == $attendant->guest_apply ? '是' : '否',
                1 == $attendant->love_bus_apply ? '是' : '否',
                $attendant->created_at,
            ];
        }, $attendants);

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
            ['table', 'l', InputOption::VALUE_OPTIONAL, 'table of vote.',  'attendants'],
            ['path', 'p', InputOption::VALUE_OPTIONAL, 'path will save file.',  sys_get_temp_dir() . '/attendants_' . date('YmdHis') . '.xls'],
            ['to', 't', InputOption::VALUE_OPTIONAL, 'where to send. (email)',  'file'],
        ];
    }
}