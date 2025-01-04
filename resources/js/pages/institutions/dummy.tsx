import React from 'react';

import { useState } from 'react';
import {
  Table,
  Button,
  Icon,
  Tbody,
  Td,
  Th,
  Thead,
  Tr,
  VStack,
  HStack,
  Text,
  IconButton,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { BrandButton } from '@/components/buttons';
import { PlusIcon } from '@heroicons/react/24/solid';
import { Inertia } from '@inertiajs/inertia';
import { Timetable } from '@/types/models';
import useModalToggle from '@/hooks/use-modal-toggle';
import CreateEditTimetableModal from './../../../components/modals/create-edit-timetable-modal';
import { PencilIcon } from '@heroicons/react/24/outline';

interface Props {
  timetables: Timetable[];
  formatedTimetables?: FormattedTimetable;
}
const dayIds = {
  0: 'Sunday',
  1: 'Monday',
  2: 'Tuesday',
  3: 'Wednesday',
  4: 'Thursday',
  5: 'Friday',
  6: 'Saturday',
};

interface FormattedTimetable {
  [dayId: number]: CellProp[];
}

export default function ListTimetables({
  timetables,
  formatedTimetables,
}: Props) {
  formatedTimetables = formatedTimetables ?? [];
  const createEditTimetableModalToggle = useModalToggle();

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Deposits"
          rightElement={
            <BrandButton
              onClick={() => {}}
              size="sm"
              mt={3}
              leftIcon={<Icon as={PlusIcon} />}
              variant={'solid'}
              colorScheme={'brand'}
              title="Add Column"
            />
          }
        />

        {/* Table */}
        <SlabBody>
          {Object.entries(dayIds).map(([key, value]) => {
            const parsedkey = parseInt(key);
            const cells = formatedTimetables[parsedkey] ?? [];
            if (!formatedTimetables[parsedkey]) {
              formatedTimetables[parsedkey] = cells;
            }
            return (
              <HStack>
                {cells.map((cell) => (
                  <Cell cell={cell} />
                ))}
                <IconButton
                  colorScheme={'brand'}
                  variant={'outline'}
                  aria-label="Add"
                  icon={<Icon as={PlusIcon} />}
                  onClick={() => {
                    cells.push({ timeFrom: 100, timeTo: 200 });
                  }}
                />
              </HStack>
            );
          })}
        </SlabBody>
      </Slab>

      <CreateEditTimetableModal
        timetable={timetables}
        {...createEditTimetableModalToggle.props}
        onSuccess={() => Inertia.reload()}
      />
    </DashboardLayout>
  );
}

interface CellProp {
  timeFrom: number;
  timeTo: number;
  activityableType?: string;
  activityableId?: number;
  cordinator?: number;
  cordinatorName?: string;
}

function Cell({ cell }: { cell?: CellProp }) {
  return (
    <VStack>
      <Text>
        Time Range: {cell ? `${cell.timeFrom} - ${cell.timeTo}` : ''}{' '}
      </Text>
      <Text>Action: {cell?.activityableType ?? ''}</Text>
      <Text>Cordinator: {cell?.cordinatorName ?? ''}</Text>
      <IconButton
        colorScheme={'brand'}
        variant={'outline'}
        aria-label="edit"
        icon={<Icon as={PencilIcon} />}
        onClick={() => {}}
      />
    </VStack>
  );
}
