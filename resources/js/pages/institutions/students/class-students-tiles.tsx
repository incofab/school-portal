import React from 'react';
import { Student } from '@/types/models';
import { Div } from '@/components/semantic';
import { Avatar, HStack, Text, useColorModeValue } from '@chakra-ui/react';
import useSharedProps from '@/hooks/use-shared-props';
import ImagePaths from '@/util/images';

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
          width={'250px'}
          mx={1}
          my={1}
          border={'1px solid #000'}
          p={2}
          key={student.id}
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
                {student.user?.full_name}
              </Text>
            </Div>
          </HStack>
          <Div>
            <Text as={'span'}>Id:</Text>
            <Text fontSize={'lg'} fontWeight={'bold'} as={'span'} ml={2}>
              {student.code}
            </Text>
          </Div>
          <Div fontSize={'sm'} mt={1}>
            {window.location.origin}/student/login
          </Div>
          {/* <Text fontSize={'sm'} lineHeight={'14px'} mt={1}>
            Use this ID in conjunction with the 
          </Text> */}
        </Div>
      ))}
    </Div>
  );
}
