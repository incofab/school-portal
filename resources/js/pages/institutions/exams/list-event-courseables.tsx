import React, { useState } from 'react';
import { Course, Event, EventCourseable } from '@/types/models';
import {
  HStack,
  IconButton,
  Icon,
  VStack,
  FormControl,
  Text,
  Divider,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { FormButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { TrashIcon } from '@heroicons/react/24/solid';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import DestructivePopover from '@/components/destructive-popover';
import useIsAdmin from '@/hooks/use-is-admin';
import CenteredBox from '@/components/centered-box';
import MySelect from '@/components/dropdown-select/my-select';
import { preventNativeSubmit } from '@/util/util';
import { Div } from '@/components/semantic';

interface Props {
  event: Event;
  courses: Course[];
  eventCourseables: PaginationResponse<EventCourseable>;
}

export default function ListEventCourseables({
  event,
  eventCourseables,
  courses,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const isAdmin = useIsAdmin();

  async function deleteItem(obj: EventCourseable) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('event-courseables.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['eventCourseables'] });
  }

  const headers: ServerPaginatedTableHeader<EventCourseable>[] = [
    {
      label: 'Title',
      value: 'title',
      render: (row) =>
        `${row.courseable?.course?.title} - ${row.courseable?.session}`,
    },
    // {
    //   label: 'Status',
    //   value: 'status',
    // },
    ...(isAdmin
      ? [
          {
            label: 'Action',
            render: (row: EventCourseable) => (
              <HStack>
                <DestructivePopover
                  label={'Delete this subjects'}
                  onConfirm={() => deleteItem(row)}
                  isLoading={deleteForm.processing}
                >
                  <IconButton
                    aria-label={'Delete subject'}
                    icon={<Icon as={TrashIcon} />}
                    variant={'ghost'}
                    colorScheme={'red'}
                  />
                </DestructivePopover>
              </HStack>
            ),
          },
        ]
      : []),
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title="Event Subjects" />
        <SlabBody>
          <CreateEventCourseable event={event} courses={courses} />
          <Divider my={1} />
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={eventCourseables.data}
            keyExtractor={(row) => row.id}
            paginator={eventCourseables}
            hideSearchField={true}
          />
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}

function CreateEventCourseable({
  event,
  courses,
}: {
  event: Event;
  courses: Course[];
}) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const [selectCourse, setSelectedCourse] = useState<Course>();
  const webForm = useWebForm({
    courseable_type: '',
    courseable_id: '',
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      return web.post(instRoute('event-courseables.store', [event.id]), data);
    });
    if (!handleResponseToast(res)) return;
    Inertia.visit(instRoute('event-courseables.index', [event.id]));
  };

  return (
    <CenteredBox>
      <Text fontSize={'lg'} fontWeight={'semibold'}>
        Add Subject
      </Text>
      <Divider my={1} />
      <HStack
        spacing={4}
        as={'form'}
        onSubmit={preventNativeSubmit(submit)}
        align={'stretch'}
        verticalAlign={'centered'}
      >
        <Div minW={'200px'}>
          <MySelect
            isMulti={false}
            selectValue={selectCourse}
            getOptions={() =>
              courses.map((course) => {
                return {
                  label: course.title,
                  value: course,
                };
              })
            }
            onChange={(e: any) => setSelectedCourse(e.value)}
          />
        </Div>
        {selectCourse?.sessions && (
          <MySelect
            isMulti={false}
            selectValue={webForm.data.courseable_id}
            getOptions={() =>
              selectCourse.sessions.map((courseSession) => {
                return {
                  label: courseSession.session,
                  value: courseSession.id + '',
                };
              })
            }
            onChange={(e: any) =>
              webForm.setData({
                ...webForm.data,
                courseable_type: 'course-session',
                courseable_id: e.value,
              })
            }
          />
        )}
        <FormButton isLoading={webForm.processing} />
      </HStack>
    </CenteredBox>
  );
}
