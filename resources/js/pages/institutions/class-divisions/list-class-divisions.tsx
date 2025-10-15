import React from 'react';
import { ClassDivision, Classification } from '@/types/models';
import {
  HStack,
  IconButton,
  Icon,
  Tag,
  TagLabel,
  TagCloseButton,
  Wrap,
  Tooltip,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import { PencilIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { BrandButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { TrashIcon, PlusIcon } from '@heroicons/react/24/solid';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import DestructivePopover from '@/components/destructive-popover';
import useIsAdmin from '@/hooks/use-is-admin';

import { useModalValueToggle } from '@/hooks/use-modal-toggle';
import CreateEditClassDivisionModal from '@/components/modals/create-edit-class-division-modal';
import AddClassificationsToClassDivisionModal from '@/components/modals/add-classifications-to-class-division-modal';

interface Props {
  classdivisions: PaginationResponse<ClassDivision>;
}

export default function ListClassDivision({ classdivisions }: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const isAdmin = useIsAdmin();
  const createEditModal = useModalValueToggle<ClassDivision | undefined>();
  const addClassificationsModal = useModalValueToggle<ClassDivision>();

  async function deleteItem(obj: ClassDivision) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('class-divisions.destroy', [obj.id]))
    );
    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.reload();
  }

  async function removeClassification(
    classDivision: ClassDivision,
    classification: Classification
  ) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(
        instRoute('class-divisions.classifications.destroy', [
          classDivision,
          classification,
        ])
      )
    );
    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.reload();
  }

  const headers: ServerPaginatedTableHeader<ClassDivision>[] = [
    {
      label: 'Title',
      value: 'title',
    },
    {
      label: 'Classifications',
      render: (row) => (
        <Wrap spacing={1}>
          {row.classifications?.map((classification) => (
            <Tag
              size={'sm'}
              key={classification.id}
              borderRadius="full"
              variant="solid"
              colorScheme="brand"
            >
              <TagLabel>{classification.title}</TagLabel>
              <TagCloseButton
                onClick={() => removeClassification(row, classification)}
              />
            </Tag>
          ))}
        </Wrap>
      ),
    },
    ...(isAdmin
      ? [
          {
            label: 'Action',
            render: (row: ClassDivision) => (
              <HStack spacing={3}>
                <IconButton
                  aria-label={'Edit Class Division'}
                  icon={<Icon as={PencilIcon} />}
                  onClick={() => createEditModal.open(row)}
                  variant={'ghost'}
                  colorScheme={'brand'}
                />
                <Tooltip label="Link Classes to this Division">
                  <IconButton
                    aria-label={'Add Classifications'}
                    icon={<Icon as={PlusIcon} />}
                    onClick={() => addClassificationsModal.open(row)}
                    variant={'ghost'}
                    colorScheme={'green'}
                  />
                </Tooltip>

                <DestructivePopover
                  label={'Delete this division'}
                  onConfirm={() => deleteItem(row)}
                  isLoading={deleteForm.processing}
                >
                  <IconButton
                    aria-label={'Delete Class Division'}
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
          title="List Class Divisions"
          rightElement={
            <HStack>
              <BrandButton
                onClick={() => createEditModal.open(undefined)}
                title={'New'}
              />
            </HStack>
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={classdivisions.data}
            keyExtractor={(row) => row.id}
            paginator={classdivisions}
          />
        </SlabBody>
      </Slab>
      {(createEditModal.state || createEditModal.state === undefined) && (
        <CreateEditClassDivisionModal
          isOpen={createEditModal.isOpen}
          onClose={createEditModal.close}
          onSuccess={() => Inertia.reload()}
          classDivision={createEditModal.state}
        />
      )}
      {addClassificationsModal.state && (
        <AddClassificationsToClassDivisionModal
          classDivision={addClassificationsModal.state}
          {...addClassificationsModal.props}
          onSuccess={() => Inertia.reload()}
        />
      )}
    </DashboardLayout>
  );
}
