<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeamMemberEnrollmentRequirementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('team_member_enrollment_requirements')) {
            Schema::create('team_member_enrollment_requirements', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('requirement_id');
                $table->integer('request_id');
                $table->string('value', 64);

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
        Schema::drop('team_member_enrollment_requirements');
    }
}
