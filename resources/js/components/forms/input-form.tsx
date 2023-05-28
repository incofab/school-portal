import {
  FormControl,
  FormControlProps,
  FormErrorMessage,
  FormLabel,
  Input,
  InputProps,
} from '@chakra-ui/react';
import React, { PropsWithChildren } from 'react';
import FormControlBox from './form-control-box';

export interface FormProps<
  Data = Record<string, any>,
  Errors = Record<keyof Data, string>
> {
  data: Data;
  errors: Errors;
  processing: boolean;
  setValue<Key extends keyof Data>(key: Key, value: Data[Key]): void;
  setData(data: Data): void;
}

interface Props {
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
  console.log('Form', formKey, form);

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
