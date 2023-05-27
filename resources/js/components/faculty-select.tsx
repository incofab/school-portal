import React, { useEffect, useState } from 'react';
import route from '@/util/route';
import { Faculty } from '@/types/models';
import ReactSelect from './react-select';
import useWebForm from '@/hooks/use-web-form';
import { Props } from 'react-select';

interface MyProps {
  selectValue?: number | string;
  faculties?: Faculty[];
}

export default function FacultySelect({
  selectValue,
  faculties,
  ...props
}: MyProps & Props) {
  const [data, setData] = useState<Faculty[]>(faculties ?? []);
  const [refreshKey, setRefreshKey] = useState<string>('');
  const webForm = useWebForm({});

  useEffect(() => {
    if (faculties) {
      return;
    }
    webForm
      .submit((data, web) => {
        return web.get(route('faculties.search'));
      })
      .then(({ ok, data }) => {
        if (!ok) {
          return;
        }
        setData(data.faculties);
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
