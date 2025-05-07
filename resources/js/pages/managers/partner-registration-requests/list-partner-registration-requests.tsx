import React from 'react';
import { PartnerRegistrationRequest } from '@/types/models';
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
import RegisterPartnerFromRequestModal from '@/components/modals/register-partner-from-request-modal';

interface Props {
  partnerRegistrationRequests: PaginationResponse<PartnerRegistrationRequest>;
}

export default function ListPartnerRegistrationRequests({
  partnerRegistrationRequests,
}: Props) {
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const partnerRegistrationRequestModalToggle =
    useModalValueToggle<PartnerRegistrationRequest>();

  async function deleteItem(
    partnerRegistrationRequest: PartnerRegistrationRequest
  ) {
    if (!window.confirm('Do you want to delete this record?')) {
      return;
    }
    const res = await deleteForm.submit((data, web) =>
      web.delete(
        route('managers.partner-registration-requests.destroy', [
          partnerRegistrationRequest.id,
        ])
      )
    );
    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.reload();
  }

  const headers: ServerPaginatedTableHeader<PartnerRegistrationRequest>[] = [
    {
      label: 'Name',
      render: (row) => row.first_name + ' ' + row.last_name,
    },
    {
      label: 'Phone',
      value: 'phone',
    },
    {
      label: 'Email',
      value: 'email',
    },
    {
      label: 'Referred by',
      render: (row) => row.referral?.user?.full_name ?? '',
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack spacing={1}>
          <BrandButton
            title="Onboard Now"
            leftIcon={<Icon as={PlusIcon} />}
            onClick={() => {
              partnerRegistrationRequestModalToggle.open(row);
            }}
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
          title="Partner Registration Requests"
          rightElement={
            <Button
              colorScheme="brand"
              variant={'solid'}
              size={'sm'}
              as={InertiaLink}
              href={route('managers.create')}
            >
              Create Partner
            </Button>
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={partnerRegistrationRequests.data}
            keyExtractor={(row) => row.id}
            paginator={partnerRegistrationRequests}
          />
        </SlabBody>
      </Slab>
      {partnerRegistrationRequestModalToggle.state && (
        <RegisterPartnerFromRequestModal
          {...partnerRegistrationRequestModalToggle.props}
          partnerRegistrationRequest={
            partnerRegistrationRequestModalToggle.state
          }
          onSuccess={() => Inertia.reload()}
        />
      )}
    </ManagerDashboardLayout>
  );
}
