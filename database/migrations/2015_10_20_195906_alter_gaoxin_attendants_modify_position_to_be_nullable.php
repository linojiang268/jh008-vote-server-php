<?php

use Illuminate\Database\Migrations\Migration;

class AlterGaoxinAttendantsModifyPositionToBeNullable extends Migration
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
                $table->string('position', 32)->nullable()->change();
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
