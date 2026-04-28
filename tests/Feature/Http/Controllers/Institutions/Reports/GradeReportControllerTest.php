<?php

use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\ClassResultInfo;
use App\Models\Course;
use App\Models\CourseResult;
use App\Models\CourseResultInfo;
use App\Models\Institution;
use App\Models\Student;
use App\Models\TermResult;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('returns grade report summary and subject grade matrix', function () {
    $institution = Institution::factory()->create();
    $user = User::factory()->admin($institution)->create();
    $classification = Classification::factory()->create([
        'institution_id' => $institution->id,
    ]);
    $session = AcademicSession::factory()->create();

    ClassResultInfo::factory()->create([
        'institution_id' => $institution->id,
        'classification_id' => $classification->id,
        'academic_session_id' => $session->id,
        'term' => 'first',
        'for_mid_term' => false,
    ]);

    $studentOne = Student::factory()->withInstitution($institution, $classification)->create();
    $studentTwo = Student::factory()->withInstitution($institution, $classification)->create();
    $studentThree = Student::factory()->withInstitution($institution, $classification)->create();

    TermResult::factory()->create([
        'institution_id' => $institution->id,
        'classification_id' => $classification->id,
        'academic_session_id' => $session->id,
        'student_id' => $studentOne->id,
        'term' => 'first',
        'for_mid_term' => false,
        'average' => 75,
    ]);
    TermResult::factory()->create([
        'institution_id' => $institution->id,
        'classification_id' => $classification->id,
        'academic_session_id' => $session->id,
        'student_id' => $studentTwo->id,
        'term' => 'first',
        'for_mid_term' => false,
        'average' => 62,
    ]);
    TermResult::factory()->create([
        'institution_id' => $institution->id,
        'classification_id' => $classification->id,
        'academic_session_id' => $session->id,
        'student_id' => $studentThree->id,
        'term' => 'first',
        'for_mid_term' => false,
        'average' => 45,
    ]);

    $maths = Course::factory()->withInstitution($institution)->create([
        'title' => 'Mathematics',
        'code' => 'MTH',
    ]);
    $english = Course::factory()->withInstitution($institution)->create([
        'title' => 'English Language',
        'code' => 'ENG',
    ]);

    CourseResultInfo::factory()->withInstitution(
        $institution,
        $classification,
        $maths,
        $session
    )->create([
        'term' => 'first',
        'for_mid_term' => false,
    ]);
    CourseResultInfo::factory()->withInstitution(
        $institution,
        $classification,
        $english,
        $session
    )->create([
        'term' => 'first',
        'for_mid_term' => false,
    ]);

    CourseResult::factory()->create([
        'institution_id' => $institution->id,
        'classification_id' => $classification->id,
        'academic_session_id' => $session->id,
        'student_id' => $studentOne->id,
        'course_id' => $maths->id,
        'term' => 'first',
        'for_mid_term' => false,
        'result' => 75,
        'exam' => 40,
    ]);
    CourseResult::factory()->create([
        'institution_id' => $institution->id,
        'classification_id' => $classification->id,
        'academic_session_id' => $session->id,
        'student_id' => $studentTwo->id,
        'course_id' => $maths->id,
        'term' => 'first',
        'for_mid_term' => false,
        'result' => 55,
        'exam' => 30,
    ]);
    CourseResult::factory()->create([
        'institution_id' => $institution->id,
        'classification_id' => $classification->id,
        'academic_session_id' => $session->id,
        'student_id' => $studentThree->id,
        'course_id' => $maths->id,
        'term' => 'first',
        'for_mid_term' => false,
        'result' => 35,
        'exam' => 20,
    ]);

    CourseResult::factory()->create([
        'institution_id' => $institution->id,
        'classification_id' => $classification->id,
        'academic_session_id' => $session->id,
        'student_id' => $studentOne->id,
        'course_id' => $english->id,
        'term' => 'first',
        'for_mid_term' => false,
        'result' => 85,
        'exam' => 50,
    ]);
    CourseResult::factory()->create([
        'institution_id' => $institution->id,
        'classification_id' => $classification->id,
        'academic_session_id' => $session->id,
        'student_id' => $studentTwo->id,
        'course_id' => $english->id,
        'term' => 'first',
        'for_mid_term' => false,
        'result' => 65,
        'exam' => 35,
    ]);
    CourseResult::factory()->create([
        'institution_id' => $institution->id,
        'classification_id' => $classification->id,
        'academic_session_id' => $session->id,
        'student_id' => $studentThree->id,
        'course_id' => $english->id,
        'term' => 'first',
        'for_mid_term' => false,
        'result' => 45,
        'exam' => 20,
    ]);

    $this
        ->actingAs($user)
        ->get(route('institutions.reports.grade-report', [
            $institution->uuid,
            'classification' => $classification->id,
            'academicSession' => $session->id,
            'term' => 'first',
            'forMidTerm' => 0,
        ]))
        ->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page
            ->component('institutions/reports/grade-report-sheet')
            ->has('gradeReport', 3)
            ->where('gradeReport.0.grade', 'A')
            ->where('gradeReport.0.count', 1)
            ->where('gradeReport.1.grade', 'B')
            ->where('gradeReport.1.count', 1)
            ->where('gradeReport.2.grade', 'D')
            ->where('gradeReport.2.count', 1)
            ->where('subjectGradeReport.grades', ['A', 'B', 'C', 'D', 'E', 'F'])
            ->has('subjectGradeReport.rows', 2)
            ->where('subjectGradeReport.rows.0.course_title', 'Mathematics')
            ->where('subjectGradeReport.rows.0.grades.A', 1)
            ->where('subjectGradeReport.rows.0.grades.C', 1)
            ->where('subjectGradeReport.rows.0.grades.F', 1)
            ->where('subjectGradeReport.rows.1.course_title', 'English Language')
            ->where('subjectGradeReport.rows.1.grades.A', 1)
            ->where('subjectGradeReport.rows.1.grades.B', 1)
            ->where('subjectGradeReport.rows.1.grades.D', 1));
});
