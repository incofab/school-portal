<?php

namespace App\Http\Controllers\Institutions\Admissions;

use App\Actions\Admisssions\RecordAdmissionApplication;
use App\Actions\HandleAdmission;
use App\DTO\PaymentReferenceDto;
use Inertia\Inertia;
use App\Models\Student;
use App\Models\Institution;
use App\Enums\InstitutionUserType;
use App\Enums\Payments\PaymentMerchantType;
use App\Enums\Payments\PaymentPurpose;
use App\Http\Controllers\Controller;
use App\Models\AdmissionApplication;
use App\Http\Requests\AdmissionApplicationRequest;
use App\Models\AdmissionForm;
use App\Models\Classification;
use App\Rules\ValidateExistsRule;
use App\Support\Payments\Merchants\PaymentMerchant;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class AdmissionApplicationController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([InstitutionUserType::Admin])->except([
      'create',
      'successMessage',
      'store',
      'admissionLetter',
      'buyAdmissionForm'
    ]);
  }

  function index()
  {
    $query = AdmissionApplication::query();
    return Inertia::render(
      'institutions/admissions/list-admission-applications',
      [
        'admissionApplications' => paginateFromRequest($query)
      ]
    );
  }

  public function create(Institution $institution)
  {
    return Inertia::render(
      'institutions/admissions/create-admission-application',
      [
        'institution' => $institution
      ]
    );
  }

  public function store(
    Institution $institution,
    AdmissionApplicationRequest $request
  ) {
    $data = $request->validated();
    $admissionApplication = (new RecordAdmissionApplication($institution))->run(
      $request->getAdmissionForm(),
      $data
    );

    return $this->ok(['admissionApplication' => $admissionApplication]);
  }

  public function updateStatus(
    Institution $institution,
    AdmissionApplication $admissionApplication,
    Request $request
  ) {
    abort_if(
      $admissionApplication->admission_status != 'pending',
      401,
      'Admission Application has been handled'
    );

    $data = $request->validate([
      'admission_status' => ['required', 'string'],
      'classification' => [
        'required',
        new ValidateExistsRule(Classification::class)
      ]
    ]);

    //== If Admitted, fill the necessary DB Tables with the needed information
    if ($data['admission_status'] === 'admitted') {
      HandleAdmission::make()->admitStudent($admissionApplication, $data);
    }

    //== Update the 'admission_status' on the 'admission_applications' DB Table
    $admissionApplication
      ->fill(['admission_status' => $data['admission_status']])
      ->save();

    return $this->ok();
  }

  public function successMessage(
    Institution $institution,
    AdmissionApplication $admissionApplication
  ) {
    $admissionApplication->load('admissionForm');
    if (!$admissionApplication->hasBeenPaid()) {
      return Inertia::render(
        'institutions/admissions/buy-admission-application',
        ['admissionApplication' => $admissionApplication]
      );
    }
    return Inertia::render(
      'institutions/admissions/admission-application-success',
      [
        'institution' => $institution,
        'admissionApplication' => $admissionApplication
      ]
    );
  }

  public function show(
    Institution $institution,
    AdmissionApplication $admissionApplication
  ) {
    $admissionApplication->load('applicationGuardians');

    return Inertia::render(
      'institutions/admissions/show-admission-application',
      [
        'admissionApplication' => $admissionApplication
        // 'applicationGuardians' => $admissionApplication->applicationGuardians
      ]
    );
  }

  public function admissionLetter(Institution $institution, Student $student)
  {
    return Inertia::render('institutions/admissions/show-admission-letter', [
      'student' => $student->load('user.institutionUser', 'classification')
    ]);
  }

  public function destroy(
    Institution $institution,
    AdmissionApplication $admissionApplication
  ) {
    $admissionApplication->delete();
    return $this->ok();
  }

  function buyAdmissionForm(
    Request $request,
    Institution $institution,
    AdmissionForm $admissionForm,
    AdmissionApplication|null $admissionApplication
  ) {
    $request->validate([
      'reference' => [
        'nullable',
        'string',
        'unique:payment_references,reference'
      ],
      'merchant' => ['nullable', new Enum(PaymentMerchantType::class)]
    ]);
    $merchant = $request->merchant ?? PaymentMerchantType::Paystack->value;
    $paymentReferenceDto = new PaymentReferenceDto(
      institution_id: $admissionForm->institution_id,
      merchant: $merchant,
      payable: $admissionForm,
      paymentable: $admissionApplication,
      amount: $admissionForm->price,
      purpose: PaymentPurpose::AdmissionFormPurchase,
      user_id: $admissionForm->institution->user_id,
      reference: $request->reference,
      redirect_url: instRoute('admissions.success', [
        $admissionApplication->id
      ]),
      meta: [
        'admission_application_id' => $admissionApplication->id
      ]
    );
    [$res, $paymentReference] = PaymentMerchant::make($merchant)->init(
      $paymentReferenceDto
    );
    abort_unless($res->isSuccessful(), 403, $res->getMessage());
    return $this->ok($res->toArray());
  }
}
