import React, { useMemo } from 'react';
import route from '@/util/route';
import { User } from '@/types/models';
import debounce from 'lodash/debounce';
import AsyncSelect, { AsyncProps } from 'react-select/async';
import { GroupBase } from 'react-select/dist/declarations/src/types';
import web from '@/util/web';

export default function StudentSelect<
  Option,
  IsMulti extends boolean,
  Group extends GroupBase<Option>
>({ ...props }: AsyncProps<Option, IsMulti, Group>) {
  const debouncedSearch = useMemo(() => {
    return debounce(async function (inputValue: string, callback: any) {
      const url = new URL(route('users.search'));
      if (inputValue) {
        url.searchParams.set('searchQuery', inputValue);
      }
      const res = await web.get(url.toString());
      const users = res.data.users.data.map((user: User) => ({
        label: user.full_name + ' - ' + user.email,
        value: user.id,
      }));
      callback(users);
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
