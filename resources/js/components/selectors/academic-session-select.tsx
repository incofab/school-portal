import React from 'react';
import route from '@/util/route';
import { Props } from 'react-select';
import SingleQuerySelect from '../dropdown-select/single-query-select';
import { SelectValue } from '@/types/types';
import useSharedProps from '@/hooks/use-shared-props';

interface MyProps {
  selectValue?: SelectValue;
}

export default function AcademicSessionSelect({
  selectValue,
  ...props
}: MyProps & Props) {
  const { academicSessions } = useSharedProps();
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
