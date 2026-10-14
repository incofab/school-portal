import React, { ReactNode } from 'react';
import { Box, Heading, Text } from '@chakra-ui/react';

interface Props {
  title: string;
  description: string;
  action?: ReactNode;
}

export default function ListEmptyState({ title, description, action }: Props) {
  return (
    <Box
      role="status"
      textAlign="center"
      borderWidth="1px"
      borderColor="gray.200"
      borderRadius="md"
      bg="gray.50"
      px={{ base: 4, md: 8 }}
      py={{ base: 6, md: 8 }}
    >
      <Heading as="h3" size="sm" mb={2}>
        {title}
      </Heading>
      <Text color="gray.600" maxW="2xl" mx="auto" mb={action ? 4 : 0}>
        {description}
      </Text>
      {action}
    </Box>
  );
}
