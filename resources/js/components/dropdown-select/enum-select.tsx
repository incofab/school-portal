import React from 'react';
import { Props } from 'react-select';
import MySelect from './my-select';
import startCase from 'lodash/startCase';
import { SelectOptionType } from '@/types/types';

interface MyProps<T> {
  enumData: { [key: string]: string };
  allowedEnum?: string[];
  selectValue?: T;
  refreshKey?: string;
}

export default function EnumSelect<T>({
  enumData,
  selectValue,
  allowedEnum,
  refreshKey,
  ...props
}: MyProps<T> & Props) {
  return (
    <MySelect
      {...props}
      isMulti={false}
      selectValue={selectValue}
      getOptions={() =>
        Object.entries(enumData)
          .map(([key, value]) => {
            if (allowedEnum) {
              if (!allowedEnum.includes(value)) {
                return null;
              }
            }
            return {
              label: startCase(value.replaceAll('-', ' ')),
              value: value,
            };
          })
          .filter((val) => val !== null) as SelectOptionType[]
      }
    />
  );
}
