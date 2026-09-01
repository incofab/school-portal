import React from 'react';
import { Institution, Student } from '@/types/models';
import { Box, HStack, Image, Text, VStack } from '@chakra-ui/react';
import ImagePaths from '@/util/images';
import { QRCodeSVG } from 'qrcode.react';

interface Props {
  institution: Institution;
  student: Student;
}

export default function StudentIdCard({ institution, student }: Props) {
  const fullName = student.user?.full_name ?? 'Unnamed student';

  return (
    <Box
      className="student-id-card"
      display="inline-flex"
      verticalAlign="top"
      flexDirection="column"
      width="100mm"
      height="60mm"
      mx={{ base: 1, md: 2 }}
      my={2}
      overflow="hidden"
      border="1px solid"
      borderColor="black"
      borderRadius="2mm"
      bg="transparent"
      color="black"
      textAlign="left"
      boxShadow="none"
      sx={{ breakInside: 'avoid' }}
    >
      <HStack
        align="center"
        spacing="4mm"
        minH="18mm"
        borderBottom="1px solid"
        borderColor="black"
      >
        <Image
          src={institution.photo ?? ImagePaths.default_school_logo}
          alt={`${institution.name} logo`}
          boxSize="12mm"
          flexShrink={0}
          objectFit="cover"
          borderRadius="full"
        />
        <VStack align="start" spacing="0.75mm" minW={0} flex={1}>
          <Text
            fontSize="11pt"
            lineHeight="1.05"
            fontWeight="800"
            color="black"
            noOfLines={2}
            wordBreak="break-word"
          >
            {institution.name}
          </Text>
          {institution.address ? (
            <Text
              fontSize="7.5pt"
              lineHeight="1.1"
              color="black"
              noOfLines={2}
              wordBreak="break-word"
            >
              {institution.address}
            </Text>
          ) : null}
        </VStack>
      </HStack>

      <HStack align="center" spacing="5mm" flex={1} minH={0} px="4mm" py="3mm">
        <Image
          src={student.user?.photo ?? ImagePaths.default_school_logo}
          alt={fullName}
          width="12mm"
          height="14mm"
          flexShrink={0}
          objectFit="cover"
          border="0px solid"
          borderColor="black"
          borderRadius="1mm"
        />

        <VStack
          align="start"
          justify="center"
          spacing="2.5mm"
          minW={0}
          flex={1}
        >
          <Box width="full" minW={0}>
            <Text fontSize="7pt" fontWeight="600" color="black">
              FULL NAME
            </Text>
            <Text
              mt="0.75mm"
              fontSize="12pt"
              lineHeight="1.05"
              fontWeight="800"
              color="black"
              noOfLines={2}
              wordBreak="break-word"
            >
              {fullName}
            </Text>
          </Box>
          <StudentDetail label="STUDENT ID" value={student.code} />
          <StudentDetail
            label="CLASS"
            value={student.classification?.title ?? 'Not assigned'}
          />
        </VStack>

        <Box
          flexShrink={0}
          width="25mm"
          height="25mm"
          display="flex"
          alignItems="center"
          justifyContent="center"
        >
          <QRCodeSVG
            value={String(student.institution_user_id)}
            width="100%"
            height="100%"
            includeMargin
            aria-label={`QR code for ${fullName}`}
          />
        </Box>
      </HStack>

      <HStack
        justify="flex-end"
        minH="5mm"
        px="4mm"
        borderTop="0px solid"
        borderColor="black"
      >
        <Text fontSize="6pt" color="black" noOfLines={1}>
          {institution.phone || institution.email || ''}
        </Text>
      </HStack>
    </Box>
  );
}

function StudentDetail({ label, value }: { label: string; value: string }) {
  return (
    <Box width="full" minW={0} color="black">
      <Text fontSize="7pt" fontWeight="600" color="black">
        {label}
      </Text>
      <Text
        mt="0.75mm"
        fontSize="10pt"
        lineHeight="1.05"
        fontWeight="800"
        color="black"
        noOfLines={1}
        wordBreak="break-word"
      >
        {value}
      </Text>
    </Box>
  );
}
