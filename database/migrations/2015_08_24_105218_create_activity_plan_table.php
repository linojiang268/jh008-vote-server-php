<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityPlanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('activity_plan')) {
            Schema::create('activity_plan', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('activity_id')->index();
                $table->dateTime('begin_time');
                $table->dateTime('end_time');
                $table->string('plan_text', 255);

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
        Schema::drop('activity_plan');
    }
}
