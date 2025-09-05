import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import useModalToggle from '@/hooks/use-modal-toggle';
import { ClassResultInfo, ClassificationGroup } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import { HStack, Icon, IconButton, Text } from '@chakra-ui/react';
import { Inertia } from '@inertiajs/inertia';
import startCase from 'lodash/startCase';
import React from 'react';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import useIsStaff from '@/hooks/use-is-staff';
import DashboardLayout from '@/layout/dashboard-layout';
import ClassResultInfoTableFilters from '@/components/table-filters/class-result-info-table-filters';
import CalculateClassResultInfoModal from '@/components/modals/calculate-class-result-info-modal';
import { BrandButton, LinkButton } from '@/components/buttons';
import DestructivePopover from '@/components/destructive-popover';
import useWebForm from '@/hooks/use-web-form';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useMyToast from '@/hooks/use-my-toast';
import { ArrowPathIcon, CloudArrowDownIcon } from '@heroicons/react/24/outline';
import route from '@/util/route';
import useSharedProps from '@/hooks/use-shared-props';
import SetResumptionDateModal from '@/components/modals/set-resumption-date-modal';
import { Div } from '@/components/semantic';

interface Props {
  classResultInfo: PaginationResponse<ClassResultInfo>;
  classificationgroups: ClassificationGroup[];
}

export default function ListClassResultInfo({
  classResultInfo,
  classificationgroups,
}: Props) {
  const webForm = useWebForm({});
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const { currentInstitution } = useSharedProps();
  const calculateClassResultInfoToggle = useModalToggle();
  const classResultInfoFilterToggle = useModalToggle();
  const setResumptionDateModalToggle = useModalToggle();
  const isStaff = useIsStaff();

  const recalculateClassResultInfo = async (
    onClose: () => void,
    row: ClassResultInfo
  ) => {
    const res = await webForm.submit((data, web) => {
      return web.post(
        instRoute('class-result-info.recalculate', [row.id]),
        data
      );
    });

    if (!handleResponseToast(res)) return;

    onClose();
    Inertia.reload({ only: ['classResultInfo'] });
  };

  function downloadResults(classResultInfo: ClassResultInfo) {
    if (
      !window.confirm(
        `Download ${classResultInfo.classification?.title} results?`
      )
    ) {
      return;
    }
    window.location.href = instRoute('class-result-info.download', [
      classResultInfo.id,
    ]);
  }

  const headers: ServerPaginatedTableHeader<ClassResultInfo>[] = [
    {
      label: 'Class',
      value: 'classification.title',
    },
    {
      label: 'Session',
      value: 'academic_session.title',
    },
    {
      label: 'Term',
      value: 'term',
      render: (row) => (
        <Text>
          {startCase(row.term)} {row.for_mid_term ? 'Mid-' : ''}Term
        </Text>
      ),
    },
    {
      label: 'Num of Students',
      value: 'num_of_students',
    },
    {
      label: 'Num of Courses',
      value: 'num_of_courses',
    },
    {
      label: 'Total Score',
      value: 'total_score',
    },
    {
      label: 'Max Obtainable Score',
      value: 'max_obtainable_score',
    },
    {
      label: 'Student Max Score',
      value: 'max_score',
    },
    {
      label: 'Student Min Score',
      value: 'min_score',
    },
    {
      label: 'Average',
      value: 'average',
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack>
          <LinkButton
            href={route('institutions.term-results.index', {
              institution: currentInstitution.uuid,
              classification: row.classification_id,
              academicSession: row.academic_session_id,
              term: row.term,
              forMidTerm: row.for_mid_term,
            })}
            title="Student Results"
          />
          <IconButton
            aria-label="Download"
            icon={<Icon as={CloudArrowDownIcon} />}
            colorScheme="brand"
            size="sm"
            variant={'ghost'}
            onClick={() => downloadResults(row)}
          />
          <LinkButton
            href={instRoute('class-result-info.result-sheets', [row.id])}
            title="Result Sheets"
            variant={'link'}
          />
          <DestructivePopover
            label={`Do you want to recalculate the results for this ${row.classification?.title}?`}
            onConfirm={(onClose) => recalculateClassResultInfo(onClose, row)}
            isLoading={webForm.processing}
            positiveButtonLabel="Recalculate"
          >
            <IconButton
              aria-label="Recalculate"
              icon={<Icon as={ArrowPathIcon} />}
              colorScheme="brand"
              size="sm"
            />
          </DestructivePopover>
        </HStack>
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Div>
        <HStack justifyContent={'space-between'} my={2}>
          <BrandButton
            title="Set Resumption Date"
            onClick={setResumptionDateModalToggle.open}
          />
          <LinkButton
            href={instRoute('course-results.class-sheet.upload')}
            title="Upload Class Sheet"
          />
        </HStack>
        <Slab>
          <SlabHeading
            title="Class Result Analysis"
            rightElement={
              isStaff && (
                <BrandButton
                  onClick={calculateClassResultInfoToggle.open}
                  title="Calculate"
                />
              )
            }
          />
          <SlabBody>
            <ServerPaginatedTable
              scroll={true}
              headers={headers}
              data={classResultInfo.data}
              keyExtractor={(row) => row.id}
              paginator={classResultInfo}
              validFilters={['classification', 'academicSession', 'term']}
              onFilterButtonClick={classResultInfoFilterToggle.open}
            />
          </SlabBody>
          <CalculateClassResultInfoModal
            {...calculateClassResultInfoToggle.props}
            onSuccess={() => Inertia.reload({ only: ['classResultInfo'] })}
          />
          <ClassResultInfoTableFilters {...classResultInfoFilterToggle.props} />
          <SetResumptionDateModal
            {...setResumptionDateModalToggle.props}
            classificationGroups={classificationgroups}
            onSuccess={() => {}}
          />
        </Slab>
      </Div>
    </DashboardLayout>
  );
}
