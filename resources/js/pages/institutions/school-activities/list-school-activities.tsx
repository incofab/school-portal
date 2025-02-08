import React from 'react';
import { SchoolActivity } from '@/types/models';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import DashboardLayout from '@/layout/dashboard-layout';
import { LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { HStack, Icon, IconButton } from '@chakra-ui/react';
import DestructivePopover from '@/components/destructive-popover';
import { PencilIcon, TrashIcon } from '@heroicons/react/24/outline';
import { InertiaLink } from '@inertiajs/inertia-react';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';

interface Props {
  schoolActivities: PaginationResponse<SchoolActivity>;
}

export default function ListSchoolActivities({ schoolActivities }: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

  async function deleteItem(obj: SchoolActivity) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('school-activities.destroy', [obj.id]))
    );

    handleResponseToast(res);
    Inertia.reload();
  }

  const headers: ServerPaginatedTableHeader<SchoolActivity>[] = [
    {
      label: 'Title',
      value: 'title',
    },
    {
      label: 'Description',
      value: 'description',
    },
    {
      label: 'Action',
      render: (row: SchoolActivity) => (
        <HStack>
          <IconButton
            aria-label={'Edit Activity'}
            icon={<Icon as={PencilIcon} />}
            as={InertiaLink}
            href={instRoute('school-activities.edit', [row.id])}
            variant={'ghost'}
            colorScheme={'brand'}
          />
          <DestructivePopover
            label={'Delete this activity'}
            onConfirm={() => deleteItem(row)}
            isLoading={deleteForm.processing}
          >
            <IconButton
              aria-label={'Delete Activity'}
              icon={<Icon as={TrashIcon} />}
              variant={'ghost'}
              colorScheme={'red'}
            />
          </DestructivePopover>
        </HStack>
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="School Activities"
          rightElement={
            <LinkButton
              href={instRoute('school-activities.create')}
              title={'Add Activity'}
            />
          }
        />

        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={schoolActivities.data}
            keyExtractor={(row) => row.id}
            paginator={schoolActivities}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
