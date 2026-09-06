import React from 'react';
import { Institution, InstitutionUser } from '@/types/models';
import { Box, HStack, Image, Text, VStack } from '@chakra-ui/react';
import ImagePaths from '@/util/images';
import { QRCodeSVG } from 'qrcode.react';
import { ucFirst } from '@/util/util';

interface Props {
  institution: Institution;
  staff: InstitutionUser;
}

export default function StaffIdCard({ institution, staff }: Props) {
  const fullName = staff.user?.full_name ?? 'Unnamed staff member';
  const staffPhone = staff.user?.phone || 'Not provided';
  const staffEmail = staff.user?.email || 'Not provided';

  return (
    <Box
      className="staff-id-card"
      display="inline-flex"
      verticalAlign="top"
      flexDirection="column"
      width="100mm"
      height="65mm"
      mx={{ base: 1, md: 2 }}
      my={2}
      overflow="hidden"
      border="1px solid"
      borderColor="gray.300"
      borderRadius="3mm"
      bg="white"
      color="gray.800"
      textAlign="left"
      boxShadow="0 1mm 3mm rgba(15, 23, 42, 0.12)"
      sx={{ breakInside: 'avoid' }}
    >
      <HStack
        align="center"
        spacing="3mm"
        minH="18mm"
        px="4mm"
        py="2.5mm"
        bg="gray.50"
        borderBottom="0.4mm solid"
        borderColor="blue.900"
      >
        <Image
          src={institution.photo ?? ImagePaths.default_school_logo}
          alt={`${institution.name} logo`}
          boxSize="11mm"
          flexShrink={0}
          objectFit="cover"
          borderRadius="full"
        />
        <VStack align="start" spacing="0.75mm" minW={0} flex={1}>
          <Text
            fontSize="11pt"
            lineHeight="1.05"
            fontWeight="800"
            color="blue.900"
            noOfLines={2}
            wordBreak="break-word"
          >
            {institution.name}
          </Text>
          {institution.address ? (
            <Text
              fontSize="7.5pt"
              lineHeight="1.1"
              color="gray.600"
              noOfLines={2}
              wordBreak="break-word"
            >
              {institution.address}
            </Text>
          ) : null}
          <Text
            fontSize="6pt"
            lineHeight="1.1"
            fontWeight="700"
            color="blue.900"
            noOfLines={1}
          >
            {[institution.phone, institution.email].filter(Boolean).join(' | ')}
          </Text>
        </VStack>
      </HStack>

      <HStack
        align="center"
        spacing="4mm"
        flex={1}
        minH={0}
        px="4mm"
        py="2.5mm"
      >
        <Image
          src={staff.user?.photo ?? ImagePaths.default_school_logo}
          alt={fullName}
          width="22mm"
          height="27mm"
          flexShrink={0}
          objectFit="cover"
          border="0.5mm solid"
          borderColor="blue.900"
          borderRadius="2mm"
        />

        <VStack
          align="start"
          justify="center"
          spacing="1.7mm"
          minW={0}
          flex={1}
        >
          <Box width="full" minW={0}>
            <Text
              fontSize="6pt"
              fontWeight="700"
              letterSpacing="0.4px"
              color="gray.500"
            >
              STAFF NAME
            </Text>
            <Text
              mt="0.75mm"
              fontSize="12pt"
              lineHeight="1.05"
              fontWeight="800"
              color="blue.900"
              noOfLines={2}
              wordBreak="break-word"
            >
              {fullName}
            </Text>
          </Box>

          <HStack align="end" spacing="4mm" width="full" minW={0}>
            <VStack align="start" spacing="1.7mm" minW={0} flex={1}>
              <StaffDetail label="PHONE" value={staffPhone} />
              <StaffDetail label="Role" value={ucFirst(String(staff.type))} />
            </VStack>

            <Box
              flexShrink={0}
              width="20mm"
              height="20mm"
              display="flex"
              alignItems="center"
              justifyContent="center"
            >
              <QRCodeSVG
                value={String(staff.id)}
                width="100%"
                height="100%"
                includeMargin
                aria-label={`QR code for ${fullName}`}
              />
            </Box>
          </HStack>
        </VStack>
      </HStack>

      <HStack
        justify="flex-end"
        minH="7mm"
        px="4mm"
        borderTop="0.3mm solid"
        borderColor="gray.200"
      >
        <Text fontSize="5.5pt" color="gray.600" noOfLines={1}>
          {`Email: ${staffEmail}`}
        </Text>
      </HStack>
    </Box>
  );
}

function StaffDetail({ label, value }: { label: string; value: string }) {
  return (
    <Box width="full" minW={0} color="black">
      <Text
        fontSize="5.5pt"
        fontWeight="700"
        letterSpacing="0.3px"
        color="gray.500"
      >
        {label}
      </Text>
      <Text
        mt="0.75mm"
        fontSize="10pt"
        lineHeight="1.05"
        fontWeight="800"
        color="gray.800"
        noOfLines={1}
        wordBreak="break-word"
      >
        {value}
      </Text>
    </Box>
  );
}
