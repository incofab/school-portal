import React from 'react';
import { Institution, InstitutionGroup } from '@/types/models';
import { HStack, Icon, IconButton } from '@chakra-ui/react';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { InstitutionStatus, PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import route from '@/util/route';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import { TrashIcon } from '@heroicons/react/24/solid';
import ButtonSwitch from '@/components/button-switch';

interface InstitutionWithMeta extends Institution {
  classifications_count: number;
  institution_group: InstitutionGroup;
}
interface Props {
  institutions: PaginationResponse<InstitutionWithMeta>;
}

export default function ListInstitutions({ institutions }: Props) {
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const suspensionForm = useWebForm({});

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

  async function updateStatus(
    institution: Institution,
    status: InstitutionStatus
  ) {
    if (!window.confirm('Do you want to change status on this institution?')) {
      return;
    }
    const res = await suspensionForm.submit((data, web) =>
      web.post(
        route('managers.institutions.update.status', [institution.uuid]),
        { status }
      )
    );

    if (!handleResponseToast(res)) return;

    Inertia.reload({ only: ['institutions'] });
  }

  const headers: ServerPaginatedTableHeader<InstitutionWithMeta>[] = [
    {
      label: 'Name',
      value: 'name',
    },
    {
      label: 'Group',
      value: 'institution_group.name',
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
      label: 'Status',
      render: (row) => (
        <ButtonSwitch
          items={[
            {
              value: true,
              label: InstitutionStatus.Active,
              onClick: () => updateStatus(row, InstitutionStatus.Active),
            },
            {
              value: false,
              label: InstitutionStatus.Suspended,
              onClick: () => updateStatus(row, InstitutionStatus.Suspended),
            },
          ]}
          value={row.status}
        />
      ),
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack>
          <LinkButton
            href={route('institutions.impersonate', [row.uuid])}
            colorScheme={'red'}
            variant={'link'}
            title="Impersonate"
          />
          <IconButton
            aria-label="Delete institution"
            colorScheme={'red'}
            size={'sm'}
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
