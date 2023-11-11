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

  const backgroundColor = useColorModeValue('white', '#2d3748');
  const hoverColor = useColorModeValue('#cbd5e0', '#1a202c');
  const textColor = useColorModeValue('#44596e', '#cbd5e0');

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
          primary25: hoverColor,
          neutral0: backgroundColor,
          neutral80: textColor,
        },
      })}
    />
  );
}
