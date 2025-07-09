import React from 'react';
import route from '@/util/route';
import { AdjustmentType } from '@/types/models';
import { Props } from 'react-select';
import SingleQuerySelect from '../dropdown-select/single-query-select';

interface MyProps {
  selectValue?: number | string;
  adjustmentTypes?: AdjustmentType[];
}

export default function AdjustmentTypeSelect({
  selectValue,
  adjustmentTypes,
  ...props
}: MyProps & Props) {
  return (
    <SingleQuerySelect
      {...props}
      selectValue={selectValue}
      dataList={adjustmentTypes}
      searchUrl={route('adjustment-types.search')}
      label={'title'}
    />
  );
} 
