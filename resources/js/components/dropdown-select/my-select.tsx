import { SelectOptionType } from '@/types/types';
import React, { useMemo } from 'react';
import Select, { Props } from 'react-select';

interface MyProps<T> {
  getOptions: () => SelectOptionType<T | string | number>[];
  selectValue?: T;
  refreshKey?: string;
}

export default function MySelect<T>({
  selectValue,
  getOptions,
  refreshKey,
  ...props
}: MyProps<T> & Props) {
  const optionsData = useMemo(() => {
    return getOptions();
  }, [refreshKey]);

  function getValue(param: T | undefined) {
    if (param === undefined) {
      return;
    }
    const result = optionsData.find((item) => item.value == param);
    return result;
  }

  return (
    <Select {...props} value={getValue(selectValue)} options={optionsData} />
  );
}
