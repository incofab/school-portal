import React from 'react';
import { Props } from 'react-select';
import { Role } from '@/types/models';
import { SelectValue } from '@/types/types';
import DataSelect from '../dropdown-select/data-select';

interface MyProps {
  roles: Role[];
  selectValue?: SelectValue;
  refreshKey?: string;
}

export default function RoleSelect({
  roles,
  selectValue,
  refreshKey,
  ...props
}: MyProps & Props) {
  return (
    <DataSelect
      {...props}
      selectValue={selectValue}
      refreshKey={refreshKey}
      data={{ main: roles, label: 'name', value: 'id' }}
    />
  );
}
