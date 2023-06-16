import React from 'react';
import useSharedProps from '@/hooks/use-shared-props';
import { Avatar, BoxProps, Divider, HStack, Text } from '@chakra-ui/react';
import { Div } from '@/components/semantic';

const ManagerSidebarHeader = ({ ...props }: BoxProps) => {
  const { currentUser } = useSharedProps();
  return (
    <Div {...props} p={1}>
      <HStack height={'70px'} spacing={2} width={'full'}>
        <Avatar
          src={currentUser.photo_url}
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
          {currentUser.full_name}
        </Text>
      </HStack>
      <Divider background={'brand.50'} />
    </Div>
  );
};
export default ManagerSidebarHeader;
