import React from 'react';
import {
  PayrollAdjustmentType,
  PayrollAdjustment,
  PayrollSummary,
} from '@/types/models';
import { PaginationResponse, TransactionType } from '@/types/types';
import { IconButton, Icon, HStack, Text, Badge } from '@chakra-ui/react';
import { PencilIcon, TrashIcon } from '@heroicons/react/24/outline';
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
import DisplayUserFullname from '@/domain/institutions/users/display-user-fullname';
import { formatAsCurrency, ucFirst } from '@/util/util';
import CreateEditPayrollAdjustmentModal from '@/components/modals/payrolls/create-edit-payroll-adjustment-modal';
import { PlusIcon } from '@heroicons/react/24/solid';

interface Props {
  payrollSummary: PayrollSummary;
  payrollAdjustments: PaginationResponse<PayrollAdjustment>;
  payrollAdjustmentTypes: PayrollAdjustmentType[];
}

export default function ListPayrollAdjustments({
  payrollSummary,
  payrollAdjustments,
  payrollAdjustmentTypes,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const userFilterToggle = useModalToggle();

  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const editPayrollAdjustmentModal = useModalValueToggle<
    PayrollAdjustment | undefined
  >();

  async function deleteItem(obj: PayrollAdjustment) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('payroll-adjustments.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['payrollAdjustments'] });
  }

  const headers: ServerPaginatedTableHeader<PayrollAdjustment>[] = [
    {
      label: 'Staff Name',
      render: (row) => (
        <DisplayUserFullname user={row.institution_user?.user} />
      ),
    },
    {
      label: 'Title',
      render: (row) => (
        <Text whiteSpace={'nowrap'}>{row.payroll_adjustment_type?.title}</Text>
      ),
    },
    {
      label: 'Month/Year',
      render: (row) => (
        <Text whiteSpace={'nowrap'}>
          {`${ucFirst(row.payroll_summary?.month)}, ${
            row.payroll_summary?.year
          }`}
        </Text>
      ),
    },
    {
      label: 'Amount',
      render: (row) => (
        <Badge
          textShadow={`1px 1px 2px ${
            row.payroll_adjustment_type?.type === TransactionType.Credit
              ? 'rgba(0, 255, 0, 0.7)'
              : 'rgba(255, 0, 0, 0.7)'
          }`}
          color={
            row.payroll_adjustment_type?.type === TransactionType.Credit
              ? 'green.600'
              : 'red.600'
          }
        >
          {formatAsCurrency(row.amount)}
        </Badge>
      ),
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack>
          <IconButton
            aria-label={'Edit salary adjustment'}
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
              aria-label={'Delete salary'}
              icon={<Icon as={TrashIcon} />}
              variant={'ghost'}
              colorScheme={'red'}
            />
          </DestructivePopover>
        </HStack>
      ),
    },
  ];

  function openModal(payrollAdjustment?: PayrollAdjustment) {
    editPayrollAdjustmentModal.open(
      payrollAdjustment ?? ({} as PayrollAdjustment)
    );
  }

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title={`Bonuses/Deductions - ${ucFirst(payrollSummary.month)}, ${
            payrollSummary.year
          }`}
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
            data={payrollAdjustments.data}
            keyExtractor={(row) => row.id}
            validFilters={['role']}
            paginator={payrollAdjustments}
            onFilterButtonClick={userFilterToggle.open}
          />
        </SlabBody>
      </Slab>

      {editPayrollAdjustmentModal.state != undefined && (
        <CreateEditPayrollAdjustmentModal
          payrollSummary={payrollSummary}
          payrollAdjustment={editPayrollAdjustmentModal.state ?? null}
          payrollAdjustmentTypes={payrollAdjustmentTypes}
          {...editPayrollAdjustmentModal.props}
          onSuccess={() => Inertia.reload()}
        />
      )}
    </DashboardLayout>
  );
}
