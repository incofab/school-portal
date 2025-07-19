import React from 'react';
import route from '@/util/route';
import { ExpenseCategory } from '@/types/models';
import { Props } from 'react-select';
import SingleQuerySelect from '../dropdown-select/single-query-select';

interface MyProps {
  selectValue?: number | string;
  expenseCategories?: ExpenseCategory[];
}

export default function ExpenseCategorySelect({
  selectValue,
  expenseCategories,
  ...props
}: MyProps & Props) {
  return (
    <SingleQuerySelect
      {...props}
      selectValue={selectValue}
      dataList={expenseCategories}
      searchUrl={route('expense-categories.search')}
      label={'title'}
    />
  );
} 
