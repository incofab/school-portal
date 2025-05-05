import React from 'react';
import { BankAccount, Commission, InstitutionUser } from '@/types/models';
import { HStack, IconButton, Icon, Text } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import useModalToggle from '@/hooks/use-modal-toggle';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { InstitutionUserType, PaginationResponse } from '@/types/types';
import { PencilIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { BrandButton, LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import { InertiaLink } from '@inertiajs/inertia-react';
import { CloudArrowUpIcon, TrashIcon } from '@heroicons/react/24/solid';
import { Inertia } from '@inertiajs/inertia';
import DestructivePopover from '@/components/destructive-popover';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import route from '@/util/route';
import DataTable, { TableHeader } from '@/components/data-table';
import { formatAsCurrency } from '@/util/util';
import useIsAdminManager from '@/hooks/use-is-admin-manager';

interface Props {
  commissions: PaginationResponse<Commission>;
}

export default function ListCommissions({ commissions }: Props) {
  const deleteForm = useWebForm({});
  const isAdminManager = useIsAdminManager();

  const headers: ServerPaginatedTableHeader<Commission>[] = [
    {
      label: 'Institution Group',
      value: '',
      render: (row) => row.institution_group.name,
    },
    ...(isAdminManager
      ? [
          {
            label: 'Partner',
            render: (row: Commission) => row.partner?.user?.full_name ?? '',
          },
        ]
      : []),
    {
      label: 'Amount',
      value: 'amount',
      render: (row) => `${formatAsCurrency(row.amount)}`,
    },
    {
      label: 'Transaction Type',
      render: (row) => row.commissionable?.transactionable_type ?? '',
    },
    {
      label: 'Date',
      render: (row) => {
        const date = new Date(row.created_at);
        const formattedDate = date.toISOString().split('T')[0]; // Get the date part (YYYY-MM-DD)
        const time = date.toISOString().split('T')[1].split('.')[0]; // Get the time part (HH:mm:ss)
        return (
          <Text>
            {formattedDate} {time}
          </Text>
        );
      },
    },
  ];

  return (
    <ManagerDashboardLayout>
      <Slab>
        <SlabHeading title="Commissions" />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={commissions.data}
            keyExtractor={(row) => row.id}
            paginator={commissions}
          />
        </SlabBody>
      </Slab>
    </ManagerDashboardLayout>
  );
}
