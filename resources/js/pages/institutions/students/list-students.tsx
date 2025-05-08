import React from 'react';
import { Student } from '@/types/models';
import { HStack, IconButton, Icon, Button, Text } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import { PencilIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { BrandButton, LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import DateTimeDisplay from '@/components/date-time-display';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { InertiaLink } from '@inertiajs/inertia-react';
import useModalToggle, { useModalValueToggle } from '@/hooks/use-modal-toggle';
import StudentsTableFilters from '@/components/table-filters/students-table-filters';
import {
  CloudArrowDownIcon,
  CloudArrowUpIcon,
  TrashIcon,
} from '@heroicons/react/24/solid';
import useIsStaff from '@/hooks/use-is-staff';
import useQueryString from '@/hooks/use-query-string';
import useMyToast from '@/hooks/use-my-toast';
import UploadStudentModal from '@/components/modals/upload-student-modal';
import { Inertia } from '@inertiajs/inertia';
import useIsAdmin from '@/hooks/use-is-admin';
import DestructivePopover from '@/components/destructive-popover';
import useWebForm from '@/hooks/use-web-form';
import DisplayUserFullname from '@/domain/institutions/users/display-user-fullname';
import EditStudentCodeModal from '@/components/modals/edit-student-code-modal';
import { Div } from '@/components/semantic';
import SuspensionToggleButton from '@/domain/institutions/user-profile/suspension-toggle-button';

interface Props {
  students: PaginationResponse<Student>;
  studentCount: number;
  alumniCount: number;
}

function ListStudents({ students, studentCount, alumniCount }: Props) {
  const { instRoute } = useInstitutionRoute();
  const isStaff = useIsStaff();
  const isAdmin = useIsAdmin();
  const { params } = useQueryString();
  const { toastError, handleResponseToast } = useMyToast();
  const studentFiltersModalToggle = useModalToggle();
  const studentUploadModalToggle = useModalToggle();
  const editStudentModalToggle = useModalValueToggle<Student | null>();
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
      sortKey: 'firstName',
    },
    {
      label: 'Class',
      value: 'classification.title',
      sortKey: 'classification',
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
      render: (row) => (
        <HStack>
          <Text as={'span'}>{row.code}</Text>
          <IconButton
            aria-label={'Edit user'}
            icon={<Icon as={PencilIcon} />}
            variant={'ghost'}
            colorScheme={'brand'}
            onClick={() => editStudentModalToggle.open(row)}
          />
        </HStack>
      ),
    },
    {
      label: 'Registered on',
      value: 'created_at',
      render: (row) => <DateTimeDisplay dateTime={row.created_at} />,
      sortKey: 'createdAt',
    },
    {
      label: 'Suspended',
      render: (row) =>
        row.institution_user ? (
          <SuspensionToggleButton institutionUser={row.institution_user} />
        ) : (
          <></>
        ),
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
          <Div>
            Students: {studentCount} | Alumni: {alumniCount}
          </Div>
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
        {editStudentModalToggle.state && (
          <EditStudentCodeModal
            student={editStudentModalToggle.state}
            {...editStudentModalToggle.props}
            onSuccess={() => Inertia.reload()}
          />
        )}
      </Slab>
    </DashboardLayout>
  );
}

export default ListStudents;
