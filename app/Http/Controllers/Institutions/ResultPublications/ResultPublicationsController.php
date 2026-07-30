<?php

namespace App\Http\Controllers\Institutions\ResultPublications;

use App\Enums\PriceLists\PriceType;
use App\Enums\TermType;
use App\Models\Institution;
use Illuminate\Http\Request;
use App\Models\Classification;
use App\Support\SettingsHandler;
use App\Http\Controllers\Controller;
use App\Models\ResultPublication;
use App\Rules\ValidateExistsRule;
use App\Support\ResultPublications\PublishResult;
use Illuminate\Validation\Rules\Enum;

class ResultPublicationsController extends Controller
{
  function index(Institution $institution)
  {
    $query = ResultPublication::query()
      ->where('institution_id', $institution->id)
      ->with('academicSession', 'transaction', 'staff')
      ->latest('id');
    return inertia(
      'institutions/result-publications/list-result-publications',
      ['resultPublications' => paginateFromRequest($query)]
    );
  }

  function create(Institution $institution, Request $request)
  {
    $selection = $this->publicationSelection($institution, $request);
    $classifications = Classification::all();
    $publicationBilling = $this->publicationBilling(
      $institution,
      $classifications->pluck('id')->toArray(),
      $selection['academic_session_id'],
      $selection['term']
    );

    return inertia(
      'institutions/result-publications/create-result-publication',
      [
        'classifications' => $classifications,
        'publicationBilling' => $publicationBilling,
        'academic_session_id' => $selection['academic_session_id'],
        'term' => $selection['term']
      ]
    );
  }

  function store(Institution $institution, Request $request)
  {
    $data = $request->validate([
      'classifications' => ['required', 'min:1'],
      'classifications.*' => [
        'required',
        new ValidateExistsRule(Classification::class)
      ],
      'academic_session_id' => ['nullable', 'exists:academic_sessions,id'],
      'term' => ['nullable', new Enum(TermType::class)],
      'send_to_guardians_whatsapp' => 'boolean'
    ]);

    $submittedClassIds = $request->classifications;
    $settingHandler = SettingsHandler::makeFromInstitution(
      $institution->fresh('institutionSettings') ?? $institution
    );
    $academicSessionId = intval(
      $data['academic_session_id'] ??
        $settingHandler->getCurrentAcademicSession()
    );
    $term = $data['term'] ?? $settingHandler->getCurrentTerm();
    $institutionGroup = $institution->institutionGroup;
    $instGroupPriceList = $institutionGroup
      ->priceLists()
      ->where('type', PriceType::ResultChecking->value)
      ->first();

    if (!$instGroupPriceList) {
      return $this->message('Price list has not been set. Contact admin', 401);
    }

    $obj = PublishResult::make(
      currentUser(),
      $institution,
      $settingHandler,
      $instGroupPriceList,
      $submittedClassIds,
      $academicSessionId,
      $term,
      $request->send_to_guardians_whatsapp
    );
    $res = $obj->execute();

    if ($res->isNotSuccessful()) {
      if ($res->insufficient_balance) {
        return response()->json(
          [
            'message' => $res->message,
            'insufficient_balance' => true,
            'billing' => [
              ...$res->billing,
              'funding_url' => $this->fundingUrl(
                $institution,
                $res->billing['amount_needed'] ?? 0
              )
            ]
          ],
          401
        );
      }

      return $this->message($res->message, 401);
    }

    return $this->message($res->message);
  }

  private function publicationBilling(
    Institution $institution,
    array $submittedClassIds,
    ?int $academicSessionId = null,
    TermType|string|null $term = null
  ): ?array {
    $settingHandler = SettingsHandler::makeFromInstitution(
      $institution->fresh('institutionSettings') ?? $institution
    );
    $institutionGroup = $institution->institutionGroup;
    $instGroupPriceList = $institutionGroup
      ->priceLists()
      ->where('type', PriceType::ResultChecking->value)
      ->first();

    if (!$instGroupPriceList) {
      return null;
    }

    $billing = PublishResult::make(
      currentUser(),
      $institution,
      $settingHandler,
      $instGroupPriceList,
      $submittedClassIds,
      $academicSessionId,
      $term
    )->getBillingSummary();

    return [
      ...$billing,
      'funding_url' => $this->fundingUrl(
        $institution,
        $billing['amount_needed'] ?? 0
      )
    ];
  }

  private function fundingUrl(Institution $institution, float $amount): string
  {
    return route('institutions.fundings.create', [
      $institution,
      'amount' => $amount
    ]);
  }

  /**
   * @return array{academic_session_id: int, term: string}
   */
  private function publicationSelection(
    Institution $institution,
    Request $request
  ): array {
    $settingHandler = SettingsHandler::makeFromInstitution(
      $institution->fresh('institutionSettings') ?? $institution
    );

    $data = $request->validate([
      'academic_session_id' => ['nullable', 'exists:academic_sessions,id'],
      'term' => ['nullable', new Enum(TermType::class)]
    ]);

    return [
      'academic_session_id' => intval(
        $data['academic_session_id'] ??
          $settingHandler->getCurrentAcademicSession()
      ),
      'term' => $data['term'] ?? $settingHandler->getCurrentTerm()
    ];
  }
}
