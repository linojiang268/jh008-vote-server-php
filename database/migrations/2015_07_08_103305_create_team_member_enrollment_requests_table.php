<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeamMemberEnrollmentRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('team_member_enrollment_requests')) {
            Schema::create('team_member_enrollment_requests', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('team_id');
                $table->integer('initiator_id');
                $table->integer('group_id')->default(\Jihe\Entities\TeamGroup::UNGROUPED);
                $table->string('name', 32)->nullable();
                $table->string('memo', 64)->nullable();
                $table->tinyInteger('status');
                $table->string('reason', 64)->nullable();
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
        Schema::drop('team_member_enrollment_requests');
    }
}
