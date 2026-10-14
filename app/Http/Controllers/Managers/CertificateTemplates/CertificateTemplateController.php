<?php

namespace App\Http\Controllers\Managers\CertificateTemplates;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use Database\Seeders\CertificateTemplateSeeder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class CertificateTemplateController extends Controller
{
    public function index()
    {
        return Inertia::render('managers/certificate-templates/list-certificate-templates', [
            'templates' => CertificateTemplate::query()
                ->withCount('certificateTypes')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function preview(CertificateTemplate $certificateTemplate)
    {
        return Inertia::render('managers/certificate-templates/preview-certificate-template', [
            'template' => $certificateTemplate,
            'previewData' => [
                'institutionName' => 'Willowbrook International School',
                'institutionLogo' => null,
                'schoolAddress' => '18 Garden Avenue, Ikeja, Lagos',
                'schoolMotto' => 'Learning with purpose',
                'primaryColor' => '#315c48',
                'recipientName' => 'Jordan Adeyemi',
                'recipientType' => 'Student',
                'awardType' => 'Academic Excellence',
                'citation' => 'For exceptional commitment, curiosity, and progress throughout the 2025 / 2026 academic year.',
                'issuedOn' => '14 October 2026',
                'academicSession' => '2025 / 2026',
                'classification' => 'Senior Secondary · Year 3',
                'signatoryName' => 'Amara Okafor',
                'signatoryTitle' => 'School Principal',
                'principalName' => 'Amara Okafor',
                'principalTitle' => 'School Principal',
                'certificateNumber' => 'WBIS-2026-00428',
                'customFooter' => 'Presented with pride by the school community.',
            ],
        ]);
    }

    public function create()
    {
        return Inertia::render('managers/certificate-templates/create-edit-certificate-template', [
            'designs' => $this->designs(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateTemplate($request);
        $data['placeholders'] = CertificateTemplateSeeder::defaultPlaceholders();
        CertificateTemplate::query()->create($data);

        return $this->ok(['message' => 'Certificate template created.']);
    }

    public function edit(CertificateTemplate $certificateTemplate)
    {
        return Inertia::render('managers/certificate-templates/create-edit-certificate-template', [
            'template' => $certificateTemplate,
            'designs' => $this->designs(),
        ]);
    }

    public function update(Request $request, CertificateTemplate $certificateTemplate)
    {
        $certificateTemplate->update($this->validateTemplate($request, $certificateTemplate));

        return $this->ok(['message' => 'Certificate template updated.']);
    }

    public function destroy(CertificateTemplate $certificateTemplate)
    {
        abort_if(
            $certificateTemplate->certificateTypes()->exists(),
            422,
            'This design is already used by an institution and cannot be deleted.'
        );

        $certificateTemplate->delete();

        return $this->ok(['message' => 'Certificate template deleted.']);
    }

    private function validateTemplate(Request $request, ?CertificateTemplate $template = null): array
    {
        return $request->validate([
            'slug' => [
                'required', 'string', 'max:100', 'alpha_dash',
                Rule::unique('certificate_templates', 'slug')->ignore($template?->id),
            ],
            'name' => ['required', 'string', 'max:120'],
            'template_path' => ['required', 'string', Rule::in(array_keys($this->designs()))],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function designs(): array
    {
        $names = [
            'Classic Honour',
            'Modern Ribbon',
            'Scholars Laurel',
            'Summit Achievement',
            'Modern Minimal',
            'Heritage Botanical',
            'Milestone Badge',
            'Prestige Night',
            'Bright Celebration',
        ];

        return collect($names)
            ->mapWithKeys(fn (string $name, int $index) => [
                'resources/js/pages/institutions/certificates/templates/template-'.($index + 1).'.tsx' => $name,
            ])
            ->all();
    }
}
