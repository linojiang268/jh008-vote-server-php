<?php

use Illuminate\Database\Migrations\Migration;

class AlterShiyangAttendantsModifyPositionToBeNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('shiyang_attendants')) {
            Schema::table('shiyang_attendants', function ($table) {
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
