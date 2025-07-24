import React from 'react';
import { PayrollSummary } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import { IconButton, Icon, HStack, Text } from '@chakra-ui/react';
import { EyeIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import DashboardLayout from '@/layout/dashboard-layout';
import { BrandButton, LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import useModalToggle from '@/hooks/use-modal-toggle';
import { InertiaLink } from '@inertiajs/inertia-react';
import { formatAsCurrency } from '@/util/util';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';

interface Props {
  payrollSummaries: PaginationResponse<PayrollSummary>;
}

export default function ListPayrollSummaries({ payrollSummaries }: Props) {
  const { instRoute } = useInstitutionRoute();
  const userFilterToggle = useModalToggle();
  const { handleResponseToast } = useMyToast();

  const webForm = useWebForm({
    payroll_summary_id: '' as number | string,
  });

  async function generatePayroll(payrollSummary: PayrollSummary) {
    if (webForm.processing) {
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

  const headers: ServerPaginatedTableHeader<PayrollSummary>[] = [
    {
      label: 'Month-Year',
      render: (row) => (
        <Text whiteSpace={'nowrap'} fontWeight={'semibold'}>
          {row.month + ', ' + row.year}
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
          <IconButton
            as={InertiaLink}
            aria-label={'Edit user'}
            icon={<Icon as={EyeIcon} />}
            href={instRoute('payroll-summaries.show', [row.id])}
            variant={'ghost'}
            colorScheme={'brand'}
          />
          <LinkButton
            title={'Bonuses/Deductions'}
            href={instRoute('payroll-adjustments.index', [row.id])}
          />
          {Number(row.payrolls_count) <= 0 && (
            <BrandButton
              title={'Generate Payroll'}
              onClick={() => generatePayroll(row)}
            />
          )}
        </HStack>
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title={`Staff Payment Summary`} />
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
    </DashboardLayout>
  );
}
