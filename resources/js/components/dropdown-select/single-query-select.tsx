import React, { useEffect, useState } from 'react';
import useWebForm from '@/hooks/use-web-form';
import { Props } from 'react-select';
import DataSelect from '../dropdown-select/data-select';

interface MyProps<T> {
  selectValue?: number | string;
  dataList?: T[];
  searchUrl: string;
  label: string | ((data: T) => string);
}

export default function SingleQuerySelect<T extends { [key: string]: any }>({
  selectValue,
  dataList,
  searchUrl,
  label,
  ...props
}: MyProps<T> & Props) {
  const [data, setData] = useState<T[]>(dataList ?? []);
  const [refreshKey, setRefreshKey] = useState<string>('');
  const webForm = useWebForm({});

  useEffect(() => {
    if (dataList) {
      return;
    }
    webForm
      .submit((data, web) => {
        return web.get(searchUrl);
      })
      .then(({ ok, data }) => {
        if (!ok) {
          return;
        }
        setData(data.result);
        setRefreshKey(Math.random() + '');
      });
  }, []);

  return (
    <DataSelect
      {...props}
      selectValue={selectValue}
      data={{ main: data, label: label, value: 'id' }}
      isLoading={webForm.processing}
      refreshKey={refreshKey}
    />
  );
}
