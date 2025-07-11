import React, { useState } from 'react';
import { Assessment, CourseTeacher, Expense, ExpenseCategory } from '@/types/models';
import { HStack, IconButton, Icon, VStack, Divider } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import { CloudArrowDownIcon, EyeIcon, PencilIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { BrandButton, LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { InertiaLink } from '@inertiajs/inertia-react';
import {
    CheckBadgeIcon,
    PaperAirplaneIcon,
    TrashIcon,
} from '@heroicons/react/24/solid';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import DestructivePopover from '@/components/destructive-popover';
import useIsAdmin from '@/hooks/use-is-admin';
import DateTimeDisplay from '@/components/date-time-display';
import { dateTimeFormat, formatAsCurrency } from '@/util/util';
import TransferEventResultModal from '@/components/modals/transfer-event-result-modal';
import useModalToggle, { useModalValueToggle } from '@/hooks/use-modal-toggle';
import useIsStaff from '@/hooks/use-is-staff';
import CreateEditExpenseCategoryFundModal from '@/components/modals/create-edit-expense-category-modal';
import ExpenseTableFilters from '@/components/table-filters/expense-table-filters';
import ViewExpenseModal from '@/components/modals/view-expense-modal';
import { LabelText } from '@/components/result-helper-components';

interface Props {
    expenses: PaginationResponse<Expense>;
    expense_count: number;
    expense_total: number;
}

export default function ListExpenses({ expenses, expense_count, expense_total }: Props) {
    const { instRoute } = useInstitutionRoute();
    const expenseFiltersModalToggle = useModalToggle();
    const viewExpenseModalToggle = useModalToggle();
    const [expense, setExpense] = useState<Expense>();
    const deleteForm = useWebForm({});
    const { handleResponseToast } = useMyToast();
    const isAdmin = useIsAdmin();
    const isStaff = useIsStaff();

    async function deleteItem(obj: Expense) {
        const res = await deleteForm.submit((data, web) =>
          web.delete(instRoute('expenses.destroy', [obj.id]))
        );
        handleResponseToast(res);
        Inertia.reload({ only: ['expenses'] });
    }

    function openModal(expense?: Expense) {
        setExpense(expense);
        viewExpenseModalToggle.open();
    }

    const headers: ServerPaginatedTableHeader<Expense>[] = [
        {
            label: 'Title',
            value: 'title',
            sortKey: 'title'
        },
        {
            label: 'Description',
            value: 'description',
        },
        {
            label: 'Amount',
            value: 'amount',
            sortKey: 'amount'
        },
        {
            label: 'Term',
            value: 'term',
        },
        {
            label: 'Date',
            value: 'expense_date',
            render: (row) => <DateTimeDisplay dateTime={row.expense_date} />,
            sortKey: 'expenseDate'
        },
        {
            label: 'Action',
            render: (row: Expense) => (
                <HStack>
                    <IconButton
                        aria-label={'View Expense'}
                        icon={<Icon as={EyeIcon} />}
                        variant={'ghost'}
                        colorScheme={'brand'}
                        onClick={() => openModal(row)}
                    />

                    <DestructivePopover
                        label={'Delete this category'}
                        onConfirm={() => deleteItem(row)}
                        isLoading={deleteForm.processing}
                    >
                        <IconButton
                            aria-label={'Delete category'}
                            icon={<Icon as={TrashIcon} />}
                            variant={'ghost'}
                            colorScheme={'red'}
                        />
                    </DestructivePopover>
                </HStack>
            ),
        },
    ];


    return (
        <DashboardLayout>
            <Slab>
                <SlabHeading
                    title="List of Expenses"
                    rightElement={
                        <HStack>

                        
                        <LinkButton
                            href={instRoute('expenses.create')}
                            title="New Expense"
                        />
                        </HStack>
                    }
                />
                <SlabBody>
                    <VStack align={'stretch'}>
                                <LabelText label="Num. of Expenses" text={expense_count} />
                                <LabelText
                                  label="Total Expenses"
                                  text={formatAsCurrency(expense_total)}
                                />
                              </VStack>
                              <Divider my={3} />

                    <ServerPaginatedTable
                        scroll={true}
                        headers={headers}
                        data={expenses.data}
                        keyExtractor={(row) => row.id}
                        paginator={expenses}
                        validFilters={['title', 'amount', 'academicSession', 'term', 'expenseDate', 'expenseCategory']}
                        onFilterButtonClick={expenseFiltersModalToggle.open}
                    />
                </SlabBody>
            </Slab>

            <ViewExpenseModal
                expense={expense}
                {...viewExpenseModalToggle.props}
                onSuccess={() => Inertia.reload()}
            />
            <ExpenseTableFilters {...expenseFiltersModalToggle.props} />
        </DashboardLayout>
    );
}
