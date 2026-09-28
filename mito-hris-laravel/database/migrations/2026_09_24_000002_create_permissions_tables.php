<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('permission_key', 128)->unique();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('group', 64)->nullable()->index();
            $table->string('status', 32)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('user_email')->index();
            $table->string('permission_key', 128);
            $table->boolean('granted')->default(false);
            $table->string('granted_by')->nullable();
            $table->timestamps();

            $table->unique(['user_email', 'permission_key']);
            $table->index('permission_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('permissions');
    }
};
