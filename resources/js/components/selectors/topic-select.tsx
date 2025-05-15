import React from 'react';
import { Topic } from '@/types/models';
import { Props } from 'react-select';
import SingleQuerySelect from '../dropdown-select/single-query-select';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface MyProps {
  selectValue?: number | string;
  topics?: Topic[];
}

export default function TopicSelect({
  selectValue,
  topics,
  ...props
}: MyProps & Props) {
  const { instRoute } = useInstitutionRoute();
  return (
    <SingleQuerySelect
      {...props}
      selectValue={selectValue}
      dataList={topics}
      searchUrl={instRoute('inst-topics.search')}
      label={'title'}
    />
  );
} 
