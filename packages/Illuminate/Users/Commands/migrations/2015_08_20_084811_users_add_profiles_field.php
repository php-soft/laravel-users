<?php

namespace {

    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Database\Migrations\Migration;

    class UsersAddProfilesField extends Migration
    {
        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
        {
            Schema::table('users', function (Blueprint $table) {
                $table->string('username', 30)->nullable();
                $table->string('location', 100)->nullable();
                $table->string('country', 100)->nullable();
                $table->string('biography', 255)->nullable();
                $table->string('occupation', 255)->nullable();
                $table->string('website', 255)->nullable();
                $table->string('image', 255)->nullable();
            });
        }

        /**
         * Reverse the migrations.
         *
         * @return void
         */
        public function down()
        {
            // Schema::table('users', function ($table) {
                // $table->dropColumn(['username', 'location','country','biography','occupation','website','image']);
            // });
        }
    }
}
