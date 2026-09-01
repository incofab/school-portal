import React from 'react';
import { Institution, InstitutionUser, Student } from '@/types/models';
import { Div } from '@/components/semantic';
import {
  Box,
  Button,
  Heading,
  HStack,
  Text,
  VStack,
  useBreakpointValue,
} from '@chakra-ui/react';
import useSharedProps from '@/hooks/use-shared-props';
import { QRCodeSVG } from 'qrcode.react';
import StaffIdCard from '@/components/id-cards/staff-id-card';
import StudentIdCard from '@/components/id-cards/student-id-card';

interface Props {
  persons: InstitutionUser[] | Student[];
}

function isStudent(person: InstitutionUser | Student): person is Student {
  return 'code' in person;
}

export default function UserIdCards({ persons }: Props) {
  const { currentInstitution } = useSharedProps();

  return (
    <Div textAlign={'center'} bg="white" minH="100vh" py={{ base: 4, md: 8 }}>
      <HStack
        className="hidden-on-print"
        maxW="6xl"
        mx="auto"
        px={{ base: 4, md: 8 }}
        mb={5}
        justify="space-between"
        align="center"
      >
        <Box textAlign="left">
          <Heading size="md">Staff ID cards</Heading>
          <Box as="p" mt={1} color="gray.500" fontSize="sm">
            Larger, print-ready cards with essential staff details.
          </Box>
        </Box>
        <Button colorScheme="brand" onClick={() => window.print()}>
          Print ID cards
        </Button>
      </HStack>

      <Div id="staff-id-cards-list">
        {persons.map((person) =>
          isStudent(person) ? (
            <StudentIdCard
              key={person.id}
              institution={currentInstitution}
              student={person}
            />
          ) : (
            <StaffIdCard
              key={person.id}
              institution={currentInstitution}
              staff={person}
            />
          )
        )}
      </Div>
      <InstitutionQrCard institution={currentInstitution} />
    </Div>
  );
}

function InstitutionQrCard({ institution }: { institution: Institution }) {
  const qrSize = useBreakpointValue({ base: 240, sm: 280, md: 340 }) ?? 280;

  function printCard() {
    const cleanup = () =>
      document.body.classList.remove('printing-institution-qr-card');

    document.body.classList.add('printing-institution-qr-card');
    window.addEventListener('afterprint', cleanup, { once: true });
    window.print();
  }

  return (
    <Div
      id="institution-qr-card"
      display={'inline-block'}
      width={{ base: 'calc(100% - 32px)', md: '520px' }}
      maxW={'520px'}
      mx={2}
      my={8}
      p={{ base: 5, md: 8 }}
      border={'1px solid #000'}
      borderRadius={'md'}
      background={'white'}
      color={'black'}
      textAlign={'center'}
      sx={{ breakInside: 'avoid' }}
    >
      <VStack spacing={2}>
        <Text
          as={'h2'}
          fontSize={{ base: 'xl', md: '2xl' }}
          fontWeight={'bold'}
        >
          {institution.name}
        </Text>
        <Text fontSize={'sm'}>Staff Attendance QR Code</Text>
        <Box
          mt={4}
          p={3}
          background={'white'}
          border={'1px solid'}
          borderColor={'gray.300'}
          display={'flex'}
          justifyContent={'center'}
        >
          <QRCodeSVG value={institution.uuid} size={qrSize} includeMargin />
        </Box>
        <Button
          className="hidden-on-print"
          mt={4}
          colorScheme={'brand'}
          onClick={printCard}
        >
          Print QR Card
        </Button>
      </VStack>
    </Div>
  );
}
