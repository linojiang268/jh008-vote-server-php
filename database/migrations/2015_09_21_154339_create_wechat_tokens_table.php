<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWechatTokensTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wechat_tokens', function (Blueprint $table) {
            $table->increments('id');
            $table->string('openid', 64)->unique();
            $table->string('web_token_access', 256)->nullable();
            $table->timestamp('web_token_expire_at')->nullable();
            $table->string('web_token_refresh', 256)->nullable();
            $table->string('token_access', 256)->nullable();
            $table->timestamp('token_expire_at')->nullable();
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
        Schema::drop('wechat_tokens');
    }
}
