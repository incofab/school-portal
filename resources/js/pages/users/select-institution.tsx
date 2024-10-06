import { Div } from '@/components/semantic';
import route from '@/util/route';
import {
  Avatar,
  Divider,
  Text,
  Wrap,
  WrapItem,
  useColorModeValue,
} from '@chakra-ui/react';
import React from 'react';
import useSharedProps from '@/hooks/use-shared-props';
import { Institution } from '@/types/models';

interface Props {
  institutions: Institution[];
}
export default function SelectInstitution({ institutions }: Props) {
  const { currentUser } = useSharedProps();

  return (
    <Div bg={useColorModeValue('blue.50', 'gray.900')} py={12} minH={'100vh'}>
      <Text fontSize={'lg'} textAlign={'center'} mb={4} fontWeight={'bold'}>
        Welcome {currentUser.first_name}, Select Institution
      </Text>
      <Wrap mx={'auto'} width={{ base: '80%', md: '40%' }}>
        {institutions.map((institution) => (
          <WrapItem
            as={'a'}
            href={route('institutions.dashboard', [institution.uuid])}
            mx={2}
          >
            <Div
              bg={'white'}
              p={4}
              shadow={'md'}
              rounded={'md'}
              width={'150px'}
            >
              <Avatar
                mx={3}
                name="Institution logo"
                src={institution.photo}
                width={'100px'}
                height={'100px'}
              />
              <Divider my={2} />
              <Text textAlign={'center'}>{institution.name}</Text>
            </Div>
          </WrapItem>
        ))}
      </Wrap>
    </Div>
  );
}
