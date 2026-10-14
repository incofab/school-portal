import React from 'react';
import { Badge, HStack, Text, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import { Designation, AcademicSession } from '@/types/models';
import { LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { Button } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import ListEmptyState from '@/components/list-empty-state';

interface Props {
  designations: PaginationResponse<Designation>;
  academicSession?: AcademicSession | null;
  canManageStaffDesignations: boolean;
}

export default function ListDesignations({
  designations,
  academicSession,
  canManageStaffDesignations,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const form = useWebForm({});
  const { handleResponseToast } = useMyToast();
  async function deactivate(item: Designation) {
    if (
      !window.confirm(
        `Deactivate ${item.title}? Existing assignment history will be kept.`
      )
    )
      return;
    const result = await form.submit((_, web) =>
      web.post(instRoute('designations.deactivate', [item]))
    );
    if (handleResponseToast(result)) Inertia.reload({ only: ['designations'] });
  }
  const headers: ServerPaginatedTableHeader<Designation>[] = [
    {
      label: 'Designation',
      render: (item) => (
        <VStack align="start" spacing={0}>
          <Text fontWeight="semibold">{item.title}</Text>
          <Text fontSize="sm" color="gray.600">
            {item.is_for_students ? 'Student' : 'Staff'}
          </Text>
        </VStack>
      ),
    },
    {
      label: 'Scope',
      render: (item) =>
        item.scope === 'classification'
          ? 'Per eligible class'
          : 'Institution-wide',
    },
    {
      label: 'Eligible classes',
      render: (item) =>
        item.is_for_students
          ? item.eligible_classes?.map((c) => c.title).join(', ') ||
            'All eligible classes'
          : '—',
    },
    { label: 'Max holders', value: 'max_holders' },
    {
      label: 'Current holders',
      render: (item) =>
        `${item.current_assignment_count ?? 0} / ${item.max_holders}`,
    },
    {
      label: 'Status',
      render: (item) => (
        <Badge colorScheme={item.is_active ? 'green' : 'gray'}>
          {item.is_active ? 'Active' : 'Inactive'}
        </Badge>
      ),
    },
    {
      label: 'Actions',
      render: (item) => (
        <HStack>
          <LinkButton
            title="View"
            href={instRoute('designations.show', [item])}
            variant="link"
          />
          {(item.is_for_students || canManageStaffDesignations) && (
            <LinkButton
              title="Edit"
              href={instRoute('designations.edit', [item])}
              variant="link"
            />
          )}
          {item.is_active &&
            (item.is_for_students || canManageStaffDesignations) && (
              <Button
                size="sm"
                variant="link"
                colorScheme="red"
                onClick={() => deactivate(item)}
              >
                Deactivate
              </Button>
            )}
        </HStack>
      ),
    },
  ];
  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Designations"
          rightElement={
            <HStack>
              <LinkButton
                title="Assignments"
                href={instRoute('designation-assignments.index')}
              />
              <LinkButton
                title="Create designation"
                href={instRoute('designations.create')}
              />
            </HStack>
          }
        />
        <SlabBody>
          <Text color="gray.600" mb={4}>
            Manage student positions and staff appointments
            {academicSession ? ` for ${academicSession.title}` : ''}.
          </Text>
          <ServerPaginatedTable
            scroll
            hideSearchField
            headers={headers}
            data={designations.data}
            keyExtractor={(row) => row.id}
            paginator={designations}
            emptyState={
              <ListEmptyState
                title="No designations yet"
                description="Designations give students or staff a recognised position or appointment, such as Head Student, Class Representative, or Sports Prefect. Create one to define who can hold it and where it applies."
                action={
                  <LinkButton
                    title="Create designation"
                    href={instRoute('designations.create')}
                  />
                }
              />
            }
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
