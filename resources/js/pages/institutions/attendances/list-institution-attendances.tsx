import React from 'react';
import { Attendance } from '@/types/models';
import {
  Badge,
  Box,
  HStack,
  Icon,
  IconButton,
  Progress,
  Stack,
  Text,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useModalToggle from '@/hooks/use-modal-toggle';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import UsersTableFilters from '@/components/table-filters/users-table-filters';
import { EyeIcon, TrashIcon } from '@heroicons/react/24/solid';
import { Inertia } from '@inertiajs/inertia';
import DestructivePopover from '@/components/destructive-popover';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import DisplayUserFullname from '@/domain/institutions/users/display-user-fullname';
import InfoPopover from '@/components/info-popover';
import { format, differenceInMinutes } from 'date-fns';

interface Props {
  attendance: PaginationResponse<Attendance>;
}

export default function ListAttendances({ attendance }: Props) {
  const { instRoute } = useInstitutionRoute();
  const userFilterToggle = useModalToggle();

  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

  async function deleteItem(obj: Attendance) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('attendances.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload();
  }

  const headers: ServerPaginatedTableHeader<Attendance>[] = [
    {
      label: 'Person',
      value: 'institution_user.user.full_name',
      render: (row) => (
        <VStack align="start" spacing={1}>
          <Box fontWeight="semibold">
            <DisplayUserFullname user={row.institution_user.user} />
          </Box>
          <HStack spacing={2}>
            <Badge textTransform="capitalize" colorScheme="purple">
              {row.institution_user.role}
            </Badge>
            {row.institution_user.role === 'student' && (
              <Badge colorScheme="gray">
                {row.institution_user.student?.classification?.title ?? 'Class'}
              </Badge>
            )}
          </HStack>
        </VStack>
      ),
    },
    {
      label: 'Status',
      render: (row) => <AttendanceStatus attendance={row} />,
    },
    {
      label: 'Check In',
      render: (row) => <TimeBlock label="In" value={row.signed_in_at} />,
    },
    {
      label: 'Check Out',
      render: (row) => <TimeBlock label="Out" value={row.signed_out_at} />,
    },
    {
      label: 'Duration',
      render: (row) => <DurationBlock attendance={row} />,
    },
    {
      label: 'Recorded By',
      render: (row) => (
        <Text color="gray.700">
          {row.staff_user?.user?.full_name ?? 'System'}
        </Text>
      ),
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack>
          <DestructivePopover
            label={
              'Do you really want to delete this Attendance? Be careful!!!'
            }
            onConfirm={() => deleteItem(row)}
            isLoading={deleteForm.processing}
          >
            <IconButton
              aria-label={'Delete attendance'}
              icon={<Icon as={TrashIcon} />}
              variant={'ghost'}
              colorScheme={'red'}
            />
          </DestructivePopover>

          {row.remark?.trim() ? (
            <InfoPopover label={row.remark}>
              <IconButton
                aria-label={'Remark'}
                icon={<Icon as={EyeIcon} />}
                variant={'ghost'}
                colorScheme={'brand'}
              />
            </InfoPopover>
          ) : null}
        </HStack>
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title="Attendance Record" />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={attendance.data}
            keyExtractor={(row) => row.id}
            validFilters={['role']}
            paginator={attendance}
            onFilterButtonClick={userFilterToggle.open}
          />
        </SlabBody>
      </Slab>
      <UsersTableFilters {...userFilterToggle.props} />
    </DashboardLayout>
  );
}

function AttendanceStatus({ attendance }: { attendance: Attendance }) {
  const isCheckedOut = !!attendance.signed_out_at;

  return (
    <Stack spacing={2} minW="150px">
      <HStack>
        <Badge colorScheme={isCheckedOut ? 'green' : 'orange'}>
          {isCheckedOut ? 'Completed' : 'Checked in'}
        </Badge>
      </HStack>
      <Progress
        value={isCheckedOut ? 100 : 50}
        size="xs"
        colorScheme={isCheckedOut ? 'green' : 'orange'}
        borderRadius="full"
      />
    </Stack>
  );
}

function TimeBlock({ label, value }: { label: string; value?: string }) {
  if (!value) {
    return <Text color="gray.500">Not recorded</Text>;
  }

  const date = new Date(value);

  return (
    <Box>
      <Text fontSize="xs" color="gray.500">
        {label} • {format(date, 'MMM d, yyyy')}
      </Text>
      <Text fontWeight="semibold">{format(date, 'h:mm a')}</Text>
    </Box>
  );
}

function DurationBlock({ attendance }: { attendance: Attendance }) {
  if (!attendance.signed_in_at || !attendance.signed_out_at) {
    return <Text color="gray.500">In progress</Text>;
  }

  const minutes = differenceInMinutes(
    new Date(attendance.signed_out_at),
    new Date(attendance.signed_in_at)
  );
  const hours = Math.floor(minutes / 60);
  const mins = minutes % 60;

  return (
    <Text fontWeight="semibold">
      {hours > 0 ? `${hours}h ` : ''}
      {mins}m
    </Text>
  );
}
