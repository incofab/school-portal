import React from 'react';
import { Funding, InstitutionGroup } from '@/types/models';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import FundInstitutionGroupModal from '@/components/modals/fund-institution-group-modal';
import useModalToggle from '@/hooks/use-modal-toggle';
import { BrandButton } from '@/components/buttons';
import { Inertia } from '@inertiajs/inertia';
import { HStack, Icon, IconButton } from '@chakra-ui/react';
import InfoPopover from '@/components/info-popover';
import { EyeIcon } from '@heroicons/react/24/outline';

interface Props {
  fundings: PaginationResponse<Funding>;
  institutionGroups: InstitutionGroup[];
}

function NumberFormatter(number: number) {
  return new Intl.NumberFormat().format(number);
}

export default function ListFundings({ fundings, institutionGroups }: Props) {
  const fundInstitutionGroupModalToggle = useModalToggle();

  const headers: ServerPaginatedTableHeader<Funding>[] = [
    {
      label: 'Name',
      value: '',
      render: (row) => row.institution_group.name,
    },
    {
      label: 'Amount Funded',
      value: 'amount',
      render: (row) => '₦ ' + NumberFormatter(row.amount),
    },
    {
      label: 'Previous Balance',
      value: 'previous_balance',
      render: (row) => '₦ ' + NumberFormatter(row.previous_balance),
    },
    {
      label: 'New Balance',
      value: 'new_balance',
      render: (row) => '₦ ' + NumberFormatter(row.new_balance),
    },
    {
      label: 'Reference',
      value: 'reference',
    },
    {
      label: 'Remark',
      render: (row) => (
        <HStack>
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
    <ManagerDashboardLayout>
      <Slab>
        <SlabHeading
          title="Deposits"
          rightElement={
            <BrandButton
              title="Add Fund"
              onClick={fundInstitutionGroupModalToggle.open}
            />
          }
        />

        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={fundings.data}
            keyExtractor={(row) => row.id}
            paginator={fundings}
          />
        </SlabBody>
      </Slab>

      <FundInstitutionGroupModal
        institutionGroups={institutionGroups}
        {...fundInstitutionGroupModalToggle.props}
        onSuccess={() => Inertia.reload()}
      />
    </ManagerDashboardLayout>
  );
}
