import React from 'react';
import { BoxProps, HStack, StackProps } from '@chakra-ui/react';
import { Div } from './semantic';

type ValueType = string | number | boolean;

interface Props extends StackProps {
  items: ButtonSwitchItem[];
  itemProps?: BoxProps;
  value: ValueType;
}

interface ButtonSwitchItem {
  label: string;
  value: ValueType;
  onClick: () => void;
}

export default function ButtonSwitch({
  items,
  itemProps,
  value,
  ...props
}: Props) {
  return (
    <HStack
      align={'stretch'}
      borderRadius={5}
      border={'1px solid'}
      borderColor={'gray.300'}
      spacing={0}
      {...props}
    >
      {items.map((item) => {
        const isActive = item.value == value;
        return (
          <Div
            backgroundColor={isActive ? 'brand.700' : 'transparent'}
            color={isActive ? 'white' : 'brand.700'}
            key={item.label}
            p={'5px 10px'}
            minWidth={'80px'}
            cursor={'pointer'}
            justifyContent={'center'}
            _hover={{ backgroundColor: isActive ? '' : 'brand.50' }}
            onClick={item.onClick}
            {...itemProps}
          >
            {item.label}
          </Div>
        );
      })}
    </HStack>
  );
}
