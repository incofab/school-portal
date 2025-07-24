import React from 'react';
import { SalaryType } from '@/types/models';
import { Props } from 'react-select';
import DataSelect from '../dropdown-select/data-select';

interface MyProps {
  selectValue?: number | string;
  salaryTypes: SalaryType[];
}

export default function SalaryTypeSelect({
  selectValue,
  salaryTypes,
  ...props
}: MyProps & Props) {
  return (
    <DataSelect
      {...props}
      selectValue={selectValue}
      data={{
        main: salaryTypes,
        label: 'title',
        value: 'id',
      }}
    />
  );
}
