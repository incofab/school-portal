import {
  Avatar,
  Divider,
  HStack,
  Img,
  Spacer,
  Text,
  VStack,
} from '@chakra-ui/react';
import React from 'react';
import { Div } from '@/components/semantic';
import startCase from 'lodash/startCase';
import useSharedProps from '@/hooks/use-shared-props';
import '@/../../public/style/result-sheet.css';
import ImagePaths from '@/util/images';
import ResultUtil, { ResultProps, useResultSetting } from '@/util/result-util';
import ResultSheetLayout, {
  ClosingDate,
  getMaxObtainableScore,
  NextTermDate,
  SchoolStamp,
} from './result-sheet-layout';
import DisplayTermResultEvaluation from '@/components/display-term-result-evaluation-component';
import { roundNumber } from '@/util/util';

export default function Template1(props: ResultProps) {
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
    learningEvaluations,
  } = props;
  const { currentInstitution } = useSharedProps();
  const { hidePosition, showGrade } = useResultSetting();

  const principalComment =
    termResult.principal_comment ??
    ResultUtil.getCommentFromTemplate(termResult.average, resultCommentTemplate)
      ?.comment;
  const teacherComment =
    termResult.teacher_comment ??
    ResultUtil.getCommentFromTemplate(termResult.average, resultCommentTemplate)
      ?.comment_2;

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

  function LabelText({
    label,
    text,
  }: {
    label: string;
    text: string | number | undefined;
  }) {
    return (
      <Div>
        <Text as={'span'} fontWeight={'semibold'}>
          {label}:
        </Text>
        <Text as={'span'} ml={3}>
          {text}
        </Text>
      </Div>
    );
  }

  // const svgCode = `<svg xmlns='http://www.w3.org/2000/svg' width='140' height='100' opacity='0.08' viewBox='0 0 100 100' transform='rotate(45)'><text x='0' y='50' font-size='18' fill='%23000'>${currentInstitution.name}</text></svg>`;
  // const backgroundStyle = {
  //   backgroundImage: `url("data:image/svg+xml;charset=utf-8,${encodeURIComponent(
  //     svgCode
  //   )}")`,
  //   backgroundRepeat: 'repeat',
  //   backgroundColor: 'white',
  // };
  return (
    <ResultSheetLayout resultProps={props}>
      <Div
        mx={'auto'}
        width={'900px'}
        px={3}
        id="result-sheet"
        position={'relative'}
      >
        <VStack align={'stretch'}>
          <HStack background={'#FAFAFA'} p={2}>
            <Avatar
              size={'2xl'}
              name="Institution logo"
              src={currentInstitution.photo ?? ImagePaths.default_school_logo}
            />
            <VStack spacing={1} align={'stretch'} width={'full'}>
              <Text fontSize={'2xl'} fontWeight={'bold'} textAlign={'center'}>
                {currentInstitution.name}
              </Text>
              <Text
                textAlign={'center'}
                fontSize={'18px'}
                whiteSpace={'nowrap'}
              >
                {currentInstitution.address}
                <br /> {currentInstitution.email} | {currentInstitution.phone}
              </Text>
              <Text
                fontWeight={'semibold'}
                textAlign={'center'}
                fontSize={'18px'}
              >
                {academicSession?.title} - {startCase(termResult.term)}{' '}
                {termResult.for_mid_term ? 'Mid ' : ''}Term Result
              </Text>
            </VStack>
            <Avatar
              size={'2xl'}
              name="Student logo"
              src={student.user?.photo_url}
            />
          </HStack>
          <HStack>
            <LabelText label="Name" text={student?.user?.full_name} />
            <Spacer />
            <LabelText label="Gender" text={student.user?.gender} />
          </HStack>
          <HStack mt={1}>
            <LabelText label="Class" text={classification.title} />
            {hidePosition ? (
              <></>
            ) : (
              <>
                <Spacer />
                <LabelText
                  label="Position"
                  text={
                    showGrade
                      ? ResultUtil.getGrade(
                          termResult.average,
                          resultCommentTemplate
                        ).grade
                      : `${termResult.position} ${ResultUtil.getPositionSuffix(
                          termResult.position
                        )}`
                  }
                />
              </>
            )}
            <Spacer />
            <LabelText
              label="No in Class"
              text={classResultInfo.num_of_students}
            />
          </HStack>
          <HStack>
            <ClosingDate resultProps={props} />
            <Spacer />
            <NextTermDate resultProps={props} />
          </HStack>
          <div className="table-container">
            <table className="result-table" width={'100%'}>
              <thead>
                <tr>
                  <th>Subjects</th>
                  {assessments.map((assessment) => (
                    <th>
                      <VerticalText text={startCase(assessment.title)} />
                    </th>
                  ))}
                  <th>
                    <VerticalText text="Exam" />
                  </th>
                  <th>
                    <VerticalText text="Total" />
                  </th>
                  <th>
                    <VerticalText text="Grade" />
                  </th>
                  <th>
                    <VerticalText text="Position" />
                  </th>
                  <th>
                    <VerticalText text="Class Average" />
                  </th>
                  <th>Remark</th>
                </tr>
              </thead>
              <tbody>
                {courseResults.map((courseResult) => {
                  const { grade, remark } = ResultUtil.getGrade(
                    courseResult.result,
                    resultCommentTemplate
                  );
                  return (
                    <tr key={courseResult.id}>
                      <td>{courseResult.course?.title}</td>
                      {assessments.map((assessment) => (
                        <td>
                          {courseResult.assessment_values[
                            assessment.raw_title
                          ] ?? '-'}
                        </td>
                      ))}
                      <td>{courseResult.exam}</td>
                      <td>{courseResult.result}</td>
                      {/* <td>{courseResult.grade}</td> */}
                      <td>{grade}</td>
                      <td>{courseResult.position}</td>
                      <td>
                        {courseResultInfoData[courseResult.course_id]?.average}
                      </td>
                      {/* <td>{ResultUtil.getRemark(courseResult.grade)}</td> */}
                      <td>{remark}</td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
          <Spacer height={'10px'} />
          <Div>
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
                    Principal/Head Teacher's comment:{' '}
                  </Text>
                  <Text>{principalComment}</Text>
                </HStack>
                <Divider />
              </>
            )}
          </Div>
          <Spacer height={'10px'} />
          <HStack align={'stretch'} justify={'space-between'}>
            <Div>
              <table className="result-analysis-table">
                <tbody>
                  {resultDetail.map(({ label, value }) => (
                    <tr key={label}>
                      <td style={{ width: '250px' }}>{label}</td>
                      <td>{roundNumber(value, 2)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
              <table
                className="result-analysis-table"
                width={'100%'}
                style={{ marginTop: '5px' }}
              >
                <thead>
                  <tr>
                    <th colSpan={3}>Keys</th>
                  </tr>
                  <tr>
                    <td>Score</td>
                    <td>Grade</td>
                    <td>Remark</td>
                  </tr>
                </thead>
                <tbody>
                  {resultCommentTemplate.map((item) => {
                    const { grade, grade_label } = item;
                    return (
                      <tr key={grade}>
                        <td>{`${item.min}-${item.max}`}</td>
                        <td>{grade_label}</td>
                        <td>{grade}</td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </Div>
            <Div>
              {learningEvaluations.length > 0 && (
                <>
                  <Div flex={3}>
                    <DisplayTermResultEvaluation
                      termResult={termResult}
                      learningEvaluations={learningEvaluations}
                    />
                  </Div>
                  <Div>
                    <b>Number Rating:</b> 5 = Excellent, 4 = Good, 3 = Average,
                    2 = Below Average, 1 = Unsatisfactory
                  </Div>
                  <Div>
                    <b>Letter Rating:</b> A = Excellent, B = Good, C = Average,
                    D = Below Average, E = Unsatisfactory
                  </Div>
                </>
              )}
              <SchoolStamp
                mt={2}
                textAlign={'center'}
                opacity={0.5}
                position={'absolute'}
                bottom={0}
                right={50}
              />
            </Div>
          </HStack>
        </VStack>
        {/* <Div
          textAlign={'center'}
          position={'absolute'}
          bottom={150}
          right={0}
          opacity={0.5}
        >
          <SchoolStamp />
        </Div> */}
      </Div>
    </ResultSheetLayout>
  );
}
