import { WebForm } from '@/hooks/use-web-form';
import React, { useMemo } from 'react';
import FormControlBox from './form-control-box';
import Select, { Props } from 'react-select';
import { Nullable, SelectOptionType } from '@/types/types';

interface MyProps {
  form: WebForm<{
    [key: string]: string;
  }>;
  formKey: string;
  title: string;
  refreshKey?: string;
  options: () => SelectOptionType[];
}

export default function ReactSelectControl({
  form,
  formKey,
  title,
  options,
  refreshKey,
  ...selectProps
}: MyProps & Props) {
  const optionsData = useMemo(() => {
    return options();
  }, [refreshKey]);

  function getValue(param: string | undefined): Nullable<SelectOptionType> {
    if (param === undefined) {
      return null;
    }
    const filtered = optionsData.filter((item) => item.value === param);
    return filtered[0] ?? null;
  }

  return (
    <FormControlBox form={form} title={title}>
      <Select
        {...selectProps}
        isMulti={false}
        isClearable={false}
        value={getValue(form.data[formKey]) as SelectOptionType}
        options={optionsData}
        onChange={(e) =>
          form.setValue(formKey, (e as Nullable<SelectOptionType>)?.value ?? '')
        }
      />
    </FormControlBox>
  );
}
