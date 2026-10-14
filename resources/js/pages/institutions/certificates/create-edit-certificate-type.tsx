import React from 'react';
import {
  Box,
  HStack,
  Image,
  Input,
  Select,
  Switch,
  Text,
  Textarea,
  FormControl,
  FormLabel,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import FormControlBox from '@/components/forms/form-control-box';
import { FormButton, LinkButton } from '@/components/buttons';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { Inertia } from '@inertiajs/inertia';
import { preventNativeSubmit } from '@/util/util';
import PrimaryColorPicker from './primary-color-picker';
import CertificateArtwork from './templates/CertificateArtwork';
import { CertificateArtworkData } from './templates/types';
import { CertificateTemplate, CertificateType } from '@/types/models';
interface Props {
  templates: CertificateTemplate[];
  institutionDetails: {
    name: string;
    address?: string | null;
    logo?: string | null;
    motto?: string | null;
  };
  certificateType?: CertificateType;
}

export default function CreateEditTemplate({
  templates,
  institutionDetails,
  certificateType,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const form = useWebForm({
    name: certificateType?.name ?? '',
    certificate_template_id:
      certificateType?.certificate_template_id ?? templates[0]?.id ?? '',
    primary_color: certificateType?.primary_color ?? '#1f6b50',
    custom_values: certificateType?.custom_values ?? {},
    is_active: certificateType?.is_active ?? true,
  });
  const selected =
    templates.find(
      (template) =>
        Number(template.id) === Number(form.data.certificate_template_id)
    ) ?? certificateType?.template;
  const institutionFields =
    selected?.placeholders.filter(
      (placeholder) =>
        placeholder.source === 'institution' &&
        ['principal_name', 'principal_title', 'custom_footer'].includes(
          placeholder.key
        )
    ) ?? [];
  const previewData: CertificateArtworkData = {
    institutionName: institutionDetails.name,
    institutionLogo: institutionDetails.logo,
    primaryColor: form.data.primary_color,
    recipientName: 'Jordan Adeyemi',
    recipientType: 'Student',
    awardType: form.data.name || 'Certificate type name',
    citation: 'For dedication, curiosity and outstanding progress.',
    issuedOn: '14 October 2026',
    academicSession: '2025 / 2026',
    classification: 'Senior Secondary',
    signatoryName: form.data.custom_values.principal_name || 'A. Principal',
    signatoryTitle:
      form.data.custom_values.principal_title || 'School Principal',
    certificateNumber: 'CERT-2026-001',
    schoolAddress: institutionDetails.address,
    schoolMotto: institutionDetails.motto,
    principalName: form.data.custom_values.principal_name,
    principalTitle: form.data.custom_values.principal_title,
    customFooter: form.data.custom_values.custom_footer,
  };
  async function submit() {
    const result = await form.submit((data, web) =>
      certificateType
        ? web.put(
            instRoute('certificate-types.update', [certificateType]),
            data
          )
        : web.post(instRoute('certificate-types.store'), data)
    );
    if (handleResponseToast(result))
      Inertia.visit(instRoute('certificate-types.index'));
  }
  return (
    <DashboardLayout>
      <VStack align="stretch" spacing={5}>
        <Slab>
          <SlabHeading
            title={`${certificateType ? 'Edit' : 'Create'} certificate type`}
            rightElement={
              <LinkButton
                title="Back to certificate types"
                href={instRoute('certificate-types.index')}
                variant="outline"
              />
            }
          />
          <SlabBody>
            <Text color="gray.600">
              Choose a shared design and brand color. Your school name, logo,
              and address come from the institution profile. You can create
              several certificate types from the same design.
            </Text>
          </SlabBody>
        </Slab>
        {selected && (
          <div className="certificate-template-preview">
            <CertificateArtwork
              templatePath={selected.template_path}
              data={previewData}
            />
          </div>
        )}
        <Slab>
          <SlabBody>
            <VStack
              as="form"
              align="stretch"
              spacing={4}
              onSubmit={preventNativeSubmit(submit)}
            >
              <FormControlBox
                title="Certificate type name"
                form={form as any}
                formKey="name"
              >
                <Input
                  value={form.data.name}
                  onChange={(e) => form.setValue('name', e.target.value)}
                  placeholder="e.g. Blue academic awards"
                  isRequired
                />
              </FormControlBox>
              <FormControlBox
                title="Shared design"
                form={form as any}
                formKey="certificate_template_id"
              >
                <Select
                  value={form.data.certificate_template_id}
                  onChange={(e) =>
                    form.setValue(
                      'certificate_template_id',
                      Number(e.target.value)
                    )
                  }
                  isRequired
                >
                  {templates.map((template) => (
                    <option key={template.id} value={template.id}>
                      {template.name}
                    </option>
                  ))}
                </Select>
              </FormControlBox>
              <Box
                borderWidth="1px"
                borderColor="gray.200"
                borderRadius="md"
                bg="gray.50"
                p={4}
              >
                <HStack align="start" spacing={4}>
                  {institutionDetails.logo && (
                    <Image
                      src={institutionDetails.logo}
                      alt={`${institutionDetails.name} logo`}
                      boxSize="56px"
                      objectFit="contain"
                    />
                  )}
                  <Box>
                    <Text fontWeight="semibold">{institutionDetails.name}</Text>
                    <Text color="gray.600" fontSize="sm">
                      {institutionDetails.address ||
                        'Add an address to the institution profile to show it on certificates.'}
                    </Text>
                    {institutionDetails.motto && (
                      <Text color="gray.500" fontSize="sm" fontStyle="italic">
                        {institutionDetails.motto}
                      </Text>
                    )}
                    {!institutionDetails.logo && (
                      <Text color="gray.500" fontSize="xs" mt={1}>
                        No school logo is set in the institution profile.
                      </Text>
                    )}
                  </Box>
                </HStack>
              </Box>
              <FormControlBox
                title="Brand color"
                form={form as any}
                formKey="primary_color"
              >
                <PrimaryColorPicker
                  value={form.data.primary_color}
                  onChange={(value) => form.setValue('primary_color', value)}
                />
              </FormControlBox>
              {institutionFields.map((field) => (
                <FormControlBox
                  key={field.key}
                  title={`${field.label}${field.required ? '' : ' (optional)'}`}
                  form={form as any}
                  formKey={`custom_values.${field.key}`}
                >
                  {field.type === 'textarea' ? (
                    <Textarea
                      value={form.data.custom_values[field.key] ?? ''}
                      onChange={(e) =>
                        form.setValue('custom_values', {
                          ...form.data.custom_values,
                          [field.key]: e.target.value,
                        })
                      }
                    />
                  ) : (
                    <Input
                      value={form.data.custom_values[field.key] ?? ''}
                      onChange={(e) =>
                        form.setValue('custom_values', {
                          ...form.data.custom_values,
                          [field.key]: e.target.value,
                        })
                      }
                      isRequired={field.required}
                    />
                  )}
                </FormControlBox>
              ))}
              <FormControl>
                <FormLabel>Available for new awards</FormLabel>
                <Switch
                  isChecked={form.data.is_active}
                  onChange={(e) => form.setValue('is_active', e.target.checked)}
                />
              </FormControl>
              <HStack justify="end">
                <FormButton isLoading={form.processing}>
                  {certificateType
                    ? 'Save certificate type'
                    : 'Create certificate type'}
                </FormButton>
              </HStack>
            </VStack>
          </SlabBody>
        </Slab>
      </VStack>
    </DashboardLayout>
  );
}
