import React from 'react';
import { useProSidebar } from 'react-pro-sidebar';
import { IconButton, Icon, BoxProps } from '@chakra-ui/react';
import {
  Bars3Icon,
  ChevronDoubleLeftIcon,
  ChevronDoubleRightIcon,
} from '@heroicons/react/24/outline';
import { Div } from '@/components/semantic';

export default function SideBarToggleButton({ ...props }: BoxProps) {
  const { toggleSidebar, collapseSidebar, broken, collapsed } = useProSidebar();

  return (
    <Div {...props}>
      {broken ? (
        <IconButton
          aria-label={'sidebar toggler'}
          icon={<Icon as={Bars3Icon} />}
          onClick={() => toggleSidebar(!collapsed)}
          variant={'ghost'}
          color={'brand.500'}
        />
      ) : (
        <IconButton
          aria-label={'sidebar collapse'}
          icon={
            <Icon
              as={collapsed ? ChevronDoubleRightIcon : ChevronDoubleLeftIcon}
            />
          }
          onClick={() => collapseSidebar(!collapsed)}
          variant={'ghost'}
          color={'brand.500'}
        />
      )}
    </Div>
  );
}
