import React from 'react';
import { ClassificationGroup } from '@/types/models';
import { MultiValue, Props } from 'react-select';
import SingleQuerySelect from '../dropdown-select/single-query-select';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { SelectOptionType } from '@/types/types';

interface MyProps {
  selectValue?:
    | string
    | number
    | SelectOptionType<number>
    | MultiValue<SelectOptionType<number>>
    | null;
  classificationGroups: ClassificationGroup[];
}

export default function ClassificationGroupSelect({
  selectValue,
  classificationGroups,
  ...props
}: MyProps & Props) {
  const { instRoute } = useInstitutionRoute();
  return (
    <SingleQuerySelect
      {...props}
      selectValue={selectValue}
      dataList={classificationGroups}
      searchUrl={instRoute('classification-groups.search')}
      label={'title'}
    />
  );
}
