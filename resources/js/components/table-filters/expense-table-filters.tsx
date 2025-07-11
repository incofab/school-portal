import React, { useState } from 'react';
import useQueryString from '@/hooks/use-query-string';
import BaseTableFilter from './base-table-filter';
import FilterFormControlBox from './filter-form-control-box';
import useSharedProps from '@/hooks/use-shared-props';
import AcademicSessionSelect from '../selectors/academic-session-select';
import EnumSelect from '../dropdown-select/enum-select';
import { TermType } from '@/types/types';
import { Input } from '@chakra-ui/react';
import ExpenseCategorySelect from '../selectors/expense-category-select';

interface Props {
    isOpen: boolean;
    onClose(): void;
}

export default function ExpenseTableFilters({ isOpen, onClose }: Props) {
    const { params } = useQueryString();
    const { currentAcademicSessionId, currentTerm} = useSharedProps();

    const [filters, setFilters] = useState(() => ({
        title: params.title ?? '',
        amount: params.amount ?? '',
        academicSession: params.academicSession ?? '',
        term: params.term ?? '',
        expenseDate: params.expenseDate ??'',
        expenseCategory: params.expenseCategory ?? '',
    }));

    return (
        <BaseTableFilter filters={filters} isOpen={isOpen} onClose={onClose}>
            <FilterFormControlBox title="Title">
                <Input
                    type="text"
                    onChange={(e: any) => setFilters({ ...filters, title: e.currentTarget.value})}
                    value={filters.title}
                />
            </FilterFormControlBox>
            
            <FilterFormControlBox title="Amount">
                <Input
                    type="number"
                    onChange={(e: any) => setFilters({ ...filters, amount: e.currentTarget.value })}
                    value={filters.amount}
                />
            </FilterFormControlBox> 

            <FilterFormControlBox title="Academic Session">
                <AcademicSessionSelect
                    selectValue={filters.academicSession}
                    onChange={(e: any) =>
                        setFilters({ ...filters, academicSession: e.value })
                    }
                />
            </FilterFormControlBox>

            <FilterFormControlBox title="Term">
                <EnumSelect
                    selectValue={filters.term}
                    enumData={TermType}
                    onChange={(e: any) => setFilters({ ...filters, term: e.value })}
                />
            </FilterFormControlBox>
            
            <FilterFormControlBox title="Expense Date">
                <Input
                    type="date"
                    onChange={(e: any) => setFilters({ ...filters, expenseDate: e.currentTarget.value})}
                    value={filters.expenseDate}
                />
            </FilterFormControlBox> 
           
            <FilterFormControlBox title="Expense Category">
                <ExpenseCategorySelect
                    selectValue={filters.expenseCategory}
                    onChange={(e: any) =>
                        setFilters({ ...filters, expenseCategory: e.value })
                    }
                />
            </FilterFormControlBox>
        </BaseTableFilter>
    );
}
