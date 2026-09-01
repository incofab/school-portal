import React from 'react';
import {
  Box,
  Flex,
  HStack,
  Heading,
  Text,
  VStack,
  useColorModeValue,
} from '@chakra-ui/react';

interface Props {
  title: string;
  description?: string;
  action?: React.ReactNode;
  children: React.ReactNode;
}

interface SectionProps {
  title: string;
  description?: string;
  action?: React.ReactNode;
  children: React.ReactNode;
  first?: boolean;
}

export function SettingsSection({
  title,
  description,
  action,
  children,
  first = false,
}: SectionProps) {
  return (
    <Box
      borderTopWidth={first ? 0 : '1px'}
      borderColor={useColorModeValue('gray.200', 'gray.600')}
      pt={first ? 0 : 5}
    >
      <Flex
        direction={{ base: 'column', md: 'row' }}
        justify="space-between"
        align={{ base: 'start', md: 'center' }}
        gap={3}
        mb={4}
      >
        <Box>
          <Heading size="sm">{title}</Heading>
          {description ? (
            <Text mt={1} color="gray.500" fontSize="sm">
              {description}
            </Text>
          ) : null}
        </Box>
        {action}
      </Flex>

      {children}
    </Box>
  );
}

export default function SettingsGroup({
  title,
  description,
  action,
  children,
}: Props) {
  return (
    <Box
      borderWidth="1px"
      borderColor={useColorModeValue('gray.400', 'gray.600')}
      borderRadius="xl"
      bg={useColorModeValue('white', 'gray.800')}
      p={{ base: 4, md: 5 }}
    >
      <Flex
        direction={{ base: 'column', md: 'row' }}
        justify="space-between"
        align={{ base: 'start', md: 'center' }}
        gap={3}
        mb={5}
      >
        <Box>
          <Heading size="sm">{title}</Heading>
          {description ? (
            <Text mt={1} color="gray.500" fontSize="sm">
              {description}
            </Text>
          ) : null}
        </Box>
      </Flex>

      <VStack align="stretch" spacing={5}>
        {children}
        <HStack justifyContent="flex-end">{action}</HStack>
      </VStack>
    </Box>
  );
}
