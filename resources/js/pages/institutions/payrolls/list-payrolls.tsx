import React from 'react';
import { Payroll, PayrollSummary, SalaryAdjustment } from '@/types/models';
import { PaginationResponse, TransactionType } from '@/types/types';
import {
    IconButton,
    Icon,
    HStack,
    Text,
    Badge,
} from '@chakra-ui/react';
import { PencilIcon, TrashIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import DashboardLayout from '@/layout/dashboard-layout';
import { BrandButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import ServerPaginatedTable, {
    ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import useModalToggle from '@/hooks/use-modal-toggle';
import { InertiaLink } from '@inertiajs/inertia-react';
import DisplayUserFullname from '@/domain/institutions/users/display-user-fullname';
import { formatAsCurrency } from '@/util/util';
import GeneratePayrollModal from '@/components/modals/generate-payroll-modal';
import { EyeIcon } from '@heroicons/react/24/solid';

interface Props {
    payrolls: PaginationResponse<Payroll>;
    payrollSummary?: PayrollSummary;
}

export default function ListPayrolls({ payrolls, payrollSummary }: Props) {

    const { instRoute } = useInstitutionRoute();
    const userFilterToggle = useModalToggle();
    const generatePayrollModal = useModalToggle();

    const headers: ServerPaginatedTableHeader<Payroll>[] = [
        {
            label: 'Staff Name',
            render: (row) => <DisplayUserFullname user={row.institution_user?.user} />,
        },
        {
            label: 'Net Income',
            render: (row) => (
                <Badge textShadow={'rgba(0, 255, 0, 0.7)'} color={'green.600'}>
                    {formatAsCurrency(row.net_amount)}
                </Badge>
            ),
        },
        {
            label: 'Salary',
            render: (row) => <Text whiteSpace={'nowrap'} fontWeight={'semibold'}>{formatAsCurrency(row.income)}</Text>,
        },
        {
            label: 'Bonuses',
            render: (row) => <Text whiteSpace={'nowrap'} color={'green.600'}>{formatAsCurrency(row.total_bonuses)}</Text>,
        },
        {
            label: 'Deductions',
            render: (row) => <Text whiteSpace={'nowrap'} color={'red.600'}>{formatAsCurrency(row.total_deductions)}</Text>,
        },

        {
            label: 'Action',
            render: (row) => (
                <HStack>
                    {(row.total_bonuses > 0 || row.total_deductions > 0) ?
                        <IconButton
                            as={InertiaLink}
                            aria-label={'View Adjustments'}
                            icon={<Icon as={EyeIcon} />}
                            href={instRoute('salary-adjustments.payroll', [row.id])}
                            variant={'ghost'}
                            colorScheme={'brand'}
                        />
                        : ''}
                </HStack>
            ),
        },
    ];

    return (
        <DashboardLayout>
            <Slab>
                <SlabHeading
                    title={`Staff Payments ${payrollSummary ? ' - ' + payrollSummary.month + ', ' + payrollSummary.year : ''}`}
                    rightElement={
                        <HStack>
                            <BrandButton
                                title='Generate Payroll'
                                onClick={generatePayrollModal.open}
                            />
                        </HStack>
                    }
                />
                <SlabBody>
                    <ServerPaginatedTable
                        scroll={true}
                        headers={headers}
                        data={payrolls.data}
                        keyExtractor={(row) => row.id}
                        // validFilters={['role']}
                        paginator={payrolls}
                        onFilterButtonClick={userFilterToggle.open}
                    />
                </SlabBody>
            </Slab>
            {/* <UsersTableFilters {...userFilterToggle.props} /> */}

            <GeneratePayrollModal
                {...generatePayrollModal.props}
                onSuccess={() => window.location.reload()} />
        </DashboardLayout>
    );

}