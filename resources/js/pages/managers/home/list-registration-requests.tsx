import React from 'react';
import { InstitutionGroup, RegistrationRequest } from '@/types/models';
import { Button, HStack, Icon, IconButton } from '@chakra-ui/react';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import route from '@/util/route';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import { PlusIcon, TrashIcon } from '@heroicons/react/24/solid';
import { InertiaLink } from '@inertiajs/inertia-react';
import { BrandButton } from '@/components/buttons';
import { useModalValueToggle } from '@/hooks/use-modal-toggle';
import RegisterInstitutionFromRequestModal from '@/components/modals/register-institution-from-request-modal';
import RegisterInstitutionGroupFromRequestModal from '@/components/modals/register-institution-group-from-request-modal';

interface Props {
  registrationRequests: PaginationResponse<RegistrationRequest>;
  institutionGroups: InstitutionGroup[];
}

export default function ListRegistrationRequests({
  registrationRequests,
  institutionGroups,
}: Props) {
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const registrationRequestModalToggle =
    useModalValueToggle<RegistrationRequest>();
  const createGroupModalToggle = useModalValueToggle<RegistrationRequest>();

  async function deleteItem(registrationRequest: RegistrationRequest) {
    if (!window.confirm('Do you want to delete this record?')) {
      return;
    }
    const res = await deleteForm.submit((data, web) =>
      web.delete(
        route('managers.registration-requests.destroy', [
          registrationRequest.id,
        ])
      )
    );
    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.reload();
  }

  const headers: ServerPaginatedTableHeader<RegistrationRequest>[] = [
    {
      label: 'Owner',
      value: 'partner.full_name',
    },
    {
      label: 'Name',
      value: 'data.institution.name',
    },
    {
      label: 'Phone',
      value: 'data.phone',
    },
    {
      label: 'Status',
      render: (row) =>
        row.institution_registered_at === null ? 'Pending' : 'Registered',
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack spacing={1}>
          <BrandButton
            title="Create Group"
            leftIcon={<Icon as={PlusIcon} />}
            onClick={() => {
              createGroupModalToggle.open(row);
            }}
            isDisabled={
              row.institution_registered_at !== null ||
              row.institution_group_registered_at !== null
            }
            size={'sm'}
          />
          <BrandButton
            title="Register Now"
            leftIcon={<Icon as={PlusIcon} />}
            onClick={() => {
              registrationRequestModalToggle.open(row);
            }}
            isDisabled={row.institution_registered_at !== null}
            size={'sm'}
          />
          <IconButton
            aria-label="Delete"
            colorScheme={'red'}
            icon={<Icon as={TrashIcon} />}
            onClick={() => deleteItem(row)}
            size={'sm'}
          />
        </HStack>
      ),
    },
  ];

  return (
    <ManagerDashboardLayout>
      <Slab>
        <SlabHeading
          title="Registration Requests"
          rightElement={
            <Button
              colorScheme="brand"
              variant={'solid'}
              size={'sm'}
              as={InertiaLink}
              href={route('managers.institution-groups.create')}
            >
              Create Group
            </Button>
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={registrationRequests.data}
            keyExtractor={(row) => row.id}
            paginator={registrationRequests}
          />
        </SlabBody>
      </Slab>
      {registrationRequestModalToggle.state && (
        <RegisterInstitutionFromRequestModal
          {...registrationRequestModalToggle.props}
          institutionGroups={institutionGroups}
          registrationRequest={registrationRequestModalToggle.state}
          onSuccess={() => Inertia.reload()}
        />
      )}
      {createGroupModalToggle.state && (
        <RegisterInstitutionGroupFromRequestModal
          {...createGroupModalToggle.props}
          registrationRequest={createGroupModalToggle.state}
          onSuccess={() => Inertia.reload()}
        />
      )}
    </ManagerDashboardLayout>
  );
}
