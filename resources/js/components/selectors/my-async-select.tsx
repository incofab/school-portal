import React, { useMemo } from 'react';
import { Row } from '@/types/models';
import debounce from 'lodash/debounce';
import AsyncSelect, { AsyncProps } from 'react-select/async';
import { GroupBase } from 'react-select/dist/declarations/src/types';
import web from '@/util/web';
import { useColorModeValue } from '@chakra-ui/react';

interface MyProps<T> {
  searchUrl: string;
  params?: { [key: string]: string | number };
  label: (item: T) => string;
  valueKey?: string;
}
export default function MyAsyncSelect<
  T extends Row,
  Option,
  IsMulti extends boolean,
  Group extends GroupBase<Option>
>({
  searchUrl,
  params,
  label,
  valueKey,
  ...props
}: MyProps<T> & AsyncProps<Option, IsMulti, Group>) {
  const refreshKey = params
    ? Object.entries(params)
        .map(([key, val]) => `${key} - ${val}`)
        .join(',')
    : '';
  const debouncedSearch = useMemo(() => {
    return debounce(async function (inputValue: string, callback: any) {
      const url = new URL(searchUrl);
      if (inputValue) {
        url.searchParams.set('search', inputValue);
      }
      if (params) {
        Object.entries(params).map(([label, value]) =>
          url.searchParams.set(label, String(value))
        );
      }

      const res = await web.get(url.toString());
      const result = res.data.result.data.map((item: T | any) => ({
        label: label(item),
        value: valueKey ? item[valueKey] : item['id'],
      }));
      callback(result);
    }, 250);
  }, [refreshKey]);

  const backgroundColor = useColorModeValue('white', '#2d3748');
  const hoverColor = useColorModeValue('#cbd5e0', '#1a202c');
  const textColor = useColorModeValue('#44596e', '#cbd5e0');
  return (
    <AsyncSelect
      loadOptions={(inputValue, callback) => {
        /**
         * Using promises with the debounce doesn't seem to work nicely
         * Intentionally not returning the result of this function
         * @see https://github.com/JedWatson/react-select/issues/3075#issuecomment-506647171
         */
        debouncedSearch(inputValue, callback);
      }}
      key={refreshKey}
      defaultOptions={true}
      {...props}
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
