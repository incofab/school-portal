import React from 'react';
import { InternalNotification, InternalNotificationRead } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { LinkButton } from '@/components/buttons';
import {
  Badge,
  Box,
  Button,
  HStack,
  Icon,
  IconButton,
  SimpleGrid,
  Stat,
  StatLabel,
  StatNumber,
  Text,
} from '@chakra-ui/react';
import DateTimeDisplay from '@/components/date-time-display';
import { InertiaLink } from '@inertiajs/inertia-react';
import { ArrowLeftIcon } from '@heroicons/react/24/outline';
import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';

interface Props {
  notification: InternalNotification;
  recipients: PaginationResponse<InternalNotificationRead>;
  listUrl: string;
}

export default function ShowSentNotification({
  notification,
  recipients,
  listUrl,
}: Props) {
  const recipientHeaders: ServerPaginatedTableHeader<InternalNotificationRead>[] =
    [
      {
        label: 'Name',
        value: 'reader_name',
      },
      {
        label: 'Read At',
        render: (row) => <DateTimeDisplay dateTime={row.read_at} />,
      },
    ];

  return (
    <Slab>
      <SlabHeading
        title="Sent Notification"
        rightElement={
          <HStack>
            <IconButton
              as={InertiaLink}
              href={listUrl}
              aria-label={'Back'}
              icon={<Icon as={ArrowLeftIcon} />}
              variant={'ghost'}
            />
            <LinkButton title="all sent" href={listUrl} />
          </HStack>
        }
      />
      <SlabBody>
        <SimpleGrid columns={{ base: 1, md: 2 }} spacing={4} mb={4}>
          <Stat p={4} bg={'white'} borderRadius={'md'}>
            <StatLabel>Read</StatLabel>
            <StatNumber>
              {/* {summary.read_count} / {summary.recipient_count} */}
              {notification.reads_count}
            </StatNumber>
          </Stat>
          <Stat p={4} bg={'white'} borderRadius={'md'}>
            <StatLabel>Created</StatLabel>
            <StatNumber fontSize={'md'}>
              <DateTimeDisplay dateTime={notification.created_at} />
            </StatNumber>
          </Stat>
        </SimpleGrid>

        <Box p={4} bg={'white'} borderRadius={'md'} mb={5}>
          <Text fontWeight={'bold'} fontSize={'lg'} mb={2}>
            {notification.title}
          </Text>
          <Text mb={3}>{notification.body || '-'}</Text>
          <HStack>
            <Badge>{notification.type || 'general'}</Badge>
            {notification.action_url && (
              <Button
                as="a"
                href={notification.action_url}
                variant="link"
                size="sm"
                colorScheme="blue"
              >
                Action
              </Button>
            )}
          </HStack>
        </Box>

        <Text fontWeight={'bold'} mb={2}>
          Recipients
        </Text>
        <Box overflow={'auto'}>
          <ServerPaginatedTable
            scroll={true}
            headers={recipientHeaders}
            data={recipients.data}
            keyExtractor={(row) => row.id}
            paginator={recipients}
            hideSearchField={true}
          />
        </Box>
      </SlabBody>
    </Slab>
  );
}
