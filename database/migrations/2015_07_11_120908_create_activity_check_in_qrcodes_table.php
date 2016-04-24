<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityCheckInQrcodesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('activity_check_in_qrcodes')) {
            Schema::create('activity_check_in_qrcodes', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('activity_id');
                $table->integer('step');
                $table->string('url',256);
                $table->timestamps();
                
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
        Schema::drop('activity_check_in_qrcodes');  
    }
}
