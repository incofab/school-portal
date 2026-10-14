import React from 'react';
import { Button, HStack, Text, VStack } from '@chakra-ui/react';

const colors = [
  { name: 'Evergreen', value: '#1f6b50' },
  { name: 'Ocean blue', value: '#1769AA' },
  { name: 'Royal blue', value: '#3447A8' },
  { name: 'Violet', value: '#7141A6' },
  { name: 'Burgundy', value: '#8F2942' },
  { name: 'Teal', value: '#087E83' },
  { name: 'Midnight', value: '#26354A' },
  { name: 'Charcoal', value: '#454B52' },
  { name: 'Rose', value: '#B94765' },
  { name: 'Amber', value: '#A86912' },
];

interface Props {
  value: string;
  onChange: (value: string) => void;
}

export default function PrimaryColorPicker({ value, onChange }: Props) {
  return (
    <VStack align="start" spacing={2}>
      <HStack spacing={2} flexWrap="wrap" aria-label="Choose a brand color">
        {colors.map((color) => (
          <Button
            key={color.value}
            type="button"
            aria-label={color.name}
            aria-pressed={value.toLowerCase() === color.value.toLowerCase()}
            title={color.name}
            onClick={() => onChange(color.value)}
            minW="40px"
            h="40px"
            p={0}
            borderRadius="full"
            borderWidth={
              value.toLowerCase() === color.value.toLowerCase() ? 3 : 1
            }
            borderColor={
              value.toLowerCase() === color.value.toLowerCase()
                ? 'gray.800'
                : 'gray.300'
            }
            bg={color.value}
            _hover={{ transform: 'scale(1.08)' }}
            _focusVisible={{ boxShadow: 'outline' }}
          >
            <span aria-hidden="true" style={{ color: 'white', fontSize: 18 }}>
              {value.toLowerCase() === color.value.toLowerCase() ? '✓' : ''}
            </span>
          </Button>
        ))}
      </HStack>
      <Text fontSize="sm" color="gray.600">
        Selected: {value}
      </Text>
    </VStack>
  );
}
