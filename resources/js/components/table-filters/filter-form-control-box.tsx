import { FormControl, FormLabel } from '@chakra-ui/react';
import React, { PropsWithChildren } from 'react';

interface Props {
  title: string;
}

export default function FilterFormControlBox({
  title,
  children,
}: Props & PropsWithChildren) {
  return (
    <FormControl>
      <FormLabel>{title}</FormLabel>
      {children}
    </FormControl>
  );
}
