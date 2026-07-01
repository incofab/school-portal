import { Radio, RadioGroup, RadioGroupProps, VStack } from '@chakra-ui/react';
import React from 'react';

interface Props {}

export default function SelectMidTerm({ ...props }: Props & RadioGroupProps) {
  let value = props.value;
  if (value == 'true' || value == '1') {
    value = '1';
  } else if (value == 'false' || value == '0') {
    value = '0';
  } else {
    value = '';
  }
  return (
    <RadioGroup {...props} value={value}>
      <VStack align={'start'}>
        <Radio value={''}>Both full and mid term records</Radio>
        <Radio value={'0'}>Only full term</Radio>
        <Radio value={'1'}>Only mid term</Radio>
      </VStack>
    </RadioGroup>
  );
}
