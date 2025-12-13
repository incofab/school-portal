import React from 'react';
import { Bank } from '@/types/models';
import { Props } from 'react-select';
import SingleQuerySelect from '../dropdown-select/single-query-select';
import { SelectValue } from '@/types/types';
import route from '@/util/route';

interface MyProps {
  selectValue?: SelectValue;
  banks?: Bank[];

  valueKey?: string;
}

export default function BankSelect({
  selectValue,
  banks,
  valueKey,
  ...props
}: MyProps & Props) {
  return (
    <SingleQuerySelect
      {...props}
      selectValue={selectValue}
      dataList={banks}
      searchUrl={route('banks.search')}
      label={'bank_name'}
      valueKey={valueKey ?? 'id'}
    />
  );
}
