import React from 'react';
import { Student } from '@/types/models';
import { HStack, IconButton, Icon, Button } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { InstitutionUserType, PaginationResponse } from '@/types/types';
import { PencilIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { BrandButton, LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import DateTimeDisplay from '@/components/date-time-display';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { InertiaLink } from '@inertiajs/inertia-react';
import useModalToggle from '@/hooks/use-modal-toggle';
import StudentsTableFilters from '@/components/table-filters/students-table-filters';
import {
  CloudArrowDownIcon,
  CloudArrowUpIcon,
  TrashIcon,
} from '@heroicons/react/24/solid';
import useIsStaff from '@/hooks/use-is-staff';
import useQueryString from '@/hooks/use-query-string';
import useMyToast from '@/hooks/use-my-toast';
import useSharedProps from '@/hooks/use-shared-props';
import UploadStudentModal from '@/components/modals/upload-student-modal';
import { Inertia } from '@inertiajs/inertia';
import useIsAdmin from '@/hooks/use-is-admin';
import DestructivePopover from '@/components/destructive-popover';
import useWebForm from '@/hooks/use-web-form';
import DisplayUserFullname from '@/domain/institutions/users/display-user-fullname';

interface Props {
  students: PaginationResponse<Student>;
}

function ListStudents({ students }: Props) {
  const { currentUser, currentInstitutionUser } = useSharedProps();
  const { instRoute } = useInstitutionRoute();
  const isStaff = useIsStaff();
  const isAdmin = useIsAdmin();
  const { params } = useQueryString();
  const { toastError, handleResponseToast } = useMyToast();
  const studentFiltersModalToggle = useModalToggle();
  const studentUploadModalToggle = useModalToggle();
  const deleteForm = useWebForm({});

  async function deleteItem(obj: Student) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('students.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['students'] });
  }

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
      render: (row) => <DisplayUserFullname user={row.user} />,
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
      label: 'Gender',
      value: 'user.gender',
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
          {/* {(row.user_id === currentUser.id ||
            currentInstitutionUser.role === InstitutionUserType.Admin ||
            currentInstitutionUser.role === InstitutionUserType.Teacher) && (
            <LinkButton
              href={instRoute('users.profile', [row.user_id])}
              colorScheme={'brand'}
              variant={'link'}
              title="Profile"
            />
          )} */}
          {isAdmin && (
            <DestructivePopover
              label={`Delete ${row.user?.full_name} from the student record. This is irreversible, be careful!!!`}
              onConfirm={() => deleteItem(row)}
              isLoading={deleteForm.processing}
            >
              <IconButton
                aria-label={'Delete'}
                icon={<Icon as={TrashIcon} />}
                variant={'ghost'}
                colorScheme={'red'}
              />
            </DestructivePopover>
          )}
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
                  <BrandButton
                    leftIcon={<Icon as={CloudArrowUpIcon} />}
                    onClick={studentUploadModalToggle.open}
                    title="Upload Students"
                  />
                  <Button
                    as={'a'}
                    href={
                      params.classification
                        ? instRoute('classifications.students-download', [
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
                    Download
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
            validFilters={['classification']}
            onFilterButtonClick={studentFiltersModalToggle.open}
          />
        </SlabBody>
        <StudentsTableFilters {...studentFiltersModalToggle.props} />
        <UploadStudentModal
          {...studentUploadModalToggle.props}
          onSuccess={() => Inertia.reload({ only: ['students'] })}
        />
      </Slab>
    </DashboardLayout>
  );
}

export default ListStudents;
