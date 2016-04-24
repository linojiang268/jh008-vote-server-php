<?php

use Illuminate\Database\Seeder;

class QuestionTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $options = [
            [1, '填空'],
            [2, '单选'],
            [3, '复选'],
        ];
        foreach($options as $option){
            DB::table('question_type')->insert([
                'id' => $option[0],
                'name' => $option[1],
                'status' => 1,
            ]);
        }

    }
}
