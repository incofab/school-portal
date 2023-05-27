import React, { useEffect, useState } from 'react';
import route from '@/util/route';
import { Hostel } from '@/types/models';
import ReactSelect from './react-select';
import useWebForm from '@/hooks/use-web-form';
import { Props } from 'react-select';

interface MyProps {
  selectValue?: number | string;
  hostels?: Hostel[];
}

export default function HostelSelect({
  selectValue,
  hostels,
  ...props
}: MyProps & Props) {
  const [data, setData] = useState<Hostel[]>(hostels ?? []);
  const [refreshKey, setRefreshKey] = useState<string>('');
  const webForm = useWebForm({});

  useEffect(() => {
    if (hostels) {
      return;
    }
    webForm
      .submit((data, web) => {
        return web.get(route('hostels.search'));
      })
      .then(({ ok, data }) => {
        if (!ok) {
          return;
        }
        setData(data.hostels);
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
