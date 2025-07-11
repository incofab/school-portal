import React from 'react';
import { InstitutionUser } from '@/types/models';
import { HStack, IconButton, Icon } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useModalToggle from '@/hooks/use-modal-toggle';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { InstitutionUserType, PaginationResponse } from '@/types/types';
import { PencilIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { BrandButton, LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import { InertiaLink } from '@inertiajs/inertia-react';
import useInstitutionRoute from '@/hooks/use-institution-route';
import UsersTableFilters from '@/components/table-filters/users-table-filters';
import { CloudArrowUpIcon, TrashIcon } from '@heroicons/react/24/solid';
import UploadStaffModal from '@/components/modals/upload-staff-modal';
import { Inertia } from '@inertiajs/inertia';
import DestructivePopover from '@/components/destructive-popover';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import DisplayUserFullname from '@/domain/institutions/users/display-user-fullname';
import useIsStaff from '@/hooks/use-is-staff';

interface Props {
  institutionUsers: PaginationResponse<InstitutionUser>;
}

export default function ListStudents({ institutionUsers }: Props) {
  const { instRoute } = useInstitutionRoute();
  const userFilterToggle = useModalToggle();
  const staffUploadModalToggle = useModalToggle();

  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

  async function deleteItem(obj: InstitutionUser) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('users.destroy', [obj.user_id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['institutionUsers'] });
  }

  const headers: ServerPaginatedTableHeader<InstitutionUser>[] = [
    {
      label: 'Name',
      value: 'user.full_name',
      render: (row) => <DisplayUserFullname user={row.user} />,
    },
    {
      label: 'Email',
      value: 'user.email',
    },
    {
      label: 'Phone',
      value: 'user.phone',
    },
    {
      label: 'Role',
      value: 'role',
    },
    {
      label: 'Gender',
      value: 'user.gender',
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack>
          <IconButton
            as={InertiaLink}
            aria-label={'Edit user'}
            icon={<Icon as={PencilIcon} />}
            href={
              row.student
                ? instRoute('students.edit', [row.student.id])
                : instRoute('users.edit', [row.id])
            }
            variant={'ghost'}
            colorScheme={'brand'}
          />
          <DestructivePopover
            label={'Do you really want to delete this user? Be careful!!!'}
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
        </HStack>
      ),
    },
  ]; 

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Members"
          rightElement={
            <HStack>
              <LinkButton href={instRoute('users.create')} title={'New'} />
              <BrandButton
                leftIcon={<Icon as={CloudArrowUpIcon} />}
                onClick={staffUploadModalToggle.open}
                title="Upload Staff"
              />
            </HStack>
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={institutionUsers.data}
            keyExtractor={(row) => row.id}
            validFilters={['role']}
            paginator={institutionUsers}
            onFilterButtonClick={userFilterToggle.open}
          />
        </SlabBody>
      </Slab>
      <UsersTableFilters {...userFilterToggle.props} />
      <UploadStaffModal
        {...staffUploadModalToggle.props}
        onSuccess={() => Inertia.reload({ only: ['institutionUsers'] })}
      />
    </DashboardLayout>
  );
}
