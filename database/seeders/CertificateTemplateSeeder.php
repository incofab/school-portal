<?php

namespace Database\Seeders;

use App\Models\CertificateTemplate;
use Illuminate\Database\Seeder;

class CertificateTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $placeholders = self::defaultPlaceholders();

        $templates = [
            [
                'classic',
                'Classic Honour',
                'Formal framed layout with a traditional seal.',
                'resources/js/pages/institutions/certificates/templates/template-1.tsx',
            ],
            [
                'modern',
                'Modern Ribbon',
                'Contemporary editorial layout with a strong side panel.',
                'resources/js/pages/institutions/certificates/templates/template-2.tsx',
            ],
            ['academic', 'Scholars Laurel', 'Academic styling with a laurel crest and centered typography.', 'resources/js/pages/institutions/certificates/templates/template-3.tsx'],
            ['summit', 'Summit Achievement', 'Bold geometric composition for standout achievements.', 'resources/js/pages/institutions/certificates/templates/template-4.tsx'],
            ['minimal', 'Modern Minimal', 'Quiet, refined typography with generous white space.', 'resources/js/pages/institutions/certificates/templates/template-5.tsx'],
            ['heritage', 'Heritage Botanical', 'Decorative botanical border with a warm paper feel.', 'resources/js/pages/institutions/certificates/templates/template-6.tsx'],
            ['milestone', 'Milestone Badge', 'Structured layout with a prominent achievement badge.', 'resources/js/pages/institutions/certificates/templates/template-7.tsx'],
            ['prestige', 'Prestige Night', 'Deep, high-contrast design with a luminous award seal.', 'resources/js/pages/institutions/certificates/templates/template-8.tsx'],
            ['celebration', 'Bright Celebration', 'A bright, approachable style for programmes and events.', 'resources/js/pages/institutions/certificates/templates/template-9.tsx'],
        ];

        foreach ($templates as [$slug, $name, $description, $path]) {
            CertificateTemplate::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'description' => $description,
                    'template_path' => $path,
                    'placeholders' => $placeholders,
                    'is_active' => true,
                ]
            );
        }
    }

    public static function defaultPlaceholders(): array
    {
        return [
            [
                'key' => 'institution_name',
                'label' => 'Institution name',
                'source' => 'institution',
                'required' => true,
                'type' => 'text',
            ],
            [
                'key' => 'institution_logo',
                'label' => 'Institution logo',
                'source' => 'institution',
                'required' => false,
                'type' => 'image',
            ],
            [
                'key' => 'school_motto',
                'label' => 'School motto',
                'source' => 'institution',
                'required' => false,
                'type' => 'text',
            ],
            [
                'key' => 'school_address',
                'label' => 'School address',
                'source' => 'institution',
                'required' => false,
                'type' => 'text',
            ],
            [
                'key' => 'principal_name',
                'label' => 'Principal name',
                'source' => 'institution',
                'required' => false,
                'type' => 'text',
            ],
            [
                'key' => 'principal_title',
                'label' => 'Principal title',
                'source' => 'institution',
                'required' => false,
                'type' => 'text',
            ],
            [
                'key' => 'custom_footer',
                'label' => 'Footer message',
                'source' => 'institution',
                'required' => false,
                'type' => 'textarea',
            ],
            [
                'key' => 'primary_color',
                'label' => 'Brand color',
                'source' => 'branding',
                'required' => true,
                'type' => 'color',
            ],
            [
                'key' => 'recipient_name',
                'label' => 'Recipient name',
                'source' => 'issuance',
                'required' => true,
                'type' => 'institution_user',
            ],
            [
                'key' => 'recipient_type',
                'label' => 'Recipient type',
                'source' => 'issuance',
                'required' => true,
                'type' => 'institution_user_type',
            ],
            [
                'key' => 'award_type',
                'label' => 'Award type',
                'source' => 'issuance',
                'required' => true,
                'type' => 'text',
            ],
            [
                'key' => 'citation',
                'label' => 'Award citation',
                'source' => 'issuance',
                'required' => false,
                'type' => 'textarea',
            ],
            [
                'key' => 'issued_on',
                'label' => 'Issue date',
                'source' => 'issuance',
                'required' => true,
                'type' => 'date',
            ],
            [
                'key' => 'academic_session',
                'label' => 'Academic session',
                'source' => 'issuance',
                'required' => false,
                'type' => 'academic_session',
            ],
            [
                'key' => 'classification',
                'label' => 'Class or programme',
                'source' => 'issuance',
                'required' => false,
                'type' => 'classification',
            ],
            [
                'key' => 'signatory_name',
                'label' => 'Signatory name',
                'source' => 'issuance',
                'required' => false,
                'type' => 'text',
            ],
            [
                'key' => 'signatory_title',
                'label' => 'Signatory title',
                'source' => 'issuance',
                'required' => false,
                'type' => 'text',
            ],
            [
                'key' => 'certificate_number',
                'label' => 'Certificate number',
                'source' => 'system',
                'required' => true,
                'type' => 'reference',
            ],
        ];

    }
}
