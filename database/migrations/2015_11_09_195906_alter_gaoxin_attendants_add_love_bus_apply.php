<?php

use Illuminate\Database\Migrations\Migration;

class AlterGaoxinAttendantsAddLoveBusApply extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('gaoxin_attendants')) {
            Schema::table('gaoxin_attendants', function ($table) {
                $table->tinyInteger('love_bus_apply', 0);
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
        //
    }
}
