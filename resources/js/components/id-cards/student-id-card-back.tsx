import React from 'react';
import { Institution } from '@/types/models';
import { Box, HStack, Image, Text, VStack } from '@chakra-ui/react';
import ImagePaths from '@/util/images';

interface Props {
  institution: Institution;
  stamp?: string;
  template?: 1 | 2 | 3;
  orientation?: 'landscape' | 'portrait';
}

export default function StudentIdCardBack({
  institution,
  stamp,
  template = 1,
  orientation = 'landscape',
}: Props) {
  return (
    <Box
      className="student-id-card student-id-card-back"
      display="inline-flex"
      verticalAlign="top"
      flexDirection="column"
      justifyContent="space-between"
      width={orientation === 'portrait' ? '65mm' : '100mm'}
      height={orientation === 'portrait' ? '100mm' : '65mm'}
      mx={{ base: 1, md: 2 }}
      my={2}
      p="5mm"
      position="relative"
      overflow="hidden"
      border="1px solid"
      borderColor="gray.300"
      borderRadius="3mm"
      bg="white"
      color="gray.800"
      textAlign="center"
      boxShadow="0 1mm 3mm rgba(15, 23, 42, 0.12)"
      sx={{ breakInside: 'avoid' }}
    >
      {stamp && (
        <Image
          src={stamp}
          alt="Institution stamp"
          position="absolute"
          width="38mm"
          height="38mm"
          objectFit="contain"
          opacity={0.18}
          top="50%"
          left="50%"
          transform="translate(-50%, -50%)"
        />
      )}
      <HStack justify="center" spacing="2mm" position="relative">
        <Image
          src={institution.photo ?? ImagePaths.default_school_logo}
          alt={`${institution.name} logo`}
          boxSize="10mm"
          objectFit="cover"
          borderRadius="full"
          border="0.5mm solid"
          borderColor="gray.200"
        />
        <Text
          fontSize="10pt"
          fontWeight="800"
          lineHeight="1.1"
          color={
            template === 2
              ? 'teal.700'
              : template === 3
              ? 'brand.700'
              : 'blue.900'
          }
          noOfLines={2}
        >
          {institution.name}
        </Text>
      </HStack>
      <VStack spacing="1mm" position="relative">
        <Text
          fontSize="8pt"
          fontWeight="700"
          color={
            template === 2
              ? 'teal.700'
              : template === 3
              ? 'brand.700'
              : 'blue.900'
          }
          letterSpacing="0.4px"
        >
          PROPERTY OF {institution.name.toUpperCase()}
        </Text>
        <Text fontSize="7pt" lineHeight="1.25">
          If found, please return this card to the school.
        </Text>
        <Text
          fontSize="7pt"
          fontWeight="700"
          color={
            template === 2
              ? 'teal.700'
              : template === 3
              ? 'brand.700'
              : 'blue.900'
          }
        >
          SCHOOL ADMINISTRATIVE CONTACT
        </Text>
        {institution.phone && (
          <Text fontSize="8pt">Phone: {institution.phone}</Text>
        )}
        {institution.email && (
          <Text fontSize="8pt">Email: {institution.email}</Text>
        )}
        {institution.website && (
          <Text fontSize="8pt">{institution.website}</Text>
        )}
      </VStack>
      <Text
        position="relative"
        borderTop="0.3mm solid"
        borderColor="gray.200"
        pt="2mm"
        fontSize="7pt"
        fontWeight="700"
        color={
          template === 2
            ? 'teal.700'
            : template === 3
            ? 'brand.700'
            : 'blue.900'
        }
      >
        OFFICIAL SCHOOL ID
      </Text>
    </Box>
  );
}
