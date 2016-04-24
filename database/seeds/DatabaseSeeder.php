<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Model::unguard();

//        $this->call('UserTagTableSeeder');
//        $this->call('CityTableSeeder');
//        $this->call('AdminUserTableSeeder');
        $this->call(QuestionTypeTableSeeder::class);

        Model::reguard();
    }
}
