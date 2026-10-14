<?php

namespace App\Http\Controllers\Institutions\Certificates;

use App\Enums\InstitutionUserStatus;
use App\Enums\InstitutionUserType;
use App\Http\Controllers\Controller;
use App\Models\AcademicSession;
use App\Models\Certificate;
use App\Models\CertificateBatch;
use App\Models\CertificateTemplate;
use App\Models\CertificateType;
use App\Models\Classification;
use App\Models\Institution;
use App\Models\InstitutionUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CertificateController extends Controller
{
    public function index(Institution $institution, Request $request)
    {
        $this->requireAdmin();
        $certificates = Certificate::query()
            ->with(['certificateType.template', 'batch', 'recipient.user'])
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = '%'.$request->string('search').'%';
                $query->where(function ($q) use ($search) {
                    $q->where('reference_code', 'like', $search)
                        ->orWhereHas(
                            'certificateType',
                            fn ($type) => $type->where('name', 'like', $search)
                        )
                        ->orWhereHas(
                            'recipient.user',
                            fn ($user) => $user
                                ->where('first_name', 'like', $search)
                                ->orWhere('last_name', 'like', $search)
                        );
                });
            })
            ->when(
                $request->filled('status'),
                fn ($q) => $q->where('status', $request->string('status'))
            )
            ->latest('issued_on')
            ->paginate(20)
            ->withQueryString();

        return Inertia::render('institutions/certificates/list-certificates', [
            'certificates' => $certificates,
            'filters' => $request->only('search', 'status'),
            'activeTemplateCount' => CertificateTemplate::query()
                ->where('is_active', true)
                ->count(),
        ]);
    }

    public function types(Institution $institution)
    {
        $this->requireAdmin();

        return Inertia::render('institutions/certificates/manage-certificate-types', [
            'certificateTypes' => CertificateType::query()
                ->with('template')
                ->withCount(['certificates', 'batches'])
                ->orderBy('id')
                ->get(),
        ]);
    }

    public function createType(Institution $institution)
    {
        $this->requireAdmin();

        return Inertia::render('institutions/certificates/create-edit-certificate-type', [
            'templates' => CertificateTemplate::query()->where('is_active', true)->orderBy('name')->get(),
            'institutionDetails' => $this->institutionCertificateDetails($institution),
        ]);
    }

    public function storeType(Institution $institution, Request $request)
    {
        $this->requireAdmin();
        $data = $this->validateType($request, $institution);
        $data['institution_id'] = $institution->id;
        CertificateType::query()->create($data);

        return $this->ok(['message' => 'Certificate type created.']);
    }

    public function editType(Institution $institution, CertificateType $certificateType)
    {
        $this->requireAdmin();
        abort_unless((int) $certificateType->institution_id === (int) $institution->id, 404);

        return Inertia::render('institutions/certificates/create-edit-certificate-type', [
            'certificateType' => $certificateType->load('template'),
            'templates' => CertificateTemplate::query()->where('is_active', true)->orderBy('name')->get(),
            'institutionDetails' => $this->institutionCertificateDetails($institution),
        ]);
    }

    public function updateType(Institution $institution, CertificateType $certificateType, Request $request)
    {
        $this->requireAdmin();
        abort_unless((int) $certificateType->institution_id === (int) $institution->id, 404);
        $data = $this->validateType($request, $institution);
        if ($certificateType->certificates()->exists()) {
            abort_if((int) $data['certificate_template_id'] !== (int) $certificateType->certificate_template_id, 422, 'A design with issued certificates cannot be changed.');
        }
        $certificateType->update($data);

        return $this->ok(['message' => 'Certificate type updated.']);
    }

    public function destroyType(Institution $institution, CertificateType $certificateType)
    {
        $this->requireAdmin();
        abort_unless((int) $certificateType->institution_id === (int) $institution->id, 404);
        abort_if($certificateType->certificates()->exists() || $certificateType->batches()->exists(), 422, 'A certificate type used by certificates or batches cannot be deleted.');
        $certificateType->delete();

        return $this->ok(['message' => 'Certificate type deleted.']);
    }

    private function validateType(Request $request, Institution $institution): array
    {
        $template = CertificateTemplate::query()->where('is_active', true)->findOrFail($request->input('certificate_template_id'));
        $rules = [
            'certificate_template_id' => ['required', 'integer', Rule::exists('certificate_templates', 'id')->where('is_active', true)],
            'name' => ['required', 'string', 'max:120'],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'is_active' => ['required', 'boolean'],
        ];
        foreach ($template->placeholders ?? [] as $placeholder) {
            if (
                ($placeholder['source'] ?? null) !== 'institution' ||
                in_array($placeholder['key'] ?? '', [
                    'institution_name',
                    'institution_logo',
                    'school_address',
                    'school_motto',
                ])
            ) {
                continue;
            }
            $key = $placeholder['key'];
            $rules["custom_values.{$key}"] = [($placeholder['required'] ?? false) ? 'required' : 'nullable', 'string', 'max:'.(($placeholder['type'] ?? '') === 'textarea' ? 2000 : 255)];
        }
        $data = $request->validate($rules);
        $customValues = [
            'school_address' => $institution->address,
            'school_motto' => $institution->subtitle,
        ];
        foreach ($template->placeholders ?? [] as $placeholder) {
            $key = $placeholder['key'] ?? null;
            if (($placeholder['source'] ?? null) === 'institution' && $key && array_key_exists($key, $data['custom_values'] ?? [])) {
                $customValues[$key] = $data['custom_values'][$key];
            }
        }
        $data['custom_values'] = $customValues;
        $data['institution_name'] = $institution->name;
        $data['logo_url'] = $institution->photo;

        return $data;
    }

    private function institutionCertificateDetails(Institution $institution): array
    {
        return [
            'name' => $institution->name,
            'address' => $institution->address,
            'logo' => $institution->photo,
            'motto' => $institution->subtitle,
        ];
    }

    public function create(Institution $institution)
    {
        $this->requireAdmin();
        $certificateTypes = CertificateType::query()
            ->where('is_active', true)
            ->whereHas('template', fn ($query) => $query->where('is_active', true))
            ->with('template')
            ->get();

        return Inertia::render('institutions/certificates/issue-certificate', [
            'certificateTypes' => $certificateTypes,
            'institutionDetails' => $this->institutionCertificateDetails($institution),
            'batches' => CertificateBatch::query()
                ->where('status', 'open')
                ->with('certificateType.template')
                ->withCount('certificates')
                ->latest()
                ->get(),
        ]);
    }

    public function store(Institution $institution, Request $request)
    {
        $this->requireAdmin();
        $data = $request->validate([
            'certificate_batch_id' => ['nullable', 'integer'],
            'institution_user_ids' => ['required', 'array', 'min:1', 'max:500'],
            'institution_user_ids.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('institution_users', 'id')
                    ->where('institution_id', $institution->id)
                    ->where('status', InstitutionUserStatus::Active->value),
            ],
            'certificate_type_id' => [
                Rule::requiredIf(! $request->filled('certificate_batch_id')),
                'nullable',
                'integer',
                Rule::exists('certificate_types', 'id')
                    ->where('institution_id', $institution->id)
                    ->where('is_active', true),
            ],
            'citation' => ['nullable', 'string', 'max:2000'],
            'issued_on' => [
                Rule::requiredIf(! $request->filled('certificate_batch_id')),
                'nullable',
                'date',
            ],
            'academic_session_id' => [
                'nullable',
                'integer',
                Rule::exists('academic_sessions', 'id'),
            ],
            'classification_id' => [
                'nullable',
                'integer',
                Rule::exists('classifications', 'id')->where(
                    'institution_id',
                    $institution->id
                ),
            ],
            'signatory_name' => ['nullable', 'string', 'max:120'],
            'signatory_title' => ['nullable', 'string', 'max:120'],
        ]);

        $batch = null;
        if (! empty($data['certificate_batch_id'])) {
            $batch = CertificateBatch::query()
                ->where('status', 'open')
                ->findOrFail($data['certificate_batch_id']);
            abort_unless(
                (int) $batch->institution_id === (int) $institution->id,
                404
            );
            $customization = $batch
                ->certificateType()
                ->with('template')
                ->firstOrFail();
            $award = $batch->only([
                'citation',
                'issued_on',
                'academic_session_id',
                'classification_id',
                'signatory_name',
                'signatory_title',
            ]);
        } else {
            $customization = CertificateType::query()
                ->with('template')
                ->findOrFail($data['certificate_type_id']);
            $award = $data;
        }
        abort_unless(
            (int) $customization->institution_id === (int) $institution->id &&
              $customization->is_active &&
              $customization->template?->is_active,
            422,
            'Choose an active certificate design for this institution.'
        );
        $customValues = $customization->custom_values ?? [];
        $award['signatory_name'] = filled($award['signatory_name'] ?? null)
            ? $award['signatory_name']
            : ($customValues['principal_name'] ?? null);
        $award['signatory_title'] = filled($award['signatory_title'] ?? null)
            ? $award['signatory_title']
            : ($customValues['principal_title'] ?? null);

        $recipients = InstitutionUser::query()
            ->whereIn('id', $data['institution_user_ids'])
            ->get();
        abort_unless(
            $recipients->count() === count($data['institution_user_ids']),
            422,
            'One or more recipients are no longer active in this institution.'
        );
        if (! empty($award['classification_id'])) {
            $types = $recipients->pluck('type')->unique();
            if (
                $types->count() === 1 &&
                $types->first() === InstitutionUserType::Student
            ) {
                $recipients->load('student');
                abort_if(
                    $recipients->contains(
                        fn (InstitutionUser $recipient) => (int) $recipient->student
                            ?->classification_id !== (int) $award['classification_id']
                    ),
                    422,
                    'The selected class must match every student in the award.'
                );
            }
        }

        DB::transaction(function () use (
            $data,
            $institution,
            $batch,
            $customization,
            $award
        ) {
            foreach ($data['institution_user_ids'] as $recipientId) {
                $recipient = InstitutionUser::query()
                    ->with('user')
                    ->findOrFail($recipientId);
                $renderData = [
                    'recipient_name' => $recipient->user?->full_name,
                    'recipient_type' => $recipient->type->value,
                    'certificate_type_name' => $customization->name,
                    'template_name' => $customization->template->name,
                    'template_path' => $customization->template->template_path,
                    'institution_name' => $institution->name,
                    'institution_logo' => $institution->photo,
                    'primary_color' => $customization->primary_color,
                    'custom_values' => [
                        ...($customization->custom_values ?? []),
                        'school_address' => $institution->address,
                        'school_motto' => $institution->subtitle,
                    ],
                    'academic_session' => ! empty($award['academic_session_id'])
                      ? AcademicSession::find($award['academic_session_id'])?->title
                      : null,
                    'classification' => ! empty($award['classification_id'])
                      ? Classification::query()->find($award['classification_id'])?->title
                      : null,
                ];
                Certificate::create([
                    'institution_id' => $institution->id,
                    'certificate_type_id' => $customization->id,
                    'certificate_batch_id' => $batch?->id,
                    'institution_user_id' => $recipientId,
                    'issued_by_institution_user_id' => currentInstitutionUser()->id,
                    'reference_code' => 'CERT-'.Str::upper(Str::random(10)),
                    'citation' => $award['citation'] ?? null,
                    'issued_on' => $award['issued_on'],
                    'academic_session_id' => $award['academic_session_id'] ?? null,
                    'classification_id' => $award['classification_id'] ?? null,
                    'signatory_name' => $award['signatory_name'] ?? null,
                    'signatory_title' => $award['signatory_title'] ?? null,
                    'render_data' => $renderData,
                ]);
            }
        });

        return $this->ok([
            'message' => count($data['institution_user_ids']).' certificate(s) issued.',
        ]);
    }

    public function batches(Institution $institution)
    {
        $this->requireAdmin();

        return Inertia::render('institutions/certificates/manage-batches', [
            'institutionDetails' => $this->institutionCertificateDetails($institution),
            'batches' => CertificateBatch::query()
                ->with('certificateType.template')
                ->withCount('certificates')
                ->latest()
                ->paginate(20),
            'certificateTypes' => CertificateType::query()
                ->where('is_active', true)
                ->whereHas('template', fn ($query) => $query->where('is_active', true))
                ->with('template')
                ->get(),
        ]);
    }

    public function storeBatch(Institution $institution, Request $request)
    {
        $this->requireAdmin();
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'certificate_type_id' => [
                'required',
                'integer',
                Rule::exists('certificate_types', 'id')
                    ->where('institution_id', $institution->id)
                    ->where('is_active', true),
            ],
            'citation' => ['nullable', 'string', 'max:2000'],
            'issued_on' => ['required', 'date'],
            'academic_session_id' => [
                'nullable',
                'integer',
                Rule::exists('academic_sessions', 'id'),
            ],
            'classification_id' => [
                'nullable',
                'integer',
                Rule::exists('classifications', 'id')->where(
                    'institution_id',
                    $institution->id
                ),
            ],
            'signatory_name' => ['nullable', 'string', 'max:120'],
            'signatory_title' => ['nullable', 'string', 'max:120'],
        ]);
        $data['created_by_institution_user_id'] = currentInstitutionUser()->id;
        $data['institution_id'] = $institution->id;
        $customization = CertificateType::query()
            ->with('template')
            ->findOrFail($data['certificate_type_id']);
        abort_unless(
            $customization->is_active && $customization->template?->is_active,
            422,
            'Choose an active certificate design.'
        );
        $customValues = $customization->custom_values ?? [];
        $data['signatory_name'] = filled($data['signatory_name'] ?? null)
            ? $data['signatory_name']
            : ($customValues['principal_name'] ?? null);
        $data['signatory_title'] = filled($data['signatory_title'] ?? null)
            ? $data['signatory_title']
            : ($customValues['principal_title'] ?? null);
        CertificateBatch::create($data);

        return $this->ok(['message' => 'Certificate batch created.']);
    }

    public function closeBatch(
        Institution $institution,
        CertificateBatch $certificateBatch
    ) {
        $this->requireAdmin();
        abort_unless(
            (int) $certificateBatch->institution_id === (int) $institution->id,
            404
        );
        abort_if(
            $certificateBatch->status !== 'open',
            422,
            'This batch is already closed.'
        );
        $certificateBatch->update(['status' => 'closed']);

        return $this->ok(['message' => 'Certificate batch closed.']);
    }

    public function show(Institution $institution, Certificate $certificate)
    {
        abort_unless(
            (int) $certificate->institution_id === (int) $institution->id,
            404
        );
        $user = currentInstitutionUser();
        abort_unless(
            $user->isAdmin() ||
              (int) $certificate->institution_user_id === (int) $user->id,
            403
        );
        $certificate->load([
            'certificateType.template',
            'batch',
            'recipient.user',
            'issuedBy.user',
            'academicSession',
            'classification',
        ]);

        return Inertia::render('institutions/certificates/show-certificate', [
            'certificate' => $certificate,
        ]);
    }

    public function myCertificates(Institution $institution)
    {
        $user = currentInstitutionUser();
        $certificates = Certificate::query()
            ->with('certificateType.template')
            ->where('institution_user_id', $user->id)
            ->latest('issued_on')
            ->paginate(20);

        return Inertia::render('institutions/certificates/my-certificates', [
            'certificates' => $certificates,
        ]);
    }

    public function revoke(
        Institution $institution,
        Certificate $certificate,
        Request $request
    ) {
        $this->requireAdmin();
        abort_unless(
            (int) $certificate->institution_id === (int) $institution->id,
            404
        );
        abort_if(
            $certificate->status !== 'issued',
            422,
            'Only issued certificates can be revoked.'
        );
        $data = $request->validate([
            'revocation_reason' => ['required', 'string', 'max:1000'],
        ]);
        $certificate->update([
            'status' => 'revoked',
            'revoked_at' => now(),
            'revocation_reason' => $data['revocation_reason'],
        ]);

        return $this->ok(['message' => 'Certificate revoked.']);
    }

    private function requireAdmin(): void
    {
        abort_unless(
            currentInstitutionUser()?->type === InstitutionUserType::Admin,
            403
        );
    }
}
