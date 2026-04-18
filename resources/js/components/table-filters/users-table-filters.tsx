import React, { useState } from 'react';
import { InstitutionUserStatus, InstitutionUserType } from '@/types/types';
import useQueryString from '@/hooks/use-query-string';
import EnumSelect from '@/components/dropdown-select/enum-select';
import BaseTableFilter from '@/components/table-filters/base-table-filter';
import FilterFormControlBox from '@/components/table-filters/filter-form-control-box';
import InstitutionSelect from '@/components/selectors/institution-select';

interface Props {
  isOpen: boolean;
  onClose(): void;
  showInstitution?: boolean;
  showStatus?: boolean;
}

export default function UsersTableFilters({
  isOpen,
  onClose,
  showInstitution,
  showStatus,
}: Props) {
  const { params } = useQueryString();
  const [filters, setFilters] = useState(() => ({
    role: params.role ?? '',
    institution_id: params.institution_id ?? '',
    status: params.status ?? '',
  }));

  return (
    <BaseTableFilter filters={filters} isOpen={isOpen} onClose={onClose}>
      <FilterFormControlBox title="Role">
        <EnumSelect
          selectValue={filters.role}
          enumData={InstitutionUserType}
          value={filters.role}
          refreshKey={filters.role}
          onChange={(e: any) => setFilters({ ...filters, role: e?.value })}
          isClearable={true}
        />
      </FilterFormControlBox>
      {showInstitution && (
        <FilterFormControlBox title="Institution">
          <InstitutionSelect
            value={filters.institution_id}
            onChange={(e: any) =>
              setFilters({ ...filters, institution_id: e?.value })
            }
            isClearable={true}
          />
        </FilterFormControlBox>
      )}
      {showStatus && (
        <FilterFormControlBox title="Status">
          <EnumSelect
            selectValue={filters.status}
            enumData={InstitutionUserStatus}
            value={filters.status}
            refreshKey={filters.status}
            onChange={(e: any) => setFilters({ ...filters, status: e?.value })}
            isClearable={true}
          />
        </FilterFormControlBox>
      )}
    </BaseTableFilter>
  );
}
