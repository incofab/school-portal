import React, { useState } from 'react';
import useQueryString from '@/hooks/use-query-string';
import BaseTableFilter from './base-table-filter';
import FilterFormControlBox from './filter-form-control-box';
import ClassificationSelect from '../selectors/classification-select';

interface Props {
  isOpen: boolean;
  onClose(): void;
  // classificationGroups: ClassificationGroup[];
}

export default function ExamTableFilters({ isOpen, onClose }: Props) {
  const { params } = useQueryString();
  const [filters, setFilters] = useState(() => ({
    classification: params.classification ?? '',
    classificationGroup: params.classificationGroup ?? '',
  }));

  return (
    <BaseTableFilter filters={filters} isOpen={isOpen} onClose={onClose}>
      {/* <FilterFormControlBox title="Class Group">
        <ClassificationGroupSelect
          classificationGroups={classificationGroups}
          onChange={(e: any) =>
            setFilters({ ...filters, classificationGroup: e.value })
          }
        />
      </FilterFormControlBox> */}
      <FilterFormControlBox title="Class">
        <ClassificationSelect
          onChange={(e: any) =>
            setFilters({ ...filters, classification: e.value })
          }
        />
      </FilterFormControlBox>
    </BaseTableFilter>
  );
}
