<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBannersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('banners')) {
            Schema::create('banners', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('city_id')->nullable();
                $table->string('image_url');
                $table->string('type', 32);
                $table->string('attributes', 255)->nullable();
                $table->string('memo', 32)->nullable();
                $table->timestamp('begin_time');
                $table->timestamp('end_time');

                $table->timestamps();
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
        Schema::drop('banners');
    }
}
