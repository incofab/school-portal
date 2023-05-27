import React, { useEffect, useState } from 'react';
import route from '@/util/route';
import { AcademicSession } from '@/types/models';
import ReactSelect from './react-select';
import useWebForm from '@/hooks/use-web-form';
import { Props } from 'react-select';

interface MyProps {
  selectValue?: number | string;
  academicSessions?: AcademicSession[];
}

export default function AcademicSessionSelect({
  selectValue,
  academicSessions,
  ...props
}: MyProps & Props) {
  const [data, setData] = useState<AcademicSession[]>(academicSessions ?? []);
  const [refreshKey, setRefreshKey] = useState<string>('');
  const webForm = useWebForm({});

  useEffect(() => {
    if (academicSessions) {
      return;
    }
    webForm
      .submit((data, web) => {
        return web.get(route('academic-sessions.search'));
      })
      .then(({ ok, data }) => {
        if (!ok) {
          return;
        }
        setData(data.academicSessions);
        setRefreshKey(Math.random() + '');
      });
  }, []);

  return (
    <ReactSelect
      {...props}
      selectValue={selectValue}
      data={{ main: data, label: 'title', value: 'id' }}
      isLoading={webForm.processing}
      refreshKey={refreshKey}
    />
  );
}
