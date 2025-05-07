import React from 'react';
import { CourseTeacher } from '@/types/models';
import MySelect from '../dropdown-select/my-select';

interface Props {
  lessonPlanCourseTeachers: CourseTeacher[];
  selectValue?: number;
} 

export default function LessonPlanCourseTeacherSelect({
  lessonPlanCourseTeachers,
  selectValue,
  ...props
}: Props) {
  return (
    <MySelect
      {...props}
      selectValue={selectValue}
      getOptions={() =>
        lessonPlanCourseTeachers.map((item) => ({
          label: `${item?.classification?.title}  ->  ${item?.user?.full_name}`,
          value: item.id,
        }))
      }
    />
  );
}
