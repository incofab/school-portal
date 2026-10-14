import React from 'react';
import { Button, HStack, Text, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody } from '@/components/slab';
import useSharedProps from '@/hooks/use-shared-props';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import CertificateArtwork from './templates/CertificateArtwork';
import { CertificateArtworkData } from './templates/types';
import { Certificate } from '@/types/models';
interface Props {
  certificate: Certificate;
}

export default function ShowCertificate({ certificate }: Props) {
  const { currentInstitutionUser } = useSharedProps();
  const { instRoute } = useInstitutionRoute();
  const isAdmin = currentInstitutionUser?.type === 'admin';
  const form = useWebForm({ revocation_reason: '' });
  const { handleResponseToast } = useMyToast();
  async function revoke() {
    const reason = window.prompt('Why is this certificate being revoked?');
    if (!reason?.trim()) return;
    form.setValue('revocation_reason', reason);
    const result = await form.submit((data, web) =>
      web.post(instRoute('certificates.revoke', [certificate]), data)
    );
    if (handleResponseToast(result)) Inertia.reload();
  }

  const customization = certificate.certificate_type!;
  const values =
    certificate.render_data?.custom_values ?? customization.custom_values ?? {};
  const artwork: CertificateArtworkData = {
    institutionName:
      certificate.render_data?.institution_name ??
      customization.institution_name,
    institutionLogo: Object.prototype.hasOwnProperty.call(
      certificate.render_data ?? {},
      'institution_logo'
    )
      ? certificate.render_data?.institution_logo
      : customization.logo_url,
    primaryColor:
      certificate.render_data?.primary_color ?? customization.primary_color,
    recipientName: certificate.render_data?.recipient_name ?? '',
    recipientType: certificate.render_data?.recipient_type,
    awardType:
      certificate.render_data?.certificate_type_name ?? customization.name,
    citation: certificate.citation,
    issuedOn: new Date(certificate.issued_on).toLocaleDateString(undefined, {
      timeZone: 'UTC',
      day: 'numeric',
      month: 'long',
      year: 'numeric',
    }),
    academicSession: certificate.render_data?.academic_session,
    classification: certificate.render_data?.classification,
    signatoryName: certificate.signatory_name,
    signatoryTitle: certificate.signatory_title,
    certificateNumber: certificate.reference_code,
    schoolMotto: values.school_motto,
    schoolAddress: values.school_address,
    principalName: values.principal_name,
    principalTitle: values.principal_title,
    customFooter: values.custom_footer,
    revoked: certificate.status === 'revoked',
  };

  return (
    <DashboardLayout>
      <VStack align="stretch" spacing={4} className="certificate-page">
        <HStack
          justify="space-between"
          className="hidden-on-print"
          flexWrap="wrap"
        >
          <Text color="gray.600">
            {certificate.render_data?.certificate_type_name ??
              customization.name}
            {certificate.batch ? ` · Batch: ${certificate.batch.name}` : ''}
          </Text>
          <HStack>
            <Button variant="outline" onClick={() => window.print()}>
              Print / Save as PDF
            </Button>
            {isAdmin && certificate.status === 'issued' && (
              <Button
                colorScheme="red"
                variant="outline"
                isLoading={form.processing}
                onClick={revoke}
              >
                Revoke
              </Button>
            )}
          </HStack>
        </HStack>
        {certificate.status === 'revoked' && (
          <Text
            color="red.600"
            fontWeight="semibold"
            className="hidden-on-print"
          >
            This certificate has been revoked.
          </Text>
        )}
        <Slab className="certificate-print-surface">
          <SlabBody p={0}>
            <CertificateArtwork
              templatePath={
                certificate.render_data?.template_path ??
                customization.template?.template_path ??
                ''
              }
              data={artwork}
            />
          </SlabBody>
        </Slab>
      </VStack>
    </DashboardLayout>
  );
}
