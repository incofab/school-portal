import React from 'react';
import { Text, TextProps } from '@chakra-ui/react';
import { format } from 'date-fns';
import { dateFormat } from '@/util/util';

export default function DateTimeDisplay({
  dateTime,
  dateTimeformat,
  ...props
}: { dateTime: string; dateTimeformat?: string } & TextProps) {
  return dateTime ? (
    <Text {...props}>
      {format(new Date(dateTime), dateTimeformat ?? dateFormat)}
    </Text>
  ) : (
    <></>
  );
}
