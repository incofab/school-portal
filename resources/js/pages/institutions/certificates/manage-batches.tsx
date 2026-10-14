import React from 'react';
import {
  Badge,
  Button,
  HStack,
  Input,
  Select,
  SimpleGrid,
  Table,
  Tbody,
  Td,
  Text,
  Textarea,
  Th,
  Thead,
  Tr,
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
import CertificateArtwork from './templates/CertificateArtwork';
import { CertificateBatch, CertificateType } from '@/types/models';
import AcademicSessionSelect from '@/components/selectors/academic-session-select';
import ClassificationSelect from '@/components/selectors/classification-select';
interface Props {
  batches: { data: CertificateBatch[]; total: number };
  certificateTypes: CertificateType[];
  institutionDetails: {
    name: string;
    address?: string | null;
    logo?: string | null;
    motto?: string | null;
  };
}

export default function ManageBatches({
  batches,
  certificateTypes,
  institutionDetails,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const form = useWebForm({
    name: '',
    certificate_type_id: '',
    citation: '',
    issued_on: new Date().toLocaleDateString('en-CA'),
    academic_session_id: '',
    classification_id: '',
    signatory_name: '',
    signatory_title: '',
  });
  const selectedCertificateType = certificateTypes.find(
    (certificateType) =>
      String(certificateType.id) === form.data.certificate_type_id
  );
  async function createBatch() {
    const result = await form.submit((data, web) =>
      web.post(instRoute('certificates.batches.store'), data)
    );
    if (handleResponseToast(result)) Inertia.reload({ only: ['batches'] });
  }
  async function closeBatch(batch: CertificateBatch) {
    if (
      !window.confirm(
        `Close “${batch.name}”? It will no longer be available for new certificate issues.`
      )
    )
      return;
    const result = await form.submit((_, web) =>
      web.post(instRoute('certificates.batches.close', [batch]))
    );
    if (handleResponseToast(result)) Inertia.reload({ only: ['batches'] });
  }
  return (
    <DashboardLayout>
      <VStack align="stretch" spacing={5}>
        <Slab>
          <SlabHeading
            title="Certificate batches"
            rightElement={
              <LinkButton
                title="Issue certificates"
                href={instRoute('certificates.create')}
              />
            }
          />
          <SlabBody>
            <Text color="gray.600" mb={4}>
              Create a named award batch once, then select it each time you
              issue certificates to recipients.
            </Text>
            <VStack
              as="form"
              align="stretch"
              spacing={4}
              onSubmit={preventNativeSubmit(createBatch)}
            >
              <SimpleGrid columns={[1, 2]} spacing={4}>
                <FormControlBox
                  title="Batch name"
                  form={form as any}
                  formKey="name"
                >
                  <Input
                    value={form.data.name}
                    onChange={(e) => form.setValue('name', e.target.value)}
                    isRequired
                    placeholder="e.g. 2025/26 graduation awards"
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
                    placeholder="Choose a design"
                  >
                    {certificateTypes.map((item) => (
                      <option key={item.id} value={item.id}>
                        {item.name}
                      </option>
                    ))}
                  </Select>
                </FormControlBox>
              </SimpleGrid>
              {selectedCertificateType && (
                <div className="certificate-issue-preview">
                  <CertificateArtwork
                    templatePath={
                      selectedCertificateType.template?.template_path ?? ''
                    }
                    data={{
                      institutionName: institutionDetails.name,
                      institutionLogo: institutionDetails.logo,
                      primaryColor: selectedCertificateType.primary_color,
                      recipientName: 'Student or staff member',
                      recipientType: 'Recipient',
                      awardType: selectedCertificateType.name,
                      citation: form.data.citation,
                      issuedOn: form.data.issued_on,
                      signatoryName: form.data.signatory_name,
                      signatoryTitle: form.data.signatory_title,
                      certificateNumber: 'CERT-2026-001',
                      schoolMotto: institutionDetails.motto,
                      schoolAddress: institutionDetails.address,
                      principalName:
                        selectedCertificateType.custom_values?.principal_name,
                      principalTitle:
                        selectedCertificateType.custom_values?.principal_title,
                      customFooter:
                        selectedCertificateType.custom_values?.custom_footer,
                    }}
                  />
                </div>
              )}
              <FormControlBox
                title="Additional comment (optional)"
                form={form as any}
                formKey="citation"
              >
                <Textarea
                  value={form.data.citation}
                  onChange={(e) => form.setValue('citation', e.target.value)}
                />
              </FormControlBox>
              <SimpleGrid columns={[1, 2, 3]} spacing={4}>
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
                  title="Academic session"
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
                  title="Class"
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
              </SimpleGrid>
              <SimpleGrid columns={[1, 2]} spacing={4}>
                <FormControlBox
                  title="Signatory name"
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
                  title="Signatory title"
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
              </SimpleGrid>
              <HStack justify="end">
                <FormButton
                  isLoading={form.processing}
                  isDisabled={!certificateTypes.length}
                >
                  Create batch
                </FormButton>
              </HStack>
            </VStack>
          </SlabBody>
        </Slab>
        <Slab>
          <SlabHeading title={`Existing batches (${batches.total})`} />
          <SlabBody>
            <Table size="sm">
              <Thead>
                <Tr>
                  <Th>Batch</Th>
                  <Th>Certificate</Th>
                  <Th>Issued</Th>
                  <Th>Records</Th>
                  <Th>Status</Th>
                  <Th>Action</Th>
                </Tr>
              </Thead>
              <Tbody>
                {batches.data.map((batch) => (
                  <Tr key={batch.id}>
                    <Td>
                      <Text fontWeight="semibold">{batch.name}</Text>
                      <Text fontSize="sm" color="gray.600">
                        {batch.certificate_type?.name ??
                          'Certificate type unavailable'}
                      </Text>
                    </Td>
                    <Td>{batch.certificate_type?.name ?? '—'}</Td>
                    <Td>{batch.issued_on}</Td>
                    <Td>{batch.certificates_count}</Td>
                    <Td>
                      <Badge
                        colorScheme={batch.status === 'open' ? 'green' : 'gray'}
                      >
                        {batch.status}
                      </Badge>
                    </Td>
                    <Td>
                      {batch.status === 'open' && (
                        <Button
                          size="sm"
                          variant="link"
                          colorScheme="red"
                          onClick={() => closeBatch(batch)}
                        >
                          Close
                        </Button>
                      )}
                    </Td>
                  </Tr>
                ))}
                {!batches.data.length && (
                  <Tr>
                    <Td colSpan={6}>
                      <Text color="gray.600">No batches yet.</Text>
                    </Td>
                  </Tr>
                )}
              </Tbody>
            </Table>
          </SlabBody>
        </Slab>
      </VStack>
    </DashboardLayout>
  );
}
