import React, { useMemo } from 'react';
import { Props } from 'react-select';
import GenericReactSelect from './generic-react-select';

interface MyProps<T> {
  data: {
    main: T[];
    label: string | ((data: T) => string);
    value: any;
  };
  selectValue?: string | number;
  refreshKey?: string;
}

export default function ReactSelect<T extends { [key: string]: any }>({
  data,
  selectValue,
  refreshKey,
  ...props
}: MyProps<T> & Props) {
  const options = useMemo(() => {
    return data.main.map((item) => {
      const label =
        typeof data.label === 'string' ? item[data.label] : data.label(item);
      return { label: label, value: item[data.value] };
    });
  }, [refreshKey]);

  return (
    <GenericReactSelect {...props} selectValue={selectValue} data={options} />
  );
}
