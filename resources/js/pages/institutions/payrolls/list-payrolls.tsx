import React from 'react';
import { Payroll, PayrollSummary } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import { IconButton, Icon, HStack, Text, Badge } from '@chakra-ui/react';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import DashboardLayout from '@/layout/dashboard-layout';
import { BrandButton, LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import useModalToggle from '@/hooks/use-modal-toggle';
import { InertiaLink } from '@inertiajs/inertia-react';
import DisplayUserFullname from '@/domain/institutions/users/display-user-fullname';
import { formatAsCurrency, ucFirst } from '@/util/util';
import { EyeIcon } from '@heroicons/react/24/solid';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';

interface Props {
  payrolls: PaginationResponse<Payroll>;
  payrollSummary: PayrollSummary;
}

export default function ListPayrolls({ payrolls, payrollSummary }: Props) {
  const { instRoute } = useInstitutionRoute();
  const userFilterToggle = useModalToggle();
  const { handleResponseToast } = useMyToast();
  const webForm = useWebForm({});

  async function generatePayroll() {
    if (!window.confirm('Are you sure you want to re-evaluate this payroll?')) {
      return;
    }
    const res = await webForm.submit((data, web) =>
      web.post(
        instRoute('payroll-summaries.generate-payroll', {
          id: payrollSummary.id,
          re_evaluate: true,
        }),
        data
      )
    );

    if (!handleResponseToast(res)) {
      return;
    }

    Inertia.reload({ only: ['payrolls'] });
  }

  const headers: ServerPaginatedTableHeader<Payroll>[] = [
    {
      label: 'Name',
      render: (row) => (
        <DisplayUserFullname user={row.institution_user?.user} />
      ),
    },
    {
      label: 'Net Salary',
      render: (row) => (
        <Badge textShadow={'rgba(0, 255, 0, 0.7)'} color={'green.600'}>
          {formatAsCurrency(row.net_salary)}
        </Badge>
      ),
    },
    {
      label: 'Gross Salary',
      render: (row) => (
        <Text whiteSpace={'nowrap'} fontWeight={'semibold'}>
          {formatAsCurrency(row.gross_salary)}
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
          {formatAsCurrency(row.total_deductions)}
        </Text>
      ),
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack>
          <IconButton
            as={InertiaLink}
            aria-label={'View Adjustments'}
            icon={<Icon as={EyeIcon} />}
            href={instRoute('payrolls.show', [row.id])}
            variant={'ghost'}
            colorScheme={'brand'}
          />
        </HStack>
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title={`Staff Payments - ${ucFirst(payrollSummary.month)}, ${
            payrollSummary.year
          }`}
          rightElement={
            <HStack>
              {payrollSummary.evaluated_at && (
                <BrandButton
                  title={'Re-Evaluate'}
                  onClick={() => generatePayroll()}
                />
              )}
            </HStack>
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={payrolls.data}
            keyExtractor={(row) => row.id}
            paginator={payrolls}
            onFilterButtonClick={userFilterToggle.open}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
