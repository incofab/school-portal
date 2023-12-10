import React from 'react';
import { ClassificationGroup } from '@/types/models';
import { HStack, IconButton, Icon, Text, Button } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import { PencilIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { BrandButton, LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { InertiaLink } from '@inertiajs/inertia-react';
import { TrashIcon } from '@heroicons/react/24/solid';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import DestructivePopover from '@/components/destructive-popover';
import useIsAdmin from '@/hooks/use-is-admin';
import SelectClassGroupModal from '@/components/modals/select-class-group-modal';
import useModalToggle, { useModalValueToggle } from '@/hooks/use-modal-toggle';
import SetResumptionDateModal from '@/components/modals/set-resumption-date-modal';

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
  const classGroupModal =
    useModalValueToggle<[ClassificationGroup, string, string]>();
  const setResumptionDateModalToggle = useModalToggle();

  async function deleteItem(obj: ClassificationGroup) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('classification-groups.destroy', [obj.id]))
    );
    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.reload();
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
                <Button
                  variant={'link'}
                  colorScheme={'brand'}
                  onClick={() =>
                    classGroupModal.open([
                      row,
                      'Select Destination Class',
                      'Intended Destination Class',
                    ])
                  }
                >
                  Promote Student
                </Button>
              </HStack>
            ),
          },
        ]
      : []),
  ];

  return (
    <DashboardLayout>
      <div>
        <HStack align={'stretch'} my={2}>
          <BrandButton
            title="Set Resumption Date"
            onClick={setResumptionDateModalToggle.open}
          />
        </HStack>
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
        {classGroupModal.state && (
          <SelectClassGroupModal
            {...classGroupModal.props}
            classificationGroups={classificationgroups.data}
            onSuccess={(classificationgroupId) =>
              Inertia.visit(
                instRoute('classification-groups.promote-students.create', [
                  classGroupModal.state![0],
                  classificationgroupId,
                ])
              )
            }
            headerTitle={classGroupModal.state[1]}
            label={classGroupModal.state[2]}
          />
        )}
        <SetResumptionDateModal
          {...setResumptionDateModalToggle.props}
          classificationGroups={classificationgroups.data}
          onSuccess={() => {}}
        />
      </div>
    </DashboardLayout>
  );
}
