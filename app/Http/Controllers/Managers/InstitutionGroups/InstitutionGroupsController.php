<?php

namespace App\Http\Controllers\Managers\InstitutionGroups;

use App\Actions\RegisterInstitutionGroup;
use App\Actions\Subscriptions\GenerateInvoice;
use App\Enums\InstitutionStatus;
use App\Enums\S3Folder;
use App\Enums\TermType;
use App\Http\Controllers\Controller;
use App\Models\InstitutionGroup;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Storage;
use Inertia\Inertia;
use App\Models\AcademicSession;

class InstitutionGroupsController extends Controller
{
  public function index(Request $request)
  {
    $user = currentUser();
    $stats = InstitutionGroup::selectRaw(
      "
      SUM(CASE WHEN status = 'suspended' THEN 1 ELSE 0 END) as suspended_count,
      SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_count,
      COUNT(id) as total
    "
    )->first();
    return Inertia::render(
      'managers/institution-groups/list-institution-groups',
      [
        'institutionGroups' => paginateFromRequest(
          InstitutionGroup::getQueryForManager($user)
            ->withCount('institutions')
            ->with('partner', 'institutions:id,institution_group_id,uuid')
            ->orderByRaw(
              "institution_groups.status IS NOT NULL, FIELD(institution_groups.status, 'active', 'suspended')"
            )
            ->latest('id')
        ),
        'stats' => $stats,
        'academicSessions' => AcademicSession::all()
      ]
    );
  }

  function search(Request $request)
  {
    $query = InstitutionGroup::getQueryForManager(currentUser())->when(
      $request->search,
      fn($q, $value) => $q->where('name', 'LIKE', "%$value%")
    );
    return response()->json([
      'result' => paginateFromRequest(
        $query
          ->orderByRaw(
            "institution_groups.status IS NOT NULL, FIELD(institution_groups.status, 'active', 'suspended')"
          )
          ->latest('id')
      )
    ]);
  }

  function create()
  {
    return inertia('managers/institution-groups/create-institution-group');
  }

  function store(Request $request)
  {
    $data = $request->validate([
      ...User::generalRule(),
      'institution_group' => ['required', 'array'],
      'institution_group.name' => ['required', 'string', 'max:255']
    ]);

    $userData = [
      ...collect($data)
        ->except('institution_group')
        ->toArray(),
      'password' => bcrypt($data['password'])
    ];
    $institutionGroupData = $data['institution_group'];

    RegisterInstitutionGroup::run(
      currentUser(),
      $userData,
      $institutionGroupData
    );

    // DB::beginTransaction();
    // $user = User::query()->create($userData);
    // currentUser()
    //   ->partnerInstitutionGroups()
    //   ->create([...$institutionGroupData, 'user_id' => $user->id]);
    // DB::commit();

    return $this->ok();
  }

  function edit(InstitutionGroup $institutionGroup)
  {
    $this->authorize('update', $institutionGroup);
    return inertia('managers/institution-groups/edit-institution-group', [
      'institutionGroup' => $institutionGroup
    ]);
  }

  public function uploadBanner(
    Request $request,
    InstitutionGroup $institutionGroup
  ) {
    $request->validate([
      'banner' => [
        'required',
        'image',
        'mimes:jpg,png,jpeg',
        'max:1024',

        function ($attribute, $value, $fail) {
          $image = getimagesize($value);
          $width = 1500;
          $height = 860;

          if ($image[0] !== $width) {
            $fail("The $attribute width must be $width pixels.");
          }

          if ($image[1] !== $height) {
            $fail("The $attribute height must be $height pixels.");
          }
        }
      ]
    ]);

    $imagePath = $request->banner->store(
      S3Folder::InstitutionGroupBanners->value,
      's3_public'
    );
    $publicUrl = Storage::disk('s3_public')->url($imagePath);

    $institutionGroup->fill(['banner' => $publicUrl])->save();

    return response()->json([
      'url' => $publicUrl
    ]);
  }

  function update(Request $request, InstitutionGroup $institutionGroup)
  {
    $this->authorize('update', $institutionGroup);
    $data = $request->validate([
      'name' => ['required', 'string', 'max:255'],
      'loan_limit' => ['required', 'integer', 'min:0'],
      'website' => ['nullable', 'string', 'max:50']
    ]);
    $institutionGroup->update($data);
    return $this->ok();
  }

  public function destroy(Request $request, InstitutionGroup $institutionGroup)
  {
    $this->authorize('delete', $institutionGroup);
    abort_if(
      $institutionGroup->institutions()->count() > 0,
      403,
      'This group contains some institution'
    );
    $institutionGroup->delete();
    return $this->ok();
  }

  function updateStatus(Request $request, InstitutionGroup $institutionGroup)
  {
    $this->authorize('delete', $institutionGroup);
    $request->validate([
      'status' => ['required', new Enum(InstitutionStatus::class)]
    ]);
    $status = $request->status;

    if ($status === $institutionGroup->status->value) {
      return $this->ok();
    }

    $institutionGroup->fill(['status' => $status])->save();
    $institutionGroup->institutions()->update(['status' => $status]);
    return $this->ok();
  }

  public function generateInvoice(
    InstitutionGroup $institutionGroup,
    AcademicSession $academicSession,
    $term
  ) {
    $termType = TermType::tryFrom($term);

    abort_unless($termType, 'Please, supply a valid term type');

    return (new GenerateInvoice(
      $institutionGroup,
      $academicSession,
      $termType
    ))->downloadAsPdf();
    // ))->viewAsHtml();
  }
}
