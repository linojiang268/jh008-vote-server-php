<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->increments('id');
                $table->char('mobile', 11)->unique();
                $table->tinyInteger('type')->default(0);
                $table->string('salt', 16);
                $table->string('password', 32);
                $table->char('remember_token', 100)->default('');
                $table->string('nick_name', 32)->default('');
                $table->tinyInteger('gender')->default(0);
                $table->date('birthday')->nullable();
                $table->string('signature', 255)->default('');      // 个性签名
                $table->string('avatar_url', 255)->default('');     // remote resource id
                $table->tinyInteger('status');

                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('users');
    }
}
