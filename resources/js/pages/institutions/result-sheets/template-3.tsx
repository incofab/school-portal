import {
  Avatar,
  Button,
  Divider,
  Flex,
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
import '@/../../public/style/result/template-3.css';
import ImagePaths from '@/util/images';
import ResultUtil, { ResultProps, useResultSetting } from '@/util/result-util';
import DisplayTermResultEvaluation from '@/components/display-term-result-evaluation-component';
import ResultSheetLayout from './result-sheet-layout';

export default function Template3({
  termResult,
  courseResults,
  classResultInfo,
  academicSession,
  classification,
  student,
  courseResultInfoData,
  assessments,
  learningEvaluations,
  resultCommentTemplate,
}: ResultProps) {
  const { currentInstitution, stamp } = useSharedProps();
  const { hidePosition, showGrade } = useResultSetting();
  // const teachersComment =
  //   termResult.teacher_comment ?? getGrade(termResult.average)[3];

  const principalComment =
    termResult.principal_comment ??
    ResultUtil.getCommentFromTemplate(termResult.average, resultCommentTemplate)
      ?.comment;
  const teacherComment =
    termResult.teacher_comment ??
    ResultUtil.getCommentFromTemplate(termResult.average, resultCommentTemplate)
      ?.comment_2 ??
    getGrade(termResult.average)[3];

  const resultSummary1 = [
    { label: 'Student Name', value: student.user?.full_name },
    { label: 'No In Class', value: classResultInfo.num_of_students },
    {
      label: 'Total Marks Obtainable',
      value: classResultInfo.max_obtainable_score,
    },
    { label: 'Class Average', value: classResultInfo.average },
    { label: 'Student Id', value: student.code },
  ];
  const resultSummary2 = [
    { label: 'Total Marks Obtained', value: termResult.total_score },
    { label: 'Class', value: classification.title },
    {
      label: 'Term',
      value: `${termResult.term} Term, ${academicSession.title} Session`,
    },
    { label: 'Average', value: termResult.average },
    {
      label: 'Position',
      value: hidePosition
        ? ResultUtil.getGrade(termResult.average, resultCommentTemplate).remark
        : ResultUtil.formatPosition(termResult.position),
    },
  ];

  function getGrade(score: number) {
    let grade = '';
    let remark = '';
    let label = '';
    let comment = '';
    if (score < 40) {
      grade = 'F';
      remark = 'Fail';
      label = '0 - 39';
      comment = 'Progressing';
    } else if (score < 45) {
      grade = 'E';
      remark = 'Poor Pass';
      label = '40 - 44';
      comment =
        'Your performance has improved quite well. But you still have a lot of catching up to do';
    } else if (score < 50) {
      grade = 'D';
      remark = 'Pass';
      label = '45 - 49';
      comment =
        'You have a lot of potential within but only needs to be more serious and less playful. If you are more disciplined, I believe the grades will improve';
    } else if (score < 55) {
      grade = 'C6';
      remark = 'Credit';
      label = '50 - 54';
      comment =
        'I am impressed with the performance. It shows you are taking your studies seriously';
    } else if (score < 60) {
      grade = 'C4';
      remark = 'Credit';
      label = '55 - 59';
      comment =
        'I am impressed with this performance. It shows you are taking your studies seriously';
    } else if (score < 65) {
      grade = 'B3';
      remark = 'Good';
      label = '60 - 64';
      comment = 'A good performance';
    } else if (score < 70) {
      grade = 'B2';
      remark = 'Very Good';
      label = '65 - 69';
      comment = 'A very good performance';
    } else if (score < 80) {
      grade = 'B1';
      remark = 'Very Good';
      label = '70 - 79';
      comment = 'A very good performance';
    } else if (score < 90) {
      grade = 'A2';
      remark = 'Excellent';
      label = '80 - 89';
      comment = 'An intelligent and excellent performance';
    } else {
      grade = 'A1';
      remark = 'Distinction';
      label = '90 - 100';
      comment = 'A very brilliant and clever performance';
    }
    return [grade, remark, label, comment];
  }

  function LabelText({
    label,
    text,
  }: {
    label: string;
    text: string | number | undefined | React.ReactNode;
  }) {
    return (
      <Div fontWeight={'bold'}>
        <Text as={'span'} textTransform={'uppercase'}>
          {label}:
        </Text>
        <Text as={'span'} ml={3}>
          {text}
        </Text>
      </Div>
    );
  }

  const svgCode = `<svg xmlns='http://www.w3.org/2000/svg' width='140' height='100' opacity='0.08' viewBox='0 0 100 100' transform='rotate(45)'><text x='0' y='50' font-size='18' fill='%23000'>${currentInstitution.name}</text></svg>`;
  const backgroundStyle = {
    backgroundImage: `url("data:image/svg+xml;charset=utf-8,${encodeURIComponent(
      svgCode
    )}")`,
    backgroundRepeat: 'repeat',
    backgroundColor: 'white',
  };

  return (
    <ResultSheetLayout>
      <Div style={backgroundStyle} minHeight={'1170px'}>
        <Button
          id={'download-btn'}
          onClick={() =>
            ResultUtil.exportAsPdf(
              'result-sheet',
              student.user?.full_name + '-result-sheet'
            )
          }
          size={'sm'}
          variant={'outline'}
          colorScheme="brand"
        >
          Download
        </Button>
        <Div
          mx={'auto'}
          width={'900px'}
          px={3}
          position={'relative'}
          id="result-sheet"
        >
          <VStack align={'stretch'}>
            <HStack background={'#FCFCFC'} p={2}>
              <Avatar
                size={'2xl'}
                name="Institution logo"
                src={currentInstitution.photo ?? ImagePaths.default_school_logo}
              />
              <VStack
                spacing={1}
                align={'stretch'}
                width={'full'}
                textAlign={'center'}
              >
                <Text
                  fontSize={'2xl'}
                  fontWeight={'extrabold'}
                  textAlign={'center'}
                  color={'#ff7900'}
                  textShadow={'2px 2px #000'}
                  textTransform={'uppercase'}
                  whiteSpace={'nowrap'}
                >
                  {currentInstitution.name}
                </Text>
                <Text
                  fontSize={'md'}
                  fontWeight={'bold'}
                  textAlign={'center'}
                  color={'black'}
                  textTransform={'uppercase'}
                  whiteSpace={'nowrap'}
                >
                  {currentInstitution.caption}
                </Text>
                <Text
                  fontSize={'2xl'}
                  fontWeight={'extrabold'}
                  textAlign={'center'}
                  color={'#0097df'}
                  textTransform={'uppercase'}
                  whiteSpace={'nowrap'}
                >
                  {currentInstitution.subtitle}
                </Text>
              </VStack>
            </HStack>
            <Flex
              flexDirection={'row'}
              justifyContent={'space-between'}
              fontSize={'sm'}
            >
              <VStack spacing={0} align={'left'}>
                {resultSummary1.map((item) => (
                  <LabelText label={item.label} text={item.value} />
                ))}
              </VStack>
              <VStack spacing={0} align={'left'}>
                {resultSummary2.map((item) => (
                  <LabelText label={item.label} text={item.value} />
                ))}
              </VStack>
            </Flex>
            <div className="table-container">
              <table
                className="result-table"
                width={'100%'}
                style={{ borderColor: '#5b9bd5' }}
              >
                <thead
                  style={{
                    background: 'red.800',
                    color: '#3f3f3f',
                    fontWeight: 'bold',
                    textTransform: 'uppercase',
                    fontSize: '14px',
                  }}
                >
                  <tr>
                    <th
                      style={{
                        background: '#5b9bd5',
                        color: '#fff',
                      }}
                    >
                      Subject
                    </th>
                    {assessments.map((assessment) => (
                      <th>{startCase(assessment.title)}</th>
                    ))}
                    <th>Exam</th>
                    <th>Total</th>
                    {!hidePosition && <th>Subject Position</th>}
                    <th>Grade</th>
                    <th>Highest Score</th>
                    <th>Lowest Score</th>
                    <th>Remark</th>
                  </tr>
                </thead>
                <tbody>
                  {courseResults.map((courseResult) => {
                    const { grade, remark } = ResultUtil.getGrade(
                      courseResult.result,
                      resultCommentTemplate
                    );
                    // const [grade, remark, label] = getGrade(
                    //   courseResult.result
                    // );
                    return (
                      <tr key={courseResult.id}>
                        <td style={{ fontWeight: 'bold' }}>
                          {courseResult.course?.title}
                        </td>
                        {assessments.map((assessment) => (
                          <td>
                            {courseResult.assessment_values[
                              assessment.raw_title
                            ] ?? '-'}
                          </td>
                        ))}
                        <td>{courseResult.exam}</td>
                        <td style={{ fontWeight: 'bold' }}>
                          {courseResult.result}
                        </td>
                        {!hidePosition && <td>{courseResult.position}</td>}
                        <td style={{ fontWeight: 'bold' }}>{grade}</td>
                        <td>
                          {
                            courseResultInfoData[courseResult.course_id]
                              .max_score
                          }
                        </td>
                        <td>
                          {
                            courseResultInfoData[courseResult.course_id]
                              .min_score
                          }
                        </td>
                        <td>{remark}</td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
            </div>
            <Spacer height={'10px'} />
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
                    Head Teacher's comment:{' '}
                  </Text>
                  <Text>{principalComment}</Text>
                </HStack>
                <Divider />
              </>
            )}
            <br />
            <Flex flexDirection={'row'} justifyContent={'space-between'}>
              <div style={{ minWidth: '240px' }}>
                <table className="keys-table">
                  <thead>
                    <tr>
                      <th colSpan={3}>
                        <Text textAlign={'center'}>Key to Grades</Text>
                      </th>
                    </tr>
                  </thead>
                  <tbody>
                    {/* {[100, 89, 79, 69, 64, 59, 54, 49, 44, 39].map((item) => { */}
                    {resultCommentTemplate.map((item) => {
                      const { grade, grade_label } = item;
                      // const [grade, remark, label] = getGrade(item);
                      return (
                        <tr>
                          <td>{`${item.min} - ${item.max}`}</td>
                          <td>{grade_label}</td>
                          <td>{grade}</td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              </div>
              <Div textAlign={'center'} mx={2}>
                <Img src={stamp} alt="School stamp" display={'inline-block'} />
              </Div>
              <DisplayTermResultEvaluation
                termResult={termResult}
                learningEvaluations={learningEvaluations}
              />
            </Flex>
          </VStack>
        </Div>
      </Div>
    </ResultSheetLayout>
  );
}
