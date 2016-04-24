<?php

use Illuminate\Database\Seeder;

class AdminUserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('admin_users')->insert([
            'id'        => 1,
            'password'  => '82E1E36B0454678727277B849A7F9BB2',
            'salt'      => 'ptrjb30aOvqWJ4mG',
            'user_name' => 'jihe-admin',
            'role'      => 'admin',
            'status'    => 1,
        ]);
    }
}
