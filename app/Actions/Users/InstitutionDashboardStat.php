<?php
namespace App\Actions\Users;

use App\Models\Classification;
use App\Models\FeePayment;
use App\Models\Institution;
use App\Models\Student;
use DB;

class InstitutionDashboardStat
{
  function __construct()
  {
  }

  /**
   * @return array{
   * num_subjects: int,
   * num_students: int,
   * num_staff: int,
   * num_classes: int,
   * student_population_year_growth: array { year: string, count: int },
   * student_population_month_growth: array { month: string, count: int },
   * gender_distribution: array { gender: string, count: int },
   * fee_payments: array { month: string, total: int },
   * students_per_class: array { name: string, students_count: int, male_students_count: int, female_students_count: int }
   * }
   */
  public static function getStat(Institution $institution)
  {
    $num_subjects = $institution->courses()->count();
    $num_students = $institution->students()->count();
    $num_staff = $institution->staff()->count();
    $num_classes = $institution->classifications()->count();

    $student_population_year_growth = Student::query()
      ->joinInstitution($institution->id)
      ->select(
        DB::raw('YEAR(students.created_at) as year'),
        DB::raw('count(*) as count')
      )
      ->where('students.created_at', '>=', now()->subYears(5))
      ->groupBy('year')
      ->get(['year', 'count']);

    $student_population_month_growth = Student::query()
      ->joinInstitution($institution->id)
      ->select(
        DB::raw('MONTH(students.created_at) as month'),
        DB::raw('count(*) as count')
      )
      ->where('students.created_at', '>=', now()->subMonths(12))
      ->groupBy('month')
      ->get(['month', 'count']);

    $gender_distribution = Student::query()
      ->select('users.gender', DB::raw('count(*) as count'))
      ->joinInstitution($institution->id)
      ->join('users', 'students.user_id', '=', 'users.id')
      ->groupBy('gender')
      ->get(['gender', 'count']);

    $fee_payments = FeePayment::forInstitution($institution)
      ->select(
        DB::raw("DATE_FORMAT(created_at, '%Y-%m') as month"),
        DB::raw('sum(amount) as total')
      )
      ->where('created_at', '>=', now()->subMonths(5))
      ->groupBy('month')
      ->get(['month', 'total']);

    $students_per_class = Classification::forInstitution($institution)
      ->withCount('students')
      ->withCount([
        'students as male_students_count' => function ($q) {
          $q->join('users', 'students.user_id', 'users.id')->where(
            'users.gender',
            'male'
          );
        }
      ])
      ->withCount([
        'students as female_students_count' => function ($q) {
          $q->join('users', 'students.user_id', 'users.id')->where(
            'users.gender',
            'female'
          );
        }
      ])
      ->get([
        'name',
        'students_count',
        'male_students_count',
        'female_students_count'
      ]);

    return [
      'num_subjects' => $num_subjects,
      'num_students' => $num_students,
      'num_staff' => $num_staff,
      'num_classes' => $num_classes,
      'student_population_year_growth' => $student_population_year_growth,
      'student_population_month_growth' => $student_population_month_growth,
      'gender_distribution' => $gender_distribution,
      'fee_payments' => $fee_payments,
      'students_per_class' => $students_per_class
    ];
  }
}
