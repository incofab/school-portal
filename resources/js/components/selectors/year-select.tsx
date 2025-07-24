import React from 'react';
import { Props } from 'react-select';
import DataSelect from '../dropdown-select/data-select';
import { SelectOptionType } from '@/types/types';

interface MyProps {
  selectValue?: number | string;
}

export default function YearSelect({ selectValue, ...props }: MyProps & Props) {
  const currentYear = new Date().getFullYear();
  const startYear = 2023;
  const generatedYears = [] as SelectOptionType<number>[];

  for (let year = startYear; year <= currentYear; year++) {
    generatedYears.push({
      label: String(year),
      value: year,
    });
  }

  return (
    <DataSelect
      {...props}
      selectValue={selectValue}
      data={{
        main: generatedYears,
        label: 'label',
        value: 'value',
      }}
    />
  );
}
