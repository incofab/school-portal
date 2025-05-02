<?php

namespace App\Http\Controllers\Institutions\Staff;

use App\Actions\Messages\GenericMessageHandler;
use App\Enums\InstitutionUserType;
use App\Enums\NotificationChannelsType;
use App\Http\Controllers\Controller;
use App\Models\Association;
use App\Models\Classification;
use App\Models\ClassificationGroup;
use App\Models\Institution;
use App\Models\Message;
use App\Models\SchoolNotification;
use App\Rules\ValidateMorphRule;
use App\Rules\ValidateUniqueRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\ValidationException;

class MessageController extends Controller
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
    $messages = Message::query()
      ->with('sender', 'messageRecipients')
      ->latest('id');

    return inertia('institutions/messages/list-messages', [
      'messages' => paginateFromRequest($messages)
    ]);
  }

  public function create(Institution $institution)
  {
    return inertia('institutions/messages/create-message', [
      'classifications' => Classification::all(),
      'classificationGroups' => ClassificationGroup::all(),
      'associations' => Association::all()
    ]);
  }

  public function store(Institution $institution, Request $request)
  {
    $validateMorph = new ValidateMorphRule('messageable');
    $data = $request->validate([
      'message' => ['required', 'string'],
      'subject' => [
        Rule::requiredIf(
          $request->channel === NotificationChannelsType::Email->value
        ),
        'string'
      ],
      'reference' => [
        'required',
        new ValidateUniqueRule(SchoolNotification::class)
      ],
      'channel' => ['required', new Enum(NotificationChannelsType::class)],
      'receivers' => ['nullable', 'string'],
      'to_guardians' => ['required', 'boolean'],
      ...$request->receivers
        ? []
        : [
          'messageable_type' => ['required', 'string', $validateMorph],
          'messageable_id' => ['required', 'integer']
        ]
    ]);

    $model = $validateMorph->getModel();

    if (empty($request->receivers) && empty($model)) {
      return ValidationException::withMessages([
        'receivers' => 'You need to supply a receiver',
        'receivers' => 'You need to supply a receiver'
      ]);
    }

    $handler = new GenericMessageHandler(
      $institution,
      currentUser(),
      $data['message'],
      $data['subject'] ?? 'Text Message'
    );

    if ($model) {
      $res = $handler->sendToUsers(
        $model,
        $data['channel'],
        $data['to_guardians']
      );
    } else {
      $receivers = collect(explode(',', $data['receivers']));
      $res = $handler->sendToReceivers($receivers, $data['channel']);
    }

    return $res->isSuccessful()
      ? $this->ok()
      : $this->message($res->getMessage(), 403);
  }
}
