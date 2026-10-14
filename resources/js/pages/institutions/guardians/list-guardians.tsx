import { GuardianStudent, User } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import React from 'react';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import DashboardLayout from '@/layout/dashboard-layout';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import DateTimeDisplay from '@/components/date-time-display';
import DisplayUserFullname from '@/domain/institutions/users/display-user-fullname';
import AssignGuardianStudentModal from '@/components/modals/assign-guardian-student-modal';
import { Inertia } from '@inertiajs/inertia';
import { useModalValueToggle } from '@/hooks/use-modal-toggle';
import { HStack } from '@chakra-ui/react';
import { BrandButton } from '@/components/buttons';
import ListEmptyState from '@/components/list-empty-state';

interface Props {
  guardians: PaginationResponse<GuardianStudent>;
}

export default function ListGuardians({ guardians }: Props) {
  const assignGuardianStudentToggle = useModalValueToggle<User>();
  const headers: ServerPaginatedTableHeader<GuardianStudent>[] = [
    {
      label: 'Name',
      value: 'guardian.full_name',
      render: (row) => <DisplayUserFullname user={row.guardian} />,
    },
    {
      label: 'Student',
      value: 'student.user.full_name',
    },
    {
      label: 'Relationship',
      value: 'relationship',
    },
    {
      label: 'Registered on',
      render: (row) => <DateTimeDisplay dateTime={row.created_at} />,
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack>
          <BrandButton
            title="Attach Student"
            onClick={() => assignGuardianStudentToggle.open(row.guardian!)}
          />
        </HStack>
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title={'Guardians'} />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={guardians.data}
            keyExtractor={(row) => row.id}
            paginator={guardians}
            emptyState={
              <ListEmptyState
                title="No guardian links yet"
                description="This list shows guardians connected to students and their relationship to each learner. Add guardian details when creating or updating a student profile to build these links."
              />
            }
          />
        </SlabBody>
        {assignGuardianStudentToggle.state && (
          <AssignGuardianStudentModal
            {...assignGuardianStudentToggle.props}
            user={assignGuardianStudentToggle.state}
            onSuccess={() => Inertia.reload({ only: ['classResultInfo'] })}
          />
        )}
      </Slab>
    </DashboardLayout>
  );
}
