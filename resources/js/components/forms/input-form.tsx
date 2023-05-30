import { Input, InputProps } from '@chakra-ui/react';
import React from 'react';
import FormControlBox from './form-control-box';
import { WebForm } from '@/hooks/use-web-form';

export interface FormProps<
  Data = Record<string, any>,
  Errors = Record<keyof Data, string>
> {
  data: Data;
  errors: Errors;
  processing: boolean;
  // setValue: (key: string, value: string | number) => void;
  setValue<Key extends keyof Data>(key: Key, value: Data[Key]): void;
  setData(data: Data): void;
}

interface Props {
  // form: WebForm;
  form: FormProps<{
    [key: string]: string;
  }>;
  title: string;
  formKey: string;
}

export default function InputForm({
  form,
  title,
  formKey,
  children,
  ...props
}: Props & InputProps) {
  return (
    <FormControlBox title={title} formKey={formKey} form={form}>
      <Input
        onChange={(e) => form.setValue(formKey, e.currentTarget.value)}
        value={form.data[formKey]}
        {...props}
      />
    </FormControlBox>
  );
}
