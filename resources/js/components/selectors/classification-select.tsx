import React from 'react';
import { Classification } from '@/types/models';
import { Props } from 'react-select';
import SingleQuerySelect from '../dropdown-select/single-query-select';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface MyProps {
  selectValue?: number | string;
  classifications?: Classification[];
}

export default function ClassificationSelect({
  selectValue,
  classifications,
  ...props
}: MyProps & Props) {
  const { instRoute } = useInstitutionRoute();
  return (
    <SingleQuerySelect
      {...props}
      selectValue={selectValue}
      dataList={classifications}
      searchUrl={instRoute('classifications.search')}
      label={'title'}
    />
  );
}
