import React, { useMemo } from 'react';
import route from '@/util/route';
import { UserRoleType } from '@/types/types';
import { User } from '@/types/models';
import debounce from 'lodash/debounce';
import AsyncSelect, { AsyncProps } from 'react-select/async';
import { GroupBase } from 'react-select/dist/declarations/src/types';
import web from '@/util/web';

interface Props {
  roles?: UserRoleType[];
}

export default function UserSelect<
  Option,
  IsMulti extends boolean,
  Group extends GroupBase<Option>
>({ roles = [], ...props }: Props & AsyncProps<Option, IsMulti, Group>) {
  const debouncedSearch = useMemo(() => {
    return debounce(async function (
      inputValue: string,
      roles: UserRoleType[],
      callback: any
    ) {
      const url = new URL(route('users.search'));
      for (const role of roles) {
        url.searchParams.append('roles[]', role);
      }
      if (inputValue) {
        url.searchParams.set('search', inputValue);
      }

      const res = await web.get(url.toString());

      const users = res.data.users.data.map((user: User) => ({
        label: user.full_name + ' - ' + user.email,
        value: user.id,
      }));
      callback(users);
    },
    250);
  }, []);

  return (
    <AsyncSelect
      loadOptions={(inputValue, callback) => {
        /**
         * Using promises with the debounce doesn't seem to work nicely
         * Intentionally not returning the result of this function
         * @see https://github.com/JedWatson/react-select/issues/3075#issuecomment-506647171
         */
        debouncedSearch(inputValue, roles, callback);
      }}
      defaultOptions={true}
      {...props}
    />
  );
}
