import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import useModalToggle from '@/hooks/use-modal-toggle';
import { ClassResultInfo } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import { Text } from '@chakra-ui/react';
import { Inertia } from '@inertiajs/inertia';
import startCase from 'lodash/startCase';
import React from 'react';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import useIsStaff from '@/hooks/use-is-staff';
import DashboardLayout from '@/layout/dashboard-layout';
import ClassResultInfoTableFilters from '@/components/table-filters/class-result-info-table-filters';
import CalculateClassResultInfoModal from '@/components/modals/calculate-class-result-info-modal';
import { BrandButton } from '@/components/buttons';
import DestructivePopover from '@/components/destructive-popover';
import useWebForm from '@/hooks/use-web-form';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useMyToast from '@/hooks/use-my-toast';

interface Props {
  classResultInfo: PaginationResponse<ClassResultInfo>;
}

export default function ListClassResultInfo({ classResultInfo }: Props) {
  const webForm = useWebForm({});
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const calculateClassResultInfoToggle = useModalToggle();
  const classResultInfoFilterToggle = useModalToggle();
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
      render: (row) => <Text>{startCase(row.term)}</Text>,
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
        <DestructivePopover
          label={`Do you want to recalculate the results for this ${row.classification?.title}?`}
          onConfirm={(onClose) => recalculateClassResultInfo(onClose, row)}
          isLoading={webForm.processing}
          positiveButtonLabel="Recalculate"
        >
          <BrandButton title="Recalculate" />
        </DestructivePopover>
      ),
    },
  ];

  return (
    <DashboardLayout>
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
      </Slab>
    </DashboardLayout>
  );
}
