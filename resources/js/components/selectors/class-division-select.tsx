import React from 'react';
import { ClassDivision } from '@/types/models';
import { MultiValue, Props } from 'react-select';
import SingleQuerySelect from '../dropdown-select/single-query-select';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { SelectOptionType } from '@/types/types';

interface MyProps {
  selectValue?:
    | string
    | number
    | SelectOptionType<number>
    | MultiValue<SelectOptionType<number>>
    | null;
  classDivisions: ClassDivision[];
}

export default function ClassDivisionSelect({
  selectValue,
  classDivisions,
  ...props
}: MyProps & Props) {
  const { instRoute } = useInstitutionRoute();
  return (
    <SingleQuerySelect
      {...props}
      selectValue={selectValue}
      dataList={classDivisions}
      searchUrl={instRoute('class-divisions.search')}
      label={'title'}
    />
  );
}
