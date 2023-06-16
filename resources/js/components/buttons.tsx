import React from 'react';
import { Button, ButtonProps, FormControl } from '@chakra-ui/react';
import { InertiaLink, InertiaLinkProps } from '@inertiajs/inertia-react';

interface Props {
  title?: string;
}

export function FormControlButton({ title, ...props }: Props & ButtonProps) {
  return (
    <FormControl>
      <FormButton title={title} {...props} />
    </FormControl>
  );
}

export function FormButton({ title, ...props }: Props & ButtonProps) {
  return (
    <Button colorScheme={'brand'} type={'submit'} mt={2} size={'sm'} {...props}>
      {title ?? 'Submit'}
    </Button>
  );
}

export function BrandButton({ title, ...props }: Props & ButtonProps) {
  return (
    <Button colorScheme={'brand'} type={'submit'} size={'sm'} {...props}>
      {title ?? 'Submit'}
    </Button>
  );
}

export function LinkButton({
  title,
  ...props
}: Props & ButtonProps & InertiaLinkProps) {
  return (
    <Button
      as={InertiaLink}
      colorScheme={'brand'}
      variant={'solid'}
      size={'sm'}
      {...props}
      fontWeight={'normal'}
    >
      {title ?? 'New'}
    </Button>
  );
}
