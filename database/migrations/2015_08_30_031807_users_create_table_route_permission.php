<?php

namespace {

    use Illuminate\Database\Schema\Blueprint;
    use Illuminate\Database\Migrations\Migration;

    /**
     * @codeCoverageIgnore
     */
    class UsersCreateTableRoutePermission extends Migration
    {
        /**
         * Run the migrations.
         *
         * @return void
         */
        public function up()
        {
            Schema::create('route_permission', function (Blueprint $table) {
                $table->increments('id');
                $table->string('route')->unique();
                $table->string('permissions')->nullable();
                $table->string('roles')->nullable();
                $table->timestamps();
            });
        }

        /**
         * Reverse the migrations.
         *
         * @return void
         */
        public function down()
        {
            Schema::drop('route_permission');
        }
    }

}
