import React from 'react';
import { HStack, Text, VStack } from '@chakra-ui/react';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { LinkButton } from '@/components/buttons';
import route from '@/util/route';
import CertificateArtwork from '@/pages/institutions/certificates/templates/CertificateArtwork';
import { CertificateArtworkData } from '@/pages/institutions/certificates/templates/types';
import { CertificateTemplate } from '@/types/models';

export default function PreviewCertificateTemplate({
  template,
  previewData,
}: {
  template: CertificateTemplate;
  previewData: CertificateArtworkData;
}) {
  return (
    <ManagerDashboardLayout>
      <VStack align="stretch" spacing={5}>
        <Slab>
          <SlabHeading
            title={template.name}
            rightElement={
              <HStack>
                <LinkButton
                  title="Back to designs"
                  href={route('managers.certificate-templates.index')}
                  variant="outline"
                />
              </HStack>
            }
          />
          <SlabBody>
            <Text color="gray.600">
              {template.description ||
                'Preview this certificate design with sample award details.'}
            </Text>
          </SlabBody>
        </Slab>
        <div className="certificate-template-showcase">
          <CertificateArtwork
            templatePath={template.template_path}
            data={previewData}
          />
        </div>
      </VStack>
    </ManagerDashboardLayout>
  );
}
