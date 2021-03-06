<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->enum('role', ['Usuario', 'Masajista', 'Admin']);
            $table->string('password');
            $table->string('api_token')->nullable()->unique();
            $table->string('image')->nullable();
            $table->string('address')->nullable();
            $table->double('lat')->nullable();
            $table->double('long')->nullable();
            $table->string('description')->nullable();
            $table->string('phone_number')->nullable();
            $table->timestamps();
            //$table->rememberToken();
            //$table->timestamp('email_verified_at')->nullable();
            });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
    }
}
