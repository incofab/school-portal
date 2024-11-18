import React from 'react';
import { Student } from '@/types/models';
import { Div } from '@/components/semantic';
import {
  Avatar,
  Box,
  HStack,
  Image,
  Text,
  useColorModeValue,
} from '@chakra-ui/react';
import useSharedProps from '@/hooks/use-shared-props';
import ImagePaths from '@/util/images';
// import QRCode from 'react-qr-code';
import { QRCodeSVG } from 'qrcode.react';

interface Props {
  students: Student[];
}

export default function ClassStudentTiles({ students }: Props) {
  const { currentInstitution } = useSharedProps();
  return (
    <Div textAlign={'center'} bg={useColorModeValue('white', 'gray.900')}>
      {students.map((student) => (
        <Div
          display={'inline-block'}
          width={'340px'}
          mx={2}
          my={2}
          border={'1px solid #000'}
          p={2}
          key={student.id}
          borderRadius={'md'}
        >
          <HStack align={'stretch'} mb={6}>
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
              src={student.user?.photo ?? ImagePaths.default_school_logo}
              h="75px"
              w="75px"
            />

            <Div textAlign={'left'} width={'190px'} fontSize={'sm'}>
              <Div mb={1}>
                <Text>
                  ID No.:
                  <Text as={'span'} fontWeight={'bold'}>
                    {' ' + student.code}
                  </Text>
                </Text>
              </Div>

              <Div>
                <Text>
                  Name:
                  <Text as={'span'} fontWeight={'bold'}>
                    {' ' + student.user?.full_name}
                  </Text>
                </Text>
              </Div>
            </Div>

            <Box
              width={'73px'}
              height={'73px'}
              display={'flex'}
              justifyContent={'center'}
              alignItems={'center'}
            >
              <QRCodeSVG value={student.institution_user_id + ''} />
            </Box>
          </HStack>
        </Div>
      ))}
    </Div>
  );
}
