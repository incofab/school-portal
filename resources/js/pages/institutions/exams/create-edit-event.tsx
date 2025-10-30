import React, { useState } from 'react';
import {
  Checkbox,
  Divider,
  FormControl,
  HStack,
  Icon,
  IconButton,
  Input,
  Spacer,
  Stack,
  Text,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { dateTimeFormat, preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { ClassificationGroup, Course, Event } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { BrandButton, FormButton } from '@/components/buttons';
import InputForm from '@/components/forms/input-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import format from 'date-fns/format';
import ClassificationGroupSelect from '@/components/selectors/classification-group-select';
import { EventType } from '@/types/types';
import EnumSelect from '@/components/dropdown-select/enum-select';
import { Div } from '@/components/semantic';
import MySelect from '@/components/dropdown-select/my-select';
import { PlusIcon, TrashIcon } from '@heroicons/react/24/outline';

interface Props {
  event?: Event;
  courses: Course[];
  classificationGroups: ClassificationGroup[];
}

export default function CreateOrUpdateEvent({
  event,
  courses,
  classificationGroups,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const [eventCourseableData, setEventCourseableData] = useState<
    EventCourseableData[]
  >([]);
  const webForm = useWebForm({
    title: event?.title ?? '',
    // description: event?.description ?? '',
    duration: event?.duration ?? '',
    status: event?.status ?? '',
    starts_at: event?.starts_at ?? '',
    num_of_subjects: event?.num_of_subjects ?? 1,
    type: event?.type ?? EventType.StudentTest,
    classification_id: event?.classification_id ?? '',
    classification_group_id: event?.classification_group_id ?? '',
    show_corrections: event?.show_corrections ?? false,
  });
  const forStudents = webForm.data.type === EventType.StudentTest;
  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      return event
        ? web.put(instRoute('events.update', [event]), data)
        : web.post(instRoute('events.store'), {
            ...data,
            event_courseables: eventCourseableData.map((item) => ({
              courseable_type: item.courseable_type,
              courseable_id: item.courseable_id,
            })),
          });
    });
    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.visit(instRoute('events.index'));
  };

  function addEventCourseable(newEventCourseableData: EventCourseableData) {
    if (
      eventCourseableData.find(
        (item) =>
          item.courseable_id === newEventCourseableData.courseable_id &&
          item.courseable_type === newEventCourseableData.courseable_type
      )
    ) {
      return;
    }
    setEventCourseableData([...eventCourseableData, newEventCourseableData]);
  }

  function deleteEventCourseable(
    selectedEventCourseableData: EventCourseableData
  ) {
    setEventCourseableData(
      eventCourseableData.filter(
        (item) =>
          item.courseable_id !== selectedEventCourseableData.courseable_id &&
          item.courseable_type !== selectedEventCourseableData.courseable_type
      )
    );
  }

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading title={`${event ? 'Update' : 'Create'} Event`} />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
            >
              <InputForm
                form={webForm as any}
                formKey="title"
                title="Event title"
                isRequired
              />
              {/* <InputForm
                form={webForm as any}
                formKey="description"
                title="Description [optional]"
              /> */}
              <InputForm
                form={webForm as any}
                formKey="duration"
                title="Duration [mins]"
                isRequired
              />
              {/* <InputForm
                form={webForm as any}
                formKey="num_of_subjects"
                title="Num of Subjects"
                type="number"
                min={1}
                max={4}
                isRequired
              /> */}
              <FormControlBox
                title="Start time"
                form={webForm as any}
                formKey="starts_at"
                isRequired
              >
                <Input
                  type={'datetime-local'}
                  max={'9999-12-31'}
                  value={
                    webForm.data.starts_at
                      ? format(new Date(webForm.data.starts_at), dateTimeFormat)
                      : ''
                  }
                  onChange={(e) =>
                    webForm.setValue(
                      'starts_at',
                      format(new Date(e.currentTarget.value), dateTimeFormat)
                    )
                  }
                />
              </FormControlBox>
              <FormControlBox
                title="Event Type"
                form={webForm as any}
                formKey="type"
              >
                <EnumSelect
                  enumData={EventType}
                  selectValue={webForm.data.type}
                  isClearable={true}
                  onChange={(e: any) => webForm.setValue('type', e?.value)}
                />
              </FormControlBox>
              {forStudents && (
                <FormControlBox
                  title="Class Group"
                  form={webForm as any}
                  formKey="classification_group_id"
                >
                  <ClassificationGroupSelect
                    selectValue={webForm.data.classification_group_id}
                    isMulti={false}
                    isClearable={true}
                    classificationGroups={classificationGroups}
                    onChange={(e: any) =>
                      webForm.setData({
                        ...webForm.data,
                        classification_group_id: e?.value,
                        classification_id: '',
                      })
                    }
                  />
                </FormControlBox>
              )}
              {!event && (
                <Div
                  border={'3px solid'}
                  borderColor={'brand.50'}
                  borderRadius={'5px'}
                  w={'100%'}
                  p={3}
                  ps={5}
                >
                  <CreateEventCourseable
                    courses={courses}
                    add={addEventCourseable}
                    remove={deleteEventCourseable}
                    eventCourseableData={eventCourseableData}
                  />
                </Div>
              )}
              <FormControl>
                <Checkbox
                  isChecked={webForm.data.show_corrections}
                  onChange={(e) =>
                    webForm.setValue(
                      'show_corrections',
                      e.currentTarget.checked
                    )
                  }
                  size={'md'}
                  colorScheme="brand"
                >
                  Allow students to view corrections after the exam
                </Checkbox>
              </FormControl>
              <FormControl>
                <FormButton isLoading={webForm.processing} />
              </FormControl>
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </DashboardLayout>
  );
}

interface EventCourseableData {
  course: Course;
  courseable_type?: string;
  courseable_id?: number;
  title?: string;
}

function CreateEventCourseable({
  courses,
  eventCourseableData,
  add,
  remove,
}: {
  courses: Course[];
  add: (newEventCourseableData: EventCourseableData) => void;
  remove: (newEventCourseableData: EventCourseableData) => void;
  eventCourseableData: EventCourseableData[];
}) {
  const [data, setData] = useState<EventCourseableData>();
  const { toastError } = useMyToast();

  const submit = async () => {
    if (!data?.course || !data.courseable_id) {
      return toastError('Please select a subject and session');
    }
    add(data);
    setData(undefined);
  };

  return (
    <CenteredBox>
      <Text fontSize={'lg'} fontWeight={'semibold'}>
        Add Subject
      </Text>
      <Divider my={1} />
      <Stack
        direction={{ base: 'column', md: 'row' }}
        spacing={4}
        align={'stretch'}
        verticalAlign={'centered'}
      >
        <Div minW={'200px'}>
          <MySelect
            isMulti={false}
            selectValue={data?.course}
            getOptions={() =>
              courses.map((course) => {
                return {
                  label: course.title,
                  value: course,
                };
              })
            }
            onChange={(e: any) => setData({ course: e.value })}
          />
        </Div>
        {data?.course?.sessions && (
          <Div minW={'200px'}>
            <MySelect
              refreshKey={String(data.course.id)}
              isMulti={false}
              selectValue={data?.courseable_id}
              getOptions={() =>
                data.course.sessions.map((courseSession) => {
                  return {
                    label: courseSession.session,
                    value: courseSession.id + '',
                  };
                })
              }
              onChange={(e: any) =>
                setData({
                  ...data,
                  courseable_id: e.value,
                  courseable_type: 'course-session',
                  title: `${data.course.code} - ${e.label}`,
                })
              }
            />
          </Div>
        )}
        <BrandButton
          leftIcon={<Icon as={PlusIcon} />}
          title="Add"
          mt={1}
          type={'button'}
          onClick={submit}
        />
      </Stack>
      <br />
      <VStack align={'stretch'} spacing={2}>
        {eventCourseableData.map((eventCourseableData) => (
          <HStack
            align={'stretch'}
            key={eventCourseableData.course.id}
            border={'1px solid'}
            borderRadius={'5px'}
            py={1}
            px={2}
          >
            <Text key={eventCourseableData.course.id} mt={1}>
              {eventCourseableData.title}
            </Text>
            <Spacer />
            <IconButton
              aria-label={'Delete subject'}
              icon={<Icon as={TrashIcon} />}
              variant={'ghost'}
              colorScheme={'red'}
              onClick={() => remove(eventCourseableData)}
            />
          </HStack>
        ))}
      </VStack>
    </CenteredBox>
  );
}
