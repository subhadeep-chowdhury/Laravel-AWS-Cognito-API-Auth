<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddUsernameFcmTokenLastLoginTimeToUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
			$table->string('username')->unique()->after('name');
			$table->string('fcm_token')->nullable();
			$table->timestamp('last_login_time')->nullable();
			$table->longText('remember_token')->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
			$table->dropColumn('username');
			$table->dropColumn('fcm_token');
			$table->dropColumn('last_login_time');
			$table->string('remember_token', 100)->nullable()->change();
        });
    }
}
