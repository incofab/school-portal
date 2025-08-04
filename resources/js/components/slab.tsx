import React, { useState } from 'react';
import { Div } from '@/components/semantic';
import {
  BoxProps,
  Divider,
  HStack,
  Heading,
  HeadingProps,
  Spacer,
  VStack,
  useColorModeValue,
  IconButton,
  Collapse,
  Icon,
  Stack,
  Wrap,
} from '@chakra-ui/react';
import {
  PencilIcon,
  DocumentPlusIcon,
  ChevronDoubleDownIcon,
  ChevronDoubleUpIcon,
} from '@heroicons/react/24/outline';
import { TrashIcon } from '@heroicons/react/24/solid';
import { PageTitle } from './page-header';
import { InertiaLink } from '@inertiajs/inertia-react';
import DestructivePopover from '@/components/destructive-popover';
import useWebForm from '@/hooks/use-web-form';
import { Inertia } from '@inertiajs/inertia';
import useMyToast from '@/hooks/use-my-toast';

export const SlabBody = ({ children, ...props }: BoxProps) => (
  <Div {...props}>{children}</Div>
);

export const SlabFooter = ({ children, ...props }: BoxProps) => (
  <Div {...props}>{children}</Div>
);

interface SlabHeadingProps {
  title?: string;
  rightElement?: React.ReactNode;
}
export const SlabHeading = ({
  children,
  title,
  rightElement,
  ...props
}: SlabHeadingProps & HeadingProps) => (
  <Heading size={'md'} fontWeight={'medium'} {...props}>
    {children ? (
      children
    ) : (
      <Wrap
        // direction={{ base: 'column', md: 'row' }}
        justify={'space-between'}
        spacing={3}
      >
        <PageTitle>{title}</PageTitle>
        {rightElement}
      </Wrap>
    )}
    <Divider mt={2} />
  </Heading>
);

export default function Slab({ children, ...props }: BoxProps) {
  return (
    <Div
      border={'solid'}
      borderWidth={1}
      borderColor={useColorModeValue('gray.200', 'transparent')}
      rounded={'lg'}
      px={6}
      py={4}
      background={useColorModeValue('white', 'gray.800')}
      boxShadow={'0px 2px 6px rgba(0, 0, 0, 0.1)'}
      w={'full'}
      {...props}
    >
      <VStack align={'stretch'} spacing={4}>
        {children}
      </VStack>
    </Div>
  );
}

interface CollapsibleSlabProps {
  collapsed?: boolean;
  title?: string;
  addNewRoute?: any;
  editRoute?: any;
  deleteRoute?: any;
  rightElement?: React.ReactNode;
  children: React.ReactNode;
}

export const CollapsibleSlab = ({
  collapsed,
  title,
  addNewRoute = null,
  editRoute = null,
  deleteRoute = null,
  rightElement,
  children,
  ...props
}: CollapsibleSlabProps & HeadingProps) => {
  const [isCollapsed, setIsCollapsed] = useState(collapsed ?? false);
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

  const handleToggle = () => {
    setIsCollapsed(!isCollapsed);
  };

  async function deleteItem(route: any) {
    const res = await deleteForm.submit((data, web) => web.delete(route));
    handleResponseToast(res);
    Inertia.reload();
  }

  return (
    <Div
      border={'solid'}
      borderWidth={1}
      borderColor={useColorModeValue('gray.200', 'transparent')}
      rounded={'lg'}
      px={6}
      py={4}
      background={useColorModeValue('white', 'gray.800')}
      boxShadow={'0px 2px 6px rgba(0, 0, 0, 0.1)'}
      w={'full'}
      mb={4}
    >
      <VStack align={'stretch'} spacing={4}>
        <Heading size={'md'} fontWeight={'medium'} {...props}>
          <HStack>
            <PageTitle style={{ whiteSpace: 'nowrap', overflow: 'hidden' }}>
              {title}
            </PageTitle>
            <Spacer />
            {rightElement}
            {addNewRoute && (
              <IconButton
                aria-label={'New Topic'}
                as={InertiaLink}
                href={addNewRoute}
                variant={'ghost'}
                colorScheme={'brand'}
                icon={<Icon as={DocumentPlusIcon} />}
              />
            )}
            {editRoute && (
              <IconButton
                aria-label={'Edit Topic'}
                as={InertiaLink}
                href={editRoute}
                variant={'ghost'}
                colorScheme={'brand'}
                icon={<Icon as={PencilIcon} />}
              />
            )}
            {deleteRoute && (
              <DestructivePopover
                label={'Delete this record?'}
                onConfirm={() => deleteItem(deleteRoute)}
                isLoading={deleteForm.processing}
              >
                <IconButton
                  aria-label={'Delete record'}
                  variant={'ghost'}
                  colorScheme={'red'}
                  icon={<Icon as={TrashIcon} />}
                />
              </DestructivePopover>
            )}
            <IconButton
              aria-label={isCollapsed ? 'Expand slab' : 'Collapse slab'}
              icon={
                isCollapsed ? (
                  <ChevronDoubleDownIcon />
                ) : (
                  <ChevronDoubleUpIcon />
                )
              }
              colorScheme={isCollapsed ? 'red' : 'brand'}
              onClick={handleToggle}
              size="xs"
              variant="ghost"
            />
          </HStack>
          <Divider mt={2} />
        </Heading>
        <Collapse in={!isCollapsed}>{children}</Collapse>
      </VStack>
    </Div>
  );
};
