import React from 'react';
import { PayrollSummary } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import { IconButton, Icon, HStack, Text } from '@chakra-ui/react';
import { CloudArrowDownIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import DashboardLayout from '@/layout/dashboard-layout';
import { BrandButton, LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import useModalToggle from '@/hooks/use-modal-toggle';
import { InertiaLink } from '@inertiajs/inertia-react';
import { formatAsCurrency, ucFirst } from '@/util/util';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import CreatePayrollSummaryModal from '@/components/modals/payrolls/create-payroll-summary-modal';

interface Props {
  payrollSummaries: PaginationResponse<PayrollSummary>;
}

export default function ListPayrollSummaries({ payrollSummaries }: Props) {
  const { instRoute } = useInstitutionRoute();
  const userFilterToggle = useModalToggle();
  const { handleResponseToast } = useMyToast();
  const createModal = useModalToggle();

  const webForm = useWebForm({
    payroll_summary_id: '' as number | string,
  });

  async function generatePayroll(payrollSummary: PayrollSummary) {
    if (webForm.processing) {
      return;
    }
    if (!window.confirm('Evaluate this payroll?')) {
      return;
    }
    webForm.setValue('payroll_summary_id', payrollSummary.id);
    const res = await webForm.submit((data, web) =>
      web.post(
        instRoute('payroll-summaries.generate-payroll', [payrollSummary.id]),
        data
      )
    );

    if (!handleResponseToast(res)) {
      return;
    }

    Inertia.reload({ only: ['payrollSummaries'] });
  }

  function downloadPayroll(payrollSummary: PayrollSummary) {
    if (!window.confirm('Download payroll records?')) {
      return;
    }
    window.location.href = instRoute('payroll-summaries.download', [
      payrollSummary.id,
    ]);
  }

  const headers: ServerPaginatedTableHeader<PayrollSummary>[] = [
    {
      label: 'Month-Year',
      render: (row) => (
        <Text whiteSpace={'nowrap'} fontWeight={'semibold'}>
          {ucFirst(row.month) + ', ' + row.year}
        </Text>
      ),
    },
    {
      label: 'Amount Paid',
      render: (row) => (
        <Text whiteSpace={'nowrap'} fontWeight={'semibold'}>
          {formatAsCurrency(row.amount)}
        </Text>
      ),
    },
    {
      label: 'Bonuses',
      render: (row) => (
        <Text whiteSpace={'nowrap'} color={'green.600'}>
          {formatAsCurrency(row.total_bonuses)}
        </Text>
      ),
    },
    {
      label: 'Deductions',
      render: (row) => (
        <Text whiteSpace={'nowrap'} color={'red.600'}>
          {formatAsCurrency(row.total_deduction)}
        </Text>
      ),
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack>
          <LinkButton
            title={'Bonuses/Deductions'}
            variant={'link'}
            href={instRoute('payroll-summaries.payroll-adjustments.index', [
              row.id,
            ])}
          />
          <IconButton
            aria-label={'Download records'}
            icon={<Icon as={CloudArrowDownIcon} />}
            variant={'ghost'}
            colorScheme={'brand'}
            onClick={() => downloadPayroll(row)}
          />
          <LinkButton
            title={'View'}
            variant={'link'}
            href={instRoute('payroll-summaries.show', [row.id])}
          />
          {Number(row.payrolls_count) <= 0 && (
            <BrandButton
              title={'Process Payroll'}
              onClick={() => generatePayroll(row)}
              isLoading={
                webForm.processing && webForm.data.payroll_summary_id === row.id
              }
            />
          )}
        </HStack>
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title={`Staff Payment Summary`}
          rightElement={
            <HStack>
              <BrandButton title={'Start Payroll'} onClick={createModal.open} />
            </HStack>
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={payrollSummaries.data}
            keyExtractor={(row) => row.id}
            // validFilters={['role']}
            paginator={payrollSummaries}
            onFilterButtonClick={userFilterToggle.open}
          />
        </SlabBody>
      </Slab>
      <CreatePayrollSummaryModal
        {...createModal.props}
        onSuccess={() => Inertia.reload()}
      />
    </DashboardLayout>
  );
}
