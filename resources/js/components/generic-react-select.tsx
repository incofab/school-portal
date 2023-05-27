import React from 'react';
import Select, { Props } from 'react-select';

interface MyProps<T> {
  data: {
    label: string;
    value: string | number;
  }[];
  selectValue?: T;
}

export default function GenericReactSelect<T>({
  data,
  selectValue,
  ...props
}: MyProps<T> & Props) {
  function getValue(param: T | undefined) {
    if (param === undefined) {
      return;
    }
    return data.find((item) => item.value === param);
  }
  return <Select {...props} value={getValue(selectValue)} options={data} />;
}
