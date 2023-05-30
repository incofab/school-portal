import React from 'react';
import { Course } from '@/types/models';
import { Props } from 'react-select';
import SingleQuerySelect from '../dropdown-select/single-query-select';
import useInstitutionRoute from '@/hooks/use-institution-route';

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
  return (
    <SingleQuerySelect
      {...props}
      selectValue={selectValue}
      dataList={courses}
      searchUrl={instRoute('courses.search')}
      label={'title'}
    />
  );
}
