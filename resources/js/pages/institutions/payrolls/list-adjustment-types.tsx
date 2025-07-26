import React from 'react';
import { PayrollAdjustmentType } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import { IconButton, Icon, HStack, Text } from '@chakra-ui/react';
import { PencilIcon, PlusIcon, TrashIcon } from '@heroicons/react/24/outline';
import { Inertia } from '@inertiajs/inertia';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import useWebForm from '@/hooks/use-web-form';
import DestructivePopover from '@/components/destructive-popover';
import DashboardLayout from '@/layout/dashboard-layout';
import { BrandButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import useModalToggle, { useModalValueToggle } from '@/hooks/use-modal-toggle';
import CreateEditAdjustmentTypeModal from '@/components/modals/payrolls/create-edit-adjustment-type-modal';

interface Props {
  payrollAdjustmentTypes: PaginationResponse<PayrollAdjustmentType>;
  parentAdjustmentTypes: PayrollAdjustmentType[];
}

export default function ListStaffTypes({
  payrollAdjustmentTypes,
  parentAdjustmentTypes,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const userFilterToggle = useModalToggle();
  const editAdjustmentTypeModal = useModalValueToggle<
    PayrollAdjustmentType | undefined
  >();

  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

  async function deleteItem(obj: PayrollAdjustmentType) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('payroll-adjustment-types.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload();
  }

  const headers: ServerPaginatedTableHeader<PayrollAdjustmentType>[] = [
    {
      label: 'Title',
      render: (row) => <Text whiteSpace={'nowrap'}>{row.title}</Text>,
    },
    {
      label: 'Type',
      render: (row) => <Text whiteSpace={'nowrap'}>{row.type}</Text>,
    },
    {
      label: 'Percentage',
      render: (row) => (
        <Text whiteSpace={'nowrap'}>
          {row.percentage ? `${row.percentage}% of ${row.parent?.title}` : ''}
        </Text>
      ),
    },
    {
      label: 'Description',
      render: (row) => <Text whiteSpace={'nowrap'}>{row.description}</Text>,
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack>
          <IconButton
            aria-label={'Edit'}
            icon={<Icon as={PencilIcon} />}
            onClick={() => openModal(row)}
            variant={'ghost'}
            colorScheme={'brand'}
          />
          <DestructivePopover
            label={'Do you really want to delete this record? Be careful!!!'}
            onConfirm={() => deleteItem(row)}
            isLoading={deleteForm.processing}
          >
            <IconButton
              aria-label={'Delete adjustment'}
              icon={<Icon as={TrashIcon} />}
              variant={'ghost'}
              colorScheme={'red'}
            />
          </DestructivePopover>
        </HStack>
      ),
    },
  ];

  function openModal(payrollAdjustmentType?: PayrollAdjustmentType) {
    editAdjustmentTypeModal.open(
      payrollAdjustmentType ?? ({} as PayrollAdjustmentType)
    );
  }

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Adjustment Types"
          rightElement={
            <HStack>
              <BrandButton
                leftIcon={<Icon as={PlusIcon} />}
                title="Add New"
                onClick={() => openModal()}
              />
            </HStack>
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={payrollAdjustmentTypes.data}
            keyExtractor={(row) => row.id}
            validFilters={['role']}
            paginator={payrollAdjustmentTypes}
            onFilterButtonClick={userFilterToggle.open}
          />
        </SlabBody>
      </Slab>
      {/* <UsersTableFilters {...userFilterToggle.props} /> */}

      {editAdjustmentTypeModal.state != undefined && (
        <CreateEditAdjustmentTypeModal
          payrollAdjustmentType={editAdjustmentTypeModal.state ?? null}
          payrollAdjustmentTypes={parentAdjustmentTypes}
          {...editAdjustmentTypeModal.props}
          onSuccess={() => Inertia.reload()}
        />
      )}
    </DashboardLayout>
  );
}
