<?php

use App\Models\Assignment;
use App\Models\AssignmentClassification;
use App\Models\InstitutionUser;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

  public function up()
  {
    if (!Schema::hasTable('assignment_classifications')) {
      Schema::create('assignment_classifications', function (Blueprint $table) {
        $table->id();
        $table->foreignId('institution_id')->constrained()->cascadeOnDelete();
        $table->foreignId('classification_id')->constrained()->cascadeOnDelete();
        $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
        $table->timestamps();
      });
    }
    
    if (!Schema::hasColumn('assignments', 'institution_user_id')) {
      Schema::table('assignments', function (Blueprint $table) {
          $table
            ->foreignId('institution_user_id')
            ->nullable()
            ->constrained()
            ->cascadeOnDelete();
      });
    }

    //Move Classes to assignment classification
    $assignments = DB::table('assignments')->get();
    foreach ($assignments as $key => $assignment) {
      AssignmentClassification::query()->firstOrCreate([
        'institution_id' => $assignment->institution_id,
        'assignment_id' => $assignment->id,
        'classification_id' => $assignment->classification_id,
      ], []);
    }
    
    // Populate institution_user_id based on the stored teacher user id.
    /** @var \Illuminate\Database\Eloquent\Collection<int, Assignment> $assignments */
    $assignments = Assignment::query()->get();

    foreach ($assignments as $assignment) {
      $teacherUserId = $assignment->teacher_user_id;
      if(!$teacherUserId){
        continue;
      }

      $institutionUser = InstitutionUser::where(
        'user_id',
        $teacherUserId
      )->where('institution_id', $assignment->institution_id)->first();
      
      if ($institutionUser) {
        $assignment->institution_user_id = $institutionUser->id;
        $assignment->save();
      }
    }

    Schema::table('assignments', function (Blueprint $table) {
      $table->dropForeign(['classification_id']);
      $table->dropColumn(['classification_id']);
    });
  }

  public function down()
  {
    if (!Schema::hasColumn('assignments', 'classification_id')) {
      Schema::table('assignments', function (Blueprint $table) {
        $table
          ->foreignId('classification_id')
          ->nullable()
          ->constrained()
          ->cascadeOnDelete();
      });
    }
    
    //Move assignment_classifications to assignments
    $assignmentClassifications = DB::table('assignment_classifications')->get();
    foreach ($assignmentClassifications as $key => $assignmentClassification) {
      DB::table('assignments')->where('id', $assignmentClassification->assignment_id)
      ->update(['classification_id' => $assignmentClassification->classification_id]);
    }

    Schema::table('assignments', function (Blueprint $table) {
      $table->dropForeign(['institution_user_id']);
      $table->dropColumn('institution_user_id');
    });

    Schema::dropIfExists('assignment_classifications');
  }
};
