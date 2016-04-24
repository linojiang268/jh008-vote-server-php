<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeamMemberEnrollmentPermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('team_member_enrollment_permissions')) {
            Schema::create('team_member_enrollment_permissions', function (Blueprint $table) {
                $table->increments('id');
                $table->char('mobile', 11);
                $table->integer('team_id');
                $table->string('name', 32)->nullable();
                $table->string('memo', 64)->nullable();
                $table->tinyInteger('status')->default(\Jihe\Entities\TeamMemberEnrollmentPermission::STATUS_PERMITTED);
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
        Schema::drop('team_member_enrollment_permissions');
    }
}
