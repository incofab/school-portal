import {
  Avatar,
  Box,
  Divider,
  HStack,
  Img,
  SimpleGrid,
  Spacer,
  Text,
  VStack,
} from '@chakra-ui/react';
import React from 'react';
import { Div } from '@/components/semantic';
import DisplayTermResultEvaluation from '@/components/display-term-result-evaluation-component';
import { LabelText } from '@/components/result-helper-components';
import useSharedProps from '@/hooks/use-shared-props';
import '@/../../public/style/result-sheet.css';
import '@/style/template-9.css';
import ResultUtil, { ResultProps, useResultSetting } from '@/util/result-util';
import ImagePaths from '@/util/images';
import ResultSheetLayout from './result-sheet-layout';
import { formatAsDate, roundNumber } from '@/util/util';
import startCase from 'lodash/startCase';
import { CourseResult } from '@/types/models';

const TOTAL_SCORE_MAX = 100;
const FALLBACK_ASSESSMENT_MAX = 20;

export default function Template9(props: ResultProps) {
  const {
    termResult,
    courseResults,
    classResultInfo,
    academicSession,
    classification,
    student,
    assessments,
    resultCommentTemplate,
    subjectCumulativeAverages,
    subjectTermTotals,
    learningEvaluations,
    termDetail,
    showExamResult,
  } = props;
  const { currentInstitution, stamp } = useSharedProps();
  const { hidePosition, showGrade } = useResultSetting();
  const titles = ResultUtil.getClassificationGroupTitles(classification);
  const watermarkLogo =
    currentInstitution.photo ?? ImagePaths.default_school_logo;
  const watermarkName = (currentInstitution.name ?? '').replace(
    /[<>&'"]/g,
    ' '
  );
  const watermarkPatternSvg = `<svg xmlns='http://www.w3.org/2000/svg' width='220' height='160' viewBox='0 0 220 160'><path d='M20 35 C70 5 145 5 200 35' fill='none' stroke='%230f766e' stroke-width='2' opacity='0.18'/><path d='M20 125 C75 155 145 155 200 125' fill='none' stroke='%230f766e' stroke-width='2' opacity='0.18'/><text x='110' y='86' text-anchor='middle' font-family='Arial, sans-serif' font-size='14' fill='%230f766e' opacity='0.16'>${watermarkName}</text></svg>`;
  const sheetBackgroundStyle = {
    backgroundImage: `url("data:image/svg+xml;charset=utf-8,${encodeURIComponent(
      watermarkPatternSvg
    )}")`,
    backgroundRepeat: 'repeat',
    backgroundSize: '220px 160px',
  };
  // const configuredAssessmentMax = assessments.reduce((total, assessment) => {
  //   const assessmentIds: number[] = [];
  //   courseResults.forEach((courseResult) => {
  //     Object.keys(courseResult.assessment_values).forEach((assessmentKey) => {
  //       const key = assessmentKey.split('|');
  //       const foundAssessment = assessments.find(
  //         (assessment) =>
  //           assessment.id === Number(key[1] ?? 0) ||
  //           assessment.raw_title === key[0]
  //       );
  //       if (
  //         foundAssessment &&
  //         !assessmentIds.find((id) => id === foundAssessment.id)
  //       ) {
  //         assessmentIds.push(foundAssessment.id);
  //       }
  //     });
  //   });
  //   const value = assessmentIds.find((id) => id === assessment.id)
  //     ? Number(assessment.max ?? 0)
  //     : 0;
  //   return total + value;
  // }, 0);
  const relevantAssessments = ResultUtil.getRelevantAssessments(
    assessments,
    courseResults
  );
  const configuredAssessmentMax = relevantAssessments.reduce(
    (total, assessment) => total + Number(assessment.max ?? 0),
    0
  );

  const assessmentMax =
    configuredAssessmentMax > 0
      ? configuredAssessmentMax
      : FALLBACK_ASSESSMENT_MAX;
  const examMax = showExamResult
    ? Math.max(TOTAL_SCORE_MAX - assessmentMax, 0)
    : undefined;
  const nextTermResumptionDate =
    classResultInfo.next_term_resumption_date ??
    termDetail?.next_term_resumption_date;

  const principalComment = ResultUtil.getPrincipalsComment(
    termResult,
    resultCommentTemplate
  );
  const teacherComment = ResultUtil.getTeachersComment(
    termResult,
    resultCommentTemplate
  );

  function score(value: number | string | undefined | null) {
    if (value === undefined || value === null || value === '') {
      return '-';
    }

    return roundNumber(Number(value), 2);
  }

  function getAssessmentTotal(courseResult: CourseResult) {
    return relevantAssessments.reduce(
      (total, assessment) =>
        total +
        Number(ResultUtil.getAssessmentValue(courseResult, assessment, 0)),
      0
    );
  }

  function getAnnualAverage(courseResult: CourseResult) {
    const totals = subjectTermTotals?.[courseResult.course_id] ?? {};
    const availableScores = [
      totals.first,
      totals.second,
      totals.third ?? courseResult.result,
    ].filter(
      (value): value is number =>
        typeof value === 'number' && !Number.isNaN(value)
    );

    if (availableScores.length === 0) {
      return undefined;
    }

    return (
      availableScores.reduce((total, value) => total + value, 0) /
      availableScores.length
    );
  }

  const cumulativeAverageScores = courseResults
    .map(
      (courseResult) =>
        subjectCumulativeAverages[courseResult.course_id] ??
        getAnnualAverage(courseResult)
    )
    .filter(
      (value): value is number =>
        typeof value === 'number' && !Number.isNaN(value)
    );
  const cumulativeAverage = cumulativeAverageScores.length
    ? roundNumber(
        cumulativeAverageScores.reduce((total, value) => total + value, 0) /
          cumulativeAverageScores.length,
        2
      )
    : '-';

  const resultSummary = [
    { label: 'Name', value: student.user?.full_name },
    { label: 'Admission No', value: student.code },
    { label: 'Class', value: classification.title },
    { label: 'Session', value: academicSession.title },
    { label: 'Term', value: `${startCase(termResult.term)} Term` },
    {
      label: `No of ${titles.students} in Class`,
      value: classResultInfo.num_of_students,
    },
    { label: 'Attendance', value: termResult.attendance_count },
    {
      label: 'School Opened',
      value: termDetail?.expected_attendance_count,
    },
    ...(termDetail?.start_date
      ? [{ label: 'Opening Date', value: formatAsDate(termDetail.start_date) }]
      : []),
    ...(termDetail?.end_date
      ? [{ label: 'Closing Date', value: formatAsDate(termDetail.end_date) }]
      : []),
    ...(nextTermResumptionDate
      ? [
          {
            label: 'Next Term Begins',
            value: formatAsDate(nextTermResumptionDate),
          },
        ]
      : []),
  ];

  return (
    <ResultSheetLayout resultProps={props} useBgStyle={false}>
      <Div
        id="result-sheet"
        className="template-9-sheet"
        mx="auto"
        width="900px"
        position="relative"
        style={sheetBackgroundStyle}
      >
        <Img
          src={watermarkLogo}
          alt=""
          aria-hidden="true"
          className="template-9-watermark"
        />
        <Div className="template-9-watermark-grid" aria-hidden="true">
          {Array.from({ length: 8 }).map((_, index) => (
            <Img key={index} src={watermarkLogo} alt="" />
          ))}
        </Div>
        <Div className="template-9-school-watermark-text" aria-hidden="true">
          {Array.from({ length: 12 }).map((_, index) => (
            <Text as="span" key={index}>
              {currentInstitution.name}
            </Text>
          ))}
        </Div>
        <Div className="template-9-content">
          <HStack className="template-9-header" align="center" spacing={5}>
            <Avatar
              size="2xl"
              name="Institution logo"
              src={currentInstitution.photo ?? ImagePaths.default_school_logo}
              className="template-9-logo"
            />
            <VStack flex={1} spacing={1} textAlign="center">
              <Text className="template-9-school-name">
                {currentInstitution.name}
              </Text>
              {currentInstitution.subtitle && (
                <Text fontWeight="semibold">{currentInstitution.subtitle}</Text>
              )}
              <Text>{currentInstitution.address}</Text>
              <Text>
                {currentInstitution.phone} | {currentInstitution.email}
              </Text>
              <Text className="template-9-report-title">
                Third-Term And Annual Report Sheet
              </Text>
            </VStack>
            <Avatar
              size="2xl"
              name={`${titles.student} passport`}
              src={student.user?.photo ?? ''}
              className="template-9-logo"
            />
          </HStack>

          <SimpleGrid columns={3} spacing={2} mb={3}>
            {resultSummary.map((item) => (
              <LabelText
                key={item.label}
                label={item.label}
                text={item.value ?? '-'}
              />
            ))}
          </SimpleGrid>

          <Div className="table-container">
            <table className="template-9-table">
              <thead>
                <tr>
                  <th>Subject</th>
                  <th>Third-Term Assessment</th>
                  <th>Third-Term Examination</th>
                  <th>Third-Term Total</th>
                  <th>Second-Term Total</th>
                  <th>First-Term Total</th>
                  <th>Annual Average</th>
                  <th>Remark/Grade</th>
                </tr>
                <tr className="template-9-max-row">
                  <th></th>
                  <th>{score(assessmentMax)}</th>
                  <th>{examMax === undefined ? '-' : score(examMax)}</th>
                  <th>{TOTAL_SCORE_MAX}</th>
                  <th>{TOTAL_SCORE_MAX}</th>
                  <th>{TOTAL_SCORE_MAX}</th>
                  <th>{TOTAL_SCORE_MAX}</th>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                {courseResults.map((courseResult) => {
                  const totals =
                    subjectTermTotals?.[courseResult.course_id] ?? {};
                  const firstTotal = totals.first;
                  const secondTotal = totals.second;
                  const thirdTotal = totals.third ?? courseResult.result;
                  const annualAverage = getAnnualAverage(courseResult);
                  const annualGrade =
                    annualAverage === undefined
                      ? undefined
                      : ResultUtil.getGrade(
                          annualAverage,
                          resultCommentTemplate
                        );

                  return (
                    <tr key={courseResult.id}>
                      <td>{courseResult.course?.title}</td>
                      <td>{score(getAssessmentTotal(courseResult))}</td>
                      <td>{showExamResult ? score(courseResult.exam) : '-'}</td>
                      <td>{score(thirdTotal)}</td>
                      <td>{score(secondTotal)}</td>
                      <td>{score(firstTotal)}</td>
                      <td>{score(annualAverage)}</td>
                      <td>
                        {annualGrade
                          ? `${annualGrade.remark} / ${annualGrade.grade}`
                          : '-'}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </Div>

          <HStack className="template-9-summary" align="stretch">
            <Box>
              <Text>
                <strong>Total:</strong> {termResult.total_score}
              </Text>
              <Text>
                <strong>Average:</strong> {termResult.average}{' '}
              </Text>
              <Text>
                <strong>Cummulative Average:</strong> {cumulativeAverage}
              </Text>
            </Box>
            <Spacer />
            {!hidePosition && (
              <Box textAlign="right">
                <Text>
                  <strong>{showGrade ? 'Overall Grade' : 'Position'}:</strong>{' '}
                  {showGrade
                    ? ResultUtil.getGrade(
                        termResult.average,
                        resultCommentTemplate
                      ).grade
                    : ResultUtil.formatPosition(termResult.position)}
                </Text>
                <Text>
                  <strong>Class Average:</strong> {classResultInfo.average}
                </Text>
              </Box>
            )}
          </HStack>
          <HStack justify={'space-between'}>
            <DisplayTermResultEvaluation
              termResult={termResult}
              learningEvaluations={learningEvaluations}
            />
            {resultCommentTemplate && resultCommentTemplate.length > 0 && (
              <table className="keys-table template-9-keys">
                <thead>
                  <tr>
                    <th>Range (%)</th>
                    <th>Remark</th>
                    <th>Grade</th>
                  </tr>
                </thead>
                <tbody>
                  {resultCommentTemplate.map((item) => (
                    <tr key={`${item.grade}-${item.min}-${item.max}`}>
                      <td>{`${item.min} - ${item.max}`}</td>
                      <td>{item.grade_label}</td>
                      <td>{item.grade}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            )}
          </HStack>

          <Divider my={3} />

          <HStack align="stretch" spacing={4}>
            <VStack flex={1} align="stretch" spacing={3}>
              {teacherComment && (
                <Text>
                  <strong>{titles.headOfClassPossessive} Comment:</strong>{' '}
                  {teacherComment}
                </Text>
              )}
              {principalComment && (
                <Text>
                  <strong>{titles.headOfSchoolPossessive} Comment:</strong>{' '}
                  {principalComment}
                </Text>
              )}
              {stamp && <Img src={stamp} alt="School stamp" boxSize="90px" />}
            </VStack>
          </HStack>
        </Div>
      </Div>
    </ResultSheetLayout>
  );
}
