import React from 'react';
import {
  Button,
  HStack,
  Icon,
  IconButton,
  Text,
  VStack,
  Wrap,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import {
  TimetableCell,
  SelectOptionType,
  TimetableActionableType,
} from '@/types/types';
import EnumSelect from '../dropdown-select/enum-select';
import CourseSelect from '../selectors/course-select';
import MySelect from '../dropdown-select/my-select';
import StaffSelect from '../selectors/staff-select';
import SchoolActivitySelect from '../selectors/school-activity-select';
import { TrashIcon } from '@heroicons/react/24/outline';

interface Props {
  timetableCell: TimetableCell;
  classificationId: number;
  isOpen: boolean;
  onClose(): void;
  onSuccess(timetableCell: TimetableCell): void;
}
const hours = [
  0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21,
  22, 23,
];
const mins = [0, 5, 10, 15, 20, 25, 30, 35, 40, 45, 50, 55];
export default function CreateEditTimetableModal({
  timetableCell,
  classificationId,
  isOpen,
  onSuccess,
  onClose,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();

  const webForm = useWebForm({
    ...timetableCell,
    classification_id: classificationId,
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(instRoute('timetables.store'), data)
    );

    if (!handleResponseToast(res)) return;

    onClose();
    onSuccess(webForm.data);
    webForm.reset();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Create / Edit Timetable'}
      bodyContent={
        <VStack spacing={2} align={'stretch'} style={{ marginTop: '20px' }}>
          <Text>From</Text>
          <TimeRange
            time={webForm.data.start_time}
            updateTime={(time) => webForm.setValue('start_time', time)}
          />
          <Text>To</Text>
          <TimeRange
            time={webForm.data.end_time}
            updateTime={(time) => webForm.setValue('end_time', time)}
          />

          <ActionSelect
            actionId={webForm.data.actionable_id}
            setActionId={(id, label) =>
              webForm.setData({
                ...webForm.data,
                actionable_id: id,
                actionable_name: label,
              })
            }
            actionType={
              webForm.data.actionable_type as
                | TimetableActionableType
                | undefined
            }
            setActionType={(type) => webForm.setValue('actionable_type', type)}
          />
          <Wrap my={3}>
            {webForm.data.coordinators?.map((coordinator) => (
              <HStack
                borderRadius="full"
                key={coordinator.coordinator_user_id}
                borderWidth={'1px'}
                borderColor={'brand.500'}
                borderLeftRadius={'10px'}
                borderRightRadius={'10px'}
                color={'brand.700'}
                px={2}
                py={0}
              >
                <Text>{coordinator.coordinator_name}</Text>
                <IconButton
                  aria-label="Remove Coordinator"
                  icon={<Icon as={TrashIcon} />}
                  variant={'ghost'}
                  color={'red'}
                  onClick={() => {
                    webForm.setValue(
                      'coordinators',
                      webForm.data.coordinators?.filter(
                        (item) =>
                          item.coordinator_user_id !==
                          coordinator.coordinator_user_id
                      )
                    );
                  }}
                />
              </HStack>
            ))}
          </Wrap>
          <CoordinatorSelect
            setCoordinatorId={(coordinator) => {
              if (
                webForm.data.coordinators?.find(
                  (item) => item.coordinator_user_id === coordinator.value
                )
              ) {
                return;
              }
              webForm.setValue('coordinators', [
                ...(webForm.data.coordinators ?? []),
                {
                  coordinator_user_id: coordinator.value,
                  coordinator_name: coordinator.label.split(' - ')[0],
                },
              ]);
            }}
          />
        </VStack>
      }
      footerContent={
        <HStack spacing={2}>
          <Button variant={'ghost'} onClick={onClose}>
            Close
          </Button>
          <Button
            colorScheme={'brand'}
            onClick={onSubmit}
            isLoading={webForm.processing}
          >
            Submit
          </Button>
        </HStack>
      }
    />
  );
}

function TimeRange({
  time,
  updateTime,
}: {
  time: string;
  updateTime: (time: string) => void;
}) {
  const [hr, min] = time.split(':').map((item) => parseInt(item));

  function padTime(time: number) {
    return `${time > 9 ? '' : '0'}${time}`;
  }
  return (
    <HStack>
      <MySelect
        isMulti={false}
        selectValue={hr}
        getOptions={() =>
          hours.map((value) => {
            return {
              label: padTime(value),
              value: value,
            };
          }) as SelectOptionType<number>[]
        }
        onChange={(e: any) => updateTime(`${e.label}:${padTime(min)}`)}
      />
      <Text>:</Text>
      <MySelect
        isMulti={false}
        selectValue={min}
        getOptions={() =>
          mins.map((value) => {
            return {
              label: `${value > 9 ? '' : '0'}${value}`,
              value: value,
            };
          }) as SelectOptionType<number>[]
        }
        onChange={(e: any) => updateTime(`${padTime(hr)}:${e.label}`)}
      />
    </HStack>
  );
}

function ActionSelect({
  actionId,
  setActionId,
  actionType,
  setActionType,
}: {
  actionId?: number;
  setActionId: (id: number, label: string) => void;
  actionType?: TimetableActionableType;
  setActionType: (type: TimetableActionableType) => void;
}) {
  return (
    <VStack align={'stretch'}>
      <EnumSelect
        enumData={TimetableActionableType}
        selectValue={actionType}
        isMulti={false}
        isClearable={true}
        onChange={(e: any) => setActionType(e.value)}
      />
      {actionType === TimetableActionableType.Course && (
        <CourseSelect
          value={actionId}
          isClearable={true}
          onChange={(e: any) => setActionId(e.value, e.label)}
          isMulti={false}
        />
      )}

      {actionType === TimetableActionableType.SchoolActivity && (
        <SchoolActivitySelect
          value={actionId}
          isClearable={true}
          onChange={(e: any) => setActionId(e.value, e.label)}
          isMulti={false}
        />
      )}
    </VStack>
  );
}

function CoordinatorSelect({
  coordinatorId,
  setCoordinatorId,
}: {
  coordinatorId?: number;
  setCoordinatorId: (coordinator: SelectOptionType<number>) => void;
}) {
  return (
    <StaffSelect
      value={coordinatorId}
      isClearable={true}
      onChange={(e: any) => {
        console.log(e);
        setCoordinatorId(e);
      }}
      isMulti={false}
      valueKey="id"
    />
  );
}
