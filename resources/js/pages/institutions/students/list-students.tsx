import React from 'react';
import { Student } from '@/types/models';
import { HStack, IconButton, Icon, Button } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { InstitutionUserType, PaginationResponse } from '@/types/types';
import { PencilIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import DateTimeDisplay from '@/components/date-time-display';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { InertiaLink } from '@inertiajs/inertia-react';
import useModalToggle from '@/hooks/use-modal-toggle';
import StudentsTableFilters from '@/components/table-filters/students-table-filters';
import { CloudArrowDownIcon } from '@heroicons/react/24/solid';
import useIsStaff from '@/hooks/use-is-staff';
import useQueryString from '@/hooks/use-query-string';
import useMyToast from '@/hooks/use-my-toast';
import useSharedProps from '@/hooks/use-shared-props';

interface Props {
  students: PaginationResponse<Student>;
}

function ListStudents({ students }: Props) {
  const { currentUser, currentInstitutionUser } = useSharedProps();
  const { instRoute } = useInstitutionRoute();
  const isStaff = useIsStaff();
  const { params } = useQueryString();
  const { toastError } = useMyToast();
  const studentFiltersModalToggle = useModalToggle();

  function canDownloadSheet() {
    return params.classification;
  }

  function downloadSheet(e: any) {
    if (!canDownloadSheet()) {
      e.preventDefault();
      toastError('You have to select a class before downloading');
      return false;
    }
    return true;
  }

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
          {(row.user_id === currentUser.id ||
            currentInstitutionUser.role === InstitutionUserType.Admin ||
            currentInstitutionUser.role === InstitutionUserType.Teacher) && (
            <LinkButton
              href={instRoute('users.profile', [row.user_id])}
              colorScheme={'brand'}
              variant={'link'}
              title="Profile"
            />
          )}
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
            <HStack>
              {isStaff && (
                <>
                  <LinkButton
                    href={instRoute('students.create')}
                    title={'New'}
                  />
                  <Button
                    as={'a'}
                    href={
                      params.classification
                        ? instRoute('students.download', [
                            params.classification,
                          ])
                        : '#'
                    }
                    colorScheme={'brand'}
                    variant={'solid'}
                    size={'sm'}
                    leftIcon={<Icon as={CloudArrowDownIcon} />}
                    onClick={downloadSheet}
                  >
                    Download Students
                  </Button>
                </>
              )}
            </HStack>
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
