<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateActivitiesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('activities')) {
            Schema::create('activities', function (Blueprint $table) {
                    $table->increments('id');
                    $table->integer('city_id')->index();
                    $table->integer('team_id')->index();
                    $table->string('title', 32)->default('');
                    $table->string('qr_code_url', 128)->nullable();
                    $table->dateTime('begin_time');
                    $table->dateTime('end_time');
                    $table->string('contact', 32)->default('');
                    $table->string('telephone', 20)->default('');
                    $table->string('cover_url', 128)->default('');
                    $table->text('images_url')->nullable();
                    $table->string('address', 255)->default('');
                    $table->string('brief_address', 32)->default('');
                    $table->text('detail')->nullable();
                    $table->dateTime('enroll_begin_time')->nullable();
                    $table->dateTime('enroll_end_time')->nullable();
                    $table->tinyInteger('enroll_type')->default(0);
                    $table->integer('enroll_limit')->default(0);
                    $table->tinyInteger('enroll_fee_type')->default(0);
                    $table->integer('enroll_fee')->default(0);
                    $table->text('enroll_attrs')->nullable();
                    $table->tinyInteger('status')->default(0);
                    $table->dateTime('publish_time')->nullable();
                    $table->tinyInteger('auditing')->default(0);
                    $table->tinyInteger('update_step')->default(1);
                    $table->tinyInteger('essence')->default(0);
                    $table->tinyInteger('top')->default(0);
                    $table->tinyInteger('has_album')->default(0);
                    $table->text('tags')->nullable();
                    $table->text('organizers')->nullable();

                    $table->timestamps();
                    $table->softDeletes();

                    $table->index(['city_id', 'status']);
                    $table->index(['city_id', 'essence']);
                    $table->index(['city_id', 'top']);

                    $table->index(['team_id', 'status']);
                    $table->index(['team_id', 'has_album']);
                    
            });
            DB::unprepared('ALTER TABLE activities ADD `location` POINT');
            DB::unprepared('ALTER TABLE activities ADD `roadmap` LINESTRING');
        }
    }

    /**
     * Reverse the migrations.
     * @return void
     */
    public function down()
    {
        Schema::drop('activities');
    }
}
