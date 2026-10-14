import React from 'react';
import {
  Divider,
  FormControl,
  FormErrorMessage,
  FormLabel,
  HStack,
  Input,
  Select,
  SimpleGrid,
  Text,
  Textarea,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import FormControlBox from '@/components/forms/form-control-box';
import InstitutionUserSelect from '@/components/selectors/institution-user-select';
import AcademicSessionSelect from '@/components/selectors/academic-session-select';
import ClassificationSelect from '@/components/selectors/classification-select';
import { FormButton, LinkButton } from '@/components/buttons';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { Inertia } from '@inertiajs/inertia';
import { preventNativeSubmit } from '@/util/util';
import CertificateArtwork from './templates/CertificateArtwork';
import { CertificateBatch, CertificateType } from '@/types/models';
import { CertificateArtworkData } from './templates/types';
import useSharedProps from '@/hooks/use-shared-props';
import { Div } from '@/components/semantic';

interface Option {
  label: string;
  value: number;
}
interface Props {
  certificateTypes: CertificateType[];
  batches: CertificateBatch[];
  institutionDetails: {
    name: string;
    address?: string | null;
    logo?: string | null;
    motto?: string | null;
  };
}

export default function IssueCertificate({
  certificateTypes,
  batches,
  institutionDetails,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const { currentAcademicSession } = useSharedProps();
  const form = useWebForm({
    certificate_batch_id: '',
    certificate_type_id: '',
    institution_user_ids: [] as Option[],
    citation: '',
    issued_on: new Date().toLocaleDateString('en-CA'),
    academic_session_id: currentAcademicSession.id.toString(),
    classification_id: '',
    signatory_name: '',
    signatory_title: '',
  });
  const { handleResponseToast } = useMyToast();
  const selectedBatch = batches.find(
    (batch) => String(batch.id) === form.data.certificate_batch_id
  );
  const selectedCertificateType = selectedBatch
    ? selectedBatch.certificate_type ?? undefined
    : certificateTypes.find(
        (certificateType) =>
          String(certificateType.id) === form.data.certificate_type_id
      );
  async function submit() {
    const result = await form.submit((data, web) =>
      web.post(instRoute('certificates.store'), {
        ...data,
        certificate_batch_id: data.certificate_batch_id || null,
        institution_user_ids: data.institution_user_ids.map(
          (option) => option.value
        ),
      })
    );
    if (handleResponseToast(result))
      Inertia.visit(instRoute('certificates.index'));
  }
  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Issue certificates"
          rightElement={
            <HStack>
              <LinkButton
                title="Manage batches"
                href={instRoute('certificates.batches')}
                variant="outline"
              />
              <LinkButton
                title="Cancel"
                href={instRoute('certificates.index')}
                variant="outline"
              />
            </HStack>
          }
        />
        <SlabBody>
          <Text color="gray.600" mb={5}>
            Choose an existing batch or leave it blank to issue an individual or
            ad hoc award.
          </Text>
          <VStack
            as="form"
            align="stretch"
            spacing={5}
            onSubmit={preventNativeSubmit(submit)}
          >
            <FormControlBox
              title="Certificate batch (optional)"
              form={form as any}
              formKey="certificate_batch_id"
            >
              <Select
                value={form.data.certificate_batch_id}
                onChange={(e) =>
                  form.setValue('certificate_batch_id', e.target.value)
                }
              >
                <option value="">No batch · issue individually</option>
                {batches.map((batch) => (
                  <option key={batch.id} value={batch.id}>
                    {batch.name} · {batch.certificates_count} issued
                  </option>
                ))}
              </Select>
            </FormControlBox>
            {/* {selectedBatch && selectedBatch.certificate_type?.template ? (
              <Slab>
                <SlabBody>
                  <Text color="gray.600">
                    {selectedBatch.certificate_type.name} ·{' '}
                    {selectedBatch.issued_on}
                  </Text>
                  {selectedBatch.citation && (
                    <Text mt={2}>{selectedBatch.citation}</Text>
                  )}
                  <div className="certificate-issue-preview">
                    <CertificateArtwork
                      templatePath={
                        selectedBatch.certificate_type.template.template_path
                      }
                      data={previewCertificate(
                        selectedBatch.certificate_type,
                        {
                          awardType: selectedBatch.certificate_type.name,
                          citation: selectedBatch.citation,
                          issuedOn: selectedBatch.issued_on,
                          signatoryName: selectedBatch.signatory_name,
                          signatoryTitle: selectedBatch.signatory_title,
                        },
                        institutionDetails
                      )}
                    />
                  </div>
                </SlabBody>
              </Slab>
            ) : ( */}
            <SimpleGrid columns={[1, 2]} spacing={4}>
              <FormControlBox
                title="Recipients"
                form={form as any}
                formKey="institution_user_ids"
              >
                <InstitutionUserSelect
                  value={form.data.institution_user_ids}
                  isMulti
                  isClearable
                  onChange={(value: any) =>
                    form.setValue('institution_user_ids', value ?? [])
                  }
                />
              </FormControlBox>
              <FormControlBox
                title="Certificate type"
                form={form as any}
                formKey="certificate_type_id"
              >
                <Select
                  value={form.data.certificate_type_id}
                  onChange={(e) => {
                    form.setValue('certificate_type_id', e.target.value);
                    const selected = certificateTypes.find(
                      (item) => String(item.id) === e.target.value
                    );
                    form.setValue(
                      'signatory_name',
                      selected?.custom_values?.principal_name ?? ''
                    );
                    form.setValue(
                      'signatory_title',
                      selected?.custom_values?.principal_title ?? ''
                    );
                  }}
                  isRequired
                  placeholder="Choose a certificate type"
                >
                  {certificateTypes.map((item) => (
                    <option key={item.id} value={item.id}>
                      {item.name}
                    </option>
                  ))}
                </Select>
              </FormControlBox>
            </SimpleGrid>
            <SimpleGrid columns={[1, 2]} spacing={4}>
              <FormControlBox
                title="Signatory name (optional)"
                form={form as any}
                formKey="signatory_name"
              >
                <Input
                  value={form.data.signatory_name}
                  onChange={(e) =>
                    form.setValue('signatory_name', e.target.value)
                  }
                />
              </FormControlBox>
              <FormControlBox
                title="Signatory title (optional)"
                form={form as any}
                formKey="signatory_title"
              >
                <Input
                  value={form.data.signatory_title}
                  onChange={(e) =>
                    form.setValue('signatory_title', e.target.value)
                  }
                />
              </FormControlBox>
              <FormControlBox
                title="Academic session (optional)"
                form={form as any}
                formKey="academic_session_id"
              >
                <AcademicSessionSelect
                  selectValue={form.data.academic_session_id}
                  isMulti={false}
                  isClearable
                  onChange={(option: any) =>
                    form.setValue('academic_session_id', option?.value ?? '')
                  }
                />
              </FormControlBox>
              <FormControlBox
                title="Issue date"
                form={form as any}
                formKey="issued_on"
              >
                <Input
                  type="date"
                  value={form.data.issued_on}
                  onChange={(e) => form.setValue('issued_on', e.target.value)}
                  isRequired
                />
              </FormControlBox>
              <FormControlBox
                title="Class (optional)"
                form={form as any}
                formKey="classification_id"
              >
                <ClassificationSelect
                  selectValue={form.data.classification_id}
                  isMulti={false}
                  isClearable
                  onChange={(option: any) =>
                    form.setValue('classification_id', option?.value ?? '')
                  }
                />
              </FormControlBox>
              {/* </SimpleGrid>
              <SimpleGrid columns={[1, 2]} spacing={4}> */}
            </SimpleGrid>
            <FormControlBox
              title="Additional comment (optional)"
              form={form as any}
              formKey="citation"
            >
              <Textarea
                value={form.data.citation}
                onChange={(e) => form.setValue('citation', e.target.value)}
                placeholder="Recognised for outstanding..."
              />
            </FormControlBox>
            {!certificateTypes.length && !selectedBatch && (
              <Text color="orange.600">
                Ask your school administrator to configure a global certificate
                design first.
              </Text>
            )}
            <HStack justify="end">
              <FormButton
                isLoading={form.processing}
                isDisabled={!selectedBatch && !certificateTypes.length}
              >
                Issue certificate(s)
              </FormButton>
            </HStack>
          </VStack>
          <Divider orientation={'horizontal'} height={10} />
          <Div>
            {selectedCertificateType && (
              <div className="certificate-issue-preview">
                <CertificateArtwork
                  templatePath={
                    selectedCertificateType.template?.template_path ?? ''
                  }
                  data={previewCertificate(
                    selectedCertificateType,
                    {
                      awardType: selectedCertificateType.name,
                      citation: form.data.citation,
                      issuedOn: form.data.issued_on,
                      signatoryName: form.data.signatory_name,
                      signatoryTitle: form.data.signatory_title,
                    },
                    institutionDetails
                  )}
                />
              </div>
            )}
          </Div>
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}

function previewCertificate(
  customization: CertificateType,
  award: {
    awardType: string;
    citation?: string | null;
    issuedOn: string;
    signatoryName?: string | null;
    signatoryTitle?: string | null;
  },
  institutionDetails: {
    name: string;
    address?: string | null;
    logo?: string | null;
    motto?: string | null;
  }
) {
  const values = customization.custom_values ?? {};
  const data: CertificateArtworkData = {
    institutionName: institutionDetails.name,
    institutionLogo: institutionDetails.logo,
    primaryColor: customization.primary_color,
    recipientName: 'Student or staff member',
    recipientType: 'Recipient',
    awardType: award.awardType,
    citation: award.citation,
    issuedOn: award.issuedOn,
    signatoryName: award.signatoryName,
    signatoryTitle: award.signatoryTitle,
    certificateNumber: 'CERT-2026-001',
    schoolMotto: institutionDetails.motto,
    schoolAddress: institutionDetails.address,
    principalName: values.principal_name,
    principalTitle: values.principal_title,
    customFooter: values.custom_footer,
  };
  return data;
}
