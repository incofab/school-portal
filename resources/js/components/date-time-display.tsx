import React from 'react';
import { Text } from '@chakra-ui/react';
import { format } from 'date-fns';
import { dateFormat } from '@/util/util';

export default function DateTimeDisplay({ dateTime }: { dateTime: string }) {
  return dateTime ? (
    <Text>{format(new Date(dateTime), dateFormat)}</Text>
  ) : (
    <></>
  );
}
