import React from 'react';
import { AdmissionForm } from '@/types/models';
import { Props } from 'react-select';
import SingleQuerySelect from '../dropdown-select/single-query-select';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface MyProps {
  selectValue?: number | string;
  admissionForms?: AdmissionForm[];
}

export default function AdmissionFormSelect({
  selectValue,
  admissionForms,
  ...props
}: MyProps & Props) {
  const { instRoute } = useInstitutionRoute();
  return (
    <SingleQuerySelect
      {...props}
      selectValue={selectValue}
      dataList={admissionForms}
      searchUrl={instRoute('admission-forms.search')}
      label={'title'}
    />
  );
}
