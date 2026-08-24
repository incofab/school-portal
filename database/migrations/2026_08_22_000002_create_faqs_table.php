<?php

use App\Enums\FaqType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::create('faqs', function (Blueprint $table) {
      $table->id();
      $table->string('name');
      $table->string('code')->unique();
      $table->enum('type', FaqType::values())->default(FaqType::Faq->value)->index();
      $table->longText('description');
      $table->string('video_url', 2048)->nullable();
      $table
        ->boolean('is_active')
        ->default(true)
        ->index();
      $table
        ->unsignedInteger('sort_order')
        ->nullable()
        ->index();
      $table->timestamps();
    });
  }

  public function down(): void
  {
    Schema::dropIfExists('faqs');
  }
};
