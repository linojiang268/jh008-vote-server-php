<?php

use Illuminate\Database\Migrations\Migration;

class AlterGaoxinAttendantsAddYearlySalary extends Migration
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
