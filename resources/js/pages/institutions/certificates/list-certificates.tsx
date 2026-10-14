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
  activeTemplateCount: number;
  filters: { search?: string; status?: string };
}

export default function ListCertificates({
  certificates,
  activeTemplateCount,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const headers: ServerPaginatedTableHeader<Certificate>[] = [
    {
      label: 'Recipient',
      render: (row) => (
        <VStack align="start" spacing={0}>
          <Text fontWeight="semibold">{row.recipient?.user?.full_name}</Text>
          <Text fontSize="sm" color="gray.600">
            {row.recipient?.type}
          </Text>
        </VStack>
      ),
    },
    {
      label: 'Certificate',
      render: (row) => (
        <VStack align="start" spacing={0}>
          <Text>
            {row.certificate_type?.name ?? 'Certificate type unavailable'}
          </Text>
          <Text fontSize="xs" color="gray.600">
            {row.reference_code}
          </Text>
        </VStack>
      ),
    },
    {
      label: 'Design',
      render: (row) => row.certificate_type?.template?.name ?? '—',
    },
    { label: 'Issued', value: 'issued_on' },
    {
      label: 'Award',
      render: (row) =>
        row.certificate_batch_id ? (
          <VStack align="start" spacing={0}>
            <Badge colorScheme="purple">Batch</Badge>
            <Text fontSize="xs">{row.batch?.name}</Text>
          </VStack>
        ) : (
          <Badge>Individual</Badge>
        ),
    },
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
          href={instRoute('certificates.show', [row])}
          variant="link"
        />
      ),
    },
  ];
  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Certificates"
          rightElement={
            <HStack>
              <LinkButton
                title="Batches"
                href={instRoute('certificates.batches')}
                variant="outline"
              />
              <LinkButton
                title="Certificate Types"
                href={instRoute('certificate-types.index')}
                variant="outline"
              />
              <LinkButton
                title="Issue certificates"
                href={instRoute('certificates.create')}
              />
            </HStack>
          }
        />
        <SlabBody>
          <Text color="gray.600" mb={4}>
            {certificates.total} awarded · {activeTemplateCount} active design
            templates
          </Text>
          <ServerPaginatedTable
            data={certificates.data}
            headers={headers}
            keyExtractor={(row) => row.id}
            paginator={certificates}
            scroll
            emptyState={
              <ListEmptyState
                title="No certificates yet"
                description="Choose a global design, configure school branding, then issue an individual or batch award."
              />
            }
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
