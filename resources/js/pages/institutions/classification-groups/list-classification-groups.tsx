import React from 'react';
import { ClassificationGroup } from '@/types/models';
import { HStack, IconButton, Icon, Text } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import { PencilIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { InertiaLink } from '@inertiajs/inertia-react';
import { TrashIcon } from '@heroicons/react/24/solid';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import DestructivePopover from '@/components/destructive-popover';
import useIsAdmin from '@/hooks/use-is-admin';

interface Props {
  classificationgroups: PaginationResponse<ClassificationGroup>;
}

export default function ListClassificationGroup({
  classificationgroups,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const isAdmin = useIsAdmin();

  async function deleteItem(obj: ClassificationGroup) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('classification-groups.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['classification-groups'] });
  }

  const headers: ServerPaginatedTableHeader<ClassificationGroup>[] = [
    {
      label: 'Title',
      value: 'title',
    },
    {
      label: 'Num of Classes',
      value: 'classifications_count',
      render: (row) => (
        <Text
          as={InertiaLink}
          href={instRoute('classifications.index', {
            classification_group: row.id,
          })}
        >
          {row.classifications_count}
        </Text>
      ),
    },
    ...(isAdmin
      ? [
          {
            label: 'Action',
            render: (row: ClassificationGroup) => (
              <HStack spacing={3}>
                <IconButton
                  aria-label={'Edit Class Group'}
                  icon={<Icon as={PencilIcon} />}
                  as={InertiaLink}
                  href={instRoute('classification-groups.edit', [row.id])}
                  variant={'ghost'}
                  colorScheme={'brand'}
                />

                <DestructivePopover
                  label={'Delete this group'}
                  onConfirm={() => deleteItem(row)}
                  isLoading={deleteForm.processing}
                >
                  <IconButton
                    aria-label={'Delete Class Group'}
                    icon={<Icon as={TrashIcon} />}
                    variant={'ghost'}
                    colorScheme={'red'}
                  />
                </DestructivePopover>
              </HStack>
            ),
          },
        ]
      : []),
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="List Class Groups"
          rightElement={
            <HStack>
              <LinkButton
                href={instRoute('classification-groups.create')}
                title={'New'}
              />
            </HStack>
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={classificationgroups.data}
            keyExtractor={(row) => row.id}
            paginator={classificationgroups}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
