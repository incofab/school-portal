import React from 'react';
import { Text, TextProps } from '@chakra-ui/react';
import { format } from 'date-fns';
import { dateFormat } from '@/util/util';

export default function DateTimeDisplay({
  dateTime,
  ...props
}: { dateTime: string } & TextProps) {
  return dateTime ? (
    <Text {...props}>{format(new Date(dateTime), dateFormat)}</Text>
  ) : (
    <></>
  );
}
