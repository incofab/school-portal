import React from 'react';
import { Transaction } from '@/types/models';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse, WalletType } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import DashboardLayout from '@/layout/dashboard-layout';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { dateTimeFormat, formatAsCurrency } from '@/util/util';
import { Badge, Wrap, WrapItem } from '@chakra-ui/react';
import { InertiaLink } from '@inertiajs/inertia-react';
import DateTimeDisplay from '@/components/date-time-display';

interface Props {
  transactions: PaginationResponse<Transaction>;
  wallet?: string;
}

export default function ListTransactions({ transactions, wallet }: Props) {
  const { instRoute } = useInstitutionRoute();

  const headers: ServerPaginatedTableHeader<Transaction>[] = [
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
      label: 'Amount',
      value: 'amount',
      render: (row) => formatAsCurrency(row.amount),
    },
    {
      label: 'BBT',
      value: 'bbt',
      render: (row) => formatAsCurrency(row.bbt),
    },
    {
      label: 'BAT',
      value: 'bat',
      render: (row) => formatAsCurrency(row.bat),
    },
    {
      label: 'Type',
      value: 'type',
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
    {
      label: 'Reeference',
      value: 'reference',
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title="Transactions" />
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
                url={instRoute('transactions.index', [item])}
              />
            ))}
          </Wrap>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={transactions.data}
            keyExtractor={(row) => row.id}
            paginator={transactions}
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
  var labelName = 'All Transactions';
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
