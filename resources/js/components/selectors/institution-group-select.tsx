import React from 'react';
import { InstitutionGroup } from '@/types/models';
import { Props } from 'react-select';
import SingleQuerySelect from '../dropdown-select/single-query-select';
import route from '@/util/route';

interface MyProps {
  selectValue?: number | string;
  institutionGroups: InstitutionGroup[];
}

export default function InstitutionGroupSelect({
  selectValue,
  institutionGroups,
  ...props
}: MyProps & Props) {
  return (
    <SingleQuerySelect
      {...props}
      selectValue={selectValue}
      dataList={institutionGroups}
      searchUrl={route('managers.institution-groups.search')}
      label={'name'}
    />
  );
}
