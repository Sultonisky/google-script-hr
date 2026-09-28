<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Core HRIS structured tables for PostgreSQL SoT cutover.
 * Sheets remain production SoT until HRIS_DATA_DRIVER=pgsql after read-only ETL.
 * NIK/phone stored as string to avoid Sheets float corruption.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('employee_id', 64)->unique();
            $table->string('full_name')->nullable();
            $table->string('branch_name')->nullable();
            $table->string('division')->nullable();
            $table->string('department')->nullable();
            $table->string('job_position_location')->nullable();
            $table->string('job_position')->nullable();
            $table->string('area_kerja')->nullable();
            $table->string('lokasi_kerja')->nullable();
            $table->string('job_level')->nullable();
            $table->string('grade')->nullable();
            $table->string('join_date', 32)->nullable();
            $table->string('status_employee', 64)->nullable()->index();
            $table->string('direct_superior')->nullable();
            $table->string('indirect_superior')->nullable();
            $table->string('personal_email')->nullable();
            $table->string('working_email')->nullable();
            $table->string('end_date_contract', 32)->nullable();
            $table->string('contract_start', 32)->nullable();
            $table->string('contract_duration', 64)->nullable();
            $table->string('contract_number')->nullable();
            $table->string('birth_place')->nullable();
            $table->string('birth_date', 32)->nullable();
            $table->text('citizen_id_address')->nullable();
            $table->text('residential_address')->nullable();
            $table->string('nik_npwp', 32)->nullable()->index();
            $table->string('npwp', 32)->nullable();
            $table->string('ptkp_status', 32)->nullable();
            $table->string('bank_name')->nullable();
            $table->string('bank_account', 64)->nullable();
            $table->string('bank_account_holder')->nullable();
            $table->string('bpjs_ketenagakerjaan', 64)->nullable();
            $table->string('bpjs_kesehatan', 64)->nullable();
            $table->string('mobile_phone', 32)->nullable();
            $table->string('religion', 64)->nullable();
            $table->string('gender', 32)->nullable();
            $table->string('marital_status', 64)->nullable();
            $table->string('blood_type', 16)->nullable();
            $table->string('cost_center')->nullable();
            $table->string('job_position_former')->nullable();
            $table->string('type_of_rotation')->nullable();
            $table->string('rotation_date', 32)->nullable();
            $table->string('nomor_sk')->nullable();
            $table->string('resign_date', 32)->nullable();
            $table->text('hr_notes')->nullable();
            $table->string('offboarding_type')->nullable();
            $table->text('offboarding_reason')->nullable();
            $table->string('offboarding_approved_by')->nullable();
            $table->string('offboarding_docs_folder')->nullable();
            $table->text('offboarding_doc_links')->nullable();
            $table->string('outsource_vendor')->nullable();
            $table->unsignedInteger('outsource_contract_seq')->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('recruitment_id', 64)->unique();
            $table->string('lifecycle_status', 32)->index(); // pending|hold|blacklist|accepted|probation
            $table->string('full_name')->nullable();
            $table->string('nik', 32)->nullable()->index();
            $table->string('birth_date', 32)->nullable();
            $table->string('age', 16)->nullable();
            $table->string('gender', 32)->nullable();
            $table->string('blood_type', 16)->nullable();
            $table->string('marital_status', 64)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('position_applied')->nullable();
            $table->string('education')->nullable();
            $table->text('work_experience')->nullable();
            $table->string('last_company')->nullable();
            $table->string('current_employment_status')->nullable();
            $table->string('available_to_join')->nullable();
            $table->string('expected_salary')->nullable();
            $table->string('recruitment_source')->nullable();
            $table->string('status', 64)->nullable()->index();
            $table->text('hr_notes')->nullable();
            $table->string('created_by')->nullable();
            $table->string('hold_reason')->nullable();
            $table->string('hold_follow_up_date', 32)->nullable();
            $table->string('blacklist_reason')->nullable();
            $table->string('blacklist_date', 32)->nullable();
            $table->string('blacklist_updated_by')->nullable();
            $table->string('employee_id', 64)->nullable()->index();
            $table->string('processed_date', 32)->nullable();
            $table->string('processed_by')->nullable();
            $table->json('offering_payload')->nullable();
            $table->json('extra_payload')->nullable();
            $table->string('created_date', 32)->nullable();
            $table->timestamps();
        });

        Schema::create('probation_evaluations', function (Blueprint $table) {
            $table->id();
            $table->string('probation_id', 64)->nullable()->index();
            $table->string('employee_id', 64)->index();
            $table->string('recruitment_id', 64)->nullable()->index();
            $table->string('contract_duration', 64)->nullable();
            $table->string('contract_start', 32)->nullable();
            $table->string('contract_end', 32)->nullable();
            $table->string('join_date', 32)->nullable();
            $table->string('status', 64)->nullable()->index();
            $table->string('eval_id', 64)->nullable()->index();
            $table->string('eval_date', 32)->nullable();
            $table->string('decision')->nullable();
            $table->string('extension_duration', 64)->nullable();
            $table->string('new_contract_start', 32)->nullable();
            $table->string('new_contract_end', 32)->nullable();
            $table->text('evaluator_notes')->nullable();
            $table->string('evaluator')->nullable();
            $table->string('sk_status')->nullable();
            $table->unsignedTinyInteger('integrity_total')->nullable();
            $table->unsignedTinyInteger('ci_total')->nullable();
            $table->unsignedTinyInteger('ee_total')->nullable();
            $table->unsignedTinyInteger('teamwork_total')->nullable();
            $table->unsignedTinyInteger('overall_total')->nullable();
            $table->string('category', 64)->nullable();
            $table->json('indicators')->nullable();
            $table->timestamps();
        });

        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_id', 64)->unique();
            $table->string('employee_id', 64)->index();
            $table->unsignedInteger('sequence')->default(0)->index();
            $table->string('doc_type')->nullable();
            $table->string('doc_code', 32)->nullable()->index();
            $table->string('nomor')->nullable();
            $table->string('entity', 16)->nullable();
            $table->string('issued_at', 32)->nullable();
            $table->string('issued_by')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('audit_id', 64)->unique();
            $table->string('entity_type', 64)->nullable()->index();
            $table->string('entity_id', 64)->nullable()->index();
            $table->string('action', 128)->nullable();
            $table->string('field')->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('user')->nullable();
            $table->string('source', 64)->nullable();
            $table->timestamp('logged_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('mpr_requests', function (Blueprint $table) {
            $table->id();
            $table->string('mpr_number', 64)->unique();
            $table->string('request_date', 32)->nullable();
            $table->string('requestor_name')->nullable();
            $table->string('requestor_email')->nullable()->index();
            $table->string('entity')->nullable();
            $table->string('department')->nullable();
            $table->string('division')->nullable();
            $table->string('approval_division')->nullable();
            $table->string('position')->nullable();
            $table->string('job_level')->nullable();
            $table->string('work_location')->nullable();
            $table->string('employment_type')->nullable();
            $table->unsignedInteger('quantity')->nullable();
            $table->string('expected_join_date', 32)->nullable();
            $table->text('reason')->nullable();
            $table->string('replacement_for')->nullable();
            $table->text('job_description')->nullable();
            $table->text('requirements')->nullable();
            $table->string('requestor_position')->nullable();
            $table->string('working_days')->nullable();
            $table->string('working_hours')->nullable();
            $table->text('shift_detail')->nullable();
            $table->text('benefits')->nullable();
            $table->text('education_background')->nullable();
            $table->text('work_experience')->nullable();
            $table->text('skills')->nullable();
            $table->text('languages')->nullable();
            $table->text('industry_reference')->nullable();
            $table->text('special_notes')->nullable();
            $table->text('key_results')->nullable();
            $table->string('status', 64)->nullable()->index();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('mpr_requestors', function (Blueprint $table) {
            $table->id();
            $table->string('requestor_id', 64)->unique();
            $table->string('email')->unique();
            $table->string('username')->nullable()->index();
            $table->string('full_name')->nullable();
            $table->string('job_position')->nullable();
            $table->string('role', 64)->nullable();
            $table->string('status', 32)->nullable()->index();
            $table->string('password_hash')->nullable();
            $table->string('last_login', 32)->nullable();
            $table->string('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mpr_requestors');
        Schema::dropIfExists('mpr_requests');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('probation_evaluations');
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('employees');
    }
};
