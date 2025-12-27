import { CourseResult } from '@/types/models';
import {
  Avatar,
  Divider,
  Flex,
  HStack,
  Spacer,
  Text,
  VStack,
} from '@chakra-ui/react';
import React from 'react';
import { Div } from '@/components/semantic';
import startCase from 'lodash/startCase';
import useSharedProps from '@/hooks/use-shared-props';
import '@/../../public/style/result-sheet.css';
import ResultUtil, { ResultProps, useResultSetting } from '@/util/result-util';
import { GradingTable, LabelText } from '@/components/result-helper-components';
import ResultSheetLayout, {
  ClosingDate,
  getMaxObtainableScore,
  getWebsite,
  NextTermDate,
  SchoolLogo,
  SchoolStamp,
  StudentPassport,
} from './result-sheet-layout';
import DisplayTermResultEvaluation from '@/components/display-term-result-evaluation-component';
import { roundNumber } from '@/util/util';

export default function Template2(props: ResultProps) {
  const {
    termResult,
    courseResults,
    classResultInfo,
    academicSession,
    classification,
    student,
    resultCommentTemplate,
    learningEvaluations,
    courseResultInfoData,
  } = props;
  const { currentInstitution } = useSharedProps();
  const { hidePosition, showGrade } = useResultSetting();
  function VerticalText({ text }: { text: string }) {
    return <Text className="vertical-header">{text}</Text>;
  }
  const resultDetail = [
    { label: 'Total Score', value: termResult.total_score },
    {
      label: 'Maximum Total Score',
      value: getMaxObtainableScore(props),
    },
    { label: 'Average Score', value: termResult.average },
    { label: 'Class Average Score', value: classResultInfo.average },
  ];

  const principalComment =
    termResult.principal_comment ??
    ResultUtil.getCommentFromTemplate(termResult.average, resultCommentTemplate)
      ?.comment;
  const teacherComment =
    termResult.teacher_comment ??
    ResultUtil.getCommentFromTemplate(termResult.average, resultCommentTemplate)
      ?.comment_2;

  // function LabelText({
  //   label,
  //   text,
  // }: {
  //   label: string;
  //   text: string | number | undefined;
  // }) {
  //   return (
  //     <Div>
  //       <Text as={'span'} fontWeight={'semibold'}>
  //         {label}:
  //       </Text>
  //       <Text as={'span'} ml={3}>
  //         {text}
  //       </Text>
  //     </Div>
  //   );
  // }

  function getAssessmentScore(courseResult: CourseResult) {
    let total = 0;
    Object.entries(courseResult.assessment_values).map(
      ([, val]) => (total += Number(val))
    );
    return total;
  }

  return (
    <ResultSheetLayout resultProps={props}>
      <Div mx={'auto'} width={'900px'} px={3} id="result-sheet">
        <VStack align={'stretch'}>
          <HStack background={'#FAFAFA'} p={2}>
            <SchoolLogo />
            <VStack
              spacing={0}
              align={'stretch'}
              width={'full'}
              textAlign={'center'}
            >
              <Text
                fontSize={'2xl'}
                fontWeight={'bold'}
                textAlign={'center'}
                color={'red'}
              >
                {currentInstitution.subtitle}
              </Text>
              <Text
                fontSize={'2xl'}
                fontWeight={'bold'}
                textAlign={'center'}
                color={'green'}
              >
                {currentInstitution.name}
              </Text>
              <Text
                textAlign={'center'}
                fontSize={'18px'}
                whiteSpace={'nowrap'}
                color={'green'}
                fontWeight={'semibold'}
              >
                {currentInstitution.address}
              </Text>
              <Div>
                <Text>
                  {currentInstitution.phone} | {currentInstitution.email} |{' '}
                  {getWebsite(currentInstitution)}
                </Text>
              </Div>
              <Div>
                <Text
                  mt={2}
                  mb={1}
                  backgroundColor={'red'}
                  color={'white'}
                  py={1}
                  px={4}
                  as={'span'}
                  display={'inline-block'}
                  borderRadius={'10px'}
                  fontWeight={'semibold'}
                >
                  {/* {ResultUtil.getClassSection(classification.title)} */}
                  {academicSession?.title} -{' '}
                  {termResult.for_mid_term ? 'Mid ' : ''}
                  {startCase(termResult.term)} Term Result
                </Text>
              </Div>
            </VStack>
            <StudentPassport student={student} />
          </HStack>
          <Div>
            <Flex flexDirection={'row'} justifyContent={'space-between'}>
              <LabelText
                labelProps={{ fontWeight: 'semibold' }}
                label="Name"
                text={student?.user?.full_name}
              />
              <LabelText
                labelProps={{ fontWeight: 'semibold' }}
                label="Class"
                text={classification.title}
              />
            </Flex>
            <Flex mt={1} flexDirection={'row'} justifyContent={'space-between'}>
              <LabelText
                labelProps={{ fontWeight: 'semibold' }}
                label="No in Class"
                text={classResultInfo.num_of_students}
              />
              <LabelText
                labelProps={{ fontWeight: 'semibold' }}
                label="Gender"
                text={student.user?.gender}
              />
            </Flex>
            <Flex mt={1} flexDirection={'row'} justifyContent={'space-between'}>
              <ClosingDate resultProps={props} />
              <NextTermDate resultProps={props} />
            </Flex>
          </Div>
          <div style={{ display: 'flex', flexDirection: 'row', gap: '10px' }}>
            <div className="table-container" style={{ flexGrow: 2 }}>
              <table className="result-table" width={'100%'}>
                <thead style={{ background: 'red.800', color: '#3f3f3f' }}>
                  <tr>
                    <th>Subject</th>
                    <th>CA</th>
                    <th>Exam</th>
                    <th>Total</th>
                    <th>Grade</th>
                    <th>Highest/Lowest</th>
                    <th>{hidePosition ? 'Remark' : 'Subject Position'}</th>
                  </tr>
                </thead>
                <tbody>
                  {courseResults.map((courseResult) => {
                    const highest =
                      courseResultInfoData[courseResult.course_id]?.max_score;
                    const lowest =
                      courseResultInfoData[courseResult.course_id]?.min_score;
                    const { grade, remark } = ResultUtil.getGrade(
                      courseResult.result,
                      resultCommentTemplate
                    );
                    return (
                      <tr key={'results-' + courseResult.id}>
                        <td
                          style={{
                            maxWidth: '190px',
                            overflow: 'hidden',
                            textOverflow: 'ellipsis',
                            whiteSpace: 'nowrap',
                          }}
                        >
                          {courseResult.course?.title}
                        </td>
                        <td>{getAssessmentScore(courseResult)}</td>
                        <td>{courseResult.exam}</td>
                        <td>{courseResult.result}</td>
                        <td>{grade}</td>
                        <td>
                          {roundNumber(highest, 0)} / {roundNumber(lowest, 0)}
                        </td>
                        <td>{hidePosition ? remark : courseResult.position}</td>
                      </tr>
                    );
                  })}
                  <tr>
                    <td>Total</td>
                    <td></td>
                    <td></td>
                    <td>{termResult.total_score}</td>
                    <td></td>
                    <td></td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div style={{ minWidth: '240px' }}>
              <GradingTable resultCommentTemplate={resultCommentTemplate} />
            </div>
          </div>
          <Spacer height={'10px'} />
          <HStack justifyContent={'space-between'}>
            {hidePosition ? (
              <></>
            ) : (
              <>
                <LabelText
                  labelProps={{ fontWeight: 'semibold' }}
                  label="Position"
                  text={
                    showGrade
                      ? ResultUtil.getGrade(
                          termResult.average,
                          resultCommentTemplate
                        ).grade
                      : termResult.position
                  }
                />
                <LabelText
                  labelProps={{ fontWeight: 'semibold' }}
                  textProps={{ fontWeight: 'bold', textTransform: 'uppercase' }}
                  label="Overall Result"
                  text={
                    ResultUtil.getGrade(
                      termResult.average,
                      resultCommentTemplate
                    ).remark
                  }
                />
              </>
            )}
          </HStack>
          <HStack>
            <LabelText
              labelProps={{ fontWeight: 'semibold' }}
              label="Overall Score"
              text={termResult.total_score}
            />
            {/* <Spacer />
            <LabelText
              labelProps={{ fontWeight: 'semibold' }}
              label="Obtainable"
              text={getMaxObtainableScore(props)}
            />
            <Spacer />
            <LabelText
              labelProps={{ fontWeight: 'semibold' }}
              label="Obtained"
              text={classResultInfo.max_score}
            /> */}
            <Spacer />
            <LabelText
              labelProps={{ fontWeight: 'semibold' }}
              label="Average"
              text={roundNumber(termResult.average)}
            />
          </HStack>
          <Spacer height={'5px'} />
          <HStack>
            {learningEvaluations.length > 0 && (
              <Div flex={3}>
                <DisplayTermResultEvaluation
                  termResult={termResult}
                  learningEvaluations={learningEvaluations}
                />
              </Div>
            )}
            <Div flex={1}>
              <table className="result-analysis-table">
                <tbody>
                  {resultDetail.map(({ label, value }) => (
                    <tr key={'result analysis' + label}>
                      <td style={{ width: '250px' }}>{label}</td>
                      <td>{roundNumber(value)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </Div>
          </HStack>
          {teacherComment && (
            <>
              <HStack align={'stretch'}>
                <Text fontWeight={'semibold'} size={'xs'}>
                  Teacher's comment:{' '}
                </Text>
                <Text>{teacherComment}</Text>
              </HStack>
              <Divider />
            </>
          )}
          {principalComment && (
            <>
              <HStack align={'stretch'}>
                <Text fontWeight={'semibold'} size={'xs'}>
                  Principal/Head Teacher:{' '}
                </Text>
                <Text>{principalComment}</Text>
              </HStack>
              <Divider />
            </>
          )}
        </VStack>
        <Div position={'absolute'} bottom={150} opacity={'0.7'} right={300}>
          <SchoolStamp />
        </Div>
      </Div>
    </ResultSheetLayout>
  );
}
