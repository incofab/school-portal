import {
  AcademicSession,
  Course,
  CourseResult,
  CourseResultInfo,
  ResultCommentTemplate,
  SessionResult,
  TermResult,
} from '@/types/models';
import React, { useMemo } from 'react';
import { TermType } from '@/types/types';
import ResultUtil, { useResultSetting } from '@/util/result-util';
import { Div } from '@/components/semantic';
import { Avatar, Divider, HStack, Img, Text, VStack } from '@chakra-ui/react';
import useSharedProps from '@/hooks/use-shared-props';
import ImagePaths from '@/util/images';
import { LabelText } from '@/components/result-helper-components';
import { roundNumber } from '@/util/util';
import '@/../../public/style/result-sheet.css';
import '@/../../public/style/result/session-result.css';
import PagePrintLayout from '@/domain/institutions/page-print-layout';

export interface SessionResultProps {
  sessionResult: SessionResult;
  resultCommentTemplate: ResultCommentTemplate[];
  termResultDetails: {
    [term: string]: {
      termResult: TermResult;
      courseResults: { [courseId: number]: CourseResult };
      courseResultInfo: { [courseId: number]: CourseResultInfo };
    };
  };
}

export default function SessionResultTemplate1({
  sessionResult,
  termResultDetails,
  resultCommentTemplate,
}: SessionResultProps) {
  const { currentInstitution, stamp } = useSharedProps();
  const { hidePosition } = useResultSetting();

  type TermRow = {
    courseResult: CourseResult;
    termResult: TermResult;
    courseResultInfo?: CourseResultInfo;
  };

  type Row = {
    [courseId: number]: {
      termCourseResult: { [term: string]: TermRow };
      course: Course;
    };
  };

  type SessionResultWithTotals = SessionResult & {
    academic_session?: AcademicSession;
    total_average?: number | null;
  };

  const rows = useMemo(
    function () {
      const rows = {} as Row;

      Object.values(TermType).map((term) => {
        const termDetail = termResultDetails[term];
        if (!termDetail) {
          return;
        }
        Object.entries(termDetail.courseResults).map(
          ([courseId, courseResult]) => {
            const courseIdInt = parseInt(courseId);
            const row = rows[courseIdInt] ?? {
              course: courseResult.course!,
              termCourseResult: {},
            };
            row.termCourseResult[term] = {
              courseResult: courseResult,
              termResult: termDetail.termResult,
              courseResultInfo: termDetail.courseResultInfo[courseIdInt],
            };
            rows[courseIdInt] = row;
          }
        );
      });
      return rows;
    },
    [termResultDetails]
  );

  function getSessionTotal(courseDetail: { [term: string]: TermRow }) {
    let count = 0;
    let total = 0;
    Object.values(courseDetail).map((termRowObj) => {
      total += parseFloat(termRowObj.courseResult.result + '');
      count += 1;
    });
    if (count <= 0) {
      return [0, 0];
    }
    const average = Math.round((total / count) * 100) / 100;
    return [total, average];
  }

  function score(value?: number | string | null) {
    if (value === undefined || value === null || value === '') {
      return '-';
    }
    return roundNumber(value, 2);
  }

  function position(value?: number | null) {
    if (hidePosition || !value) {
      return '-';
    }
    return ResultUtil.formatPosition(value);
  }

  function comment(value?: string | null) {
    return value?.trim() || '-';
  }

  const student = sessionResult.student!;
  const classification = sessionResult.classification!;
  const sessionResultWithTotals = sessionResult as SessionResultWithTotals;
  const academicSession = sessionResultWithTotals.academic_session!;
  const titles = ResultUtil.getClassificationGroupTitles(classification);
  const sessionGrade =
    sessionResult.grade ||
    ResultUtil.getGrade(sessionResult.average, resultCommentTemplate).grade;
  const sessionRemark =
    sessionResult.remark ||
    ResultUtil.getGrade(sessionResult.average, resultCommentTemplate).remark;
  const teacherComment = ResultUtil.getTeachersComment(
    sessionResult as unknown as TermResult,
    resultCommentTemplate
  );
  const principalComment = ResultUtil.getPrincipalsComment(
    sessionResult as unknown as TermResult,
    resultCommentTemplate
  );
  const showClassGroupPosition = Boolean(
    classification.classification_group?.show_class_group_position
  );
  const availableTermResults = Object.values(TermType)
    .map((term) => ({
      term,
      termResult: termResultDetails[term]?.termResult,
    }))
    .filter((item): item is { term: TermType; termResult: TermResult } =>
      Boolean(item.termResult)
    );
  const resultSummary1 = [
    { label: 'Name', value: student.user?.full_name },
    { label: `${titles.student} Id`, value: student.code },
  ];
  const resultSummary2 = [
    { label: 'Class', value: classification.title },
    {
      label: 'Session',
      value: `${academicSession.title}`,
    },
  ];
  const performanceSummary = [
    { label: 'Total Points', value: score(sessionResult.result) },
    {
      label: 'Points Average',
      value: score(sessionResultWithTotals.total_average),
    },
    { label: 'Overall Average', value: `${score(sessionResult.average)}%` },
    { label: 'Overall Grade', value: sessionGrade || '-' },
    { label: 'Position in Class', value: position(sessionResult.position) },
  ];

  const filename = `${student.user?.full_name}-${academicSession.title}-session-result.pdf`;
  return (
    <PagePrintLayout
      useBgStyle={true}
      filename={filename}
      contentId={'result-sheet'}
    >
      <Div
        mx={'auto'}
        width={'900px'}
        px={3}
        position={'relative'}
        id={'result-sheet'}
      >
        <VStack align={'stretch'} spacing={2} className="session-result-sheet">
          <HStack background={'#FAFAFA'} p={2} className="result-sheet-header">
            <Avatar
              size={'2xl'}
              name="Institution logo"
              src={currentInstitution.photo ?? ImagePaths.default_school_logo}
            />
            <VStack spacing={0.5} align={'stretch'} width={'full'}>
              <Text fontSize={'2xl'} fontWeight={'bold'} textAlign={'center'}>
                {currentInstitution.name}
              </Text>
              {currentInstitution.subtitle && (
                <Text fontSize={'sm'} textAlign={'center'} fontWeight={'bold'}>
                  {currentInstitution.subtitle}
                </Text>
              )}
              <Text textAlign={'center'} fontSize={'16px'}>
                {currentInstitution.address}
                <br /> {currentInstitution.email} | {currentInstitution.phone}
              </Text>
              <Text fontWeight={'semibold'} textAlign={'center'}>
                Annual Result Sheet - {academicSession?.title}
              </Text>
            </VStack>
            <Avatar
              size={'2xl'}
              name={`${titles.student} passport`}
              src={student.user?.photo_url}
            />
          </HStack>

          <HStack justify={'space-between'} align={'start'} fontSize={'sm'}>
            <VStack align={'stretch'} spacing={0} flex={1}>
              {resultSummary1.map((item) => (
                <LabelText
                  label={item.label}
                  text={item.value}
                  key={'summary1' + item.label}
                  labelProps={{ fontWeight: 'semibold' }}
                />
              ))}
            </VStack>
            <VStack align={'stretch'} spacing={0} flex={1}>
              {resultSummary2.map((item) => (
                <LabelText
                  label={item.label}
                  text={item.value}
                  key={'summary2' + item.label}
                  labelProps={{ fontWeight: 'semibold' }}
                />
              ))}
            </VStack>
          </HStack>

          <table className="result-analysis-table session-summary-table">
            <tbody>
              <tr>
                {performanceSummary.map((item) => (
                  <td key={item.label}>
                    <b>{item.label}:</b> {item.value}
                  </td>
                ))}
              </tr>
            </tbody>
          </table>

          <Div className="table-container">
            <table className="result-table session-term-table" width={'100%'}>
              <thead>
                <tr>
                  <th>Term</th>
                  <th>Total Score</th>
                  <th>Average</th>
                  <th>Class Position</th>
                  {showClassGroupPosition && <th>Group Position</th>}
                  <th>Grade</th>
                </tr>
              </thead>
              <tbody>
                {availableTermResults.map(({ term, termResult }) => (
                  <tr key={term}>
                    <td>{term} Term</td>
                    <td>{score(termResult.total_score)}</td>
                    <td>{score(termResult.average)}</td>
                    <td>{position(termResult.position)}</td>
                    {showClassGroupPosition && (
                      <td>{position(termResult.class_group_position)}</td>
                    )}
                    <td>
                      {
                        ResultUtil.getGrade(
                          Number(termResult.average ?? 0),
                          resultCommentTemplate
                        ).grade
                      }
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </Div>

          <Div className="table-container">
            <table
              className="result-table session-subject-table"
              width={'100%'}
            >
              <thead>
                <tr>
                  <th rowSpan={2}>Subjects</th>
                  <th colSpan={3}>First Term</th>
                  <th colSpan={3}>Second Term</th>
                  <th colSpan={3}>Third Term</th>
                  <th rowSpan={2}>Total</th>
                  <th rowSpan={2}>Average</th>
                  <th rowSpan={2}>Grade</th>
                  <th rowSpan={2}>Remark</th>
                </tr>
                <tr>
                  {Object.values(TermType).map((term) => (
                    <React.Fragment key={term}>
                      <th className="session-term-subheader">Score</th>
                      <th className="session-term-subheader">Pos</th>
                      <th className="session-term-subheader">Avg</th>
                    </React.Fragment>
                  ))}
                </tr>
              </thead>
              <tbody>
                {Object.entries(rows).map(([courseId, termRow]) => {
                  const [sessionTotal, sessionTotalAverage] = getSessionTotal(
                    termRow.termCourseResult
                  );
                  const grade = ResultUtil.getGrade(
                    sessionTotalAverage,
                    resultCommentTemplate
                  );
                  return (
                    <tr key={'row' + courseId}>
                      <td>{termRow.course?.title}</td>
                      {termDataCells(termRow.termCourseResult[TermType.First])}
                      {termDataCells(termRow.termCourseResult[TermType.Second])}
                      {termDataCells(termRow.termCourseResult[TermType.Third])}
                      <td>
                        <b>{score(sessionTotal)}</b>
                      </td>
                      <td>{score(sessionTotalAverage)}</td>
                      <td>{grade.grade}</td>
                      <td>{grade.remark}</td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </Div>

          <HStack align={'start'} spacing={2}>
            <table className="result-analysis-table session-assessment-table">
              <tbody>
                <tr>
                  <td>Overall Grade</td>
                  <td>{sessionGrade || '-'}</td>
                </tr>
                <tr>
                  <td>Overall Remark</td>
                  <td>{sessionRemark || '-'}</td>
                </tr>
                {teacherComment && (
                  <tr>
                    <td>{titles.headOfClassPossessive} Comment</td>
                    <td>{teacherComment}</td>
                  </tr>
                )}
                {principalComment && (
                  <tr>
                    <td>{titles.headOfSchoolPossessive} Comment</td>
                    <td>{principalComment}</td>
                  </tr>
                )}
              </tbody>
            </table>

            <table className="result-analysis-table session-comments-table">
              <thead>
                <tr>
                  <th>Term</th>
                  <th>{titles.headOfClass}</th>
                  <th>{titles.headOfSchool}</th>
                </tr>
              </thead>
              <tbody>
                {availableTermResults.map(({ term, termResult }) => (
                  <tr key={term}>
                    <td>{term}</td>
                    <td>{comment(termResult.teacher_comment)}</td>
                    <td>{comment(termResult.principal_comment)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </HStack>

          <Divider />

          <HStack align={'end'} justify={'space-between'} spacing={8} pt={4}>
            {/* <Div flex={1}>
              <Divider borderColor={'gray.500'} />
              <Text mt={1} fontSize={'xs'} fontWeight={'bold'}>
                {titles.headOfClassPossessive} Signature
              </Text>
            </Div> */}
            {stamp && (
              <Div textAlign={'center'} flex={1}>
                <Img
                  src={stamp}
                  alt="School stamp"
                  display={'inline-block'}
                  maxH={'70px'}
                />
                <Text mt={1} fontSize={'xs'} fontWeight={'bold'}>
                  School Stamp
                </Text>
              </Div>
            )}
            {/* <Div flex={1}>
              <Divider borderColor={'gray.500'} />
              <Text mt={1} fontSize={'xs'} fontWeight={'bold'}>
                {titles.headOfSchoolPossessive} Signature
              </Text>
            </Div> */}
          </HStack>
        </VStack>
      </Div>
    </PagePrintLayout>
  );

  function termDataCells(termRowObj?: TermRow) {
    return (
      <>
        <td>{score(termRowObj?.courseResult.result)}</td>
        <td>{position(termRowObj?.courseResult.position)}</td>
        <td>{score(termRowObj?.courseResultInfo?.average)}</td>
      </>
    );
  }
}
