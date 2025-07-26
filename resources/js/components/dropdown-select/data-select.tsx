import React from 'react';
import { Props } from 'react-select';
import MySelect from './my-select';
import { SelectValue } from '@/types/types';

interface MyProps<T> {
  data: {
    main: T[];
    label: string | ((data: T) => string);
    value: any;
  };
  selectValue?: SelectValue;
  refreshKey?: string;
}

export default function DataSelect<T extends { [key: string]: any }>({
  data,
  selectValue,
  refreshKey,
  ...props
}: MyProps<T> & Props) {
  // console.log('main', data.main);
  // if (!data.main) {
  //   data.main = []; 08037569098
  // }
  return (
    <MySelect
      {...props}
      selectValue={selectValue}
      refreshKey={refreshKey}
      getOptions={() =>
        data.main.map((item) => {
          const label =
            typeof data.label === 'string'
              ? item[data.label]
              : data.label(item);
          return { label: label, value: item[data.value] };
        })
      }
    />
  );
}
