import React from 'react';
import {
  Button,
  HStack,
  Icon,
  IconButton,
  Input,
  VStack,
} from '@chakra-ui/react';
import { Inertia } from '@inertiajs/inertia';
import { PencilIcon } from '@heroicons/react/24/solid';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import GenericModal, { BaseModalProps } from '@/components/generic-modal';
import FormControlBox from '@/components/forms/form-control-box';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { useModalValueToggle } from '@/hooks/use-modal-toggle';
import { Partner } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import { formatAsCurrency } from '@/util/util';
import route from '@/util/route';

interface Props {
  partners: PaginationResponse<Partner>;
}

export default function ListPartners({ partners }: Props) {
  const editPartnerModalToggle = useModalValueToggle<Partner>();

  const headers: ServerPaginatedTableHeader<Partner>[] = [
    {
      label: 'Partner Account',
      render: (row) => row.name ?? row.user?.full_name ?? '',
    },
    {
      label: 'Admin',
      render: (row) =>
        row.admin_users?.map((admin) => admin.full_name).join(', ') ??
        row.user?.full_name ??
        '',
    },
    {
      label: 'Admin Email',
      render: (row) =>
        row.admin_users?.map((admin) => admin.email).join(', ') ??
        row.user?.email ??
        '',
    },
    {
      label: 'Commission',
      render: (row) => `${row.commission ?? 0}%`,
    },
    {
      label: 'Referral',
      render: (row) =>
        row.referral?.name ?? row.referral?.user?.full_name ?? '',
    },
    {
      label: 'Referral Commission',
      render: (row) => `${row.referral_commission ?? 0}%`,
    },
    {
      label: 'Users',
      render: (row) =>
        `${row.partner_users_count ?? row.partner_users?.length ?? 0}`,
    },
    {
      label: 'Wallet',
      render: (row) => formatAsCurrency(row.wallet ?? 0),
    },
    {
      label: 'Action',
      render: (row) => (
        <IconButton
          aria-label="Edit Partner"
          colorScheme="brand"
          icon={<Icon as={PencilIcon} />}
          onClick={() => editPartnerModalToggle.open(row)}
          size="sm"
        />
      ),
    },
  ];

  return (
    <ManagerDashboardLayout>
      <Slab>
        <SlabHeading title="Partners" />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={partners.data}
            keyExtractor={(row) => row.id}
            paginator={partners}
          />
        </SlabBody>
      </Slab>

      {editPartnerModalToggle.state && (
        <EditPartnerModal
          {...editPartnerModalToggle.props}
          partner={editPartnerModalToggle.state}
          onSuccess={() => Inertia.reload()}
        />
      )}
    </ManagerDashboardLayout>
  );
}

function EditPartnerModal({
  isOpen,
  onClose,
  onSuccess,
  partner,
}: BaseModalProps & { partner: Partner; onSuccess(): void }) {
  const { handleResponseToast } = useMyToast();
  const webForm = useWebForm({
    name: partner.name ?? partner.user?.full_name ?? '',
    commission: `${partner.commission ?? ''}`,
    referral_email: partner.referral?.user?.email ?? '',
    referral_commission: `${partner.referral_commission ?? 0}`,
  });

  async function onSubmit() {
    const res = await webForm.submit((data, web) =>
      web.post(route('managers.partners.update', [partner.id]), data)
    );
    if (!handleResponseToast(res)) {
      return;
    }

    onClose();
    onSuccess();
  }

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent="Edit Partner"
      bodyContent={
        <VStack align="stretch" spacing={3}>
          <FormControlBox
            form={webForm}
            title="Partner Account Name"
            formKey="name"
          >
            <Input
              value={webForm.data.name}
              onChange={(e) => webForm.setValue('name', e.currentTarget.value)}
              required
            />
          </FormControlBox>
          <FormControlBox
            form={webForm}
            title="Commission"
            formKey="commission"
          >
            <Input
              type="number"
              value={webForm.data.commission}
              onChange={(e) =>
                webForm.setValue('commission', e.currentTarget.value)
              }
              required
            />
          </FormControlBox>
          <FormControlBox
            form={webForm}
            title="Referral Email"
            formKey="referral_email"
          >
            <Input
              type="email"
              value={webForm.data.referral_email}
              onChange={(e) =>
                webForm.setValue('referral_email', e.currentTarget.value)
              }
            />
          </FormControlBox>
          <FormControlBox
            form={webForm}
            title="Referral Commission"
            formKey="referral_commission"
          >
            <Input
              type="number"
              value={webForm.data.referral_commission}
              onChange={(e) =>
                webForm.setValue('referral_commission', e.currentTarget.value)
              }
            />
          </FormControlBox>
        </VStack>
      }
      footerContent={
        <HStack spacing={2}>
          <Button variant="ghost" onClick={onClose}>
            Close
          </Button>
          <Button
            colorScheme="brand"
            onClick={onSubmit}
            isLoading={webForm.processing}
          >
            Save
          </Button>
        </HStack>
      }
    />
  );
}
