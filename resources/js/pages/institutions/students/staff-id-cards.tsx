import React from 'react';
import { Institution, InstitutionUser, Student } from '@/types/models';
import { Div } from '@/components/semantic';
import {
  Avatar,
  Box,
  Button,
  HStack,
  Image,
  Text,
  VStack,
  useBreakpointValue,
  useColorModeValue,
} from '@chakra-ui/react';
import useSharedProps from '@/hooks/use-shared-props';
import ImagePaths from '@/util/images';
import { QRCodeSVG } from 'qrcode.react';

function isStudent(person: InstitutionUser | Student) {
  return (person as InstitutionUser).role === undefined;
}

interface Props {
  persons: InstitutionUser[] | Student[];
}

export default function UserIdCards({ persons }: Props) {
  const { currentInstitution } = useSharedProps();

  return (
    <Div textAlign={'center'} bg={useColorModeValue('white', 'gray.900')}>
      <Div id="staff-id-cards-list">
        {persons.map((person) => (
          <Div
            display={'inline-block'}
            width={'340px'}
            mx={2}
            my={2}
            border={'1px solid #000'}
            p={2}
            key={person.id}
            borderRadius={'md'}
          >
            <HStack align={'stretch'} mb={3}>
              <Avatar
                size="md"
                src={currentInstitution.photo ?? ImagePaths.default_school_logo}
              />
              <Div
                verticalAlign={'center'}
                pl={1}
                overflow={'hidden'}
                textAlign={'center'}
              >
                <Text whiteSpace={'nowrap'} fontSize={'lg'} fontWeight={'bold'}>
                  {currentInstitution.name}
                </Text>
                <Text whiteSpace={'nowrap'} fontSize={'xs'}>
                  {currentInstitution.address}
                </Text>
                <Text whiteSpace={'nowrap'} fontSize={'xs'}>
                  {currentInstitution.phone + ' / ' + currentInstitution.email}
                </Text>
              </Div>
            </HStack>

            <HStack align={'stretch'}>
              <Image
                rounded="md"
                src={person.user?.photo ?? ImagePaths.default_school_logo}
                h="75px"
                w="75px"
              />

              <Div textAlign={'left'} width={'190px'} fontSize={'sm'}>
                <Div mb={1}>
                  {isStudent(person) ? (
                    <Text>
                      ID No.:{' '}
                      <Text as={'span'} fontWeight={'bold'}>
                        {(person as Student).code}
                      </Text>
                    </Text>
                  ) : (
                    <Text>
                      Role:{' '}
                      <Text as={'span'} fontWeight={'bold'}>
                        {(person as InstitutionUser).role}
                      </Text>
                    </Text>
                  )}
                </Div>

                <Div>
                  <Text noOfLines={1}>
                    Name:{' '}
                    <Text as={'span'} fontWeight={'bold'}>
                      {person.user?.full_name}
                    </Text>
                  </Text>
                </Div>

                <Div fontSize={'xs'} mt={1} noOfLines={1}>
                  www.{window.location.hostname}/login
                </Div>
              </Div>

              <Box
                width={'73px'}
                height={'73px'}
                display={'flex'}
                justifyContent={'center'}
                alignItems={'center'}
              >
                <QRCodeSVG
                  value={
                    isStudent(person)
                      ? String((person as Student).institution_user_id)
                      : String(person.id)
                  }
                />
              </Box>
            </HStack>
          </Div>
        ))}
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
