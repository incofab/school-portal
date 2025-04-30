import React from 'react';
import { BankAccount, InstitutionUser } from '@/types/models';
import { HStack, IconButton, Icon } from '@chakra-ui/react';
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

interface Props {
  bankAccounts: BankAccount[];
}

export default function ListBankAccounts({ bankAccounts }: Props) {
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

  async function deleteItem(obj: BankAccount) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(route('managers.bank-accounts.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['bankAccounts'] });
  }

  const headers: TableHeader<BankAccount>[] = [
    {
      label: 'Bank Name',
      value: 'bank_name',
    },
    {
      label: 'Account Name',
      value: 'account_name',
    },
    {
      label: 'Account Number',
      value: 'account_number',
    },
    {
      label: 'Action',
      render: (row) =>
        row.withdrawals_count < 1 ? (
          <HStack>
            <IconButton
              as={InertiaLink}
              aria-label={'Edit Bank Details'}
              icon={<Icon as={PencilIcon} />}
              href={route('managers.bank-accounts.edit', [row.id])}
              variant={'ghost'}
              colorScheme={'brand'}
            />
            <DestructivePopover
              label={
                'Do you really want to delete this Bank Details? Be careful!!!'
              }
              onConfirm={() => deleteItem(row)}
              isLoading={deleteForm.processing}
            >
              <IconButton
                aria-label={'Delete bank account'}
                icon={<Icon as={TrashIcon} />}
                variant={'ghost'}
                colorScheme={'red'}
              />
            </DestructivePopover>
          </HStack>
        ) : (
          ''
        ),
    },
  ];

  return (
    <ManagerDashboardLayout>
      <Slab>
        <SlabHeading
          title="Bank Accounts"
          rightElement={
            <HStack>
              <LinkButton
                href={route('managers.bank-accounts.create')}
                title={'New'}
              />
            </HStack>
          }
        />
        <SlabBody>
          <DataTable
            scroll={true}
            headers={headers}
            data={bankAccounts}
            keyExtractor={(row) => row.id}
            // hideSearchField={true}
          />
        </SlabBody>
      </Slab>
    </ManagerDashboardLayout>
  );
}
