import React from 'react';
import { Fee } from '@/types/models';
import { Props } from 'react-select';
import SingleQuerySelect from '../dropdown-select/single-query-select';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface MyProps {
  selectValue?: number | string;
  fees?: Fee[];
}

export default function FeeSelect({
  selectValue,
  fees,
  ...props
}: MyProps & Props) {
  const { instRoute } = useInstitutionRoute();
  return (
    <SingleQuerySelect
      {...props}
      selectValue={selectValue}
      dataList={fees}
      searchUrl={instRoute('fees.search')}
      label={'title'}
    />
  );
}
