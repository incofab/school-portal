import React from 'react';
import { ExpenseCategory } from '@/types/models';
import { Props } from 'react-select';
import DataSelect from '../dropdown-select/data-select';

interface MyProps {
  selectValue?: number | string;
  expenseCategories: ExpenseCategory[];
}

export default function ExpenseCategorySelect({
  selectValue,
  expenseCategories,
  ...props
}: MyProps & Props) {
  return (
    <DataSelect
      {...props}
      selectValue={selectValue}
      data={{
        main: expenseCategories,
        label: 'title',
        value: 'id',
      }}
    />
  );
}
