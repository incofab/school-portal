<?php

use App\Enums\RecruitmentApplicationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('vacancy_posts', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->constrained()
        ->cascadeOnDelete();
      $table->string('title');
      $table->string('department')->nullable();
      $table->string('employment_type')->default('full-time');
      $table->string('location')->nullable();
      $table->text('summary')->nullable();
      $table->longText('description');
      $table->longText('requirements')->nullable();
      $table->longText('responsibilities')->nullable();
      $table->string('salary_range')->nullable();
      $table->unsignedInteger('positions_available')->default(1);
      $table->date('application_deadline')->nullable();
      $table->boolean('is_published')->default(false);
      $table->timestamps();
      $table->softDeletes();
    });

    Schema::create('recruitment_applications', function (Blueprint $table) {
      $table->id();
      $table
        ->foreignId('institution_id')
        ->constrained()
        ->cascadeOnDelete();
      $table
        ->foreignId('vacancy_post_id')
        ->constrained()
        ->cascadeOnDelete();
      $table->string('application_no')->unique();
      $table->string('reference')->unique();
      $table->string('first_name');
      $table->string('last_name');
      $table->string('other_names')->nullable();
      $table->string('email');
      $table->string('phone');
      $table->string('current_role')->nullable();
      $table->unsignedTinyInteger('years_of_experience')->nullable();
      $table->string('highest_qualification')->nullable();
      $table->string('cv_url', 2048)->nullable();
      $table->longText('cover_letter')->nullable();
      $table->string('cover_letter_url', 2048)->nullable();
      $table->string('portfolio_url', 2048)->nullable();
      $table->date('available_from')->nullable();
      $table
        ->string('status')
        ->default(RecruitmentApplicationStatus::Pending->value);
      $table->text('review_note')->nullable();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('recruitment_applications');
    Schema::dropIfExists('vacancy_posts');
  }
};
