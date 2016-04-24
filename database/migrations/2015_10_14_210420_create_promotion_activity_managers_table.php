<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePromotionActivityManagersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('promotion_activity_managers', function (Blueprint $table) {
            $table->increments('id');
            $table->string('activity_name', 32)->unique();
            $table->string('activity_desc', 128)->default('');
            $table->string('template_segment', 32);
            $table->string('name', 32);
            $table->string('password', 128);
            $table->tinyInteger('status');      // 0 - 普通
                                                // 1 - 禁用

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
        Schema::drop('promotion_activity_managers');
    }
}
