<?php

namespace App\Http\Controllers\Institutions\Payments;

use App\Actions\Payments\ManualPaymentHandler;
use App\Enums\InstitutionUserType;
use App\Enums\Payments\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Models\AdmissionApplication;
use App\Models\AdmissionForm;
use App\Models\BankAccount;
use App\Models\Fee;
use App\Models\Institution;
use App\Models\ManualPayment;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ManualPaymentController extends Controller
{
  public function __construct()
  {
    $this->allowedRoles([
      InstitutionUserType::Admin,
      InstitutionUserType::Accountant
    ])->except(['history', 'show', 'updatePending']);
  }

  public function index(Institution $institution)
  {
    $query = ManualPayment::query()
      ->where('institution_id', $institution->id)
      ->with(
        'user',
        'payable',
        'paymentable',
        'bankAccount',
        'confirmedBy',
        'rejectedBy'
      )
      ->pendingFirst()
      ->latest('id');

    return inertia('institutions/payments/list-manual-payments', [
      'manualPayments' => paginateFromRequest($query)
    ]);
  }

  public function history(Institution $institution, Student $student)
  {
    $query = ManualPayment::query()
      ->where('user_id', $student->user_id)
      ->with('paymentable', 'bankAccount', 'confirmedBy', 'rejectedBy')
      ->pendingFirst()
      ->latest('id');

    return inertia('institutions/payments/manual-payment-history', [
      'manualPayments' => paginateFromRequest($query)
    ]);
  }

  public function show(Institution $institution, ManualPayment $manualPayment)
  {
    abort_unless(
      $manualPayment->institution_id === $institution->id,
      404,
      'Manual payment not found'
    );

    return inertia('institutions/payments/manual-payment-pending', [
      'manualPayment' => $manualPayment->load(
        'bankAccount',
        'payable',
        'paymentable'
      ),
      'bankAccounts' => $institution->institutionGroup->bankAccounts()->get(),
      'payableDetails' => $this->describePaymentEntity(
        $manualPayment->payable,
        'Paid By'
      ),
      'paymentableDetails' => $this->describePaymentEntity(
        $manualPayment->paymentable,
        'Payment For'
      )
    ]);
  }

  public function updatePending(
    Request $request,
    Institution $institution,
    ManualPayment $manualPayment
  ) {
    abort_unless(
      $manualPayment->institution_id === $institution->id,
      404,
      'Manual payment not found'
    );

    abort_if(
      $manualPayment->status !== PaymentStatus::Pending,
      422,
      'Only pending manual payments can be updated.'
    );

    abort_if(
      $manualPayment->user_id !== currentUser()->id,
      403,
      'You are not allowed to update this manual payment.'
    );

    $data = $request->validate([
      'bank_account_id' => ['required', 'integer'],
      'payment_proof' => [
        'nullable',
        'file',
        'mimes:jpg,jpeg,png,pdf',
        'max:4096'
      ],
      'depositor_name' => ['nullable', 'string', 'max:255'],
      'paid_at' => ['nullable', 'date'],
      'note' => ['nullable', 'string', 'max:1000']
    ]);

    $bankAccountId = $data['bank_account_id'];
    $bankAccount = BankAccount::query()
      ->where('id', $bankAccountId)
      ->where(
        'accountable_type',
        $institution->institutionGroup->getMorphClass()
      )
      ->where('accountable_id', $institution->institutionGroup->id)
      ->first();

    abort_unless(
      $bankAccount,
      422,
      'Please select a valid institution bank account.'
    );

    $proofPath = $manualPayment->proof_path;
    $proofUrl = $manualPayment->proof_url;
    if ($request->hasFile('payment_proof')) {
      $proofPath = $request
        ->file('payment_proof')
        ->store("institutions/{$institution->id}/manual-payments", 's3_public');
      $proofUrl = Storage::disk('s3_public')->url($proofPath);
    }

    $manualPayment
      ->fill([
        'bank_account_id' => $bankAccountId,
        'depositor_name' =>
          $data['depositor_name'] ?? $manualPayment->depositor_name,
        'paid_at' => $data['paid_at'] ?? $manualPayment->paid_at,
        'proof_path' => $proofPath,
        'proof_url' => $proofUrl,
        'payload' => [
          ...$manualPayment->payload?->getArrayCopy() ?? [],
          'note' => $data['note'] ?? ($manualPayment->payload['note'] ?? null)
        ]
      ])
      ->save();

    return $this->ok([
      'manualPayment' => $manualPayment->fresh()->load('bankAccount'),
      'message' => 'Your manual payment details have been updated.'
    ]);
  }

  public function confirm(
    Institution $institution,
    ManualPayment $manualPayment
  ) {
    abort_unless(
      $manualPayment->institution_id === $institution->id,
      404,
      'Manual payment not found'
    );

    $res = (new ManualPaymentHandler())->confirm(
      $manualPayment->load('institution', 'payable', 'paymentable'),
      currentUser()
    );

    return $this->apiRes($res);
  }

  public function reject(
    Request $request,
    Institution $institution,
    ManualPayment $manualPayment
  ) {
    abort_unless(
      $manualPayment->institution_id === $institution->id,
      404,
      'Manual payment not found'
    );

    $data = $request->validate([
      'review_note' => ['nullable', 'string', 'max:1000']
    ]);

    $res = (new ManualPaymentHandler())->reject(
      $manualPayment,
      currentUser(),
      $data['review_note'] ?? null
    );

    return $this->apiRes($res);
  }

  private function describePaymentEntity(?Model $entity, string $fallbackLabel)
  {
    if (!$entity) {
      return null;
    }

    if ($entity instanceof User) {
      $entity->loadMissing('student.classification');

      return [
        'label' => $fallbackLabel,
        'title' => $entity->full_name,
        'subtitle' => 'User',
        'attributes' => array_values(
          array_filter(
            [
              ['label' => 'Email', 'value' => $entity->email],
              ['label' => 'Phone', 'value' => $entity->phone],
              ['label' => 'Username', 'value' => $entity->username],
              [
                'label' => 'Student Code',
                'value' => $entity->student?->full_code
              ],
              [
                'label' => 'Class',
                'value' => $entity->student?->classification?->title
              ]
            ],
            fn($item) => filled($item['value'])
          )
        )
      ];
    }

    if ($entity instanceof Fee) {
      $entity->loadMissing('academicSession');

      return [
        'label' => $fallbackLabel,
        'title' => $entity->title,
        'subtitle' => 'Fee',
        'attributes' => array_values(
          array_filter(
            [
              [
                'label' => 'Amount',
                'value' => number_format($entity->amount)
              ],
              ['label' => 'Interval', 'value' => $entity->payment_interval],
              [
                'label' => 'Term',
                'value' => $entity->term?->value ?? $entity->term
              ],
              [
                'label' => 'Academic Session',
                'value' => $entity->academicSession?->title
              ]
            ],
            fn($item) => filled($item['value'])
          )
        )
      ];
    }

    if ($entity instanceof AdmissionForm) {
      $entity->loadMissing('academicSession');

      return [
        'label' => $fallbackLabel,
        'title' => $entity->title,
        'subtitle' => 'Admission Form',
        'attributes' => array_values(
          array_filter(
            [
              ['label' => 'Price', 'value' => number_format($entity->price)],
              [
                'label' => 'Term',
                'value' => $entity->term?->value ?? $entity->term
              ],
              [
                'label' => 'Academic Session',
                'value' => $entity->academicSession?->title
              ],
              [
                'label' => 'Published',
                'value' => $entity->is_published ? 'Yes' : 'No'
              ]
            ],
            fn($item) => filled($item['value'])
          )
        )
      ];
    }

    if ($entity instanceof AdmissionApplication) {
      return [
        'label' => $fallbackLabel,
        'title' =>
          $entity->name ?:
          trim(
            implode(' ', [
              $entity->first_name,
              $entity->last_name,
              $entity->other_names
            ])
          ),
        'subtitle' => 'Admission Application',
        'attributes' => array_values(
          array_filter(
            [
              ['label' => 'Application No', 'value' => $entity->application_no],
              ['label' => 'Email', 'value' => $entity->email],
              ['label' => 'Phone', 'value' => $entity->phone],
              [
                'label' => 'Intended Class',
                'value' => $entity->intended_class_of_admission
              ],
              ['label' => 'Status', 'value' => $entity->admission_status]
            ],
            fn($item) => filled($item['value'])
          )
        )
      ];
    }

    return [
      'label' => $fallbackLabel,
      'title' => class_basename($entity),
      'subtitle' => 'Record',
      'attributes' => [['label' => 'ID', 'value' => $entity->id]]
    ];
  }
}
