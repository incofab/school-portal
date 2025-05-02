import React from 'react';
import { Association } from '@/types/models';
import { MultiValue, Props } from 'react-select';
import SingleQuerySelect from '../dropdown-select/single-query-select';
import { SelectOptionType } from '@/types/types';

interface MyProps {
  selectValue?:
    | string
    | number
    | SelectOptionType<number>
    | MultiValue<SelectOptionType<number>>
    | null;
  associations: Association[];
}

export default function AssociationSelect({
  selectValue,
  associations,
  ...props
}: MyProps & Props) {
  return (
    <SingleQuerySelect
      {...props}
      selectValue={selectValue}
      dataList={associations}
      searchUrl={''}
      label={'title'}
    />
  );
}
