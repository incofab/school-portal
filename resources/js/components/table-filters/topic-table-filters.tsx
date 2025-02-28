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
import CourseTeacherSelect from '../selectors/course-teacher-select';
import { ClassificationGroup } from '@/types/models';
import useIsStaff from '@/hooks/use-is-staff';

interface Props {
  classificationGroups: ClassificationGroup[];
  isOpen: boolean;
  onClose(): void;
}

export default function TopicTableFilters({
  isOpen,
  onClose,
  classificationGroups,
}: Props) {
  const { params } = useQueryString();
  const { currentTerm } = useSharedProps();
  const isStaff = useIsStaff();
  const [filters, setFilters] = useState(() => ({
    term: params.term ?? currentTerm,
    classification: params.classification ?? '',
    classificationGroup: params.classificationGroup ?? '',
    courseTeacher: params.courseTeacher ?? '',
    course: params.course ?? '',
    // teacher: params.teacher ?? '',
    status: params.status,
  }));

  return (
    <BaseTableFilter filters={filters} isOpen={isOpen} onClose={onClose}>
      {isStaff && (
        <>
          <FilterFormControlBox title="Class Group">
            <ClassificationGroupSelect
              classificationGroups={classificationGroups}
              selectValue={filters.classificationGroup}
              onChange={(e: any) =>
                setFilters({ ...filters, classificationGroup: e.value })
              }
            />
          </FilterFormControlBox>
          <FilterFormControlBox title="Class">
            <ClassificationSelect
              selectValue={filters.classification}
              onChange={(e: any) =>
                setFilters({ ...filters, classification: e.value })
              }
            />
          </FilterFormControlBox>
        </>
      )}

      <FilterFormControlBox title="Subject">
        <CourseSelect
          selectValue={filters.course}
          onChange={(e: any) => setFilters({ ...filters, course: e.value })}
        />
      </FilterFormControlBox>
      {isStaff && (
        <>
          <FilterFormControlBox title="Teacher's Subjects">
            <CourseTeacherSelect
              value={filters.courseTeacher}
              onChange={(e: any) =>
                setFilters({ ...filters, courseTeacher: e.value })
              }
            />
          </FilterFormControlBox>
          <FilterFormControlBox title="Term">
            <EnumSelect
              selectValue={filters.term}
              enumData={TermType}
              onChange={(e: any) => setFilters({ ...filters, term: e.value })}
            />
          </FilterFormControlBox>
        </>
      )}
    </BaseTableFilter>
  );
}
