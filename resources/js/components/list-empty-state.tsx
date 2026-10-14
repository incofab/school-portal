import React, { ReactNode } from 'react';
import { ArchiveBoxIcon } from '@heroicons/react/24/outline';
import { Box, Heading, Icon, Text, useColorModeValue } from '@chakra-ui/react';

interface Props {
  title: string;
  description: string;
  action?: ReactNode;
}

export default function ListEmptyState({ title, description, action }: Props) {
  const surface = useColorModeValue('brand.50', 'gray.800');
  const border = useColorModeValue('brand.200', 'gray.600');
  const iconSurface = useColorModeValue('brand.100', 'whiteAlpha.100');
  const iconColor = useColorModeValue('brand.700', 'brand.200');
  const headingColor = useColorModeValue('gray.800', 'gray.100');
  const descriptionColor = useColorModeValue('gray.600', 'gray.300');

  return (
    <Box
      role="status"
      textAlign="center"
      borderWidth="1px"
      borderColor={border}
      borderRadius="lg"
      bg={surface}
      px={{ base: 4, md: 8 }}
      py={{ base: 6, md: 8 }}
    >
      <Box
        display="grid"
        placeItems="center"
        boxSize={12}
        mx="auto"
        mb={3}
        borderRadius="full"
        bg={iconSurface}
        color={iconColor}
      >
        <Icon as={ArchiveBoxIcon} boxSize={5} aria-hidden="true" />
      </Box>
      <Heading as="h3" size="sm" color={headingColor} mb={2}>
        {title}
      </Heading>
      <Text
        color={descriptionColor}
        maxW="2xl"
        mx="auto"
        lineHeight="1.7"
        mb={action ? 4 : 0}
      >
        {description}
      </Text>
      {action}
    </Box>
  );
}
