import React, { useState } from 'react';
import { Classification } from '@/types/models';
import { Props } from 'react-select';
import SingleQuerySelect from '../dropdown-select/single-query-select';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface MyProps {
  selectValue?: number | string;
  classGroupId?: number | string;
  classifications?: Classification[];
}

export default function ClassificationSelect({
  selectValue,
  classifications,
  classGroupId,
  ...props
}: MyProps & Props) {
  const { instRoute } = useInstitutionRoute();
  function dataFilter(data: Classification[]) {
    // return data; // Todo: Need to find a way to re-render when class group changes
    return classGroupId
      ? data?.filter(
          (classification) =>
            classification.classification_group_id == classGroupId
        )
      : data;
  }
  return (
    <SingleQuerySelect
      {...props}
      selectValue={selectValue}
      dataList={dataFilter(classifications ?? [])}
      dataFilter={dataFilter}
      searchUrl={instRoute('classifications.search')}
      label={'title'}
      refreshKey={String(classGroupId)}
    />
  );
}
