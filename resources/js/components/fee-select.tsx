import React, { useEffect, useState } from 'react';
import route from '@/util/route';
import { Fee } from '@/types/models';
import ReactSelect from './react-select';
import useWebForm from '@/hooks/use-web-form';
import { Props } from 'react-select';

interface MyProps {
  selectValue?: number | string;
  fees?: Fee[];
}

export default function FeeSelect({
  selectValue,
  fees,
  ...props
}: MyProps & Props) {
  const [data, setData] = useState<Fee[]>(fees ?? []);
  const [refreshKey, setRefreshKey] = useState<string>('');
  const webForm = useWebForm({});

  useEffect(() => {
    if (fees) {
      return;
    }
    webForm
      .submit((data, web) => {
        return web.get(route('fees.search'));
      })
      .then(({ ok, data }) => {
        if (!ok) {
          return;
        }
        setData(data.fees);
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
