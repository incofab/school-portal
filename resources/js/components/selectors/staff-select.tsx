import React from 'react';
import { InstitutionUser } from '@/types/models';
import { AsyncProps } from 'react-select/async';
import { GroupBase } from 'react-select/dist/declarations/src/types';
import MyAsyncSelect from './my-async-select';
import { InstitutionUserType } from '@/types/types';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface Props {
  rolesIn?: string[];
  rolesExclude?: string[];
  valueKey?: string;
}

export default function StaffSelect<
  Option,
  IsMulti extends boolean,
  Group extends GroupBase<Option>
>({
  rolesIn,
  rolesExclude,
  valueKey,
  ...props
}: Props & AsyncProps<Option, IsMulti, Group>) {
  if (!rolesIn && !rolesExclude) {
    rolesExclude = [
      InstitutionUserType.Student,
      InstitutionUserType.Alumni,
      InstitutionUserType.Guardian,
    ];
  }

  const { instRoute } = useInstitutionRoute();
  return (
    <MyAsyncSelect
      searchUrl={instRoute('users.search', [
        { roles_not_in: rolesExclude },
        { roles_in: rolesIn },
      ])}
      label={(item: InstitutionUser) =>
        item.user!.full_name + ' - ' + item.role
      }
      valueKey={valueKey ?? 'user_id'}
      {...props}
    />
  );
}
