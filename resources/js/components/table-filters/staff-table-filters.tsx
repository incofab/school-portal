import React, { useState } from 'react';
import useQueryString from '@/hooks/use-query-string';
import BaseTableFilter from './base-table-filter';
import FilterFormControlBox from './filter-form-control-box';
import EnumSelect from '../dropdown-select/enum-select';
import { InstitutionUserType } from '@/types/types';
import { Role } from '@/types/models';
import RoleSelect from '../selectors/role-select';

interface Props {
  isOpen: boolean;
  onClose(): void;
  roles?: Role[];
}

export default function StaffTableFilters({ isOpen, onClose, roles }: Props) {
  const { params } = useQueryString();
  const [filters, setFilters] = useState(() => ({
    role: params.role ?? '',
  }));

  return (
    <BaseTableFilter filters={filters} isOpen={isOpen} onClose={onClose}>
      <FilterFormControlBox title="Role">
        {roles ? (
          <RoleSelect
            roles={roles}
            selectValue={filters.role}
            onChange={(e: any) => setFilters({ ...filters, role: e?.value })}
            isClearable={true}
          />
        ) : (
          <EnumSelect
            selectValue={filters.role}
            enumData={InstitutionUserType}
            onChange={(e: any) => setFilters({ ...filters, role: e?.value })}
            isClearable={true}
          />
        )}
      </FilterFormControlBox>
    </BaseTableFilter>
  );
}
