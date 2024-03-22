import React from 'react';
import { Classification, Pin, Student, User } from '@/types/models';
import { Div } from '@/components/semantic';
import { Avatar, HStack, Text, useColorModeValue } from '@chakra-ui/react';
import useSharedProps from '@/hooks/use-shared-props';
import ImagePaths from '@/util/images';
import startCase from 'lodash/startCase';

interface Props {
  classification: Classification;
  pins: (Pin & {
    student: Student & {
      user: User;
    };
  })[];
}

export default function StudentPinTiles({ pins, classification }: Props) {
  const { currentInstitution } = useSharedProps();
  return (
    <Div textAlign={'center'} bg={useColorModeValue('white', 'gray.900')}>
      <Div my={2}>
        <Text size={'md'} fontWeight={'bold'} textDecoration={'underline'}>
          {classification.title}
        </Text>
      </Div>
      {pins.map((pin) => (
        <Div
          display={'inline-block'}
          width={'250px'}
          mx={1}
          my={1}
          border={'1px solid #000'}
          p={2}
          key={pin.id}
        >
          <HStack align={'stretch'}>
            <Avatar
              src={currentInstitution.photo ?? ImagePaths.default_school_logo}
            />
            <Div
              verticalAlign={'center'}
              pl={1}
              overflow={'hidden'}
              textAlign={'left'}
            >
              <Text whiteSpace={'nowrap'} fontSize={'md'} fontWeight={'bold'}>
                {currentInstitution.name}
              </Text>
              <Text
                fontSize={'sm'}
                whiteSpace={'nowrap'}
                textOverflow={'ellipsis'}
                overflow={'hidden'}
              >
                {pin.student.user.full_name}
              </Text>
            </Div>
          </HStack>
          <Div>
            <Text fontSize={'xs'} fontWeight={'bold'}>{`${startCase(
              pin.term
            )} Term, ${pin.academic_session?.title} Result Checker`}</Text>
          </Div>
          <Div>
            <Text as={'span'}>Id:</Text>
            <Text fontSize={'md'} fontWeight={'bold'} as={'span'} ml={2}>
              {pin.student.code}
            </Text>
          </Div>
          <Div>
            <Text as={'span'}>Pin:</Text>
            <Text fontSize={'md'} fontWeight={'bold'} as={'span'} ml={2}>
              {pin.pin}
            </Text>
          </Div>
          {/* <Text fontSize={'sm'} lineHeight={'14px'} mt={1}>
            Use this ID in conjunction with the 
          </Text> */}
        </Div>
      ))}
    </Div>
  );
}
