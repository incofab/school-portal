import React from 'react';
import { PayrollAdjustmentType } from '@/types/models';
import { Props } from 'react-select';
import DataSelect from '../dropdown-select/data-select';

interface MyProps {
  selectValue?: number | string;
  payrollAdjustmentTypes: PayrollAdjustmentType[];
}

export default function PayrollAdjustmentTypeSelect({
  selectValue,
  payrollAdjustmentTypes,
  ...props
}: MyProps & Props) {
  return (
    <DataSelect
      {...props}
      selectValue={selectValue}
      data={{
        main: payrollAdjustmentTypes,
        label: 'title',
        value: 'id',
      }}
    />
  );
}
