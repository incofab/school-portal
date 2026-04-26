<?php

use App\Models\AcademicSession;
use App\Models\Classification;
use App\Models\ClassResultInfo;
use App\Models\Institution;
use App\Models\Student;
use App\Models\TermResult;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

it('returns grade report when listing term results by class result info', function () {
    $institution = Institution::factory()->create();
    $user = User::factory()->admin($institution)->create();
    $classification = Classification::factory()->create(['institution_id' => $institution->id]);
    $session = AcademicSession::factory()->create();
    
    $classResultInfo = ClassResultInfo::factory()->create([
        'institution_id' => $institution->id,
        'classification_id' => $classification->id,
        'academic_session_id' => $session->id,
        'term' => 'first',
    ]);

    // Create some term results
    TermResult::factory()->count(3)->create([
        'institution_id' => $institution->id,
        'classification_id' => $classification->id,
        'academic_session_id' => $session->id,
        'student_id' => Student::factory()->withInstitution($institution, $classification)->create()->id,
        'term' => 'first',
        'average' => 75, // Should be grade A
    ]);

    TermResult::factory()->count(2)->create([
        'institution_id' => $institution->id,
        'classification_id' => $classification->id,
        'academic_session_id' => $session->id,
        'student_id' => Student::factory()->withInstitution($institution, $classification)->create()->id,
        'term' => 'first',
        'average' => 45, // Should be grade D
    ]);

    $this->actingAs($user)
        ->get(route('institutions.term-results.class-result-info.index', [$institution->uuid, $classResultInfo->id]))
        ->assertStatus(200)
        ->assertInertia(fn (Assert $page) => $page
            ->component('institutions/results/list-term-results')
            ->has('gradeReport', 2)
            ->where('gradeReport.0.grade', 'A')
            ->where('gradeReport.0.count', 3)
            ->where('gradeReport.1.grade', 'D')
            ->where('gradeReport.1.count', 2)
        );
});
