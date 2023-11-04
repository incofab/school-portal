import React from 'react';
import { Institution } from '@/types/models';
import { HStack, Icon, IconButton } from '@chakra-ui/react';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import route from '@/util/route';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import { TrashIcon } from '@heroicons/react/24/solid';

interface InstitutionWithMeta extends Institution {
  classifications_count: number;
}
interface Props {
  institutions: PaginationResponse<InstitutionWithMeta>;
}

export default function ListInstitutions({ institutions }: Props) {
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

  async function deleteInstitution(institution: Institution) {
    if (!window.confirm('Do you want to delete this institution?')) {
      return;
    }
    const res = await deleteForm.submit((data, web) =>
      web.delete(route('managers.institutions.destroy', [institution.uuid]))
    );
    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.reload();
  }

  const headers: ServerPaginatedTableHeader<InstitutionWithMeta>[] = [
    {
      label: 'Name',
      value: 'name',
    },
    {
      label: 'Classes',
      value: 'classifications_count',
    },
    {
      label: 'Email',
      value: 'email',
    },
    {
      label: 'Phone',
      value: 'phone',
    },
    {
      label: 'Address',
      value: 'address',
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack>
          <LinkButton
            href={route('users.impersonate', [row.user_id])}
            colorScheme={'red'}
            variant={'link'}
            title="Impersonate"
          />
          <IconButton
            aria-label="Delete institution"
            colorScheme={'red'}
            icon={<Icon as={TrashIcon} />}
            onClick={() => deleteInstitution(row)}
            isDisabled={row.classifications_count > 0}
          />
        </HStack>
      ),
    },
  ];

  return (
    <ManagerDashboardLayout>
      <Slab>
        <SlabHeading title="Institutions" />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={institutions.data}
            keyExtractor={(row) => row.id}
            paginator={institutions}
          />
        </SlabBody>
      </Slab>
    </ManagerDashboardLayout>
  );
}
