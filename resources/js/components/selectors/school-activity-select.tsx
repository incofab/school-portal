import React from 'react';
import { SchoolActivity } from '@/types/models';
import { Props } from 'react-select';
import SingleQuerySelect from '../dropdown-select/single-query-select';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface MyProps {
  selectValue?: number | string;
  schoolActivities?: SchoolActivity[];
}

export default function SchoolActivitySelect({
  selectValue,
  schoolActivities,
  ...props
}: MyProps & Props) {
  const { instRoute } = useInstitutionRoute();
  return (
    <SingleQuerySelect
      {...props}
      selectValue={selectValue}
      dataList={schoolActivities}
      searchUrl={instRoute('school-activities.search')}
      label={'title'}
    />
  );
}
