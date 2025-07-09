import React from 'react';
import route from '@/util/route';
import { SalaryType } from '@/types/models';
import { Props } from 'react-select';
import SingleQuerySelect from '../dropdown-select/single-query-select';

interface MyProps {
  selectValue?: number | string;
  salaryTypes?: SalaryType[];
}

export default function SalaryTypeSelect({
  selectValue,
  salaryTypes,
  ...props
}: MyProps & Props) {
  return (
    <SingleQuerySelect
      {...props}
      selectValue={selectValue}
      dataList={salaryTypes}
      searchUrl={route('salary-types.search')}
      label={'title'}
    />
  );
} 
