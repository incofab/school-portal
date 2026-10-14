import React from 'react';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import {
  Badge,
  Button,
  HStack,
  Icon,
  IconButton,
  Text,
  VStack,
} from '@chakra-ui/react';
import { InertiaLink } from '@inertiajs/inertia-react';
import { Inertia } from '@inertiajs/inertia';
import { EyeIcon, PencilIcon, TrashIcon } from '@heroicons/react/24/solid';
import route from '@/util/route';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { CertificateTemplate } from '@/types/models';
export default function ListCertificateTemplates({
  templates,
}: {
  templates: CertificateTemplate[];
}) {
  const form = useWebForm({});
  const { handleResponseToast } = useMyToast();
  async function remove(template: CertificateTemplate) {
    if (!window.confirm(`Delete ${template.name}?`)) return;
    const response = await form.submit((data, web) =>
      web.delete(route('managers.certificate-templates.destroy', [template]))
    );
    if (handleResponseToast(response)) Inertia.reload();
  }
  return (
    <ManagerDashboardLayout>
      <Slab>
        <SlabHeading
          title="Global certificate designs"
          rightElement={
            <Button
              as={InertiaLink}
              href={route('managers.certificate-templates.create')}
              colorScheme="brand"
              size="sm"
            >
              New design
            </Button>
          }
        />
        <SlabBody>
          <Text color="gray.600" mb={4}>
            Manage the shared designs that institutions can customize for their
            own certificates.
          </Text>
          <VStack align="stretch" spacing={3}>
            {templates.map((template) => (
              <HStack
                key={template.id}
                justify="space-between"
                borderWidth="1px"
                borderRadius="md"
                p={4}
                align="start"
              >
                <div>
                  <HStack>
                    <Text fontWeight="semibold">{template.name}</Text>
                    <Badge colorScheme={template.is_active ? 'green' : 'gray'}>
                      {template.is_active ? 'Active' : 'Inactive'}
                    </Badge>
                  </HStack>
                  <Text color="gray.600" fontSize="sm">
                    {template.description || template.slug}
                  </Text>
                  <Text color="gray.500" fontSize="xs">
                    {template.certificate_types_count ??
                      template.certificate_types?.length ??
                      0}{' '}
                    certificate type(s) ·{' '}
                    {template.template_path.split('/').pop()}
                  </Text>
                </div>
                <HStack flexShrink={0}>
                  <Button
                    as={InertiaLink}
                    href={route('managers.certificate-templates.preview', [
                      template,
                    ])}
                    leftIcon={<Icon as={EyeIcon} />}
                    size="sm"
                    variant="outline"
                  >
                    View
                  </Button>
                  <IconButton
                    aria-label="Edit design"
                    as={InertiaLink}
                    href={route('managers.certificate-templates.edit', [
                      template,
                    ])}
                    icon={<Icon as={PencilIcon} />}
                    size="sm"
                  />
                  <IconButton
                    aria-label="Delete design"
                    icon={<Icon as={TrashIcon} />}
                    colorScheme="red"
                    size="sm"
                    onClick={() => remove(template)}
                    isLoading={form.processing}
                    isDisabled={
                      (template.certificate_types_count ??
                        template.certificate_types?.length ??
                        0) > 0
                    }
                  />
                </HStack>
              </HStack>
            ))}
          </VStack>
        </SlabBody>
      </Slab>
    </ManagerDashboardLayout>
  );
}
