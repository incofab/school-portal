import { WebForm } from '@/hooks/use-web-form';
import {
  FormControl,
  FormControlProps,
  FormErrorMessage,
  FormLabel,
} from '@chakra-ui/react';
import React, { PropsWithChildren } from 'react';

interface Props {
  form: WebForm<{
    [key: string]: string;
  }>;
  title: string;
}

export default function FormControlBox({
  form,
  title,
  children,
  ...props
}: Props & PropsWithChildren<FormControlProps>) {
  return (
    <FormControl isInvalid={!!form.errors.key} {...props}>
      <FormLabel>{title}</FormLabel>
      {children}
      <FormErrorMessage>{form.errors.key}</FormErrorMessage>
    </FormControl>
  );
}
