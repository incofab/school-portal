import React from 'react';
import { Billing, InstitutionGroup } from '@/types/models';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import useModalToggle, { useModalValueToggle } from '@/hooks/use-modal-toggle';
import { BrandButton } from '@/components/buttons';
import { Inertia } from '@inertiajs/inertia';
import BillInstitutionGroupModal from '@/components/modals/bill-institution-group-modal';
import { HStack, Icon, IconButton } from '@chakra-ui/react';
import { PencilIcon } from '@heroicons/react/24/outline';

interface Props {
  billings: PaginationResponse<Billing>;
  institutionGroups: InstitutionGroup[];
}

function NumberFormatter(number: number) {
  return new Intl.NumberFormat().format(number);
}

export default function ListBillings({ billings, institutionGroups }: Props) {
  const billInstitutionGroupModalToggle = useModalToggle();
  const updateBillInstitutionGroupModalToggle =
    useModalValueToggle<Billing | null>();

  const headers: ServerPaginatedTableHeader<Billing>[] = [
    {
      label: 'Bill Type',
      value: '',
      render: (row) => row.type,
    },
    {
      label: 'Institution Group',
      value: '',
      render: (row) => row.institution_group.name,
    },
    {
      label: 'Amount Billed',
      value: 'amount',
      render: (row) => '₦ ' + NumberFormatter(row.amount),
    },
    {
      label: 'Payment Structure',
      value: 'payment_structure',
      render: (row) => row.payment_structure,
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack>
          <IconButton
            aria-label={'Remark'}
            icon={<Icon as={PencilIcon} />}
            variant={'ghost'}
            colorScheme={'brand'}
            onClick={() => updateBillInstitutionGroupModalToggle.open(row)}
          />
        </HStack>
      ),
    },
  ];

  return (
    <ManagerDashboardLayout>
      <Slab>
        <SlabHeading
          title="Billings"
          rightElement={
            <BrandButton
              title="Add Billing"
              onClick={() => billInstitutionGroupModalToggle.open()}
            />
          }
        />

        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={billings.data}
            keyExtractor={(row) => row.id}
            paginator={billings}
          />
        </SlabBody>
      </Slab>

      <BillInstitutionGroupModal
        institutionGroups={institutionGroups}
        {...billInstitutionGroupModalToggle.props}
        onSuccess={() => Inertia.reload()}
      />
      {updateBillInstitutionGroupModalToggle.state && (
        <BillInstitutionGroupModal
          priceList={updateBillInstitutionGroupModalToggle.state}
          institutionGroups={institutionGroups}
          {...updateBillInstitutionGroupModalToggle.props}
          onSuccess={() => Inertia.reload()}
        />
      )}
    </ManagerDashboardLayout>
  );
}
