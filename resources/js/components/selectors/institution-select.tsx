import React from 'react';
import { Institution } from '@/types/models';
import { AsyncProps } from 'react-select/async';
import { GroupBase } from 'react-select/dist/declarations/src/types';
import MyAsyncSelect from './my-async-select';
import route from '@/util/route';

export default function InstitutionSelect<
  Option,
  IsMulti extends boolean,
  Group extends GroupBase<Option>
>({ ...props }: AsyncProps<Option, IsMulti, Group>) {
  return (
    <MyAsyncSelect
      searchUrl={route('institutions.search')}
      label={(item: Institution) => `${item.name}`}
      {...props}
    />
  );
}
