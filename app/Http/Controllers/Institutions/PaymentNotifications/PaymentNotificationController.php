<?php

namespace App\Http\Controllers\Institutions\PaymentNotifications;

use App\Actions\Payments\RecordFeePaymentReminder;
use App\Enums\InstitutionUserType;
use App\Enums\NotificationChannelsType;
use App\Http\Controllers\Controller;
use App\Models\Fee;
use App\Models\Institution;
use App\Models\SchoolNotification;
use App\Rules\ValidateExistsRule;
use App\Rules\ValidateUniqueRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class PaymentNotificationController extends Controller
{
  function __construct(protected Institution $institution)
  {
    $this->allowedRoles([InstitutionUserType::Admin])->except([
      'index',
      'search',
      'show'
    ]);
  }

  public function index(Institution $institution)
  {
  }

  /** @deprecated */
  public function create(Institution $institution)
  {
    return inertia('institutions/payment-notifications/create-notification', [
      'fees' => Fee::all()
    ]);
  }

  public function store(Institution $institution, Request $request)
  {
    $feeExistRule = new ValidateExistsRule(Fee::class);
    $data = $request->validate([
      'fee_id' => ['required', $feeExistRule],
      'reference' => [
        'required',
        new ValidateUniqueRule(SchoolNotification::class)
      ],
      'channel' => ['required', new Enum(NotificationChannelsType::class)]
    ]);

    $res = (new RecordFeePaymentReminder(
      currentUser(),
      $data,
      $institution,
      $feeExistRule->getModel()
    ))->run();

    return $res->isSuccessful()
      ? $this->ok()
      : $this->message($res->getMessage(), 403);
  }
}
