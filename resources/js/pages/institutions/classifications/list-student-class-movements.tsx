import React from 'react';
import { Classification, Student, StudentClassMovement } from '@/types/models';
import {
  HStack,
  Button,
  Menu,
  MenuButton,
  MenuList,
  MenuItem,
  Text,
  Icon,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import useIsAdmin from '@/hooks/use-is-admin';
import useModalToggle, { useModalValueToggle } from '@/hooks/use-modal-toggle';
import StudentClassMovementTableFilters from '@/components/table-filters/student-class-movement-table-filters';
import ChangeStudentClassModal from '@/components/modals/change-student-class-modal';
import RevertBatchStudentClassMovementModal from '@/components/modals/revert-batch-student-class-movement-modal';
import DestructivePopover from '@/components/destructive-popover';
import { ArrowPathIcon, ArrowUturnRightIcon } from '@heroicons/react/24/solid';
import startCase from 'lodash/startCase';

interface Props {
  studentClassMovements: PaginationResponse<StudentClassMovement>;
}

export default function ListStudentClassMovements({
  studentClassMovements,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const revertForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const isAdmin = useIsAdmin();
  const changeStudentClassModalToggle =
    useModalValueToggle<[Student, Classification | undefined]>();
  const studentClassFilterToggle = useModalToggle();
  const batchRevertModalToggle = useModalValueToggle<[string, boolean]>();

  async function revertMovement(studentClassMovement: StudentClassMovement) {
    const isConfirmed = window.confirm(
      'Do you want to revert this class change'
    );
    if (!isConfirmed) {
      return;
    }
    const res = await revertForm.submit((data, web) =>
      web.post(
        instRoute('student-class-movements.revert', [studentClassMovement.id])
      )
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['studentClassMovements'] });
  }

  const headers: ServerPaginatedTableHeader<StudentClassMovement>[] = [
    {
      label: 'Student',
      value: 'student.user.full_name',
    },
    {
      label: 'From',
      render: (row) => row.source_class?.title ?? 'Alumni',
    },
    {
      label: 'To',
      render: (row) => row.destination_class?.title ?? 'Alumni',
    },
    {
      label: 'Term/Session',
      render: (row) => (
        <Text>
          {row.term ? startCase(row.term) + ' Term' : ''} <br />{' '}
          {row.academic_session?.title}
        </Text>
      ),
    },
    {
      label: 'Reason',
      value: 'reason',
    },
    {
      label: 'Staff',
      value: 'user.full_name',
    },
    {
      label: 'Batch',
      value: 'batch_no',
    },
    ...(isAdmin
      ? [
          {
            label: 'Action',
            render: (row: StudentClassMovement) => (
              <HStack spacing={3}>
                <Menu>
                  <MenuButton
                    as={Button}
                    colorScheme={'red'}
                    fontWeight={'normal'}
                    size={'sm'}
                    leftIcon={<Icon as={ArrowPathIcon} />}
                  >
                    Revert
                  </MenuButton>
                  <MenuList>
                    <MenuItem
                      as={Button}
                      py={2}
                      onClick={() => revertMovement(row)}
                      fontWeight={'semibold'}
                    >
                      Revert One
                    </MenuItem>
                    <MenuItem
                      as={Button}
                      py={2}
                      onClick={() =>
                        batchRevertModalToggle.open([row.batch_no, false])
                      }
                      fontWeight={'semibold'}
                    >
                      Revert entire Batch
                    </MenuItem>
                  </MenuList>
                </Menu>
                <Menu>
                  <MenuButton
                    as={Button}
                    variant={'solid'}
                    colorScheme={'brand'}
                    fontWeight={'normal'}
                    size={'sm'}
                    rightIcon={<Icon as={ArrowUturnRightIcon} />}
                  >
                    Change Class
                  </MenuButton>
                  <MenuList>
                    <MenuItem
                      as={Button}
                      py={2}
                      onClick={() =>
                        changeStudentClassModalToggle.open([
                          row.student!,
                          row.destination_class,
                        ])
                      }
                    >
                      Change One
                    </MenuItem>
                    <MenuItem
                      as={Button}
                      py={2}
                      onClick={() =>
                        batchRevertModalToggle.open([row.batch_no, true])
                      }
                    >
                      Change entire Batch
                    </MenuItem>
                  </MenuList>
                </Menu>
              </HStack>
            ),
          },
        ]
      : []),
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title="Student Class Changes" />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={studentClassMovements.data}
            keyExtractor={(row) => row.id}
            paginator={studentClassMovements}
            validFilters={[
              'term',
              'academicSession',
              'user',
              'student',
              'batchNo',
              'sourceClass',
              'destinationClass',
            ]}
            onFilterButtonClick={studentClassFilterToggle.open}
          />
        </SlabBody>
      </Slab>
      <StudentClassMovementTableFilters {...studentClassFilterToggle.props} />

      {changeStudentClassModalToggle.state && (
        <ChangeStudentClassModal
          student={changeStudentClassModalToggle.state[0]}
          studentClass={changeStudentClassModalToggle.state[1]}
          {...changeStudentClassModalToggle.props}
          onSuccess={() => Inertia.reload()}
        />
      )}

      {batchRevertModalToggle.state && (
        <RevertBatchStudentClassMovementModal
          {...batchRevertModalToggle.props}
          batchNo={batchRevertModalToggle.state[0]}
          changeClass={batchRevertModalToggle.state[1]}
          onSuccess={() => Inertia.reload()}
        />
      )}
    </DashboardLayout>
  );
}
