import React from 'react';
import { Student } from '@/types/models';
import { HStack, IconButton, Icon } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import { PencilIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import DateTimeDisplay from '@/components/date-time-display';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { InertiaLink } from '@inertiajs/inertia-react';
import useModalToggle from '@/hooks/use-modal-toggle';
import StudentsTableFilters from '@/components/table-filters/students-table-filters';

interface Props {
  students: PaginationResponse<Student>;
}

function ListStudents({ students }: Props) {
  const { instRoute } = useInstitutionRoute();
  const studentFiltersModalToggle = useModalToggle();
  const headers: ServerPaginatedTableHeader<Student>[] = [
    {
      label: 'Name',
      value: 'user.full_name',
    },
    {
      label: 'Email',
      value: 'user.email',
    },
    {
      label: 'Class',
      value: 'classification.title',
    },
    {
      label: 'Guardian Phone',
      value: 'guardian_phone',
    },
    {
      label: 'Student Id',
      value: 'code',
    },
    {
      label: 'Registered on',
      value: 'created_at',
      render: (row) => <DateTimeDisplay dateTime={row.created_at} />,
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack>
          <IconButton
            as={InertiaLink}
            aria-label={'Edit user'}
            icon={<Icon as={PencilIcon} />}
            href={instRoute('students.edit', [row.id])}
            variant={'ghost'}
            colorScheme={'brand'}
          />
          {/* <LinkButton
            href={route('users.impersonate', [row.user_id])}
            colorScheme={'red'}
            variant={'link'}
            title="Impersonate"
          /> */}
        </HStack>
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="List Students"
          rightElement={
            <LinkButton href={instRoute('students.create')} title={'New'} />
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={students.data}
            keyExtractor={(row) => row.id}
            paginator={students}
            validFilters={['clssification']}
            onFilterButtonClick={studentFiltersModalToggle.open}
          />
        </SlabBody>
        <StudentsTableFilters {...studentFiltersModalToggle.props} />
      </Slab>
    </DashboardLayout>
  );
}

export default ListStudents;
