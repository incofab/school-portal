<?php

namespace App\Http\Controllers\Institutions\ResultPublications;

use App\Enums\PriceLists\PriceType;
use App\Models\Institution;
use Illuminate\Http\Request;
use App\Models\Classification;
use App\Support\SettingsHandler;
use App\Http\Controllers\Controller;
use App\Models\ResultPublication;
use App\Rules\ValidateExistsRule;
use App\Support\ResultPublications\PublishResult;

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

  function create(Institution $institution)
  {
    $classifications = $institution->classifications()->get();
    return inertia(
      'institutions/result-publications/create-result-publication',
      [
        'classifications' => $classifications
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
      'send_to_guardians_whatsapp' => 'boolean'
    ]);

    $submittedClassIds = $request->classifications;
    $settingHandler = SettingsHandler::makeFromRoute();
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
      $request->send_to_guardians_whatsapp
    );
    $res = $obj->execute();

    if ($res->isNotSuccessful()) {
      return $this->message($res->message, 401);
    }

    return $this->message($res->message);
  }
}
