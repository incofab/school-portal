<?php

namespace App\Http\Controllers;

use App\Enums\InstitutionUserType;
use App\Models\Institution;
use App\Models\PaymentReference;
use App\Support\Payments\Processors\PaymentProcessor;
use App\Support\UITableFilters\PaymentReferenceUITableFilters;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class PaymentHistoryController extends Controller
{
  public function userIndex(Request $request)
  {
    $user = $request->user();

    $query = $this->filteredBaseQuery($request)
      ->withoutGlobalScopes()
      ->where(
        fn(Builder $query) => $query
          ->where('user_id', $user->id)
          ->orWhere(
            fn(Builder $query) => $query
              ->where('payable_type', $user->getMorphClass())
              ->where('payable_id', $user->id)
          )
      );

    return inertia('payments/list-payment-attempts', [
      'paymentReferences' => $this->paginatePaymentReferences($query),
      'filters' => $this->filtersFromRequest($request),
      'context' => 'user'
    ]);
  }

  public function institutionIndex(Request $request, Institution $institution)
  {
    $institutionUser = currentInstitutionUser();
    $canManageInstitutionPayments = in_array($institutionUser?->role, [
      InstitutionUserType::Admin,
      InstitutionUserType::Accountant
    ]);

    $query = $this->filteredBaseQuery($request)->when(
      !$canManageInstitutionPayments,
      function (Builder $query) use ($request) {
        $user = $request->user();

        $query->where(
          fn(Builder $query) => $query
            ->where('user_id', $user->id)
            ->orWhere(
              fn(Builder $query) => $query
                ->where('payable_type', $user->getMorphClass())
                ->where('payable_id', $user->id)
            )
        );
      }
    );

    return inertia('payments/list-payment-attempts', [
      'paymentReferences' => $this->paginatePaymentReferences($query),
      'filters' => $this->filtersFromRequest($request),
      'context' => 'institution'
    ]);
  }

  public function managerIndex(Request $request)
  {
    $query = $this->filteredBaseQuery($request)->withoutGlobalScopes();

    return inertia('payments/list-payment-attempts', [
      'paymentReferences' => $this->paginatePaymentReferences(
        $query->with('institution')
      ),
      'filters' => $this->filtersFromRequest($request),
      'context' => 'manager'
    ]);
  }

  public function verifyOwn(
    Request $request,
    PaymentReference $paymentReference
  ) {
    $paymentReference = $paymentReference->load('institution', 'payable');
    $this->authorize('verifyOwn', $paymentReference);
    abort_unless(
      $this->belongsToUser($request->user(), $paymentReference),
      403,
      'You cannot verify this payment because it was not made by you.'
    );

    return $this->verify($request, $paymentReference);
  }

  public function verifyInstitution(
    Request $request,
    Institution $institution,
    PaymentReference $paymentReference
  ) {
    $paymentReference = $paymentReference->load('institution', 'payable');
    $this->authorize('verifyInstitution', [$paymentReference, $institution]);
    abort_unless(
      $paymentReference->institution_id === $institution->id &&
        ($this->canManageInstitutionPayments($institution) ||
          $this->belongsToUser($request->user(), $paymentReference)),
      403,
      'You cannot verify this payment because you are not an admin.'
    );

    return $this->verify($request, $paymentReference);
  }

  private function verify(Request $request, PaymentReference $paymentReference)
  {
    abort_unless(
      $paymentReference->isVerificationEligible(),
      422,
      'This payment can no longer be verified from here.'
    );

    try {
      $res = PaymentProcessor::make($paymentReference)
        ->confirmedBy($request->user())
        ->processPaymentWithTransaction();

      $freshPaymentReference = $paymentReference
        ->fresh()
        ->load('institution', 'user', 'payable', 'paymentable');

      return $this->apiRes(
        $res->isSuccessful()
          ? successRes($res->message, [
            'paymentReference' => $this->serializePaymentReference(
              $freshPaymentReference
            )
          ])
          : failRes($res->message, [
            'paymentReference' => $this->serializePaymentReference(
              $freshPaymentReference
            )
          ]),
        422
      );
    } catch (Throwable $exception) {
      Log::error('Payment verification failed unexpectedly.', [
        'reference' => $paymentReference->reference,
        'payment_reference_id' => $paymentReference->id,
        'user_id' => $request->user()?->id,
        'exception' => $exception::class,
        'message' => $exception->getMessage()
      ]);

      return $this->message(
        'We could not verify this payment right now. Please try again later.',
        500
      );
    }
  }

  private function filteredBaseQuery(Request $request): Builder
  {
    return PaymentReferenceUITableFilters::make(
      $request->all(),
      $this->baseQuery()
    )
      ->filterQuery()
      ->getQuery()
      ->latest('payment_references.id');
  }

  private function baseQuery(): Builder
  {
    return PaymentReference::query()
      ->select('payment_references.*')
      ->with('institution', 'user', 'payable', 'paymentable');
  }

  private function paginatePaymentReferences(Builder $query)
  {
    return paginateFromRequest($query)->through(
      fn(
        PaymentReference $paymentReference
      ) => $this->serializePaymentReference($paymentReference)
    );
  }

  private function serializePaymentReference(
    PaymentReference $paymentReference
  ): array {
    $meta = $paymentReference->getPaymentMeta();

    return [
      'id' => $paymentReference->id,
      'institution_id' => $paymentReference->institution_id,
      'user_id' => $paymentReference->user_id,
      'reference' => $paymentReference->reference,
      'amount' => $paymentReference->amount,
      'charges' => $paymentReference->charges,
      'merchant' => $paymentReference->merchant?->value,
      'method' => $paymentReference->method?->value,
      'purpose' => $paymentReference->purpose?->value,
      'status' => $paymentReference->status?->value,
      'created_at' => optional($paymentReference->created_at)->toISOString(),
      'updated_at' => optional($paymentReference->updated_at)->toISOString(),
      'processed_at' => optional(
        $paymentReference->processed_at
      )->toISOString(),
      'can_verify' => $paymentReference->isVerificationEligible(),
      'payer_name' => $this->displayName(
        $paymentReference->payable ?? $paymentReference->user
      ),
      'purpose_details' => $this->displayName($paymentReference->paymentable),
      'institution' => $paymentReference->institution,
      'user' => $paymentReference->user,
      'payable_type' => $paymentReference->payable_type,
      'payable_id' => $paymentReference->payable_id,
      'paymentable_type' => $paymentReference->paymentable_type,
      'paymentable_id' => $paymentReference->paymentable_id,
      'meta_summary' => collect($meta)
        ->filter(fn($value) => is_scalar($value) || is_null($value))
        ->take(6)
        ->all()
    ];
  }

  private function displayName($model): ?string
  {
    if (!$model) {
      return null;
    }

    foreach (['full_name', 'name', 'title', 'reference'] as $attribute) {
      $value = data_get($model, $attribute);
      if (filled($value)) {
        return $value;
      }
    }

    return class_basename($model) . ' #' . $model->getKey();
  }

  private function canManageInstitutionPayments(Institution $institution): bool
  {
    $institutionUser = currentInstitutionUser();

    return $institutionUser?->institution_id === $institution->id &&
      in_array($institutionUser->role, [
        InstitutionUserType::Admin,
        InstitutionUserType::Accountant
      ]);
  }

  private function belongsToUser(
    $user,
    PaymentReference $paymentReference
  ): bool {
    if ($user->isAdmin()) {
      return true;
    }
    return $paymentReference->user_id === $user?->id ||
      ($paymentReference->payable_type === $user?->getMorphClass() &&
        $paymentReference->payable_id === $user?->id);
  }

  private function filtersFromRequest(Request $request): array
  {
    return $request->only([
      'status',
      'merchant',
      'purpose',
      'search',
      'reference',
      'institution_id',
      'date_from',
      'date_to'
    ]);
  }
}
