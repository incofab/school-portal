import React from 'react';
import { Props } from 'react-select';
import SingleQuerySelect from '../dropdown-select/single-query-select';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useSharedProps from '@/hooks/use-shared-props';
import { Course } from '@/types/models';

interface MyProps {
  selectValue?: number | string;
  courses?: Course[];
}

export default function CourseSelect({
  selectValue,
  courses,
  ...props
}: MyProps & Props) {
  const { instRoute } = useInstitutionRoute();
  const { currentInstitution } = useSharedProps();
  return (
    <SingleQuerySelect
      {...props}
      selectValue={selectValue}
      dataList={courses ?? currentInstitution.courses}
      searchUrl={instRoute('courses.search')}
      label={'title'}
    />
  );
}
