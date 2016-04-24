<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('openid', 64)->unique();
            $table->string('nick_name', 32);
            $table->tinyInteger('gender')->default(0);  // 0 - unknown;
                                                        // 1 - male;
                                                        // 2 - femal;
            $table->string('country', 128)->nullable();
            $table->string('province', 128)->nullable();
            $table->string('city', 128)->nullable();
            $table->string('headimgurl', 256)->nullable();
            $table->string('unionid', 128)->nullable();

            // available fetch user info in wechat app
            $table->tinyInteger('subscribe')->default(0);   // 0 -- unknown
                                                            // 1 -- not subscribe 
                                                            // 2 -- subscribed
            $table->timestamp('subscribe_at')->nullable();
            $table->integer('groupid')->nullable();     // 分组编号
            $table->text('remark')->nullable();         // 公众号运营者对fans
                                                        // 的备注
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('wechat_users');
    }
}
