import React, { useState } from 'react';
import { Input, Text } from '@chakra-ui/react';
import useQueryString from '@/hooks/use-query-string';
import useSharedProps from '@/hooks/use-shared-props';
import BaseTableFilter from './base-table-filter';
import FilterFormControlBox from './filter-form-control-box';
import DateRangeFilter, { getDateRangeFilterParams } from './date-range-filter';
import ClassificationSelect from '../selectors/classification-select';
import EnumSelect from '../dropdown-select/enum-select';
import {
  Attendance as AttendanceType,
  InstitutionUserType,
} from '@/types/types';

interface Props {
  isOpen: boolean;
  onClose(): void;
}

const Lateness = {
  Late: 'late',
  OnTime: 'on_time',
};

function getCookie(name: string) {
  if (typeof document === 'undefined') return '';

  const cookie = document.cookie
    .split('; ')
    .find((value) => value.startsWith(`${name}=`));

  return cookie ? decodeURIComponent(cookie.split('=').slice(1).join('=')) : '';
}

function saveCookie(name: string, value: string) {
  if (typeof document === 'undefined') return;

  if (!value) {
    document.cookie = `${name}=; Max-Age=0; Path=/`;
    return;
  }

  document.cookie = `${name}=${encodeURIComponent(
    value
  )}; Max-Age=31536000; Path=/`;
}

export default function AttendanceTableFilters({ isOpen, onClose }: Props) {
  const { params } = useQueryString();
  const { currentInstitution } = useSharedProps();
  const cookieName = `edumanager_attendance_check_in_time_${
    currentInstitution?.id ?? 'default'
  }`;
  const [filters, setFilters] = useState(() => ({
    name: params.name ?? '',
    role: params.role ?? '',
    type: params.type ?? '',
    classification: params.classification ?? '',
    lateness: params.lateness ?? '',
    checkInTime: params.checkInTime ?? getCookie(cookieName),
    ...getDateRangeFilterParams(params, 'signed_in_at'),
  }));

  function updateCheckInTime(value: string) {
    saveCookie(cookieName, value);
    setFilters({ ...filters, checkInTime: value });
  }

  return (
    <BaseTableFilter filters={filters} isOpen={isOpen} onClose={onClose}>
      <FilterFormControlBox title="Person">
        <Input
          value={filters.name}
          onChange={(e) => setFilters({ ...filters, name: e.target.value })}
          placeholder="Search by name"
        />
      </FilterFormControlBox>

      <FilterFormControlBox title="Role">
        <EnumSelect
          selectValue={filters.role}
          enumData={InstitutionUserType}
          onChange={(e: any) => setFilters({ ...filters, role: e?.value })}
          isClearable={true}
        />
      </FilterFormControlBox>

      <FilterFormControlBox title="Attendance event">
        <EnumSelect
          selectValue={filters.type}
          enumData={AttendanceType}
          onChange={(e: any) => setFilters({ ...filters, type: e?.value })}
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

      <DateRangeFilter
        label="Attendance date"
        filterKey="signed_in_at"
        filters={filters}
        onChange={(dateRange) => setFilters({ ...filters, ...dateRange })}
      />

      <FilterFormControlBox title="Check-in time">
        <Input
          type="time"
          value={filters.checkInTime}
          onChange={(e) => updateCheckInTime(e.target.value)}
        />
        <Text mt={1} fontSize="xs" color="gray.500">
          Anyone checking in after this time is marked late. This time is saved
          for your next visit.
        </Text>
      </FilterFormControlBox>

      <FilterFormControlBox title="Lateness">
        <EnumSelect
          selectValue={filters.lateness}
          enumData={Lateness}
          onChange={(e: any) => setFilters({ ...filters, lateness: e?.value })}
          isClearable={true}
        />
        <Text mt={1} fontSize="xs" color="gray.500">
          Select a check-in time to filter late or on-time arrivals.
        </Text>
      </FilterFormControlBox>
    </BaseTableFilter>
  );
}
