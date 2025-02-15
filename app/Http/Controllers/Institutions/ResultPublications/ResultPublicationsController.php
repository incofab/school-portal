<?php

namespace App\Http\Controllers\Institutions\ResultPublications;

use App\Enums\PriceLists\PriceType;
use App\Models\Institution;
use Illuminate\Http\Request;
use App\Models\Classification;
use App\Support\SettingsHandler;
use App\Http\Controllers\Controller;
use App\Rules\ValidateExistsRule;
use App\Support\ResultPublications\PublishResult;

class ResultPublicationsController extends Controller
{
  //
  function index(Institution $institution)
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
      ]
    ]);

    $submittedClassIds = $request->classifications;
    $settingHandler = SettingsHandler::makeFromRoute();
    $institutionGroup = $institution->institutionGroup;
    $instGroupPriceList = $institutionGroup
      ->pricelists()
      ->where('type', PriceType::ResultChecking->value)
      ->first();

    $obj = PublishResult::make(
      currentUser(),
      $institution,
      $settingHandler,
      $instGroupPriceList,
      $submittedClassIds
    );
    $res = $obj->execute();

    if ($res->isNotSuccessful()) {
      return $this->message($res->message, 401);
    }

    return $this->message($res->message);
  }
}
