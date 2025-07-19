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
import { EyeIcon, PencilIcon, TrashIcon } from '@heroicons/react/24/outline';
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

interface Props {
    payrollSummaries: PaginationResponse<PayrollSummary>;
}

export default function ListPayrollSummaries({ payrollSummaries }: Props) {

    const { instRoute } = useInstitutionRoute();
    const userFilterToggle = useModalToggle();
    const generatePayrollModal = useModalToggle();

    const headers: ServerPaginatedTableHeader<PayrollSummary>[] = [
        {
            label: 'Month-Year',
            render: (row) => <Text whiteSpace={'nowrap'} fontWeight={'semibold'}>{row.month+', '+row.year}</Text>,
        },
        {
            label: 'Amount Paid',
            render: (row) => <Text whiteSpace={'nowrap'} fontWeight={'semibold'}>{formatAsCurrency(row.amount)}</Text>,
        },
        {
            label: 'Bonuses',
            render: (row) => <Text whiteSpace={'nowrap'} color={'green.600'}>{formatAsCurrency(row.total_bonuses)}</Text>,
        },
        {
            label: 'Deductions',
            render: (row) => <Text whiteSpace={'nowrap'} color={'red.600'}>{formatAsCurrency(row.total_deduction)}</Text>,
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
                        data={payrollSummaries.data}
                        keyExtractor={(row) => row.id}
                        // validFilters={['role']}
                        paginator={payrollSummaries}
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