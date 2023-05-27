import React, { useEffect, useState } from 'react';
import route from '@/util/route';
import { Department } from '@/types/models';
import ReactSelect from './react-select';
import useWebForm from '@/hooks/use-web-form';
import { Props } from 'react-select';

interface MyProps {
  selectValue?: number | string;
  departments?: Department[];
}

export default function DepartmentSelect({
  selectValue,
  departments,
  ...props
}: MyProps & Props) {
  const [data, setData] = useState<Department[]>(departments ?? []);
  const [refreshKey, setRefreshKey] = useState<string>('');
  const webForm = useWebForm({});

  useEffect(() => {
    if (departments) {
      return;
    }

    webForm
      .submit((data, web) => {
        return web.get(route('departments.search'));
      })
      .then(({ ok, data }) => {
        if (!ok) {
          return;
        }
        setData(data.departments);
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
