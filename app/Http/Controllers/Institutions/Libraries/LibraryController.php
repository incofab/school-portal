<?php

namespace App\Http\Controllers\Institutions\Libraries;

use App\Actions\RecordLibrary;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Http\Requests\LibraryRequest;
use App\Models\Institution;
use App\Models\Library;
use App\Support\UITableFilters\LibraryUITableFilters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class LibraryController extends Controller
{
    public function __construct()
    {
        $this->allowedRoles([
            InstitutionUserType::Admin,
            InstitutionUserType::Teacher,
        ])->except('index', 'show');

        $this->allowedRoles([
            InstitutionUserType::Student,
            InstitutionUserType::Admin,
            InstitutionUserType::Teacher,
        ])->only('index', 'show');
    }

    public function index(Request $request, Institution $institution)
    {
        $institutionUser = currentInstitutionUser();

        $query = Library::query()
            ->with('classifications', 'course', 'institutionUser.user', 'media')
            ->when(
                $institutionUser->isStudent(),
                fn ($query) => $query->forStudent(
                    $institutionUser->student()->firstOrFail()
                )
            );

        LibraryUITableFilters::make($request->all(), $query)->filterQuery();

        return Inertia::render('institutions/libraries/list-libraries', [
            'libraries' => paginateFromRequest(
                $query->latest('libraries.id')
            ),
        ]);
    }

    public function create(Institution $institution)
    {
        return Inertia::render('institutions/libraries/create-edit-library');
    }

    public function store(
        Institution $institution,
        LibraryRequest $request
    ) {
        (new RecordLibrary(
            $institution,
            $request->getInstitutionUser(),
            $request->validated()
        ))->create();

        return $this->ok();
    }

    public function show(Institution $institution, Library $library)
    {
        $this->authorizeStudentAccess($library);

        return Inertia::render('institutions/libraries/show-library', [
            'library' => $library->load(
                'classifications',
                'course',
                'institutionUser.user',
                'media'
            ),
        ]);
    }

    public function edit(Institution $institution, Library $library)
    {
        $this->authorizeStaffAccess($library);
        $library->load('classifications', 'media');

        return Inertia::render('institutions/libraries/create-edit-library', [
            'library' => $library,
        ]);
    }

    public function update(
        LibraryRequest $request,
        Institution $institution,
        Library $library
    ) {
        $this->authorizeStaffAccess($library);

        (new RecordLibrary(
            $institution,
            $request->getInstitutionUser(),
            $request->validated()
        ))->update($library);

        return $this->ok();
    }

    public function destroy(Institution $institution, Library $library)
    {
        $this->authorizeStaffAccess($library);
        $library->media()->get()->each(function ($media) {
            Storage::disk($media->disk)->delete($media->path);
            $media->delete();
        });
        $library->delete();

        return $this->ok();
    }

    private function authorizeStudentAccess(Library $library): void
    {
        $institutionUser = currentInstitutionUser();

        if (! $institutionUser->isStudent()) {
            return;
        }

        $student = $institutionUser->student()->firstOrFail();

        abort_unless(
            $library->is_published &&
              (
                  $library->is_public ||
                  $library
                      ->classifications()
                      ->where('classifications.id', $student->classification_id)
                      ->exists()
              ),
            403
        );
    }

    private function authorizeStaffAccess(Library $library): void
    {
        $institutionUser = currentInstitutionUser();

        abort_unless(
            $institutionUser->isAdmin() ||
              $library->institution_user_id === $institutionUser->id,
            403
        );
    }
}
