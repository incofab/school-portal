import React from 'react';
import { Institution, Student } from '@/types/models';
import { Box, HStack, Image, Text, VStack } from '@chakra-ui/react';
import ImagePaths from '@/util/images';
import { QRCodeSVG } from 'qrcode.react';
import { ucFirst } from '@/util/util';

interface Props {
  institution: Institution;
  student: Student;
  template?: 1 | 2 | 3;
  orientation?: 'landscape' | 'portrait';
}

export default function StudentIdCard({
  institution,
  student,
  template = 1,
  orientation = 'landscape',
}: Props) {
  const fullName = student.user?.full_name ?? 'Unnamed student';
  const designationTitles = student.active_designation_assignments
    ?.map((assignment) => assignment.designation?.title)
    .filter(Boolean)
    .join(', ');

  if (orientation === 'portrait') {
    return (
      <PortraitCard
        institution={institution}
        student={student}
        fullName={fullName}
        designation={
          designationTitles || ucFirst(student.user?.gender || 'Not provided')
        }
        template={template}
      />
    );
  }

  if (template === 2) {
    return (
      <EditorialCard
        institution={institution}
        student={student}
        fullName={fullName}
        designation={
          designationTitles || ucFirst(student.user?.gender || 'Not provided')
        }
        orientation={orientation}
      />
    );
  }
  if (template === 3) {
    return (
      <ModernCard
        institution={institution}
        student={student}
        fullName={fullName}
        designation={
          designationTitles || ucFirst(student.user?.gender || 'Not provided')
        }
        orientation={orientation}
      />
    );
  }

  return (
    <Box
      className="student-id-card"
      display="inline-flex"
      boxSizing="border-box"
      verticalAlign="top"
      flexDirection="column"
      width={orientation === 'portrait' ? '65mm' : '100mm'}
      height={orientation === 'portrait' ? '100mm' : '65mm'}
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
          src={student.user?.photo ?? ImagePaths.default_user_photo}
          alt={fullName}
          width="22mm"
          height="27mm"
          flexShrink={0}
          objectFit="cover"
          border="0.5mm solid"
          borderColor="gray.100"
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
              FULL NAME
            </Text>
            <Text
              mt="0.75mm"
              fontSize="11pt"
              lineHeight="1.05"
              fontWeight="600"
              color="blue.900"
              noOfLines={2}
              wordBreak="break-word"
            >
              {fullName}
            </Text>
          </Box>
          <HStack align="end" spacing="4mm" width="full" minW={0}>
            <VStack align="start" spacing="1.7mm" minW={0} flex={1}>
              <StudentDetail label="STUDENT ID" value={student.code} />
              <StudentDetail
                label="CLASS"
                value={student.classification?.title ?? 'Not assigned'}
              />
              {designationTitles ? (
                <StudentDetail label="DESIGNATION" value={designationTitles} />
              ) : (
                <StudentDetail
                  label="GENDER"
                  value={ucFirst(student.user?.gender || 'Not provided')}
                />
              )}
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
                value={String(student.institution_user_id)}
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
        justify={'space-between'}
        minH="7mm"
        px="4mm"
        gap="3mm"
        borderTop="0.3mm solid"
        borderColor="gray.200"
      >
        {/* <Text fontSize="5.5pt" color="gray.600" noOfLines={1}>
          {institution.website ? `Web: ${institution.website}` : ''}
        </Text> */}
        {student.guardian_phone && (
          <Text
            fontSize="5.5pt"
            color="gray.600"
            noOfLines={1}
            textAlign="right"
          >
            {`Emergency: ${student.guardian_phone}`}
          </Text>
        )}
        {designationTitles && (
          <Text
            fontSize="5.5pt"
            color="gray.600"
            noOfLines={1}
            minW={0}
            flex={1}
          >
            Gender: {ucFirst(student.user?.gender || 'Not provided')}
          </Text>
        )}
        {/* {institution.website && (
          <Text
            fontSize="5.5pt"
            color="gray.600"
            noOfLines={1}
            textAlign="right"
            flexShrink={0}
            maxW="50%"
          >
            {`Web: ${institution.website}`}
          </Text>
        )} */}
      </HStack>
    </Box>
  );
}

function PortraitCard({
  institution,
  student,
  fullName,
  designation,
  template,
}: {
  institution: Institution;
  student: Student;
  fullName: string;
  designation: string;
  template: 1 | 2 | 3;
}) {
  const primary =
    template === 1 ? 'blue.900' : template === 2 ? 'purple.700' : 'brand.700';
  const secondary =
    template === 1 ? 'teal.700' : template === 2 ? 'teal.700' : 'teal.600';
  const accent =
    template === 1
      ? 'orange.600'
      : template === 2
      ? 'orange.600'
      : 'orange.600';
  return (
    <Box
      className="student-id-card"
      display="inline-flex"
      boxSizing="border-box"
      verticalAlign="top"
      flexDirection="column"
      width="65mm"
      height="100mm"
      mx={{ base: 1, md: 2 }}
      my={2}
      p="4mm"
      overflow="hidden"
      border="1px solid"
      borderColor="gray.300"
      borderRadius="3mm"
      bg="white"
      textAlign="center"
      boxShadow="0 1mm 3mm rgba(15, 23, 42, 0.12)"
      sx={{ breakInside: 'avoid' }}
    >
      {template === 1 && (
        <>
          <HStack
            justify="center"
            spacing="2mm"
            bg="blue.50"
            py="2mm"
            px="4mm"
            pt="4mm"
            mx="-4mm"
            mt="-4mm"
          >
            <Image
              src={institution.photo ?? ImagePaths.default_school_logo}
              alt={`${institution.name} logo`}
              boxSize="10mm"
              objectFit="contain"
            />
            <VStack align="start" spacing={0}>
              <Text
                fontSize="8pt"
                fontWeight="800"
                color={primary}
                noOfLines={2}
              >
                {institution.name}
              </Text>
              <Text fontSize="7pt" fontWeight="800" color={secondary}>
                STUDENT ID CARD
              </Text>
            </VStack>
          </HStack>
          <Box height="0.5mm" bg={primary} mt={0} mb="2mm" mx="-4mm" />
          <Image
            src={student.user?.photo ?? ImagePaths.default_user_photo}
            alt={fullName}
            width="25mm"
            height="27mm"
            objectFit="cover"
            borderRadius="2mm"
            mx="auto"
            border="0.6mm solid"
            borderColor={accent}
          />
          <Text fontSize="6pt" color={secondary} fontWeight="700" mt="1mm">
            FULL NAME
          </Text>
          <Text
            fontSize="11pt"
            lineHeight="1.05"
            color={accent}
            fontWeight="800"
            noOfLines={2}
          >
            {fullName}
          </Text>
          <VStack align="stretch" spacing="1.5mm" mt="2mm" textAlign="left">
            <StudentDetail
              label="STUDENT ID"
              value={student.code}
              labelColor={primary}
              valueColor={primary}
            />
            <HStack>
              <StudentDetail
                label="CLASS"
                value={student.classification?.title ?? 'Not assigned'}
                labelColor={secondary}
              />
              <StudentDetail
                label={
                  student.active_designation_assignments?.length
                    ? 'DESIGNATION'
                    : 'GENDER'
                }
                value={designation}
                labelColor={accent}
              />
            </HStack>
          </VStack>
          <HStack justify="space-between" align="center" mt="auto">
            <Text fontSize="5pt" color={primary} noOfLines={2} textAlign="left">
              {[institution.phone, institution.email]
                .filter(Boolean)
                .join(' · ')}
            </Text>
            <QRCodeSVG
              value={String(student.institution_user_id)}
              width="14mm"
              height="14mm"
              includeMargin
              aria-label={`QR code for ${fullName}`}
            />
          </HStack>
        </>
      )}
      {template === 2 && (
        <>
          <HStack
            justify="space-between"
            align="center"
            bg="purple.50"
            py="2mm"
            px="4mm"
            mx="-4mm"
            mt="-4mm"
            pt="4mm"
          >
            <Image
              src={institution.photo ?? ImagePaths.default_school_logo}
              alt={`${institution.name} logo`}
              boxSize="11mm"
              objectFit="contain"
            />
            <VStack align="end" spacing={0}>
              <Text
                fontSize="8pt"
                fontWeight="800"
                color={primary}
                noOfLines={2}
              >
                {institution.name}
              </Text>
              <Text fontSize="7pt" fontWeight="800" color={accent}>
                STUDENT ID CARD
              </Text>
            </VStack>
          </HStack>
          <Box
            borderTop="0.5mm solid"
            borderColor={accent}
            mt={0}
            mb="2mm"
            mx="-4mm"
          />
          <HStack align="center" spacing="3mm">
            <Image
              src={student.user?.photo ?? ImagePaths.default_user_photo}
              alt={fullName}
              width="22mm"
              height="25mm"
              objectFit="cover"
              borderRadius="2mm"
            />
            <VStack align="start" flex={1} minW={0}>
              <Text fontSize="6pt" color={secondary} fontWeight="700">
                FULL NAME
              </Text>
              <Text
                fontSize="11pt"
                lineHeight="1.05"
                color={accent}
                fontWeight="800"
                noOfLines={3}
              >
                {fullName}
              </Text>
              <Text fontSize="6pt" color={primary} fontWeight="700">
                STUDENT
              </Text>
            </VStack>
          </HStack>
          <VStack
            align="stretch"
            mt="3mm"
            spacing="2mm"
            textAlign="left"
            borderLeft="0.6mm solid"
            borderColor={secondary}
            pl="2mm"
          >
            <StudentDetail
              label="STUDENT ID"
              value={student.code}
              labelColor={primary}
              valueColor={primary}
            />
            <StudentDetail
              label="CLASS"
              value={student.classification?.title ?? 'Not assigned'}
              labelColor={secondary}
            />
            <StudentDetail
              label={
                student.active_designation_assignments?.length
                  ? 'DESIGNATION'
                  : 'GENDER'
              }
              value={designation}
              labelColor={accent}
            />
          </VStack>
          <HStack justify="space-between" align="center" mt="auto">
            <Text fontSize="5pt" color={primary} noOfLines={2} textAlign="left">
              {[institution.phone, institution.email]
                .filter(Boolean)
                .join(' · ')}
            </Text>
            <QRCodeSVG
              value={String(student.institution_user_id)}
              width="14mm"
              height="14mm"
              includeMargin
              aria-label={`QR code for ${fullName}`}
            />
          </HStack>
        </>
      )}
      {template === 3 && (
        <>
          <VStack spacing="1mm">
            <Image
              src={institution.photo ?? ImagePaths.default_school_logo}
              alt={`${institution.name} logo`}
              boxSize="12mm"
              objectFit="contain"
            />
            <Text fontSize="8pt" fontWeight="800" color={primary} noOfLines={2}>
              {institution.name}
            </Text>
            <Text fontSize="7pt" fontWeight="800" color={accent}>
              STUDENT ID CARD
            </Text>
          </VStack>
          <HStack justify="space-between" align="center" mt="2mm">
            <VStack align="start" spacing="1mm">
              <Text fontSize="6pt" color={secondary} fontWeight="700">
                FULL NAME
              </Text>
              <Text
                fontSize="11pt"
                lineHeight="1.05"
                color={accent}
                fontWeight="800"
                noOfLines={3}
              >
                {fullName}
              </Text>
            </VStack>
            <Image
              src={student.user?.photo ?? ImagePaths.default_user_photo}
              alt={fullName}
              width="21mm"
              height="25mm"
              objectFit="cover"
              borderRadius="2mm"
              border="0.5mm solid"
              borderColor={primary}
            />
          </HStack>
          <VStack align="stretch" mt="2mm" spacing="1.5mm" textAlign="left">
            <StudentDetail
              label="STUDENT ID"
              value={student.code}
              labelColor={primary}
            />
            <StudentDetail
              label="CLASS"
              value={student.classification?.title ?? 'Not assigned'}
              labelColor={secondary}
            />
            <StudentDetail
              label={
                student.active_designation_assignments?.length
                  ? 'DESIGNATION'
                  : 'GENDER'
              }
              value={designation}
              labelColor={accent}
            />
          </VStack>
          <HStack
            justify="space-between"
            align="center"
            mt="auto"
            borderTop="0.4mm solid"
            borderColor={accent}
            pt="1mm"
            bg="orange.50"
            px="4mm"
            mx="-4mm"
            mb="-4mm"
            pb="4mm"
          >
            <Text fontSize="5pt" color={primary} noOfLines={2} textAlign="left">
              {[institution.phone, institution.email]
                .filter(Boolean)
                .join(' · ')}
            </Text>
            <QRCodeSVG
              value={String(student.institution_user_id)}
              width="13mm"
              height="13mm"
              includeMargin
              aria-label={`QR code for ${fullName}`}
            />
          </HStack>
        </>
      )}
    </Box>
  );
}

function EditorialCard({
  institution,
  student,
  fullName,
  designation,
  orientation,
}: {
  institution: Institution;
  student: Student;
  fullName: string;
  designation: string;
  orientation: 'landscape' | 'portrait';
}) {
  return (
    <Box
      className="student-id-card"
      display="inline-flex"
      width={orientation === 'portrait' ? '65mm' : '100mm'}
      height={orientation === 'portrait' ? '100mm' : '65mm'}
      boxSizing="border-box"
      mx={{ base: 1, md: 2 }}
      my={2}
      p="4mm"
      gap="3mm"
      overflow="hidden"
      border="1px solid"
      borderColor="gray.300"
      borderRadius="3mm"
      bg="white"
      textAlign="left"
      boxShadow="0 1mm 3mm rgba(15, 23, 42, 0.12)"
      sx={{ breakInside: 'avoid' }}
    >
      <VStack
        width={orientation === 'portrait' ? 'full' : '20mm'}
        flexShrink={0}
        flexDirection={orientation === 'portrait' ? 'row' : 'column'}
        justify="space-between"
        align="center"
        borderRight={orientation === 'portrait' ? 'none' : '0.4mm solid'}
        borderBottom={orientation === 'portrait' ? '0.4mm solid' : 'none'}
        borderColor="orange.400"
        pr="3mm"
      >
        <Image
          src={institution.photo ?? ImagePaths.default_school_logo}
          alt={`${institution.name} logo`}
          boxSize="13mm"
          objectFit="contain"
        />
        <Image
          src={student.user?.photo ?? ImagePaths.default_user_photo}
          alt={fullName}
          width={orientation === 'portrait' ? '18mm' : '17mm'}
          height={orientation === 'portrait' ? '18mm' : '25mm'}
          objectFit="cover"
          borderRadius="1mm"
        />
        <Text
          fontSize="6pt"
          fontWeight="800"
          color="teal.700"
          textAlign="center"
        >
          STUDENT
        </Text>
      </VStack>
      <VStack align="stretch" justify="space-between" flex={1} minW={0}>
        <Box bg="purple.50" borderRadius="2mm" px="2mm" py="1.5mm">
          <HStack
            justify="space-between"
            align="center"
            borderBottom="0.4mm solid"
            borderColor="orange.300"
            pb="1mm"
            bg="purple.50"
            borderRadius="2mm"
            px="1.5mm"
            py="1mm"
          >
            <VStack align="start" spacing="0.3mm" minW={0}>
              <Text
                fontSize="7.5pt"
                fontWeight="800"
                color="purple.700"
                noOfLines={1}
              >
                {institution.name}
              </Text>
              <Text
                fontSize="9pt"
                fontWeight="900"
                color="teal.700"
                letterSpacing=".2px"
              >
                STUDENT ID CARD
              </Text>
            </VStack>
            <Text
              fontSize="6pt"
              fontWeight="800"
              color="orange.600"
              flexShrink={0}
            >
              IDENTITY
            </Text>
          </HStack>
        </Box>
        <Box>
          <Text
            fontSize="6pt"
            fontWeight="700"
            letterSpacing=".5px"
            color="teal.600"
          >
            FULL NAME
          </Text>
          <Text
            fontSize="12pt"
            fontWeight="800"
            color="orange.600"
            lineHeight="1.05"
            noOfLines={2}
          >
            {fullName}
          </Text>
        </Box>
        <HStack align="center" justify="space-between" flex={1} minH={0}>
          <VStack
            align="stretch"
            justify="space-around"
            h="full"
            spacing="1.2mm"
          >
            <StudentDetail label="STUDENT ID" value={student.code} />
            <StudentDetail
              label="CLASS"
              value={student.classification?.title ?? 'Not assigned'}
            />
            <StudentDetail
              label={
                student.active_designation_assignments?.length
                  ? 'DESIGNATION'
                  : 'GENDER'
              }
              value={designation}
            />
          </VStack>
          <QRCodeSVG
            value={String(student.institution_user_id)}
            width="18mm"
            height="18mm"
            includeMargin
            aria-label={`QR code for ${fullName}`}
          />
        </HStack>
        <Text fontSize="6pt" fontWeight="600" color="purple.600" noOfLines={1}>
          {[institution.phone, institution.email].filter(Boolean).join(' · ')}
        </Text>
      </VStack>
    </Box>
  );
}

function ModernCard({
  institution,
  student,
  fullName,
  designation,
  orientation,
}: {
  institution: Institution;
  student: Student;
  fullName: string;
  designation: string;
  orientation: 'landscape' | 'portrait';
}) {
  return (
    <Box
      className="student-id-card"
      display="inline-flex"
      flexDirection="column"
      verticalAlign="top"
      boxSizing="border-box"
      width={orientation === 'portrait' ? '65mm' : '100mm'}
      height={orientation === 'portrait' ? '100mm' : '65mm'}
      mx={{ base: 1, md: 2 }}
      my={2}
      p="4mm"
      overflow="hidden"
      border="1px solid"
      borderColor="gray.300"
      borderRadius="3mm"
      bg="white"
      textAlign="left"
      boxShadow="0 1mm 3mm rgba(15, 23, 42, 0.12)"
      sx={{ breakInside: 'avoid' }}
    >
      <HStack
        justify="space-between"
        align="center"
        pb="2mm"
        borderBottom="0.4mm solid"
        borderColor="orange.400"
      >
        <HStack spacing="2mm" minW={0}>
          <Image
            src={institution.photo ?? ImagePaths.default_school_logo}
            alt={`${institution.name} logo`}
            boxSize="9mm"
            objectFit="contain"
          />
          <Text fontSize="8pt" fontWeight="800" color="brand.700" noOfLines={1}>
            {institution.name}
          </Text>
        </HStack>
        <Text fontSize="5pt" fontWeight="800" color="orange.600">
          STUDENT ID
        </Text>
      </HStack>
      <HStack
        flex={1}
        minH={0}
        spacing="4mm"
        pt="2mm"
        flexDirection={orientation === 'portrait' ? 'column' : 'row'}
      >
        <Image
          src={student.user?.photo ?? ImagePaths.default_user_photo}
          alt={fullName}
          width="25mm"
          height={orientation === 'portrait' ? '27mm' : '34mm'}
          objectFit="cover"
          borderRadius="2mm"
        />
        <VStack
          align="stretch"
          justify="center"
          flex={1}
          minW={0}
          spacing="1.5mm"
        >
          <Text
            fontSize="5pt"
            fontWeight="700"
            color="teal.600"
            letterSpacing=".5px"
          >
            FULL NAME
          </Text>
          <Text
            fontSize="10pt"
            fontWeight="800"
            color="brand.800"
            lineHeight="1.05"
            noOfLines={2}
          >
            {fullName}
          </Text>
          <HStack align="start" spacing="3mm">
            <VStack align="start" flex={1} spacing="1mm">
              <StudentDetail label="STUDENT ID" value={student.code} />
              <StudentDetail
                label="CLASS"
                value={student.classification?.title ?? 'Not assigned'}
              />
              <StudentDetail
                label={
                  student.active_designation_assignments?.length
                    ? 'DESIGNATION'
                    : 'GENDER'
                }
                value={designation}
              />
            </VStack>
            <QRCodeSVG
              value={String(student.institution_user_id)}
              width="17mm"
              height="17mm"
              includeMargin
              aria-label={`QR code for ${fullName}`}
            />
          </HStack>
        </VStack>
      </HStack>
      <Text
        fontSize="5pt"
        color="brand.700"
        fontWeight="700"
        textAlign="right"
        noOfLines={1}
        bg="orange.50"
        borderRadius="2mm"
        px="2mm"
        py="1mm"
      >
        {[institution.phone, institution.email].filter(Boolean).join(' · ')}
      </Text>
    </Box>
  );
}

function StudentDetail({
  label,
  value,
  labelColor = 'gray.500',
  valueColor = 'gray.800',
}: {
  label: string;
  value: string;
  labelColor?: string;
  valueColor?: string;
}) {
  return (
    <Box width="full" minW={0} color="black">
      <Text
        fontSize="5.5pt"
        fontWeight="700"
        letterSpacing="0.3px"
        color={labelColor}
      >
        {label}
      </Text>
      <Text
        mt="0.75mm"
        fontSize="10pt"
        lineHeight="1.05"
        fontWeight="600"
        color={valueColor}
        noOfLines={1}
        wordBreak="break-word"
      >
        {value}
      </Text>
    </Box>
  );
}
