<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Immutable PDF archive per issued Employee_Documents row, so HR can
     * re-download the exact file that was generated at issuance.
     */
    public function up(): void
    {
        Schema::create('employee_document_files', function (Blueprint $table) {
            $table->id();
            $table->string('document_id', 64)->unique();
            $table->string('employee_id', 64)->index();
            $table->string('doc_code', 32)->nullable();
            $table->string('nomor')->nullable();
            $table->string('file_name');
            $table->string('mime_type', 100)->default('application/pdf');
            $table->unsignedInteger('size_bytes')->default(0);
            $table->char('checksum_sha256', 64);
            // Base64 text keeps the payload portable across pgsql/sqlite/mysql.
            $table->longText('content_base64');
            $table->string('source', 32)->default('export');
            $table->string('archived_by')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_document_files');
    }
};
