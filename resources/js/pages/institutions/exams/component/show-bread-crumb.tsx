import React from 'react';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  Icon,
} from '@chakra-ui/react';
import { ChevronRightIcon } from '@heroicons/react/24/solid';
import { BreadCrumbParam } from '@/types/types';
import { InertiaLink } from '@inertiajs/inertia-react';

interface Props {
  breadCrumbItems: BreadCrumbParam[];
}

export default function ShowBreadCrumb({ breadCrumbItems }: Props) {
  return (
    <Breadcrumb
      spacing="8px"
      separator={<Icon as={ChevronRightIcon} color="gray.500" />}
      backgroundColor={'brand.100'}
      px={2}
      py={1}
    >
      {breadCrumbItems.map((item, i) => (
        <BreadcrumbItem
          key={item.title}
          isCurrentPage={i === breadCrumbItems.length - 1}
        >
          <BreadcrumbLink href={item.href ?? '#'} as={InertiaLink}>
            {item.title}
          </BreadcrumbLink>
        </BreadcrumbItem>
      ))}
    </Breadcrumb>
  );
}
