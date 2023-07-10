import { Radio, RadioGroup, RadioGroupProps, VStack } from '@chakra-ui/react';
import React from 'react';

interface Props {}

export default function SelectMidTerm({ ...props }: Props & RadioGroupProps) {
  return (
    <RadioGroup {...props}>
      <VStack align={'start'}>
        <Radio value={''}>Both full and mid term records</Radio>
        <Radio value={'0'}>Only full term</Radio>
        <Radio value={'1'}>Only mid term</Radio>
      </VStack>
    </RadioGroup>
  );
}
