<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('profile_image')
                    ->default('https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_960_720.png')
                    ->nullable();
            $table->string('profile_banner_image')
                    ->default('https://res.cloudinary.com/omaha-code/image/upload/ar_4:3,c_fill,dpr_1.0,e_art:quartz,g_auto,h_396,q_auto:best,t_Linkedin_official,w_1584/v1561576558/mountains-1412683_1280.png')
                    ->nullable();
            $table->text('about_me')->nullable();
            $table->enum('role', ['user', 'admin'])->default('user');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
