<?php

namespace App\Models;

use App\Enums\InstitutionStatus;
use App\Enums\InstitutionUserType;
use App\Enums\S3Folder;
use App\Support\Queries\InstitutionQueryBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Institution extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    public $casts = [
        'institution_group_id' => 'integer',
        'user_id' => 'integer',
        'status' => InstitutionStatus::class,
    ];

    public static function generalRule($prefix = '')
    {
        return [
            $prefix.'name' => ['required', 'string'],
            $prefix.'institution_group_id' => [
                'required',
                'exists:institution_groups,id',
            ],
            $prefix.'phone' => ['nullable', 'string'],
            $prefix.'email' => ['nullable', 'string'],
            $prefix.'address' => ['nullable', 'string'],
        ];
    }

    public static function query(): InstitutionQueryBuilder
    {
        return parent::query();
    }

    public function newEloquentBuilder($query)
    {
        return new InstitutionQueryBuilder($query);
    }

    public function getRouteKeyName()
    {
        return 'uuid';
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $user = currentUser();
        $field = $field ?? 'uuid';
        // $field = 'uuid';
        $institutionModel = Institution::query()
            ->select('institutions.*')
            ->join(
                'institution_users',
                'institution_users.institution_id',
                'institutions.id'
            )
            ->where($field, $value)
            ->when(
                $user && ! $user->isManager(),
                fn ($q) => $q
                    ->where('institution_users.user_id', $user->id)
                    ->with(
                        'institutionUsers',
                        fn ($q) => $q
                            ->where('institution_users.user_id', $user->id)
                            ->with('student')
                    )
            )
            ->with('institutionSettings', 'institutionGroup')
            ->first();

        abort_unless($institutionModel, 403, 'Institution not found for this user');

        return $institutionModel;
    }

    /** Return the institution folder without a leading or preceding slash */
    public function folder(S3Folder $s3Folder = S3Folder::Base, $append = '')
    {
        $dir = "institutions/{$this->id}/{$s3Folder->value}";

        return $append ? "$dir/$append" : $dir;
    }

    public static function generateInstitutionCode()
    {
        $key = mt_rand(100000, 999999);

        while (Institution::whereCode($key)->first()) {
            $key = mt_rand(100000, 999999);
        }

        return $key;
    }

    public function courses()
    {
        return $this->hasMany(Course::class);
    }

    public function courseTeachers()
    {
        return $this->hasMany(CourseTeacher::class);
    }

    public function classifications()
    {
        return $this->hasMany(Classification::class);
    }

    public function classificationGroups()
    {
        return $this->hasMany(ClassificationGroup::class);
    }

    public function classDivisions()
    {
        return $this->hasMany(ClassDivision::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    public function institutionUsers()
    {
        return $this->hasMany(InstitutionUser::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function termResults()
    {
        return $this->hasMany(TermResult::class);
    }

    public function sessionResults()
    {
        return $this->hasMany(SessionResult::class);
    }

    public function pins()
    {
        return $this->hasMany(Pin::class);
    }

    public function pinGenerators()
    {
        return $this->hasMany(PinGenerator::class);
    }

    public function fees()
    {
        return $this->hasMany(Fee::class);
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class);
    }

    public function feePayments()
    {
        return $this->hasMany(FeePayment::class);
    }

    public function paymentReferences()
    {
        return $this->hasMany(PaymentReference::class);
    }

    public function institutionSettings()
    {
        return $this->hasMany(InstitutionSetting::class);
    }

    public function admissionApplications()
    {
        return $this->hasMany(AdmissionApplication::class);
    }

    public function assessments()
    {
        return $this->hasMany(Assessment::class);
    }

    public function learningEvaluationDomains()
    {
        return $this->hasMany(LearningEvaluationDomain::class);
    }

    public function learningEvaluations()
    {
        return $this->hasMany(LearningEvaluation::class);
    }

    public function resultCommentTemplates()
    {
        return $this->hasMany(ResultCommentTemplate::class);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    public function exams()
    {
        return $this->hasMany(Exam::class);
    }

    public function tokenUsers()
    {
        return $this->hasMany(TokenUser::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function institutionGroup()
    {
        return $this->belongsTo(InstitutionGroup::class);
    }

    public function schoolActivities()
    {
        return $this->hasMany(SchoolActivity::class);
    }

    public function schemeOfWorks()
    {
        return $this->hasMany(SchemeOfWork::class);
    }

    public function admissionForms()
    {
        return $this->hasMany(AdmissionForm::class);
    }

    public function associations()
    {
        return $this->hasMany(Association::class);
    }

    public function courseResults()
    {
        return $this->hasMany(CourseResult::class);
    }

    public function students()
    {
        return $this->hasManyThrough(
            User::class,
            InstitutionUser::class,
            'institution_id', // Foreign key on InstitutionUser table
            'id', // Foreign key on User table
            'id', // Local key on Institution table
            'user_id' // Local key on InstitutionUser table
        )->whereHas('institutionUsers', function ($query) {
            $query->where('role', InstitutionUserType::Student);
        });
    }

    public function teachers()
    {
        return $this->hasManyThrough(
            User::class,
            InstitutionUser::class,
            'institution_id', // Foreign key on InstitutionUser table
            'id', // Foreign key on User table
            'id', // Local key on Institution table
            'user_id' // Local key on InstitutionUser table
        )->whereHas('institutionUsers', function ($query) {
            $query->where('role', InstitutionUserType::Teacher);
        });
    }

    public function staff()
    {
        return $this->hasMany(InstitutionUser::class)->whereIn('role', [
            InstitutionUserType::Teacher,
            InstitutionUserType::Accountant,
            InstitutionUserType::Admin,
        ]);
    }

    public function expenses()
    {
        return $this->hasMany(Expense::class);
    }

    public function expenseCategories()
    {
        return $this->hasMany(ExpenseCategory::class);
    }

    public function salaryTypes()
    {
        return $this->hasMany(SalaryType::class);
    }

    public function salaries()
    {
        return $this->hasMany(Salary::class);
    }

    public function payrollAdjustmentTypes()
    {
        return $this->hasMany(PayrollAdjustmentType::class);
    }

    public function payrollAdjustments()
    {
        return $this->hasMany(PayrollAdjustment::class);
    }

    public function payrolls()
    {
        return $this->hasMany(Payroll::class);
    }

    public function payrollSummaries()
    {
        return $this->hasMany(PayrollSummary::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function manualPayments()
    {
        return $this->hasMany(ManualPayment::class);
    }

    public function latestResultPublication()
    {
        return $this->hasOne(ResultPublication::class)
            ->with('academicSession')
            ->latestOfMany();
    }
}
