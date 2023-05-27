import React, { useMemo } from 'react';
import { Button, ButtonProps, Icon, Text } from '@chakra-ui/react';
import { FunnelIcon } from '@heroicons/react/24/solid';
import useQueryString from '@/hooks/use-query-string';

interface Props extends ButtonProps {
  validFilters: string[];
}

export default function FilterButton(props: Props) {
  const { params } = useQueryString();
  const { validFilters, ...buttonProps } = props;
  const appliedFilters = useMemo(() => {
    return Object.entries(params).filter(([key]) => validFilters.includes(key));
  }, [params]);

  return (
    <Button
      {...buttonProps}
      leftIcon={<Icon as={FunnelIcon} />}
      variant={'ghost'}
      colorScheme={'brand'}
      position={'relative'}
    >
      <span>Filters</span>
      {appliedFilters.length ? (
        <Text
          as={'span'}
          position={'absolute'}
          top={1}
          right={1}
          bg={'red.400'}
          rounded={'full'}
          color={'white'}
          fontSize={'10px'}
          w={4}
          h={4}
          display={'inline-flex'}
          justifyContent={'center'}
          alignItems={'center'}
        >
          {appliedFilters.length}
        </Text>
      ) : null}
    </Button>
  );
}
