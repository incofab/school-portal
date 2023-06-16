import React, { useState } from 'react';
import useQueryString from '@/hooks/use-query-string';
import BaseTableFilter from './base-table-filter';
import FilterFormControlBox from './filter-form-control-box';
import { TermType } from '@/types/types';
import AcademicSessionSelect from '../selectors/academic-session-select';
import EnumSelect from '../dropdown-select/enum-select';
import InstitutionSelect from '../selectors/institution-select';

interface Props {
  isOpen: boolean;
  onClose(): void;
}

export default function PinsTableFilters({ isOpen, onClose }: Props) {
  const { params } = useQueryString();
  const [filters, setFilters] = useState(() => ({
    term: params.term ?? '',
    institution_id: params.institution ?? '',
    academicSession: params.academicSession ?? '',
  }));

  return (
    <BaseTableFilter filters={filters} isOpen={isOpen} onClose={onClose}>
      <FilterFormControlBox title="Class">
        <InstitutionSelect
          onChange={(e: any) =>
            setFilters({ ...filters, institution_id: e.value })
          }
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Academic Session">
        <AcademicSessionSelect
          onChange={(e: any) =>
            setFilters({ ...filters, academicSession: e.value })
          }
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Term">
        <EnumSelect
          enumData={TermType}
          onChange={(e: any) => setFilters({ ...filters, term: e.value })}
        />
      </FilterFormControlBox>
    </BaseTableFilter>
  );
}
