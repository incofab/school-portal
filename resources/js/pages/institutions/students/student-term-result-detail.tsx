import {
  AcademicSession,
  Classification,
  CourseResult,
  Assessment,
  Student,
  TermResult,
  LearningEvaluation,
  ResultCommentTemplate,
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
  Stack,
  Text,
  VStack,
  useColorModeValue,
} from '@chakra-ui/react';
import startCase from 'lodash/startCase';
import { LinkButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { PencilIcon } from '@heroicons/react/24/solid';
import useModalToggle from '@/hooks/use-modal-toggle';
import TermResultTeacherCommentModal from '@/components/modals/term-result-teacher-comment-modal';
import { Inertia } from '@inertiajs/inertia';
import TermResultPrincipalCommentModal from '@/components/modals/term-result-principal-comment-modal';
import SetTermResultEvaluation from '../learning-evaluations/set-term-result-evaluations-component';
import { TermResultExtraData } from '../learning-evaluations/term-result-extra-data';
import ResultUtil from '@/util/result-util';
import useIsAdmin from '@/hooks/use-is-admin';
import useSharedProps from '@/hooks/use-shared-props';
import { InstitutionPermission } from '@/types/permissions';
import PermissionGate from '@/components/permission-gate';

interface Props {
  term: string;
  academicSession: AcademicSession;
  student: Student;
  classification: Classification;
  courseResults: CourseResult[];
  termResult: TermResult;
  assessments?: Assessment[];
  learningEvaluations?: LearningEvaluation[];
  resultCommentTemplate: ResultCommentTemplate[];
}

export default function StudentTermResultDetail({
  term,
  academicSession,
  student,
  classification,
  courseResults,
  termResult,
  assessments,
  learningEvaluations,
  resultCommentTemplate,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const teacherCommentModalToggle = useModalToggle();
  const principalCommentModalToggle = useModalToggle();
  const { currentUser } = useSharedProps();
  const isAdmin = useIsAdmin();
  const canWorkOnClass =
    isAdmin || classification.form_teacher_id === currentUser.id;

  const relevantAssessments = ResultUtil.getRelevantAssessments(
    assessments,
    courseResults
  );
  const principalComment = ResultUtil.getPrincipalsComment(
    termResult.average,
    resultCommentTemplate,
    termResult.principal_comment
  );
  const teacherComment = ResultUtil.getTeachersComment(
    termResult.average,
    resultCommentTemplate,
    termResult.teacher_comment
  );

  const headers: TableHeader<CourseResult>[] = [
    {
      label: 'Subject',
      value: 'course.title',
    },
    ...(relevantAssessments
      ? relevantAssessments.map((item) => ({
          label: startCase(item.title),
          render: (row: CourseResult) =>
            String(ResultUtil.getAssessmentValue(row, item, 0)),
        }))
      : []),
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
      render: (row: CourseResult) =>
        ResultUtil.getCommentFromTemplate(row.result, resultCommentTemplate)
          ?.grade ?? row.grade,
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
    {
      label: 'Term',
      value: `${startCase(term)} ${termResult.for_mid_term ? 'Mid ' : ''}Term`,
    },
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
                termResult.for_mid_term ? 1 : 0,
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
                <Text>{teacherComment}</Text>
                <Spacer />
                <PermissionGate
                  permissions={InstitutionPermission.NaturalAccess}
                  when={canWorkOnClass}
                >
                  <IconButton
                    aria-label="edit teacher's comment"
                    icon={<Icon as={PencilIcon} />}
                    variant={'outline'}
                    onClick={teacherCommentModalToggle.open}
                  />
                </PermissionGate>
              </HStack>
            </>
            <>
              <Text fontWeight={'semibold'} size={'sm'}>
                Principal/Head Teacher's Comment
              </Text>
              <HStack align={'stretch'}>
                <Text>{principalComment}</Text>
                <Spacer />
                <PermissionGate
                  permissions={InstitutionPermission.NaturalAccess}
                  when={canWorkOnClass && isAdmin}
                >
                  <IconButton
                    aria-label="edit Administrator's comment"
                    icon={<Icon as={PencilIcon} />}
                    variant={'outline'}
                    onClick={principalCommentModalToggle.open}
                  />
                </PermissionGate>
              </HStack>
            </>
          </VStack>
          <Spacer height={5} />
        </SlabBody>
        <Spacer height={3} />
        <Stack direction={{ base: 'column', md: 'row' }} spacing={3}>
          <Div
            maxWidth={'500px'}
            background={useColorModeValue('#FAFAFA', 'gray.700')}
            py={4}
            px={5}
            flex={1}
          >
            <PermissionGate
              permissions={InstitutionPermission.NaturalAccess}
              when={canWorkOnClass}
            >
              <SetTermResultEvaluation
                termResult={termResult}
                learningEvaluations={learningEvaluations}
              />
            </PermissionGate>
          </Div>
          <Div
            flex={1}
            background={useColorModeValue('#FAFAFA', 'gray.700')}
            p={4}
          >
            <PermissionGate
              permissions={InstitutionPermission.NaturalAccess}
              when={canWorkOnClass}
            >
              <TermResultExtraData termResult={termResult} />
            </PermissionGate>
          </Div>
        </Stack>
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
