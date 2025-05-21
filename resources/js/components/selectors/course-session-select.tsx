import React from 'react';
import { CourseSession} from '@/types/models';
import { Props } from 'react-select';
import MySelect from '../dropdown-select/my-select';

interface MyProps {
  selectValue?: number | string;
  courseSessions: CourseSession[];
}

export default function CourseSessionSelect({
  selectValue,
  courseSessions,
  ...props
}: MyProps & Props) {
  return (
    <MySelect
      {...props}
      selectValue={selectValue}
      getOptions={() =>
        courseSessions.map((item) => ({
          label: `${item?.session}`,
          value: item.id,
        }))
      }
    />
  );
} 
