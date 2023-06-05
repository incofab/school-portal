import React from 'react';
import {
  BoxProps,
  HStack,
  ResponsiveValue,
  Text,
  VStack,
} from '@chakra-ui/react';
import { SelectOptionType } from '@/types/types';

interface Props {
  contentData: SelectOptionType[];
  labelWidth?: ResponsiveValue<number | 'px'>;
  spacingVertical?: ResponsiveValue<number | 'px'>;
}
export default function Dt({
  contentData,
  labelWidth,
  spacingVertical,
  children,
  ...props
}: Props & BoxProps) {
  return (
    <VStack spacing={spacingVertical ?? 1} align={'stretch'}>
      {contentData.map(({ label, value }) => (
        <HStack my={1} key={value}>
          <Text width={labelWidth ?? '100px'} fontWeight={'semibold'}>
            {label}
          </Text>{' '}
          <Text>{value}</Text>
        </HStack>
      ))}
    </VStack>
  );
}
