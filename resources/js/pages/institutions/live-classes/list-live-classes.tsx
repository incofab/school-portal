import React from 'react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { HStack, Icon, IconButton } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { Inertia } from '@inertiajs/inertia';
import DataTable, { TableHeader } from '@/components/data-table';
import { LiveClass } from '@/types/models';
import { PencilIcon, PlusIcon, TrashIcon } from '@heroicons/react/24/outline';
import { InertiaLink } from '@inertiajs/inertia-react';
import DestructivePopover from '@/components/destructive-popover';
import { LinkButton } from '@/components/buttons';
import LiveIndicator from '@/components/live-indicator';
import { Div } from '@/components/semantic';
import useIsStaff from '@/hooks/use-is-staff';

interface Props {
  liveClasses: LiveClass[];
}

export default function ListLiveClasses({ liveClasses }: Props) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const isStaff = useIsStaff();
  const deleteForm = useWebForm({});

  async function deleteItem(obj: LiveClass) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('live-classes.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['liveClasses'] });
  }

  const headers: TableHeader<LiveClass>[] = [
    { label: 'Title', value: 'title' },
    {
      label: 'Class Target',
      render: (row) => row.liveable?.title ?? '-',
    },
    {
      label: 'Teacher',
      render: (row) => row.teacher?.full_name ?? '-',
    },
    {
      label: 'Meet Link',
      render: (row) => (
        <a href={row.meet_url} target="_blank" rel="noreferrer">
          Open
        </a>
      ),
    },
    {
      label: 'Active',
      render: (row) => (
        <Div>{row.is_active ? <LiveIndicator ml={2} /> : 'No'}</Div>
      ),
    },
    {
      label: 'Actions',
      render: (row) => (
        <HStack>
          {isStaff && (
            <>
              <IconButton
                aria-label={'Edit Live Class'}
                icon={<Icon as={PencilIcon} />}
                as={InertiaLink}
                href={instRoute('live-classes.edit', [row.id])}
                variant={'ghost'}
                colorScheme={'brand'}
              />
              <DestructivePopover
                label={'Delete this live class'}
                onConfirm={() => deleteItem(row)}
                isLoading={deleteForm.processing}
              >
                <IconButton
                  aria-label={'Delete live class'}
                  icon={<Icon as={TrashIcon} />}
                  variant={'ghost'}
                  colorScheme={'red'}
                />
              </DestructivePopover>
            </>
          )}
          <LinkButton
            as={'a'}
            href={instRoute('live-classes.join', [row.id])}
            variant={'link'}
            title="Join Class"
            target="_blank"
          />
        </HStack>
      ),
    },
  ];
  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Live Classes"
          rightElement={
            isStaff && (
              <LinkButton
                href={instRoute('live-classes.create')}
                variant={'solid'}
                title="New Live Class"
                leftIcon={<Icon as={PlusIcon} />}
              />
            )
          }
        />
        <SlabBody>
          <DataTable
            headers={headers}
            data={liveClasses}
            keyExtractor={(row) => row.id}
            hideSearchField={true}
            scroll={true}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
