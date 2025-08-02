import React from 'react';
import { Button, ButtonProps, FormControl, HStack } from '@chakra-ui/react';
import { InertiaLink, InertiaLinkProps } from '@inertiajs/inertia-react';
import { Div } from './semantic';
import useSharedProps from '@/hooks/use-shared-props';
import { formatAsCurrency } from '@/util/util';

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

export function FormButton({ title, children, ...props }: Props & ButtonProps) {
  return (
    <Button colorScheme={'brand'} type={'submit'} mt={2} size={'sm'} {...props}>
      {title ?? children ?? 'Submit'}
    </Button>
  );
}

export function BrandButton({
  title,
  children,
  ...props
}: Props & ButtonProps) {
  return (
    <Button colorScheme={'brand'} type={'submit'} size={'sm'} {...props}>
      {title ?? children ?? 'Submit'}
    </Button>
  );
}
export function BrandButton2({ title, ...props }: Props & ButtonProps) {
  return (
    <Button colorScheme={'brand'} mt={2} size={'sm'} {...props}>
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

export function PayFromWalletButton({ title, ...props }: Props & ButtonProps) {
  const { currentUser } = useSharedProps();
  return (
    <Div>
      <BrandButton title={title} {...props} />
      <Div textAlign={'center'} mt={1} fontSize={'sm'}>
        Bal: {formatAsCurrency(currentUser.wallet)}
      </Div>
    </Div>
  );
}
