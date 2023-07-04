import {
  AcademicSession,
  Classification,
  CourseResult,
  Student,
  TermResult,
} from '@/types/models';
import React from 'react';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import DashboardLayout from '@/layout/dashboard-layout';
import { Div } from '@/components/semantic';
import DataTable from '@/components/data-table';
import { TableHeader } from '@/components/data-table';
import {
  Divider,
  HStack,
  Icon,
  IconButton,
  Spacer,
  Text,
  VStack,
} from '@chakra-ui/react';
import startCase from 'lodash/startCase';
import { LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { PencilIcon } from '@heroicons/react/24/solid';
import useModalToggle from '@/hooks/use-modal-toggle';
import TermResultTeacherCommentModal from '@/components/modals/term-result-teacher-comment-modal';
import { Inertia } from '@inertiajs/inertia';
import TermResultPrincipalCommentModal from '@/components/modals/term-result-principal-comment-modal';

interface Props {
  term: string;
  academicSession: AcademicSession;
  student: Student;
  classification: Classification;
  courseResults: CourseResult[];
  termResult: TermResult;
}

export default function StudentTermResultDetail({
  term,
  academicSession,
  student,
  classification,
  courseResults,
  termResult,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const teacherCommentModalToggle = useModalToggle();
  const principalCommentModalToggle = useModalToggle();
  const headers: TableHeader<CourseResult>[] = [
    {
      label: 'Subject',
      value: 'course.title',
    },
    {
      label: '1st CA',
      value: 'first_assessment',
    },
    {
      label: '2nd CA',
      value: 'second_assessment',
    },
    {
      label: 'Exam',
      value: 'exam',
    },
    {
      label: 'Result',
      value: 'result',
    },
    {
      label: 'Grade',
      value: 'grade',
    },
    {
      label: 'Teacher',
      value: 'teacher.full_name',
    },
  ];

  const headerInfo = [
    { label: 'Student', value: student.user?.full_name },
    { label: 'Class', value: classification.title },
    { label: 'Session', value: academicSession.title },
    { label: 'Term', value: startCase(term) },
    { label: 'Position', value: termResult.position },
    { label: 'Average', value: termResult.average },
  ];

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title="Student Results"
          rightElement={
            <LinkButton
              as={'a'}
              target="_blank"
              title="View result sheet"
              variant={'outline'}
              href={instRoute('students.result-sheet', [
                student.id,
                classification.id,
                academicSession.id,
                term,
              ])}
            />
          }
        />
        <SlabBody>
          <Div>
            {headerInfo.map(({ label, value }) => (
              <HStack my={1} key={value}>
                <Text width={'100px'}>{label}</Text> <Text>{value}</Text>
              </HStack>
            ))}
          </Div>
          <DataTable
            scroll={true}
            data={courseResults}
            headers={headers}
            keyExtractor={(row) => row.id}
            hideSearchField={true}
          />
          <Spacer height={5} />
          <VStack divider={<Divider />} spacing={2} align={'stretch'}>
            <>
              <Text fontWeight={'semibold'} size={'sm'}>
                Teacher's Comment
              </Text>
              <HStack align={'stretch'}>
                <Text>{termResult.teacher_comment}</Text>
                <Spacer />
                <IconButton
                  aria-label="edit teacher's comment"
                  icon={<Icon as={PencilIcon} />}
                  variant={'outline'}
                  onClick={teacherCommentModalToggle.open}
                />
              </HStack>
            </>
            <>
              <Text fontWeight={'semibold'} size={'sm'}>
                Principal's Comment
              </Text>
              <HStack align={'stretch'}>
                <Text>{termResult.principal_comment}</Text>
                <Spacer />
                <IconButton
                  aria-label="edit principal's comment"
                  icon={<Icon as={PencilIcon} />}
                  variant={'outline'}
                  onClick={principalCommentModalToggle.open}
                />
              </HStack>
            </>
          </VStack>
          <Spacer height={5} />
        </SlabBody>
        <TermResultTeacherCommentModal
          termResult={termResult}
          {...teacherCommentModalToggle.props}
          onSuccess={() => Inertia.reload({ only: ['termResult'] })}
        />
        <TermResultPrincipalCommentModal
          termResult={termResult}
          {...principalCommentModalToggle.props}
          onSuccess={() => Inertia.reload({ only: ['termResult'] })}
        />
      </Slab>
    </DashboardLayout>
  );
}
