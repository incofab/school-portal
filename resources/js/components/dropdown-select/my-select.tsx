import { SelectOptionType } from '@/types/types';
import { useColorModeValue } from '@chakra-ui/react';
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

  const neutral0 = useColorModeValue('white', '#2d3748');
  const primary25 = useColorModeValue('#cbd5e0', '#1a202c');

  return (
    <Select
      {...props}
      value={getValue(selectValue)}
      options={optionsData}
      theme={(theme) => ({
        ...theme,
        borderRadius: 0,
        colors: {
          ...theme.colors,
          primary25: primary25,
          neutral0: neutral0,
        },
      })}
    />
  );
}
