<?php

use Illuminate\Database\Migrations\Migration;

class ActivitiesAlterBeginAndEndTime extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('activities')) {
            DB::unprepared('ALTER TABLE activities CHANGE `begin_time` `begin_time` DATETIME NULL DEFAULT NULL');
            DB::unprepared('ALTER TABLE activities  CHANGE `end_time` `end_time` DATETIME NULL DEFAULT NULL');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
