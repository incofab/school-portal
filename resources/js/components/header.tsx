import { Text } from '@chakra-ui/react';
import React from 'react';
import { Div } from './semantic';

function Header({ title, desc }: { title: string; desc: string }) {
  return (
    <Div
      textAlign={{ base: 'left', sm: 'center' }}
      mt={2}
      mb={5}
      ml={5}
      w={'full'}
    >
      <Text
        color={'brand.800'}
        fontSize={{ base: '20px', sm: '25px' }}
        fontWeight={'bold'}
      >
        {title}
      </Text>
      <Text color={'brand.800'}>{desc}</Text>
    </Div>
  );
}

export default Header;
