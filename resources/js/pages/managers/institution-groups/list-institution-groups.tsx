import React from 'react';
import { InstitutionGroup } from '@/types/models';
import { Button, HStack, Icon, IconButton, Tooltip } from '@chakra-ui/react';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import route from '@/util/route';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import { PencilIcon, PlusIcon, TrashIcon } from '@heroicons/react/24/solid';
import { InertiaLink } from '@inertiajs/inertia-react';

interface InstitutionGroupWithMeta extends InstitutionGroup {
  institutions_count: number;
}
interface Props {
  institutionGroups: PaginationResponse<InstitutionGroupWithMeta>;
}

function NumberFormatter(number: number) {
  return new Intl.NumberFormat().format(number);
}

export default function ListInstitutionGropus({ institutionGroups }: Props) {
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

  async function deleteInstitution(institutionGroup: InstitutionGroup) {
    if (!window.confirm('Do you want to delete this group?')) {
      return;
    }
    const res = await deleteForm.submit((data, web) =>
      web.delete(
        route('managers.institution-groups.destroy', [institutionGroup])
      )
    );
    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.reload();
  }

  const headers: ServerPaginatedTableHeader<InstitutionGroupWithMeta>[] = [
    {
      label: 'Name',
      value: 'name',
    },
    {
      label: 'Institutions',
      value: 'institutions_count',
    },
    {
      label: 'Credit',
      value: 'credit_wallet',
      render: (row) => '₦ ' + NumberFormatter(row.credit_wallet),
    },
    {
      label: 'Debt',
      value: 'debt_wallet',
      render: (row) => '₦ ' + NumberFormatter(row.debt_wallet),
    },
    {
      label: 'Loan Limit',
      value: 'loan_limit',
      render: (row) => '₦ ' + NumberFormatter(row.loan_limit),
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack spacing={2}>
          <IconButton
            aria-label="Edit Group"
            colorScheme={'brand'}
            size={'sm'}
            icon={<Icon as={PencilIcon} />}
            as={InertiaLink}
            href={route('managers.institution-groups.edit', [row])}
          />
          <IconButton
            aria-label="Delete Group"
            colorScheme={'red'}
            size={'sm'}
            icon={<Icon as={TrashIcon} />}
            onClick={() => deleteInstitution(row)}
            isDisabled={row.institutions_count > 0}
          />
          <Tooltip label={'Add Institution'}>
            <IconButton
              aria-label="Add Institution"
              colorScheme={'brand'}
              size={'sm'}
              icon={<Icon as={PlusIcon} />}
              as={InertiaLink}
              href={route('managers.institutions.create')}
            />
          </Tooltip>
        </HStack>
      ),
    },
  ];

  return (
    <ManagerDashboardLayout>
      <Slab>
        <SlabHeading
          title="Groups"
          rightElement={
            <Button
              as={InertiaLink}
              href={route('managers.institution-groups.create')}
              colorScheme={'brand'}
              variant={'solid'}
              size={'sm'}
            >
              New
            </Button>
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={institutionGroups.data}
            keyExtractor={(row) => row.id}
            paginator={institutionGroups}
          />
        </SlabBody>
      </Slab>
    </ManagerDashboardLayout>
  );
}
