<?php

use Illuminate\Database\Migrations\Migration;

class AlterShiyangAttendantsAddYearlySalary extends Migration
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
                $table->string('yearly_salary', 32);
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
