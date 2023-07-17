import { Avatar, BoxProps, Divider, HStack, Text } from '@chakra-ui/react';
import React from 'react';
import { Div } from './semantic';
import useSharedProps from '@/hooks/use-shared-props';

export const SidebarHeader = ({ ...props }: BoxProps) => {
  const { currentInstitution, currentUser } = useSharedProps();
  return (
    <Div {...props} p={1}>
      <HStack height={'70px'} spacing={2} width={'full'}>
        <Avatar
          src={currentInstitution.photo}
          width={'50px'}
          height={'50px'}
          border={'2px solid #2a8864'}
        />
        <Text
          color={'brand.100'}
          fontSize={'md'}
          width={'full'}
          fontWeight={'semibold'}
          whiteSpace={'nowrap'}
        >
          {currentInstitution.name}
        </Text>
      </HStack>
      <Divider background={'brand.50'} />
    </Div>
  );
};
