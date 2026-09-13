import React, { useState } from 'react';
import { Input } from '@chakra-ui/react';
import useQueryString from '@/hooks/use-query-string';
import BaseTableFilter from './base-table-filter';
import FilterFormControlBox from './filter-form-control-box';
import { PaymentMethod, ReceiptStatus, TermType } from '@/types/types';
import AcademicSessionSelect from '../selectors/academic-session-select';
import EnumSelect from '../dropdown-select/enum-select';
import FeeSelect from '../selectors/fee-select';
import useSharedProps from '@/hooks/use-shared-props';
import StudentSelect from '../selectors/student-select';
import ClassificationSelect from '../selectors/classification-select';
import DateRangeFilter, { getDateRangeFilterParams } from './date-range-filter';

interface Props {
  isOpen: boolean;
  onClose(): void;
}

export default function FeePaymentTableFilters({ isOpen, onClose }: Props) {
  const { params } = useQueryString();
  const { currentAcademicSessionId, currentTerm } = useSharedProps();
  const [filters, setFilters] = useState(() => ({
    term: params.term ?? currentTerm,
    academicSession: params.academicSession ?? currentAcademicSessionId,
    fee: params.fee ?? '',
    user: params.user ?? '',
    classification: params.classification ?? '',
    status: params.status ?? '',
    method: params.method ?? '',
    reference: params.reference ?? '',
    ...getDateRangeFilterParams(params, 'created_at'),
  }));

  return (
    <BaseTableFilter filters={filters} isOpen={isOpen} onClose={onClose}>
      <FilterFormControlBox title="Fee">
        <FeeSelect
          selectValue={filters.fee}
          onChange={(e: any) => setFilters({ ...filters, fee: e?.value })}
          isClearable={true}
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Student">
        <StudentSelect
          value={filters.user}
          valueKey="user_id"
          onChange={(e: any) => setFilters({ ...filters, user: e?.value })}
          isClearable={true}
          isMulti={false}
          classification={filters.classification}
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
      <FilterFormControlBox title="Academic Session">
        <AcademicSessionSelect
          selectValue={filters.academicSession}
          onChange={(e: any) =>
            setFilters({ ...filters, academicSession: e?.value })
          }
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
      <FilterFormControlBox title="Payment status">
        <EnumSelect
          selectValue={filters.status}
          enumData={ReceiptStatus}
          onChange={(e: any) => setFilters({ ...filters, status: e?.value })}
          isClearable={true}
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Payment method">
        <EnumSelect
          selectValue={filters.method}
          enumData={PaymentMethod}
          onChange={(e: any) => setFilters({ ...filters, method: e?.value })}
          isClearable={true}
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Reference">
        <Input
          value={filters.reference}
          onChange={(e) =>
            setFilters({ ...filters, reference: e.target.value })
          }
          placeholder="Payment reference"
        />
      </FilterFormControlBox>
      <DateRangeFilter
        label="Payment date"
        filterKey="created_at"
        filters={filters}
        onChange={(dateRange) => setFilters({ ...filters, ...dateRange })}
      />
    </BaseTableFilter>
  );
}
