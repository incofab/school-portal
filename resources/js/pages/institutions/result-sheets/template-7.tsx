// File: template-7.tsx
import {
  Avatar,
  Divider,
  Flex,
  HStack,
  Img,
  Spacer,
  Text,
  VStack,
  Wrap,
  WrapItem,
} from '@chakra-ui/react';
import React from 'react';
import { Div } from '@/components/semantic';
import startCase from 'lodash/startCase';
import useSharedProps from '@/hooks/use-shared-props';
import '@/../../public/style/result-sheet.css';
// import '@/style/template-7.css'; // You’ll create this
import ImagePaths from '@/util/images';
import DisplayTermResultEvaluation from '@/components/display-term-result-evaluation-component';
import ResultUtil, { ResultProps, useResultSetting } from '@/util/result-util';
import DataTable, { TableHeader } from '@/components/data-table';
import { Assessment, CourseResult } from '@/types/models';
import ResultSheetLayout from './result-sheet-layout';
import DateTimeDisplay from '@/components/date-time-display';
import { dateFormat } from '@/util/util';
import { LabelText } from '@/components/result-helper-components';

export default function Template7(props: ResultProps) {
  const {
    termResult,
    courseResults,
    classResultInfo,
    academicSession,
    classification,
    student,
    assessments,
    resultCommentTemplate,
    courseResultInfoData,
    signed_url,
    learningEvaluations,
  } = props;
  const { currentInstitution, stamp } = useSharedProps();
  const { hidePosition, showGrade } = useResultSetting();

  const resultSummary = [
    { label: 'Student Name', value: student.user?.full_name },
    { label: 'Class', value: termResult.classification?.title },
    { label: 'Student ID', value: student.code },
    { label: 'No in Class', value: classResultInfo.num_of_students },
    {
      label: 'Term',
      value: startCase(termResult.term),
    },
    { label: 'Session', value: academicSession.title },
    ...(classResultInfo.next_term_resumption_date
      ? [
          {
            label: 'Next Term Begins',
            value: (
              <DateTimeDisplay
                as={'span'}
                dateTime={classResultInfo.next_term_resumption_date}
                dateTimeformat={dateFormat}
              />
            ),
          },
        ]
      : []),
  ];

  const resultTableHeaders: TableHeader<CourseResult>[] = [
    { label: 'Subject', value: 'course.title' },
    ...assessments.map((a: Assessment) => ({
      label: a.title,
      render: (cr: CourseResult) =>
        String(cr.assessment_values[a.raw_title] ?? ''),
    })),
    { label: 'Exam', value: 'exam' },
    { label: 'Total', value: 'result' },
    {
      label: 'Grade',
      render: (cr: CourseResult) =>
        ResultUtil.getGrade(cr.result, resultCommentTemplate).grade,
    },
    ...(hidePosition
      ? []
      : [
          {
            label: 'Position',
            render: (cr: CourseResult) =>
              ResultUtil.formatPosition(cr.position),
          },
        ]),
    {
      label: 'Average',
      render: (cr: CourseResult) =>
        String(courseResultInfoData[cr.course_id]?.average ?? ''),
    },
    {
      label: 'Highest',
      render: (cr: CourseResult) =>
        String(courseResultInfoData[cr.course_id]?.max_score ?? ''),
    },
    {
      label: 'Lowest',
      render: (cr: CourseResult) =>
        String(courseResultInfoData[cr.course_id]?.min_score ?? ''),
    },
    {
      label: 'Remark',
      render: (cr: CourseResult) =>
        ResultUtil.getGrade(cr.result, resultCommentTemplate).remark ?? '',
    },
  ];

  const principalComment =
    termResult.principal_comment ??
    ResultUtil.getCommentFromTemplate(termResult.average, resultCommentTemplate)
      ?.comment;

  const teacherComment =
    termResult.teacher_comment ??
    ResultUtil.getCommentFromTemplate(termResult.average, resultCommentTemplate)
      ?.comment_2;

  function Header() {
    return (
      <Div
        className="result-sheet-header"
        bgGradient="linear(to-r, #777777, #AAAAAA)"
        color="white"
        p={5}
        borderRadius="md"
        mb={5}
      >
        <Flex justify="space-between" align="center">
          <Avatar
            size="xl"
            name="School Logo"
            src={currentInstitution.photo ?? ImagePaths.default_school_logo}
          />
          <VStack spacing={0}>
            <Text fontSize="2xl" fontWeight="bold">
              {currentInstitution.name}
            </Text>
            <Text>{currentInstitution.subtitle}</Text>
            <Text>{currentInstitution.address}</Text>
            <Text>
              {currentInstitution.phone} | {currentInstitution.email}
            </Text>
          </VStack>
          <Avatar size="xl" name="Student" src={student.user?.photo ?? ''} />
        </Flex>
        <Text
          textAlign="center"
          fontWeight="bold"
          fontSize="lg"
          mt={3}
          textTransform="uppercase"
        >
          {startCase(termResult.term)} Term Report Sheet -{' '}
          {academicSession.title}
        </Text>
      </Div>
    );
  }

  return (
    <ResultSheetLayout resultProps={props}>
      <Div mx="auto" width="900px" px={3} position="relative" id="result-sheet">
        <Header />
        <Wrap spacing={3} mb={4}>
          {resultSummary.map((item) => (
            <WrapItem key={item.label} flex={1}>
              <LabelText label={item.label} text={item.value} />
            </WrapItem>
          ))}
        </Wrap>
        <Div className="table-container">
          <DataTable
            scroll
            headers={resultTableHeaders}
            data={courseResults}
            keyExtractor={(row) => row.id}
            hideSearchField
            tableProps={{ className: 'result-table' }}
          />
        </Div>
        <Spacer height={5} />
        <DisplayTermResultEvaluation
          termResult={termResult}
          learningEvaluations={learningEvaluations}
        />
        <Divider my={4} />
        <HStack align="stretch" spacing={5}>
          <VStack align="start" spacing={2}>
            {teacherComment && (
              <Text>
                <strong>Teacher's Comment:</strong> {teacherComment}
              </Text>
            )}
            {principalComment && (
              <Text>
                <strong>Administrator's Comment:</strong> {principalComment}
              </Text>
            )}
            {stamp && <Img src={stamp} alt="Stamp" boxSize="100px" />}
          </VStack>
          {resultCommentTemplate && (
            <table className="keys-table" style={{ textAlign: 'center' }}>
              <thead>
                <tr>
                  <th>Range (%)</th>
                  <th>Remark</th>
                  <th>Grade</th>
                </tr>
              </thead>
              <tbody>
                {resultCommentTemplate.map((item) => (
                  <tr key={item.grade}>
                    <td>{`${item.min} - ${item.max}`}</td>
                    <td>{item.grade_label}</td>
                    <td>{item.grade}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          )}
        </HStack>
      </Div>
    </ResultSheetLayout>
  );
}
