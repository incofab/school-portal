import React from 'react';
import { Attendance } from '@/types/models';
import { HStack, IconButton, Icon, Text } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useModalToggle from '@/hooks/use-modal-toggle';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import UsersTableFilters from '@/components/table-filters/users-table-filters';
import { EyeIcon, TrashIcon } from '@heroicons/react/24/solid';
import { Inertia } from '@inertiajs/inertia';
import DestructivePopover from '@/components/destructive-popover';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import DisplayUserFullname from '@/domain/institutions/users/display-user-fullname';
import InfoPopover from '@/components/info-popover';

interface Props {
  attendance: PaginationResponse<Attendance>;
}

export default function ListAttendances({ attendance }: Props) {
  const { instRoute } = useInstitutionRoute();
  const userFilterToggle = useModalToggle();

  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

  async function deleteItem(obj: Attendance) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('attendances.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload();
  }

  const headers: ServerPaginatedTableHeader<Attendance>[] = [
    {
      label: 'Name',
      value: 'institution_user.user.full_name',
      render: (row) => <DisplayUserFullname user={row.institution_user.user} />,
    },
    {
      label: 'Role',
      value: 'institution_user.role',
    },
    {
      label: 'Class',
      value: 'institution_user.student?.classification?.title',
      render: (row) => (
        <Text>
          {row.institution_user.role === 'student'
            ? row.institution_user.student?.classification?.title
            : row.institution_user.role}
        </Text>
      ),
    },
    {
      label: 'Signed In At',
      render: (row) => {
        const date = new Date(row.signed_in_at);
        const formattedDate = date.toISOString().split('T')[0]; // Get the date part (YYYY-MM-DD)
        const time = date.toISOString().split('T')[1].split('.')[0]; // Get the time part (HH:mm:ss)
        return (
          <Text>
            {formattedDate} {time}
          </Text>
        );
      },
    },
    {
      label: 'Signed Out At',
      render: (row) => {
        const date = new Date(row.signed_out_at);
        const formattedDate = date.toISOString().split('T')[0];
        const time = date.toISOString().split('T')[1].split('.')[0];
        return (
          <Text>
            {row.signed_out_at !== null ? formattedDate + ' ' + time : ''}
          </Text>
        );
      },
    },

    {
      label: 'Action',
      render: (row) => (
        <HStack>
          <DestructivePopover
            label={
              'Do you really want to delete this Attendance? Be careful!!!'
            }
            onConfirm={() => deleteItem(row)}
            isLoading={deleteForm.processing}
          >
            <IconButton
              aria-label={'Delete user'}
              icon={<Icon as={TrashIcon} />}
              variant={'ghost'}
              colorScheme={'red'}
            />
          </DestructivePopover>

          {!row.remark || row.remark.trim() === '' ? (
            ''
          ) : (
            <InfoPopover label={row.remark}>
              <IconButton
                aria-label={'Remark'}
                icon={<Icon as={EyeIcon} />}
                variant={'ghost'}
                colorScheme={'brand'}
              />
            </InfoPopover>
          )}
        </HStack>
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title="Attendance Record" />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={attendance.data}
            keyExtractor={(row) => row.id}
            validFilters={['role']}
            paginator={attendance}
            onFilterButtonClick={userFilterToggle.open}
          />
        </SlabBody>
      </Slab>
      <UsersTableFilters {...userFilterToggle.props} />
    </DashboardLayout>
  );
}
