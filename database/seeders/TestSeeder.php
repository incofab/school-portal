<?php
namespace Database\Seeders;

use App\Actions\CourseResult\EvaluateCourseResultForClass;
use App\Enums\InstitutionUserType;
use App\Enums\TermType;
use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\Institution;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestSeeder extends Seeder
{
  /**
   * Run the database seeds.
   *
   * @return void
   */
  public function run()
  {
    $date = date('Y');
    // $term = TermType::Third;
    $academicSession = AcademicSession::query()->firstOrCreate([
      'title' => $date - 1 . '/' . $date
    ]);
    $admin = User::factory()->create(['email' => 'success@email.com']);
    $institution = Institution::factory()->create([
      'user_id' => $admin->id,
      'name' => 'Success Academy'
    ]);
    $admin->institutionUsers()->create([
      'role' => InstitutionUserType::Admin,
      'institution_id' => $institution->id
    ]);

    $courses = Course::factory()
      ->withInstitution($institution)
      ->count(8)
      ->create();

    $classification = Classification::factory()
      ->withInstitution($institution)
      ->create(['title' => 'JSS 1']);

    $students = Student::factory()
      ->withInstitution($institution)
      ->count(10)
      ->create(['classification_id' => $classification->id]);

    $this->seedMoreClassesAndStudents($institution);

    /** @var \App\Models\User $teacher */
    $teacher = User::factory()
      ->teacher($institution)
      ->create();

    foreach ($courses as $key => $course) {
      $teacher->courseTeachers()->create([
        'course_id' => $course->id,
        'classification_id' => $classification->id
      ]);
    }

    foreach (TermType::cases() as $key => $term) {
      foreach ($students as $key => $student) {
        foreach ($courses as $key => $course) {
          CourseResult::factory()->create([
            'institution_id' => $institution->id,
            'student_id' => $student->id,
            'teacher_user_id' => $teacher->id,
            'course_id' => $course->id,
            'classification_id' => $classification->id,
            'academic_session_id' => $academicSession->id,
            'term' => $term
          ]);
        }
      }

      foreach ($courses as $key => $course) {
        EvaluateCourseResultForClass::run(
          $classification,
          $course->id,
          $academicSession->id,
          $term->value
        );
      }
    }
  }

  private function seedMoreClassesAndStudents(Institution $institution)
  {
    $classTitles = ['JSS 2', 'JSS 3'];
    foreach ($classTitles as $key => $classTitle) {
      $classification = Classification::factory()
        ->withInstitution($institution)
        ->create(['title' => $classTitle]);

      Student::factory()
        ->withInstitution($institution)
        ->count(10)
        ->create(['classification_id' => $classification->id]);
    }
  }
}
