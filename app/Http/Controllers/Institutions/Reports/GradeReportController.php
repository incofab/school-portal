<?php

namespace App\Http\Controllers\Institutions\Reports;

use App\Actions\CourseResult\GetGrade;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\GradeReportRequest;
use App\Models\ClassResultInfo;
use App\Models\Institution;

class GradeReportController extends Controller
{
    public function __construct()
    {
        $this->allowedRoles([
            InstitutionUserType::Admin,
            InstitutionUserType::Teacher,
        ]);
    }

    public function __invoke(Institution $institution, GradeReportRequest $request)
    {
        $classification = $request->classificationObj;
        $academicSession = $request->academicSessionObj;
        $term = $request->term;
        $forMidTerm = (bool) $request->boolean('forMidTerm');

        $gradeReport = [];
        $subjectGradeReport = [
            'grades' => [],
            'rows' => [],
        ];

        if ($classification && $academicSession && $term) {
            $classResultInfo = ClassResultInfo::query()
                ->where('classification_id', $classification->id)
                ->where('academic_session_id', $academicSession->id)
                ->where('term', $term)
                ->where('for_mid_term', $forMidTerm)
                ->first();

            $gradeReport = GetGrade::getGradeReport($classResultInfo);
            $subjectGradeReport = GetGrade::getSubjectGradeReport(
                $classification,
                $academicSession,
                $term,
                $forMidTerm
            );
        }

        return inertia('institutions/reports/grade-report-sheet', [
            'classification' => $classification,
            'academicSession' => $academicSession,
            'term' => $term,
            'forMidTerm' => $forMidTerm,
            'gradeReport' => $gradeReport,
            'subjectGradeReport' => $subjectGradeReport,
        ]);
    }
}
