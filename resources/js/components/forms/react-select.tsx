import { SelectOptionType } from '@/types/types';
import React, { useMemo } from 'react';
import Select, { Props } from 'react-select';

interface MyProps<T> {
  // data: {
  //   main: any[];
  //   label: string;
  //   value: string;
  // };
  options: () => SelectOptionType[];
  selectValue?: T;
  refreshKey?: string;
}

export default function ReactSelect<T>({
  // data,
  selectValue,
  options,
  refreshKey,
  ...props
}: MyProps<T> & Props) {
  const optionsData = useMemo(() => {
    return options();
  }, [refreshKey]);

  function getValue(param: T | undefined) {
    if (param === undefined) {
      return;
    }
    return optionsData.filter((item) => item.value === param);
  }

  return (
    <Select {...props} value={getValue(selectValue)} options={optionsData} />
  );
}
