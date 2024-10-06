import React from 'react';
import { ReceiptType } from '@/types/models';
import { Props } from 'react-select';
import SingleQuerySelect from '../dropdown-select/single-query-select';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface MyProps {
  selectValue?: number | string;
  receiptTypes?: ReceiptType[];
}

export default function ReceiptTypeSelect({
  selectValue,
  receiptTypes,
  ...props
}: MyProps & Props) {
  const { instRoute } = useInstitutionRoute();
  return (
    <SingleQuerySelect
      {...props}
      selectValue={selectValue}
      dataList={receiptTypes}
      searchUrl={instRoute('receipt-types.search')}
      label={'title'}
    />
  );
}
