<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivityMembersTable extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up() {
        if (!Schema::hasTable('activity_members')) {
            Schema::create('activity_members', function (Blueprint $table) {
                //$table->engine = 'MyISAM';
                $table->increments('id');
                $table->integer('activity_id');
                $table->integer('user_id');
                $table->string('mobile', 16);
                $table->string('name', 32);
                $table->text('attrs');
                $table->integer('group_id')->default(0);
                $table->tinyInteger('role')->default(0);
                $table->integer('score')->nullable();
                $table->string('score_attributes', 255)->nullable();
                $table->string('score_memo', 255)->nullable();
                $table->tinyInteger('checkin')->default(0);         // checkin info;
                                                                    // 0 - waiting for checkin
                                                                    // 1 - aready checkin
                
                $table->timestamps();
                $table->softDeletes();

                $table->index('activity_id');
                $table->index('user_id');
                $table->index('group_id');
                $table->index('role');
            });

            DB::unprepared('ALTER TABLE `activity_members` ADD `location` POINT');
            //DB::unprepared('ALTER TABLE `activity_members` ADD SPATIAL INDEX activity_members_location_spatial_index(`location`)');
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down() {
        Schema::drop('activity_members');
    }

}
