<?php

use App\Enums\AttendanceType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('institution_id');
            $table->unsignedBigInteger('institution_user_id');
            //$table->string('type')->default(AttendanceType::In->value)->index();
            $table->unsignedBigInteger('institution_staff_user_id');
            $table->text('remark')->nullable();
            $table->dateTime('signed_in_at')->nullable();
            $table->dateTime('signed_out_at')->nullable();
            $table->string('reference')->unique();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('institution_id')->references('id')->on('institutions')->onDelete('cascade');
            $table->foreign('institution_user_id')->references('id')->on('institution_users')->onDelete('cascade');
            $table->foreign('institution_staff_user_id')->references('id')->on('institution_users')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('attendances');
    }
};