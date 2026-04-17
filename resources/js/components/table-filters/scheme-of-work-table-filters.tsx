import React, { useState } from 'react';
import useQueryString from '@/hooks/use-query-string';
import BaseTableFilter from './base-table-filter';
import FilterFormControlBox from './filter-form-control-box';
import ClassificationSelect from '../selectors/classification-select';
import CourseSelect from '../selectors/course-select';
import { TermType } from '@/types/types';
import EnumSelect from '../dropdown-select/enum-select';
import useSharedProps from '@/hooks/use-shared-props';
import ClassificationGroupSelect from '../selectors/classification-group-select';
import useIsStaff from '@/hooks/use-is-staff';

interface Props {
  isOpen: boolean;
  onClose(): void;
}

export default function SchemeOfWorkTableFilters({ isOpen, onClose }: Props) {
  const { params } = useQueryString();
  const { currentTerm } = useSharedProps();
  const isStaff = useIsStaff();
  const [filters, setFilters] = useState(() => ({
    term: params.term ?? currentTerm,
    classification: params.classification ?? '',
    classificationGroup: params.classificationGroup ?? '',
    course: params.course ?? '',
  }));

  return (
    <BaseTableFilter filters={filters} isOpen={isOpen} onClose={onClose}>
      {isStaff && (
        <>
          <FilterFormControlBox title="Class Group">
            <ClassificationGroupSelect
              selectValue={filters.classificationGroup}
              onChange={(e: any) =>
                setFilters({ ...filters, classificationGroup: e?.value })
              }
              isClearable={true}
            />
          </FilterFormControlBox>
          <FilterFormControlBox title="Class">
            <ClassificationSelect
              selectValue={filters.classification}
              onChange={(e: any) =>
                setFilters({ ...filters, classification: e?.value })
              }
              isClearable={true}
            />
          </FilterFormControlBox>
        </>
      )}

      <FilterFormControlBox title="Subject">
        <CourseSelect
          selectValue={filters.course}
          onChange={(e: any) => setFilters({ ...filters, course: e?.value })}
          isClearable={true}
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Term">
        <EnumSelect
          selectValue={filters.term}
          enumData={TermType}
          onChange={(e: any) => setFilters({ ...filters, term: e?.value })}
          isClearable={true}
        />
      </FilterFormControlBox>
    </BaseTableFilter>
  );
}
