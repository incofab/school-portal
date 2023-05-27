import React, { useEffect, useState } from 'react';
import route from '@/util/route';
import { Course } from '@/types/models';
import ReactSelect from './react-select';
import useWebForm from '@/hooks/use-web-form';
import { Props } from 'react-select';

interface MyProps {
  selectValue?: number | string;
}

export default function CourseSelect({
  selectValue,
  ...props
}: MyProps & Props) {
  const [courses, setCourses] = useState<Course[]>([]);
  const [refreshKey, setRefreshKey] = useState<string>('');
  const webForm = useWebForm({});

  useEffect(() => {
    webForm
      .submit((data, web) => {
        return web.get(route('courses.search'));
      })
      .then(({ ok, data }) => {
        if (!ok) {
          return;
        }
        setCourses(data.courses);
        setRefreshKey(Math.random() + '');
      });
  }, []);

  return (
    <ReactSelect
      {...props}
      selectValue={selectValue}
      data={{
        main: courses,
        label: (course) => `${course.code} - ${course.title}`,
        value: 'id',
      }}
      isLoading={webForm.processing}
      refreshKey={refreshKey}
    />
  );
}
