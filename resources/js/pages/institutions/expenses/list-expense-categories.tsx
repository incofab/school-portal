import React, { useState } from 'react';
import { Assessment, CourseTeacher, ExpenseCategory } from '@/types/models';
import { HStack, IconButton, Icon } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import { CloudArrowDownIcon, PencilIcon } from '@heroicons/react/24/outline';
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
import { dateTimeFormat } from '@/util/util';
import TransferEventResultModal from '@/components/modals/transfer-event-result-modal';
import useModalToggle, { useModalValueToggle } from '@/hooks/use-modal-toggle';
import useIsStaff from '@/hooks/use-is-staff';
import CreateEditExpenseCategoryFundModal from '@/components/modals/create-edit-expense-category-modal';

interface Props {
    expenseCategories: PaginationResponse<ExpenseCategory>;
}

export default function ListExpenseCategories({ expenseCategories }: Props) {
    const createExpenseCategoryModalToggle = useModalToggle();
    const editExpenseCategoryModalToggle = useModalValueToggle<ExpenseCategory|undefined>();
    const { instRoute } = useInstitutionRoute();
    const deleteForm = useWebForm({});
    const { handleResponseToast } = useMyToast();
    const isAdmin = useIsAdmin();
    const isStaff = useIsStaff();

    async function deleteItem(obj: ExpenseCategory) {
        const res = await deleteForm.submit((data, web) =>
          web.delete(instRoute('expense-categories.destroy', [obj.id]))
        );
        handleResponseToast(res);
        Inertia.reload({ only: ['expenseCategories'] });
    }

    const headers: ServerPaginatedTableHeader<ExpenseCategory>[] = [
        {
            label: 'Title',
            value: 'title',
        },
        {
            label: 'Description',
            value: 'description',
        },
        {
            label: 'Action',
            render: (row: ExpenseCategory) => (
                <HStack>

                    <IconButton
                        aria-label={'Edit category'}
                        icon={<Icon as={PencilIcon} />}
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

    function openModal(expenseCategory?: ExpenseCategory) {
        editExpenseCategoryModalToggle.open(expenseCategory ?? {} as ExpenseCategory);
    }

    return (
        <DashboardLayout>
            <Slab>
                <SlabHeading
                    title="List Expense Categories"
                    rightElement={
                        <BrandButton
                            title="Add Category"
                            onClick={() => openModal()}
                        />
                    }
                />
                <SlabBody>
                    <ServerPaginatedTable
                        scroll={true}
                        headers={headers}
                        data={expenseCategories.data}
                        keyExtractor={(row) => row.id}
                        paginator={expenseCategories}
                        hideSearchField={true}
                    />
                </SlabBody>
            </Slab>
            {editExpenseCategoryModalToggle.state != undefined &&
                <CreateEditExpenseCategoryFundModal
                expenseCategory={editExpenseCategoryModalToggle.state}
                {...editExpenseCategoryModalToggle.props}
                onSuccess={() => Inertia.reload()}
                />
            }
        </DashboardLayout>
    );
}
