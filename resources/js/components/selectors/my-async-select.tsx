import React, { useMemo } from 'react';
import { Row } from '@/types/models';
import debounce from 'lodash/debounce';
import AsyncSelect, { AsyncProps } from 'react-select/async';
import { GroupBase } from 'react-select/dist/declarations/src/types';
import web from '@/util/web';

interface MyProps<T> {
  searchUrl: string;
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
  label,
  valueKey,
  ...props
}: MyProps<T> & AsyncProps<Option, IsMulti, Group>) {
  const debouncedSearch = useMemo(() => {
    return debounce(async function (inputValue: string, callback: any) {
      const url = new URL(searchUrl);
      if (inputValue) {
        url.searchParams.set('search', inputValue);
      }
      const res = await web.get(url.toString());
      const result = res.data.result.data.map((item: T | any) => ({
        label: label(item),
        value: valueKey ? item[valueKey] : item['id'],
      }));
      callback(result);
    }, 250);
  }, []);

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
      defaultOptions={true}
      {...props}
    />
  );
}
