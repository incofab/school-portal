import React from 'react';
import route from '@/util/route';
import { AcademicSession } from '@/types/models';
import { Props } from 'react-select';
import SingleQuerySelect from '../dropdown-select/single-query-select';

interface MyProps {
  selectValue?: number | string;
  academicSessions?: AcademicSession[];
}

export default function AcademicSessionSelect({
  selectValue,
  academicSessions,
  ...props
}: MyProps & Props) {
  return (
    <SingleQuerySelect
      {...props}
      selectValue={selectValue}
      dataList={academicSessions}
      searchUrl={route('academic-sessions.search')}
      label={'title'}
    />
  );
}
