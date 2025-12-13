import React from 'react';
import { Funding, InstitutionGroup } from '@/types/models';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse, WalletType } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import FundInstitutionGroupModal from '@/components/modals/fund-institution-group-modal';
import useModalToggle from '@/hooks/use-modal-toggle';
import { BrandButton } from '@/components/buttons';
import { Inertia } from '@inertiajs/inertia';
import { Button, HStack, Icon, IconButton } from '@chakra-ui/react';
import InfoPopover from '@/components/info-popover';
import { ArrowDownTrayIcon, EyeIcon } from '@heroicons/react/24/outline';
import { formatAsCurrency } from '@/util/util';
import RecordDebtModal from '@/components/modals/record-debt-modal';
import route from '@/util/route';
import DestructivePopover from '@/components/destructive-popover';
import DateTimeDisplay from '@/components/date-time-display';

interface Props {
  fundings: PaginationResponse<Funding>;
  institutionGroups: InstitutionGroup[];
}

export default function ListFundings({ fundings, institutionGroups }: Props) {
  const fundInstitutionGroupModalToggle = useModalToggle();
  const recordDebtModalToggle = useModalToggle();

  const headers: ServerPaginatedTableHeader<Funding>[] = [
    {
      label: 'Name',
      value: '',
      render: (row) => row.institution_group.name,
    },
    {
      label: 'Amount Funded',
      value: 'amount',
      render: (row) => formatAsCurrency(row.amount),
    },
    {
      label: 'Previous Balance',
      value: 'previous_balance',
      render: (row) => formatAsCurrency(row.previous_balance),
    },
    {
      label: 'New Balance',
      value: 'new_balance',
      render: (row) => formatAsCurrency(row.new_balance),
    },
    {
      label: 'Date',
      value: 'created_at',
      render: (row) => <DateTimeDisplay dateTime={row.created_at} />,
    },
    {
      label: 'Reference',
      value: 'reference',
    },
    {
      label: 'Remark',
      value: 'remark',
      // render: (row) => (
      //   <HStack>
      //     {!row.remark || row.remark.trim() === '' ? (
      //       ''
      //     ) : (
      //       <InfoPopover label={row.remark}>
      //         <IconButton
      //           aria-label={'Remark'}
      //           icon={<Icon as={EyeIcon} />}
      //           variant={'ghost'}
      //           colorScheme={'brand'}
      //         />
      //       </InfoPopover>
      //     )}
      //   </HStack>
      // ),
    },
    {
      label: 'Action',
      render: (row) =>
        row.wallet == WalletType.Credit ? (
          <DestructivePopover
            label={'Generate Receipt for this payment?'}
            positiveButtonLabel={'Generate Receipt'}
            onConfirm={() =>
              window.open(route('managers.funding.receipt', [row.id]))
            }
          >
            <Button
              size="sm"
              colorScheme="brand"
              leftIcon={<Icon as={ArrowDownTrayIcon} />}
            >
              Receipt
            </Button>
          </DestructivePopover>
        ) : (
          <></>
        ),
    },
  ];

  return (
    <ManagerDashboardLayout>
      <Slab>
        <SlabHeading
          title="Deposits"
          rightElement={
            <HStack>
              <BrandButton
                title="Record Debt"
                onClick={recordDebtModalToggle.open}
              />
              <BrandButton
                title="Add Fund"
                onClick={fundInstitutionGroupModalToggle.open}
              />
            </HStack>
          }
        />

        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={fundings.data}
            keyExtractor={(row) => row.id}
            paginator={fundings}
            tableRowProps={(row) => ({
              backgroundColor:
                row.wallet == WalletType.Debt ? 'red.50' : undefined,
            })}
          />
        </SlabBody>
      </Slab>

      <FundInstitutionGroupModal
        institutionGroups={institutionGroups}
        {...fundInstitutionGroupModalToggle.props}
        onSuccess={() => Inertia.reload()}
      />
      <RecordDebtModal
        institutionGroups={institutionGroups}
        {...recordDebtModalToggle.props}
        onSuccess={() => Inertia.reload()}
      />
    </ManagerDashboardLayout>
  );
}
