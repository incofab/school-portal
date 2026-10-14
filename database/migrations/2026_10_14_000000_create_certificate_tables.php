<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('certificate_templates', function (Blueprint $table) {
      $table->id();
      $table->string('slug');
      $table->string('name');
      $table->string('template_path');
      $table->text('description')->nullable();
      $table->json('placeholders');
      $table->boolean('is_active')->default(true);
      $table->timestamps();
      $table->unique('slug', 'cert_tpl_slug_uq');
    });

    Schema::create('certificate_types', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('institution_id');
      $table->unsignedBigInteger('certificate_template_id');
      $table->string('name');
      $table->string('institution_name');
      $table->string('logo_url')->nullable();
      $table->string('primary_color', 7)->default('#1f6b50');
      $table->json('custom_values')->nullable();
      $table->boolean('is_active')->default(true);
      $table->timestamps();
    });
    Schema::table('certificate_types', function (Blueprint $table) {
      $table
        ->foreign('institution_id', 'cert_types_inst_fk')
        ->references('id')
        ->on('institutions')
        ->cascadeOnDelete();
      $table
        ->foreign('certificate_template_id', 'cert_types_tpl_fk')
        ->references('id')
        ->on('certificate_templates')
        ->cascadeOnDelete();
    });

    Schema::create('certificate_batches', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('institution_id');
      $table->unsignedBigInteger('certificate_type_id');
      $table->unsignedBigInteger('created_by_institution_user_id');
      $table->string('name');
      $table->text('citation')->nullable();
      $table->date('issued_on');
      $table->unsignedBigInteger('academic_session_id')->nullable();
      $table->unsignedBigInteger('classification_id')->nullable();
      $table->string('signatory_name')->nullable();
      $table->string('signatory_title')->nullable();
      $table->string('status')->default('open');
      $table->timestamps();
      $table->index(
        ['institution_id', 'status'],
        'cert_batches_inst_status_idx'
      );
    });
    Schema::table('certificate_batches', function (Blueprint $table) {
      $table
        ->foreign('institution_id', 'cert_batches_inst_fk')
        ->references('id')
        ->on('institutions')
        ->cascadeOnDelete();
      $table
        ->foreign('certificate_type_id', 'cert_batches_type_fk')
        ->references('id')
        ->on('certificate_types')
        ->restrictOnDelete();
      $table
        ->foreign('created_by_institution_user_id', 'cert_batches_creator_fk')
        ->references('id')
        ->on('institution_users')
        ->restrictOnDelete();
      $table
        ->foreign('academic_session_id', 'cert_batches_session_fk')
        ->references('id')
        ->on('academic_sessions')
        ->nullOnDelete();
      $table
        ->foreign('classification_id', 'cert_batches_class_fk')
        ->references('id')
        ->on('classifications')
        ->nullOnDelete();
    });

    Schema::create('certificates', function (Blueprint $table) {
      $table->id();
      $table->unsignedBigInteger('institution_id');
      $table->unsignedBigInteger('certificate_type_id');
      $table->unsignedBigInteger('certificate_batch_id')->nullable();
      $table->unsignedBigInteger('institution_user_id');
      $table->unsignedBigInteger('issued_by_institution_user_id');
      $table->string('reference_code');
      $table->text('citation')->nullable();
      $table->date('issued_on');
      $table->unsignedBigInteger('academic_session_id')->nullable();
      $table->unsignedBigInteger('classification_id')->nullable();
      $table->string('signatory_name')->nullable();
      $table->string('signatory_title')->nullable();
      $table->json('render_data')->nullable();
      $table->string('status')->default('issued');
      $table->timestamp('revoked_at')->nullable();
      $table->text('revocation_reason')->nullable();
      $table->timestamps();
      $table->unique('reference_code', 'certificates_ref_code_uq');
      $table->index(
        ['institution_id', 'institution_user_id', 'status'],
        'certs_inst_recipient_status_idx'
      );
      $table->index(
        ['institution_id', 'certificate_batch_id'],
        'certs_inst_batch_idx'
      );
    });
    Schema::table('certificates', function (Blueprint $table) {
      $table
        ->foreign('institution_id', 'certs_inst_fk')
        ->references('id')
        ->on('institutions')
        ->cascadeOnDelete();
      $table
        ->foreign('certificate_type_id', 'certs_type_fk')
        ->references('id')
        ->on('certificate_types')
        ->restrictOnDelete();
      $table
        ->foreign('certificate_batch_id', 'certs_batch_fk')
        ->references('id')
        ->on('certificate_batches')
        ->restrictOnDelete();
      $table
        ->foreign('institution_user_id', 'certs_recipient_fk')
        ->references('id')
        ->on('institution_users')
        ->restrictOnDelete();
      $table
        ->foreign('issued_by_institution_user_id', 'certs_issuer_fk')
        ->references('id')
        ->on('institution_users')
        ->restrictOnDelete();
      $table
        ->foreign('academic_session_id', 'certs_session_fk')
        ->references('id')
        ->on('academic_sessions')
        ->nullOnDelete();
      $table
        ->foreign('classification_id', 'certs_class_fk')
        ->references('id')
        ->on('classifications')
        ->nullOnDelete();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('certificates');
    Schema::dropIfExists('certificate_batches');
    Schema::dropIfExists('certificate_types');
    Schema::dropIfExists('certificate_templates');
  }
};
