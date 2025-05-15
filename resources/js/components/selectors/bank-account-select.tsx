import React from 'react';
import { BankAccount, InstitutionGroup } from '@/types/models';
import { Props } from 'react-select';
import SingleQuerySelect from '../dropdown-select/single-query-select';
import route from '@/util/route';
import MySelect from '../dropdown-select/my-select';

interface MyProps {
  selectValue?: number | string;
  bankAccounts: BankAccount[];
}

export default function BankAccountSelect({
  selectValue,
  bankAccounts,
  ...props
}: MyProps & Props) {
  return (
    <MySelect
      {...props}
      selectValue={selectValue}
      getOptions={() =>
        bankAccounts.map((item) => ({
          label: `${item?.bank_name}  -  ${item?.account_number}`,
          value: item.id,
        }))
      }
    />
  );
} 
