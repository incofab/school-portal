import React from 'react';
import { Badge, HStack, Text, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import { LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import ListEmptyState from '@/components/list-empty-state';
import { Certificate } from '@/types/models';
interface Props {
  certificates: PaginationResponse<Certificate>;
}
export default function MyCertificates({ certificates }: Props) {
  const { instRoute } = useInstitutionRoute();
  const headers: ServerPaginatedTableHeader<Certificate>[] = [
    {
      label: 'Certificate',
      render: (row) => (
        <VStack align="start" spacing={0}>
          <Text fontWeight="semibold">
            {row.certificate_type?.name ?? 'Certificate type unavailable'}
          </Text>
          <Text fontSize="xs" color="gray.600">
            {row.reference_code}
          </Text>
        </VStack>
      ),
    },
    {
      label: 'Template',
      render: (row) => row.certificate_type?.template?.name ?? '—',
    },
    { label: 'Issued', value: 'issued_on' },
    {
      label: 'Status',
      render: (row) => (
        <Badge colorScheme={row.status === 'issued' ? 'green' : 'red'}>
          {row.status}
        </Badge>
      ),
    },
    {
      label: 'Action',
      render: (row) => (
        <LinkButton
          title="View"
          href={instRoute('my-certificates.show', [row])}
          variant="link"
        />
      ),
    },
  ];
  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title="My certificates" />
        <SlabBody>
          <Text color="gray.600" mb={4}>
            Certificates awarded to you by this institution.
          </Text>
          <ServerPaginatedTable
            data={certificates.data}
            headers={headers}
            keyExtractor={(row) => row.id}
            paginator={certificates}
            emptyState={
              <ListEmptyState
                title="No certificates yet"
                description="Any certificates awarded to you will appear here."
              />
            }
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
