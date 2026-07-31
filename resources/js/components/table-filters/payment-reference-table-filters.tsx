import React, { useState } from 'react';
import { Input } from '@chakra-ui/react';
import useQueryString from '@/hooks/use-query-string';
import BaseTableFilter from './base-table-filter';
import FilterFormControlBox from './filter-form-control-box';
import EnumSelect from '../dropdown-select/enum-select';
import {
  PaymentMerchantType,
  PaymentPurpose,
  PaymentStatus,
} from '@/types/types';
import DateRangeFilter, { getDateRangeFilterParams } from './date-range-filter';
import InstitutionSelect from '../selectors/institution-select';

interface Props {
  isOpen: boolean;
  onClose(): void;
  showInstitution?: boolean;
}

export default function PaymentReferenceTableFilters({
  isOpen,
  onClose,
  showInstitution,
}: Props) {
  const { params } = useQueryString();
  const [filters, setFilters] = useState(() => ({
    search: params.search ?? '',
    reference: params.reference ?? '',
    institution_id: params.institution_id ?? '',
    status: params.status ?? '',
    merchant: params.merchant ?? '',
    purpose: params.purpose ?? '',
    ...getDateRangeFilterParams(params, 'created_at'),
  }));

  return (
    <BaseTableFilter filters={filters} isOpen={isOpen} onClose={onClose}>
      <FilterFormControlBox title="Search">
        <Input
          value={filters.search}
          onChange={(e) => setFilters({ ...filters, search: e.target.value })}
          placeholder="Reference, purpose or provider"
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
      <FilterFormControlBox title="Status">
        <EnumSelect
          selectValue={filters.status}
          enumData={PaymentStatus}
          onChange={(e: any) => setFilters({ ...filters, status: e?.value })}
          isClearable={true}
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Provider">
        <EnumSelect
          selectValue={filters.merchant}
          enumData={PaymentMerchantType}
          onChange={(e: any) => setFilters({ ...filters, merchant: e?.value })}
          isClearable={true}
        />
      </FilterFormControlBox>
      <FilterFormControlBox title="Purpose">
        <EnumSelect
          selectValue={filters.purpose}
          enumData={PaymentPurpose}
          onChange={(e: any) => setFilters({ ...filters, purpose: e?.value })}
          isClearable={true}
        />
      </FilterFormControlBox>
      <DateRangeFilter
        label="Attempt Date"
        filterKey="created_at"
        filters={filters}
        onChange={(dateRange) =>
          setFilters({
            ...filters,
            ...dateRange,
          })
        }
      />
    </BaseTableFilter>
  );
}
