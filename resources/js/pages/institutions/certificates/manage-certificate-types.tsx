import React from 'react';
import DashboardLayout from '@/layout/dashboard-layout';
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
import { PencilIcon, TrashIcon } from '@heroicons/react/24/solid';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useMyToast from '@/hooks/use-my-toast';
import useWebForm from '@/hooks/use-web-form';
import { LinkButton } from '@/components/buttons';
import { preventNativeSubmit } from '@/util/util';
import { CertificateType } from '@/types/models';
export default function ManageCertificateTypes({
  certificateTypes,
}: {
  certificateTypes: CertificateType[];
}) {
  const { instRoute } = useInstitutionRoute();
  const form = useWebForm({});
  const { handleResponseToast } = useMyToast();
  async function remove(item: CertificateType) {
    if (!window.confirm(`Delete ${item.name}?`)) return;
    const result = await form.submit((data, web) =>
      web.delete(instRoute('certificate-types.destroy', [item]))
    );
    if (handleResponseToast(result)) Inertia.reload();
  }
  return (
    <DashboardLayout>
      <VStack align="stretch" spacing={5}>
        <Slab>
          <SlabHeading
            title="Certificate types"
            rightElement={
              <HStack>
                <LinkButton
                  title="Back to certificates"
                  href={instRoute('certificates.index')}
                  variant="outline"
                />
                <Button
                  as={InertiaLink}
                  href={instRoute('certificate-types.create')}
                  colorScheme="brand"
                  size="sm"
                >
                  Create certificate type
                </Button>
              </HStack>
            }
          />
          <SlabBody>
            <Text color="gray.600">
              Create branded certificate types from shared designs. You can
              create several types from the same design.
            </Text>
          </SlabBody>
        </Slab>
        <VStack align="stretch" spacing={3}>
          {certificateTypes.length === 0 && (
            <Slab>
              <SlabBody>
                <Text color="gray.600">
                  No certificate types yet. Create one from a shared design to
                  start issuing certificates.
                </Text>
              </SlabBody>
            </Slab>
          )}
          {certificateTypes.map((item) => (
            <Slab key={item.id}>
              <SlabBody>
                <HStack justify="space-between" align="start">
                  <div>
                    <HStack>
                      <Text fontWeight="semibold">{item.name}</Text>
                      <Badge
                        colorScheme={
                          item.is_active && item.template?.is_active
                            ? 'green'
                            : 'gray'
                        }
                      >
                        {item.is_active && item.template?.is_active
                          ? 'Active'
                          : 'Inactive'}
                      </Badge>
                    </HStack>
                    <Text fontSize="sm" color="gray.600">
                      {item.name} · {item.institution_name} ·{' '}
                      {item.certificates_count} issued
                    </Text>
                    {item.template?.description && (
                      <Text fontSize="sm" color="gray.500">
                        {item.template.description}
                      </Text>
                    )}
                  </div>
                  <HStack>
                    <IconButton
                      aria-label="Edit certificate type"
                      as={InertiaLink}
                      href={instRoute('certificate-types.edit', [item])}
                      icon={<Icon as={PencilIcon} />}
                      size="sm"
                    />
                    <IconButton
                      aria-label="Delete certificate type"
                      icon={<Icon as={TrashIcon} />}
                      colorScheme="red"
                      size="sm"
                      onClick={() => remove(item)}
                      isLoading={form.processing}
                      isDisabled={
                        (item.certificates_count ?? 0) > 0 ||
                        (item.batches_count ?? 0) > 0
                      }
                    />
                  </HStack>
                </HStack>
              </SlabBody>
            </Slab>
          ))}
        </VStack>
      </VStack>
    </DashboardLayout>
  );
}
