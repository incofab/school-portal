import { WebForm } from '@/hooks/use-web-form';
import { Select } from '@chakra-ui/react';
import React from 'react';
import FormControlBox from './form-control-box';

interface Props {
  form: WebForm<{
    [key: string]: string;
  }>;
  formKey: string;
  title: string;
  options: () => React.FC<{ text: string }>[];
}

export default function SelectControl({
  form,
  title,
  // options,
  formKey,
}: Props) {
  return (
    <FormControlBox form={form} title={title}>
      <Select
        value={form.data[formKey]}
        onChange={(e) => form.setValue(formKey, e.currentTarget.value)}
      >
        {/* <option></option> */}
        {/* {options()} */}
      </Select>
    </FormControlBox>
  );
}
