import React from 'react';
import { Student } from '@/types/models';
import { AsyncProps } from 'react-select/async';
import { GroupBase } from 'react-select/dist/declarations/src/types';
import MyAsyncSelect from './my-async-select';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface MyProps {
  params?: { [key: string]: string | number };
  classification?: number;
  valueKey?: string;
}

export default function StudentSelect<
  Option,
  IsMulti extends boolean,
  Group extends GroupBase<Option>
>({
  params,
  classification,
  valueKey,
  ...props
}: MyProps & AsyncProps<Option, IsMulti, Group>) {
  const { instRoute } = useInstitutionRoute();

  if (!params) {
    params = {};
  }
  if (classification) {
    params.classification = classification;
  }
  if (!valueKey) {
    valueKey = 'id';
  }

  return (
    <MyAsyncSelect
      searchUrl={instRoute('students.search')}
      valueKey={valueKey}
      params={params}
      label={(item: Student) =>
        item.user!.full_name + ' - ' + item.classification!.title
      }
      {...props}
    />
  );
}
