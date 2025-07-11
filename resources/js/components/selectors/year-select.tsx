import React from 'react';
import route from '@/util/route';
import { Props } from 'react-select';
import SingleQuerySelect from '../dropdown-select/single-query-select';

interface MyProps {
  selectValue?: number | string;
}

export default function YearSelect({ selectValue, ...props }: MyProps & Props) {
    
  const currentYear = new Date().getFullYear();
  const startYear = 2023;
  const generatedYears = [];

  for (let year = startYear; year <= currentYear; year++) {
    generatedYears.push({
      label: year,
      id: year,
    });
  }

  return (
    <SingleQuerySelect
      {...props}
      selectValue={selectValue}
      dataList={generatedYears}
      searchUrl={route('adjustment-types.search')}
      label="label"
    />
  );
}
