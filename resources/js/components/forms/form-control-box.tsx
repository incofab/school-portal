import { WebForm } from '@/hooks/use-web-form';
import {
  FormControl,
  FormControlProps,
  FormErrorMessage,
  FormLabel,
} from '@chakra-ui/react';
import React, { PropsWithChildren } from 'react';

export interface FormProps<
  Data = Record<string, any>,
  Errors = Record<keyof Data, string>
> {
  data: Data;
  errors: Errors;
  processing: boolean;
}

interface Props {
  form: FormProps<{
    [key: string]: string | number | undefined | { [key: string]: string };
  }>;
  title: string;
  formKey: string;
}

export default function FormControlBox({
  form,
  title,
  formKey,
  children,
  ...props
}: Props & PropsWithChildren<FormControlProps>) {
  // console.log('Form', formKey, form);

  return (
    <FormControl isInvalid={!!form.errors[formKey]} {...props}>
      <FormLabel>{title}</FormLabel>
      {children}
      <FormErrorMessage>{form.errors[formKey]}</FormErrorMessage>
    </FormControl>
  );
}
