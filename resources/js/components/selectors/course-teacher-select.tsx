import React from 'react';
import { CourseTeacher } from '@/types/models';
import { AsyncProps } from 'react-select/async';
import { GroupBase } from 'react-select/dist/declarations/src/types';
import MyAsyncSelect from './my-async-select';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface Props {
  course?: number | string;
  classification?: number | string;
}

export default function CourseTeacherSelect<
  Option,
  IsMulti extends boolean,
  Group extends GroupBase<Option>
>({
  course,
  classification,
  ...props
}: Props & AsyncProps<Option, IsMulti, Group>) {
  const { instRoute } = useInstitutionRoute();

  const searchParams = {} as { [key: string]: any };

  if (course) {
    searchParams.course = course;
  }

  if (classification) {
    searchParams.classification = classification;
  }

  return (
    <MyAsyncSelect
      searchUrl={instRoute('course-teachers.search', searchParams)}
      label={(item: CourseTeacher) =>
        `${item.user!.full_name} - ${item.course?.title} - ${
          item.classification?.title
        }`
      }
      {...props}
    />
  );
}
