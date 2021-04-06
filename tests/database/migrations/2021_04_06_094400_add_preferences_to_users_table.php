<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AddPreferencesToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table(config('user-preferences.database.table'), function (Blueprint $table) {
            $table->text(config('user-preferences.database.column'))->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table(config('user-preferences.database.table'), function (Blueprint $table) {
            $table->dropColumn(config('user-preferences.database.column'));
        });
    }
}
