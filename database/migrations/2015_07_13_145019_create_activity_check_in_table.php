<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityCheckInTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('activity_check_in')) {
            Schema::create('activity_check_in', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('user_id');
                $table->integer('activity_id');
                $table->integer('step');
                $table->timestamps();
                
                $table->index('user_id');
                $table->index('activity_id');
                $table->index('step');
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
        Schema::drop('activity_check_in');  
    }
}
