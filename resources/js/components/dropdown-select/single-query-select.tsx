import React, { useEffect, useState } from 'react';
import useWebForm from '@/hooks/use-web-form';
import { Props } from 'react-select';
import DataSelect from '../dropdown-select/data-select';
import { SelectValue } from '@/types/types';

interface MyProps<T> {
  selectValue?: SelectValue;
  dataList?: T[];
  searchUrl: string;
  label: string | ((data: T) => string);
  dataFilter?: (data: T[]) => T[];
  refreshKey?: string;
  valueKey?: string;
}

export default function SingleQuerySelect<T extends { [key: string]: any }>({
  selectValue,
  dataList,
  searchUrl,
  label,
  dataFilter,
  refreshKey,
  valueKey,
  ...props
}: MyProps<T> & Props) {
  const [data, setData] = useState<T[]>(dataList ?? []);
  const [_refreshKey, setRefreshKey] = useState<string>('');
  const webForm = useWebForm({});

  if (refreshKey && refreshKey != _refreshKey) {
    setRefreshKey(refreshKey);
  }

  useEffect(
    () => {
      if (dataList) {
        setData(dataList);
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
    },
    refreshKey ? [refreshKey] : []
  );

  return (
    <DataSelect
      {...props}
      selectValue={selectValue}
      data={{
        main: dataFilter ? dataFilter(data) : data,
        label: label,
        value: valueKey ?? 'id',
      }}
      isLoading={webForm.processing}
      refreshKey={_refreshKey}
    />
  );
}
