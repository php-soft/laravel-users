<?php

namespace {

    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Database\Migrations\Migration;

    /**
     * @codeCoverageIgnore
     */
    class UsersAddStatusField extends Migration
    {
        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
        {
            Schema::table('users', function (Blueprint $table) {
                $table->integer('status')->default(0);
            });
        }

        /**
         * Reverse the migrations.
         *
         * @return void
         */
        public function down()
        {

        }
    }
}
