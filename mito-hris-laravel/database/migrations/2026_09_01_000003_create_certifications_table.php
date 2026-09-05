<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certifications', function (Blueprint $table) {
            $table->id();
            $table->string('cert_code', 50)->nullable()->unique();
            $table->string('cert_type', 100);
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('issuing_organization', 255);
            $table->string('certificate_number', 255)->nullable();
            $table->date('issue_date');
            $table->date('expiry_date')->nullable();
            $table->string('status', 50);
            $table->string('employee_id', 100);
            $table->string('employee_name', 255);
            $table->string('division', 255)->nullable();
            $table->string('department', 255)->nullable();
            $table->text('notes')->nullable();
            $table->string('attachment_path', 500)->nullable();
            $table->string('created_by', 255);
            $table->timestamps();

            $table->index('cert_code');
            $table->index('employee_id');
            $table->index('cert_type');
            $table->index('status');
            $table->index('expiry_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certifications');
    }
};
