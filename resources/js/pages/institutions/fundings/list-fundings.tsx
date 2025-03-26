import React from 'react';
import { Funding } from '@/types/models';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse, WalletType } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import DashboardLayout from '@/layout/dashboard-layout';
import { LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { dateTimeFormat, formatAsCurrency } from '@/util/util';
import { Badge, Wrap, WrapItem } from '@chakra-ui/react';
import { InertiaLink } from '@inertiajs/inertia-react';
import DateTimeDisplay from '@/components/date-time-display';

interface Props {
  fundings: PaginationResponse<Funding>;
  wallet?: string;
}

export default function ListFundings({ fundings, wallet }: Props) {
  const { instRoute } = useInstitutionRoute();

  const headers: ServerPaginatedTableHeader<Funding>[] = [
    {
      label: 'Wallet',
      render: (row) => (
        <Badge
          textShadow={`1px 1px 2px ${
            row.wallet === WalletType.Credit
              ? 'rgba(0, 255, 0, 0.7)'
              : 'rgba(255, 0, 0, 0.7)'
          }`}
          color={row.wallet === WalletType.Credit ? 'green.600' : 'red.600'}
        >
          {row.wallet}
        </Badge>
      ),
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
      label: 'Type',
      value: 'transaction.type',
    },
    {
      label: 'Remark',
      value: 'remark',
    },
    {
      label: 'Date',
      render: (row) => (
        <small>
          <DateTimeDisplay
            dateTime={row.created_at}
            dateTimeformat={dateTimeFormat}
          />
        </small>
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Deposits"
          rightElement={
            <LinkButton
              href={instRoute('fundings.create')}
              title={'Add Fund'}
            />
          }
        />
        <SlabBody>
          <Wrap
            border={'1px solid'}
            borderColor={'gray.300'}
            display={'inline-block'}
            spacing={0}
            gap={0}
          >
            {[WalletType.Credit, WalletType.Debt, ''].map((item) => (
              <Item
                label={item}
                isActive={item === (wallet ?? '')}
                url={instRoute('fundings.index', [item])}
              />
            ))}
          </Wrap>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={fundings.data}
            keyExtractor={(row) => row.id}
            paginator={fundings}
            hideSearchField={true}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}

function Item({
  label,
  isActive,
  url,
}: {
  label: string;
  isActive: boolean;
  url: string;
}) {
  var labelName = 'All Fundings';
  if (label === WalletType.Credit) {
    labelName = 'Wallet';
  } else if (label === WalletType.Debt) {
    labelName = 'Debt';
  }
  return (
    <WrapItem
      borderLeft={'1px solid'}
      borderColor={'gray.300'}
      backgroundColor={isActive ? 'brand.700' : 'transparent'}
      color={isActive ? 'white' : 'brand.700'}
      p={'5px 10px'}
      minWidth={'80px'}
      cursor={'pointer'}
      justifyContent={'center'}
      _hover={{ backgroundColor: isActive ? '' : 'brand.50' }}
      as={InertiaLink}
      href={url}
    >
      {labelName}
    </WrapItem>
  );
}
