import React from 'react';
import { Props } from 'react-select';
import MySelect from './my-select';
import startCase from 'lodash/startCase';

interface MyProps<T> {
  enumData: { [key: string]: string };
  selectValue?: T;
  refreshKey?: string;
}

export default function EnumSelect<T>({
  enumData,
  selectValue,
  refreshKey,
  ...props
}: MyProps<T> & Props) {
  return (
    <MySelect
      {...props}
      isMulti={false}
      selectValue={selectValue}
      getOptions={() =>
        Object.entries(enumData).map(([key, value]) => ({
          label: startCase(value.replaceAll('-', ' ')),
          value: value,
        }))
      }
    />
  );
}
