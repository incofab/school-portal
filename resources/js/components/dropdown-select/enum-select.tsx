import React from 'react';
import { Props } from 'react-select';
import MySelect from './my-select';
import startCase from 'lodash/startCase';
import { SelectOptionType } from '@/types/types';

interface MyProps<T> {
  enumData: { [key: string]: string };
  additionalEnumData?: { [key: string]: any };
  allowedEnum?: string[];
  selectValue?: T;
  refreshKey?: string;
}

export default function EnumSelect<T>({
  enumData,
  additionalEnumData,
  selectValue,
  allowedEnum,
  refreshKey,
  ...props
}: MyProps<T> & Props) {
  const fullData = { ...(additionalEnumData ?? {}), ...enumData };
  // if (additionalEnumData) {
  //   Object.entries(additionalEnumData).map(
  //     ([key, value]) => (enumData[key] = value)
  //   );
  // }
  return (
    <MySelect
      {...props}
      isMulti={false}
      selectValue={selectValue}
      getOptions={() =>
        Object.entries(fullData)
          .map(([key, value]) => {
            if (allowedEnum) {
              if (!allowedEnum.includes(value)) {
                return null;
              }
            }
            return {
              label: startCase(value === '' ? key : value.replaceAll('-', ' ')),
              value: value,
            };
          })
          .filter((val) => val !== null) as SelectOptionType[]
      }
    />
  );
}
