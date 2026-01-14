import React from 'react';
import { useState } from 'react';
import { Icon, VStack, HStack, Text, IconButton, Flex } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { PlusIcon } from '@heroicons/react/24/solid';
import {
  Timetable,
  SchoolActivity,
  Course,
  Classification,
} from '@/types/models';
import { useModalValueToggle } from '@/hooks/use-modal-toggle';
import CreateEditTimetableModal from './../../../components/modals/create-edit-timetable-modal';
import { PencilIcon, TrashIcon } from '@heroicons/react/24/solid';
import { TimetableCell, WeekDay, TimetableActionableType } from '@/types/types';
import { Div } from '@/components/semantic';
import DestructivePopover from '@/components/destructive-popover';
import useWebForm from '@/hooks/use-web-form';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useMyToast from '@/hooks/use-my-toast';
import useIsStaff from '@/hooks/use-is-staff';
import ClassificationSelect from '@/components/selectors/classification-select';
import { Inertia } from '@inertiajs/inertia';

interface Props {
  timetables: Timetable[];
  classification: Classification;
  schoolActivities: SchoolActivity[];
}

interface FormattedTimetable {
  [dayId: number]: TimetableCell[];
}

export default function ListTimetables({
  timetables,
  classification,
  schoolActivities,
}: Props) {
  const createEditTimetableModalToggle = useModalValueToggle<TimetableCell>();
  const [formattedTableState, setFormattedTableState] =
    useState<FormattedTimetable>(getFormattedTimetable());
  const { instRoute } = useInstitutionRoute();
  const isStaff = useIsStaff();

  function getActionableName(timetable: Timetable) {
    if (timetable.actionable_type === TimetableActionableType.Course) {
      return (timetable.actionable as Course)?.title;
    }
    return (timetable.actionable as SchoolActivity)?.title;
  }

  // let f = {} as FormattedTimetable;
  // useEffect(() => {
  //   f = {};
  //   timetables.forEach((timetable) => {
  //     const thisDay = f[timetable.day] ?? [];
  //     if (!f[timetable.day]) {
  //       f[timetable.day] = thisDay;
  //     }
  //     thisDay.push({
  //       id: timetable.id,
  //       day: timetable.day,
  //       start_time: timetable.start_time,
  //       end_time: timetable.end_time,
  //       actionable_type: timetable.actionable_type,
  //       actionable_id: timetable.actionable_id,
  //       actionable_name: getActionableName(timetable),
  //       coordinators: timetable.timetable_coordinators?.map((coordinator) => ({
  //         coordinator_user_id: coordinator.institution_user!.id,
  //         coordinator_name: coordinator.institution_user!.user!.full_name,
  //       })),
  //     });
  //   });
  //   setFormattedTableState(f);
  // }, []);

  function getFormattedTimetable() {
    let fff = {} as FormattedTimetable;
    timetables.forEach((timetable) => {
      const thisDay = fff[timetable.day] ?? [];
      if (!fff[timetable.day]) {
        fff[timetable.day] = thisDay;
      }

      thisDay.push({
        id: timetable.id,
        day: timetable.day,
        start_time: timetable.start_time,
        classification_id: timetable.classification_id,
        end_time: timetable.end_time,
        actionable_type: timetable.actionable_type,
        actionable_id: timetable.actionable_id,
        actionable_name: getActionableName(timetable),
        coordinators: timetable.timetable_coordinators?.map((coordinator) => ({
          coordinator_user_id: coordinator.institution_user!.id,
          coordinator_name: coordinator.institution_user!.user!.full_name,
        })),
      });
    });
    return fff;
  }

  function getNextTimeRange(
    prevCellStartTime: string,
    prevCellEndTime: string
  ) {
    const [prevStartTimeHour, prevStartTimeMinute] = prevCellStartTime
      .split(':')
      .map((item) => parseInt(item));

    let [prevEndTimeHour, prevEndTimeMinute] = prevCellEndTime
      .split(':')
      .map((item) => parseInt(item));

    //== Move 1 hour to the minutes
    if (prevStartTimeMinute > prevEndTimeMinute) {
      prevEndTimeHour = prevEndTimeHour - 1;
      prevEndTimeMinute = prevEndTimeMinute + 60;
    }

    const minutesDiff = prevEndTimeMinute - prevStartTimeMinute; // Minutes Difference
    const sumMinutes = prevEndTimeMinute + minutesDiff;

    const extraHour = Math.floor(sumMinutes / 60); // Whole number result
    const nextMinute = sumMinutes % 60; // Remainder

    const hourDiff = prevEndTimeHour - prevStartTimeHour;
    let nextHour = prevEndTimeHour + hourDiff + extraHour;

    if (nextHour > 23) {
      nextHour = nextHour - 24;
    }

    return `${nextHour.toString().padStart(2, '0')}:${nextMinute
      .toString()
      .padStart(2, '0')}`;
  }

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Timetable"
          rightElement={
            isStaff && (
              <Div minW={'150px'}>
                <ClassificationSelect
                  selectValue={{
                    label: classification.title,
                    value: classification.id,
                  }}
                  isMulti={false}
                  isClearable={true}
                  onChange={(e: any) => {
                    const id = e?.value;
                    if (!id || id == classification.id) {
                      return;
                    }
                    Inertia.visit(instRoute('timetables.classTimetable', [id]));
                  }}
                />
              </Div>
            )
          }
        />

        {/* Table */}
        <SlabBody overflowX="auto">
          {Object.entries(WeekDay).map(([day, dayId]) => {
            const parsedkey = parseInt(dayId.toString());
            const cells = formattedTableState[parsedkey] ?? [];
            return (
              <HStack key={dayId} gap="0">
                <Div
                  p="2"
                  borderWidth="2px"
                  minW="120px"
                  h="110px"
                  display={'flex'}
                  alignItems={'center'}
                >
                  {day}
                </Div>
                {cells.map((cell, i) => (
                  <Cell
                    cell={cell}
                    key={`${dayId}.${i}.${cell.start_time}`}
                    onEditClick={() =>
                      createEditTimetableModalToggle.open(cell)
                    }
                  />
                ))}

                {isStaff && (
                  <IconButton
                    colorScheme={'brand'}
                    variant={'outline'}
                    aria-label="Add"
                    size="sm"
                    ml="2"
                    icon={<Icon as={PlusIcon} />}
                    onClick={() => {
                      const prevCellStartTime =
                        cells[cells.length - 1]?.start_time ?? '08:00';
                      const prevCellEndTime =
                        cells[cells.length - 1]?.end_time ?? '09:00';

                      const startTimeRange =
                        cells[cells.length - 1]?.end_time ?? '08:00';
                      const cell = {
                        day: parsedkey,
                        start_time: startTimeRange,
                        classification_id: classification.id,
                        end_time: getNextTimeRange(
                          prevCellStartTime,
                          prevCellEndTime
                        ),
                      };
                      cells.push(cell);
                      setFormattedTableState({
                        ...formattedTableState,
                        [dayId]: cells,
                      });
                      createEditTimetableModalToggle.open(cell);
                    }}
                  />
                )}
              </HStack>
            );
          })}
        </SlabBody>
      </Slab>
      {createEditTimetableModalToggle.state && (
        <CreateEditTimetableModal
          timetableCell={createEditTimetableModalToggle.state}
          classificationId={
            createEditTimetableModalToggle.state.classification_id
          }
          schoolActivities={schoolActivities}
          {...createEditTimetableModalToggle.props}
          onSuccess={() => window.location.reload()} // Reloads the page
        />
      )}
    </DashboardLayout>
  );
}

function Cell({
  cell,
  onEditClick,
}: {
  cell: TimetableCell;
  onEditClick(): void;
}) {
  const deleteForm = useWebForm({});
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const isStaff = useIsStaff();

  async function deleteItem(cell: TimetableCell) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('timetables.destroy', [cell.id]))
    );
    handleResponseToast(res);
    // Inertia.reload();
    window.location.reload(); // Reloads the page
  }

  //== Add a border style to the active activity in the timetable.
  let cellBorderColor = '';
  const now = new Date();
  const todayIndex = now.getDay() - 1; //== In Javascript, days of the week starts on Sunday, hence Sunday = 0. But on this project, WeekDay starts on Monday, making Monday = 0.

  if (cell.day === todayIndex) {
    const currentHour = String(now.getHours()).padStart(2, '0');
    const currentMinute = String(now.getMinutes()).padStart(2, '0');

    const cellStartTime = cell ? cell.start_time : '8:00';
    const cellEndTime = cell ? cell.end_time : '9:00';

    const [cellStartTimeHour, cellStartTimeMinute] = cellStartTime
      .split(':')
      .map((item) => parseInt(item));

    // eslint-disable-next-line prefer-const
    let [cellEndTimeHour, cellEndTimeMinute] = cellEndTime
      .split(':')
      .map((item) => parseInt(item));

    if (cellEndTimeMinute < 1) {
      cellEndTimeMinute = 60;
    }

    if (
      parseInt(currentHour) >= cellStartTimeHour &&
      parseInt(currentHour) <= cellEndTimeHour &&
      parseInt(currentMinute) >= cellStartTimeMinute &&
      parseInt(currentMinute) <= cellEndTimeMinute
    ) {
      cellBorderColor = 'tomato';
    }
  }

  return (
    <VStack gap={1}>
      <Div
        p="2"
        borderWidth="2px"
        w="150px"
        h="110px"
        borderColor={cellBorderColor ?? ''}
      >
        {/* Time Range:  */}
        <Flex justify="space-between">
          <Text fontSize="sm" fontWeight="medium" marginEnd="auto">
            {cell ? `${cell.start_time} - ${cell.end_time}` : ''}{' '}
          </Text>

          {isStaff && (
            <IconButton
              colorScheme={'red'}
              variant={'ghost'}
              aria-label="edit"
              size="xs"
              icon={<Icon as={PencilIcon} />}
              onClick={() => onEditClick()}
            />
          )}
        </Flex>

        {/* Action:  */}
        <Flex justify="space-between" my={1}>
          <Text fontSize="sm" marginEnd="auto" noOfLines={1}>
            {cell.actionable_name}
          </Text>

          {isStaff && (
            <DestructivePopover
              label={'Delete this activity'}
              onConfirm={() => deleteItem(cell)}
              isLoading={deleteForm.processing}
            >
              <IconButton
                colorScheme={'red'}
                variant={'ghost'}
                aria-label="edit"
                size="xs"
                icon={<Icon as={TrashIcon} />}
              />
            </DestructivePopover>
          )}
        </Flex>

        {/* Coordinator:  */}
        <Text fontSize="sm" noOfLines={2} color={'brand.700'}>
          {cell?.coordinators
            ?.map((item) => item.coordinator_name)
            .join(', ') ?? ''}
        </Text>
      </Div>
    </VStack>
  );
}
