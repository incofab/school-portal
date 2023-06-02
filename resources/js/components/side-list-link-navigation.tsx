import React from 'react';
import route from '@/util/route';
import { Box, Select, Text, VStack } from '@chakra-ui/react';
import { InertiaLink } from '@inertiajs/inertia-react';
import { Inertia } from '@inertiajs/inertia';

interface Props {
  links: Array<{
    label: string;
    routeName: string;
    routeParams?: any[];
    activeRoute?: string;
  }>;
}

export default function SideListLinkNavigation({ links }: Props) {
  function onSelectChange(e: React.ChangeEvent<HTMLSelectElement>) {
    Inertia.visit(e.currentTarget.value);
  }

  return (
    <div>
      <Select
        display={{ base: 'block', lg: 'none' }}
        onChange={onSelectChange}
        value={route().current()}
      >
        {links.map(link => (
          <option
            key={link.routeName}
            value={route(link.routeName, link.routeParams || [])}
          >
            {link.label}
          </option>
        ))}
      </Select>
      <VStack
        align={'stretch'}
        spacing={4}
        display={{ base: 'none', lg: 'flex' }}
      >
        {links.map(link => (
          <Box
            key={link.routeName}
            as={InertiaLink}
            href={route(link.routeName, link.routeParams || [])}
            color={
              route().current(
                link.activeRoute || link.routeName,
                link.routeParams || [],
              )
                ? 'brand.400'
                : undefined
            }
          >
            <Text>{link.label}</Text>
          </Box>
        ))}
      </VStack>
    </div>
  );
}
