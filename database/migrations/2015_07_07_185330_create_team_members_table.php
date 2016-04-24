<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTeamMembersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('team_members')) {
            Schema::create('team_members', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('team_id');
                $table->integer('user_id');
                $table->integer('group_id')->default(\Jihe\Entities\TeamGroup::UNGROUPED);
                $table->tinyInteger('role')->default(0);
                $table->string('name', 32)->nullable();
                $table->string('memo', 64)->nullable();
                $table->tinyInteger('status');
                $table->tinyInteger('visibility')->default(\Jihe\Entities\TeamMember::VISIBILITY_ALL);
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
        Schema::drop('team_members');
    }
}
