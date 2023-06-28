import React from 'react';
import {
  HStack,
  ResponsiveValue,
  StackProps,
  Text,
  VStack,
} from '@chakra-ui/react';
import { SelectOptionType } from '@/types/types';

interface Props {
  contentData: SelectOptionType<string | React.ReactNode>[];
  labelWidth?: number | string | ResponsiveValue<number | 'px'>;
}
export default function Dt({
  contentData,
  labelWidth,
  children,
  ...props
}: Props & StackProps) {
  return (
    <VStack spacing={1} align={'stretch'} {...props}>
      {contentData.map(({ label, value }) => (
        <HStack my={1} key={label} align={'stretch'} spacing={2}>
          <Text width={labelWidth ?? '120px'} fontWeight={'semibold'}>
            {label}
          </Text>
          <Text>{value}</Text>
        </HStack>
      ))}
    </VStack>
  );
}
