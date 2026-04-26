import ServerPaginatedTable, {
  ServerPaginatedTableHeader,
} from '@/components/server-paginated-table';
import useModalToggle from '@/hooks/use-modal-toggle';
import { ClassResultInfo, TermResult } from '@/types/models';
import { PaginationResponse } from '@/types/types';
import {
  Box,
  Heading,
  Icon,
  IconButton,
  Table,
  Tbody,
  Td,
  Text,
  Th,
  Thead,
  Tr,
} from '@chakra-ui/react';
import startCase from 'lodash/startCase';
import React from 'react';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import DashboardLayout from '@/layout/dashboard-layout';
import TermResultsTableFilters from '@/components/table-filters/term-result-table-filters';
import { LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useIsStaff from '@/hooks/use-is-staff';
import useIsAdmin from '@/hooks/use-is-admin';
import useSharedProps from '@/hooks/use-shared-props';
import DestructivePopover from '@/components/destructive-popover';
import { TrashIcon } from '@heroicons/react/24/solid';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import { useResultSetting } from '@/util/result-util';
import { roundNumber } from '@/util/util';

interface GradeReportItem {
  grade: string;
  count: number;
  percentage: number;
}

interface Props {
  termResults: PaginationResponse<TermResult>;
  classResultInfo?: ClassResultInfo;
  gradeReport?: GradeReportItem[];
}

export default function ListTermResults({
  termResults,
  classResultInfo,
  gradeReport,
}: Props) {
  const termResultFilterToggle = useModalToggle();
  const { instRoute } = useInstitutionRoute();
  const { currentUser } = useSharedProps();
  const { handleResponseToast } = useMyToast();
  const isStaff = useIsStaff();
  const isAdmin = useIsAdmin();
  const canViewDetails = !isStaff || isAdmin;
  const deleteForm = useWebForm({});
  const { hidePosition, showGrade } = useResultSetting();

  async function deleteItem(obj: TermResult) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('term-results.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['termResults'] });
  }
  const shouldHidePosition = hidePosition && !isStaff;
  const shouldShowGrade = showGrade && !isStaff;
  const headers: ServerPaginatedTableHeader<TermResult>[] = [
    {
      label: 'User',
      value: 'student.user.full_name',
    },
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
    ...(shouldHidePosition
      ? []
      : [
          {
            label: 'Position',
            value: shouldShowGrade ? 'grade' : 'position',
            sortKey: shouldShowGrade ? 'grade' : 'position',
          },
        ]),
    {
      label: 'Total Score',
      value: 'total_score',
      sortKey: 'totalScore',
    },
    {
      label: 'Average',
      value: 'average',
      render: (row) => String(roundNumber(row.average, 2)),
      sortKey: 'average',
    },
    {
      label: 'Remark',
      value: 'remark',
    },
    {
      label: 'Action',
      render: (row) => (
        <>
          {(canViewDetails ||
            row.classification!.form_teacher_id === currentUser.id) && (
            <LinkButton
              href={instRoute('students.term-result-detail', [
                row.student_id,
                row.classification_id,
                row.academic_session_id,
                row.term,
                row.for_mid_term ? 1 : 0,
              ])}
              title="Result Detail"
            />
          )}
          {isStaff && (
            <DestructivePopover
              label={'Are you sure you want to delete this result?'}
              onConfirm={() => deleteItem(row)}
            >
              <IconButton
                aria-label="Delete Result"
                icon={<Icon as={TrashIcon} />}
                variant="ghost"
                colorScheme="red"
              />
            </DestructivePopover>
          )}
        </>
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Term Results"
          rightElement={
            <>
              {classResultInfo && (
                <LinkButton
                  title="Record Evaluations"
                  href={instRoute('class-result-info.record-evaluations', [
                    classResultInfo.id,
                  ])}
                />
              )}
            </>
          }
        />
        <SlabBody>
          <ServerPaginatedTable
            scroll={true}
            headers={headers}
            data={termResults.data}
            keyExtractor={(row) => row.id}
            paginator={termResults}
            validFilters={
              classResultInfo
                ? undefined
                : ['classification', 'academicSession', 'student', 'term']
            }
            onFilterButtonClick={termResultFilterToggle.open}
          />

          {gradeReport && gradeReport.length > 0 && (
            <Box mt={10}>
              <Heading size="md" mb={4}>
                Grade Report Summary
              </Heading>
              <Table variant="simple" size="sm" colorScheme="blue">
                <Thead bg="gray.50">
                  <Tr>
                    <Th>Grade</Th>
                    <Th isNumeric>Students</Th>
                    <Th isNumeric>Percentage (%)</Th>
                  </Tr>
                </Thead>
                <Tbody>
                  {gradeReport.map((item) => (
                    <Tr key={item.grade}>
                      <Td fontWeight="bold">{item.grade}</Td>
                      <Td isNumeric>{item.count}</Td>
                      <Td isNumeric>{item.percentage}%</Td>
                    </Tr>
                  ))}
                  <Tr bg="gray.50" fontWeight="bold">
                    <Td>Total</Td>
                    <Td isNumeric>
                      {gradeReport.reduce((acc, item) => acc + item.count, 0)}
                    </Td>
                    <Td isNumeric>100%</Td>
                  </Tr>
                </Tbody>
              </Table>
            </Box>
          )}
        </SlabBody>
        <TermResultsTableFilters {...termResultFilterToggle.props} />
      </Slab>
    </DashboardLayout>
  );
}
