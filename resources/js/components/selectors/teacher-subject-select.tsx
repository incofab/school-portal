import React from 'react';
import { CourseTeacher } from '@/types/models';
import MySelect from '../dropdown-select/my-select';

interface Props {
  teacherCourses: CourseTeacher[];
  selectValue?: number;
}

export default function TeacherSubjectSelect({
  teacherCourses,
  selectValue,
  ...props
}: Props) { 
  return (
    <MySelect
      {...props}
      selectValue={selectValue}
      getOptions={() =>
        teacherCourses.map((item) => ({
          label: `${item.course?.title} - ${item.classification?.title}`,
          value: item.id,
        }))
      }
    />
  );
}
