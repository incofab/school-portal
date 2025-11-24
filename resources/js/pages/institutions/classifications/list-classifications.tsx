import React from 'react';
import { Classification, Timetable } from '@/types/models';
import {
  HStack,
  IconButton,
  Icon,
  Text,
  Menu,
  MenuButton,
  MenuList,
  MenuItem,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import { PencilIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { BrandButton, LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { InertiaLink } from '@inertiajs/inertia-react';
import { EllipsisVerticalIcon, TrashIcon } from '@heroicons/react/24/solid';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import DestructivePopover from '@/components/destructive-popover';
import useIsAdmin from '@/hooks/use-is-admin';
import useModalToggle, { useModalValueToggle } from '@/hooks/use-modal-toggle';
import MigrateClassStudentsModal from '@/components/modals/migrate-class-students-modal';
import UploadClassificationModal from '@/components/modals/upload-classification-modal';
import DisplayUserFullname from '@/domain/institutions/users/display-user-fullname';
import useSharedProps from '@/hooks/use-shared-props';

interface Props {
  classifications: PaginationResponse<Classification>;
}

export default function ListClassification({ classifications }: Props) {
  const { instRoute } = useInstitutionRoute();
  const { currentTerm, currentAcademicSession } = useSharedProps();
  const deleteForm = useWebForm({});
  const form = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const isAdmin = useIsAdmin();
  const migrateClassStudentsModalToggle = useModalValueToggle<Classification>();
  const uploadClassModalToggle = useModalToggle();

  async function deleteItem(obj: Classification) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('classifications.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['classifications'] });
  }

  async function generateResultPin(classification: Classification) {
    if (
      !window.confirm(
        `Generate result checker pins for the ${currentAcademicSession.title} Session and ${currentTerm} Term`
      )
    ) {
      return;
    }
    const res = await form.submit((data, web) =>
      web.post(instRoute('pins.classifications.store', [classification]), data)
    );

    if (!handleResponseToast(res)) return;

    Inertia.visit(
      instRoute('pins.classification.student-pin-tiles', [classification])
    );
  }

  const headers: ServerPaginatedTableHeader<Classification>[] = [
    {
      label: 'Group',
      value: 'classification_group.title',
    },
    {
      label: 'Title',
      value: 'title',
      render: (row) => <Text whiteSpace={'nowrap'}>{row.title}</Text>,
    },
    {
      label: 'Num of Students',
      value: 'students_count',
    },
    {
      label: 'Same Num of Subjects',
      render: (row) => (row.has_equal_subjects ? 'Yes' : 'No'),
    },
    {
      label: 'Form Teacher',
      render: (row) => <DisplayUserFullname user={row.form_teacher} />,
    },
    ...(isAdmin
      ? [
          {
            label: 'Action',
            render: (row: Classification) => (
              <HStack spacing={3}>
                <LinkButton
                  title="Results"
                  href={instRoute('class-result-info.index', {
                    classification: row.id,
                  })}
                  variant={'link'}
                />
                <IconButton
                  aria-label={'Edit Class'}
                  icon={<Icon as={PencilIcon} />}
                  as={InertiaLink}
                  href={instRoute('classifications.edit', [row.id])}
                  variant={'ghost'}
                  colorScheme={'brand'}
                />
                {/*                 
                <BrandButton
                  title="Move Students"
                  onClick={() => migrateClassStudentsModalToggle.open(row)}
                /> */}
                <DestructivePopover
                  label={'Delete this class'}
                  onConfirm={() => deleteItem(row)}
                  isLoading={deleteForm.processing}
                >
                  <IconButton
                    aria-label={'Delete Class'}
                    icon={<Icon as={TrashIcon} />}
                    variant={'ghost'}
                    colorScheme={'red'}
                  />
                </DestructivePopover>
                {/*                 
                <LinkButton
                  title="Student Tiles"
                  href={instRoute('classifications.students', [row])}
                  variant={'link'}
                /> */}
                <Menu>
                  <MenuButton
                    as={IconButton}
                    aria-label={'open file menu'}
                    icon={<Icon as={EllipsisVerticalIcon} />}
                    size={'sm'}
                    variant={'ghost'}
                  />
                  <MenuList>
                    <MenuItem
                      as={InertiaLink}
                      href={instRoute('timetables.classTimetable', [row])}
                    >
                      Timetable
                    </MenuItem>
                    <MenuItem
                      as={InertiaLink}
                      href={instRoute('classifications.students', [row])}
                    >
                      Student Tiles
                    </MenuItem>
                    <MenuItem
                      as={InertiaLink}
                      href={instRoute('users.idcards', [row])}
                    >
                      Students ID Cards
                    </MenuItem>

                    <MenuItem
                      onClick={() => migrateClassStudentsModalToggle.open(row)}
                    >
                      Move Students
                    </MenuItem>
                    <MenuItem onClick={() => generateResultPin(row)}>
                      Generate Result Pins
                    </MenuItem>
                    <MenuItem
                      as={InertiaLink}
                      href={instRoute('user-associations.create', [
                        'classification',
                        row.id,
                      ])}
                    >
                      Add to Divisions
                    </MenuItem>
                    <MenuItem
                      as={InertiaLink}
                      href={instRoute('pins.classification.student-pin-tiles', [
                        row,
                      ])}
                    >
                      View Result Pins
                    </MenuItem>
                    <MenuItem
                      as={InertiaLink}
                      href={instRoute('guardians.classifications.create', [
                        row,
                      ])}
                    >
                      Record Guardians
                    </MenuItem>
                    <MenuItem
                      as={InertiaLink}
                      href={instRoute('students.index', {
                        classification: row.id,
                      })}
                    >
                      View Students
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
        <SlabHeading
          title="List Classes"
          rightElement={
            <HStack>
              {isAdmin && (
                <>
                  <LinkButton
                    href={instRoute('classifications.create')}
                    title={'New'}
                  />
                  <LinkButton
                    title="Multi Create"
                    href={instRoute('classifications.multi-create')}
                  />
                </>
              )}
            </HStack>
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={classifications.data}
            keyExtractor={(row) => row.id}
            paginator={classifications}
          />
        </SlabBody>
      </Slab>
      <UploadClassificationModal
        {...uploadClassModalToggle.props}
        onSuccess={() => Inertia.reload()}
      />
      {migrateClassStudentsModalToggle.state && (
        <MigrateClassStudentsModal
          {...migrateClassStudentsModalToggle.props}
          classification={migrateClassStudentsModalToggle.state}
          onSuccess={() => Inertia.reload({ only: ['classifications'] })}
        />
      )}
    </DashboardLayout>
  );
}
