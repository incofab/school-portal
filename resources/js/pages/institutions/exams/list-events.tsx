import React from 'react';
import { Assessment, CourseTeacher, Event } from '@/types/models';
import { HStack, IconButton, Icon } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import { CloudArrowDownIcon, PencilIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { InertiaLink } from '@inertiajs/inertia-react';
import {
  CheckBadgeIcon,
  PaperAirplaneIcon,
  TrashIcon,
} from '@heroicons/react/24/solid';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import DestructivePopover from '@/components/destructive-popover';
import useIsAdmin from '@/hooks/use-is-admin';
import DateTimeDisplay from '@/components/date-time-display';
import { dateTimeFormat } from '@/util/util';
import TransferEventResultModal from '@/components/modals/transfer-event-result-modal';
import { useModalValueToggle } from '@/hooks/use-modal-toggle';
import useIsStaff from '@/hooks/use-is-staff';

interface Props {
  events: PaginationResponse<Event>;
  assessments: Assessment[];
  courseTeacher?: CourseTeacher;
}

export default function ListEvents({
  events,
  assessments,
  courseTeacher,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const isAdmin = useIsAdmin();
  const isStaff = useIsStaff();
  const transferEventResultModalToggle = useModalValueToggle<Event>();

  async function deleteItem(obj: Event) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('events.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['events'] });
  }

  const headers: ServerPaginatedTableHeader<Event>[] = [
    {
      label: 'Title',
      value: 'title',
    },
    {
      label: 'Duration',
      value: 'duration',
    },
    {
      label: 'Starts at',
      value: 'starts_at',
      render: (row) => (
        <DateTimeDisplay
          dateTime={row.starts_at}
          dateTimeformat={dateTimeFormat}
        />
      ),
    },
    {
      label: 'View Corrections',
      render: (row) => (row.show_corrections ? 'Yes' : 'No'),
    },
    {
      label: 'Selectable Subjects',
      value: 'event_courseables_count',
    },
    {
      label: 'Num of Subjects',
      value: 'num_of_subjects',
    },
    ...(isStaff
      ? [
          {
            label: 'Transferred At',
            render: (row: Event) => {
              return (
                <>
                  {row.transferred_at && (
                    <Icon as={CheckBadgeIcon} color={'brand'} />
                  )}
                  <IconButton
                    aria-label={row.transferred_at ? 'Upload Again' : 'Upload'}
                    icon={<Icon as={PaperAirplaneIcon} />}
                    variant={'ghost'}
                    colorScheme={'brand'}
                    onClick={() => transferEventResultModalToggle.open(row)}
                    ml={2}
                  />
                </>
              );
            },
          },
        ]
      : []),
    {
      label: 'Action',
      render: (row: Event) => (
        <HStack>
          {isStaff && (
            <>
              <LinkButton
                href={instRoute('event-courseables.index', [row.id])}
                variant={'link'}
                title="Content"
              />
              <LinkButton
                href={instRoute('exams.index', [row.id])}
                variant={'link'}
                title="Exams"
              />
              <IconButton
                aria-label={'Edit Event'}
                icon={<Icon as={PencilIcon} />}
                as={InertiaLink}
                href={instRoute('events.edit', [row.id])}
                variant={'ghost'}
                colorScheme={'brand'}
              />
              <DestructivePopover
                label={'Download the exams of this event'}
                onConfirm={(onClose) => {
                  window.location.href = instRoute('events.download', [row.id]);
                  onClose();
                }}
                positiveButtonLabel="Download"
              >
                <IconButton
                  aria-label={'Download event exams'}
                  icon={<Icon as={CloudArrowDownIcon} />}
                  variant={'ghost'}
                  colorScheme={'green'}
                />
              </DestructivePopover>
              <DestructivePopover
                label={'Delete this event'}
                onConfirm={() => deleteItem(row)}
                isLoading={deleteForm.processing}
              >
                <IconButton
                  aria-label={'Delete event'}
                  icon={<Icon as={TrashIcon} />}
                  variant={'ghost'}
                  colorScheme={'red'}
                />
              </DestructivePopover>
            </>
          )}
          <LinkButton
            href={instRoute('events.show', [row.id])}
            variant={'link'}
            title="View"
          />
        </HStack>
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="List Events"
          rightElement={
            isAdmin ? (
              <LinkButton href={instRoute('events.create')} title={'New'} />
            ) : (
              <></>
            )
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={events.data}
            keyExtractor={(row) => row.id}
            paginator={events}
            hideSearchField={true}
          />
        </SlabBody>
        {transferEventResultModalToggle.state && (
          <TransferEventResultModal
            courseTeacher={courseTeacher}
            event={transferEventResultModalToggle.state}
            assessments={assessments}
            {...transferEventResultModalToggle.props}
            onSuccess={() => Inertia.reload({ only: ['events'] })}
          />
        )}
      </Slab>
    </DashboardLayout>
  );
}
