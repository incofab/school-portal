import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import useModalToggle from '@/hooks/use-modal-toggle';
import { ClassResultInfo, ClassificationGroup } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import {
  Button,
  HStack,
  Icon,
  IconButton,
  Menu,
  MenuButton,
  MenuItem,
  MenuList,
  Text,
} from '@chakra-ui/react';
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
import {
  ArrowPathIcon,
  CloudArrowDownIcon,
  EllipsisVerticalIcon,
} from '@heroicons/react/24/outline';
import route from '@/util/route';
import useSharedProps from '@/hooks/use-shared-props';
import SetResumptionDateModal from '@/components/modals/set-resumption-date-modal';
import { Div } from '@/components/semantic';
import useIsAdmin from '@/hooks/use-is-admin';
import { InertiaLink } from '@inertiajs/inertia-react';

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
  const { currentInstitution, currentUser } = useSharedProps();
  const calculateClassResultInfoToggle = useModalToggle();
  const classResultInfoFilterToggle = useModalToggle();
  const setResumptionDateModalToggle = useModalToggle();
  const isStaff = useIsStaff();
  const isAdmin = useIsAdmin();
  const sendViaWhatsappForm = useWebForm({
    class_result_info: '',
  });

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

  async function sendViaWhatsapp(resultInfo: ClassResultInfo) {
    sendViaWhatsappForm.setValue('class_result_info', String(resultInfo.id));
    const res = await webForm.submit((data, web) => {
      return web.post(
        instRoute('class-result-info.send-results', [resultInfo.id])
      );
    });
    if (!handleResponseToast(res)) return;
    resultInfo.whatsapp_message_count =
      (resultInfo.whatsapp_message_count ?? 0) + 1;
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
        <HStack spacing={2}>
          {(isAdmin ||
            row.classification!.form_teacher_id === currentUser.id) && (
            <>
              {/* <LinkButton
                href={route('term-results.class-result-info.index', {
                  institution: currentInstitution.uuid,
                  classification: row.classification_id,
                  academicSession: row.academic_session_id,
                  term: row.term,
                  forMidTerm: row.for_mid_term,
                })}
                title="Student Results"
              /> */}

              <LinkButton
                href={instRoute('term-results.class-result-info.index', [
                  row.id,
                ])}
                title="Student Results"
              />

              <Menu>
                <MenuButton
                  as={IconButton}
                  aria-label={'open file menu'}
                  icon={<Icon as={EllipsisVerticalIcon} />}
                  size={'sm'}
                  variant={'ghost'}
                />
                <MenuList>
                  <MenuItem
                    as={InertiaLink}
                    href={instRoute('class-result-info.record-evaluations', [
                      row.id,
                    ])}
                    py={2}
                  >
                    Record Evaluations
                  </MenuItem>
                  <MenuItem
                    aria-label="Download"
                    onClick={() => downloadResults(row)}
                    py={2}
                  >
                    Download Results
                  </MenuItem>
                  <MenuItem
                    as={InertiaLink}
                    href={instRoute('class-result-info.result-sheets', [
                      row.id,
                    ])}
                    py={2}
                  >
                    All Result Sheets
                  </MenuItem>
                  <MenuItem
                    as={Button}
                    onClick={() => sendViaWhatsapp(row)}
                    py={2}
                  >
                    Send Results via Whatsapp
                  </MenuItem>
                </MenuList>
              </Menu>
              <DestructivePopover
                label={`Do you want to recalculate the results for this ${row.classification?.title}?`}
                onConfirm={(onClose) =>
                  recalculateClassResultInfo(onClose, row)
                }
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
            </>
          )}
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
