import {
  AcademicSession,
  Assessment,
  ClassResultInfo,
  Classification,
  CourseResult,
  CourseResultInfo,
  Student,
  TermResult,
} from '@/types/models';
import { Avatar, Flex, HStack, Text, VStack } from '@chakra-ui/react';
import React from 'react';
import { Div } from '@/components/semantic';
import startCase from 'lodash/startCase';
import useSharedProps from '@/hooks/use-shared-props';
import '@/../../public/style/result-sheet.css';
import '@/../../public/style/result/template-3.css';
import ImagePaths from '@/util/images';
import ResultUtil from '@/util/result-util';

interface Props {
  termResult: TermResult;
  courseResults: CourseResult[];
  classResultInfo: ClassResultInfo;
  courseResultInfoData: { [key: string | number]: CourseResultInfo };
  academicSession: AcademicSession;
  classification: Classification;
  student: Student;
  assessments: Assessment[];
}

export default function Template3({
  termResult,
  courseResults,
  classResultInfo,
  academicSession,
  classification,
  student,
  courseResultInfoData,
  assessments,
}: Props) {
  const { currentInstitution } = useSharedProps();

  const resultSummary1 = [
    { label: 'Student Name', value: student.user?.full_name },
    { label: 'Number In Class', value: classResultInfo.num_of_students },
    {
      label: 'Total Marks Obtainable',
      value: classResultInfo.max_obtainable_score,
    },
    { label: 'Class Average', value: classResultInfo.average },
    { label: 'Student Id', value: student.code },
  ];
  const resultSummary2 = [
    { label: 'Total Marks Id', value: termResult.total_score },
    { label: 'Class', value: classification.title },
    {
      label: 'Term',
      value: `${termResult.term} Term, ${academicSession.title} Session`,
    },
    { label: 'Average', value: termResult.average },
    {
      label: 'Position',
      value: ResultUtil.formatPosition(termResult.position),
    },
  ];

  function getGrade(score: number) {
    let grade = '';
    let remark = '';
    let label = '';
    if (score < 40) {
      grade = 'F';
      remark = 'Fail';
      label = '0 - 39';
    } else if (score < 45) {
      grade = 'E';
      remark = 'Poor Pass';
      label = '40 - 44';
    } else if (score < 50) {
      grade = 'D';
      remark = 'Pass';
      label = '45 - 49';
    } else if (score < 55) {
      grade = 'C6';
      remark = 'Credit';
      label = '50 - 54';
    } else if (score < 60) {
      grade = 'C4';
      remark = 'Credit';
      label = '55 - 59';
    } else if (score < 65) {
      grade = 'B3';
      remark = 'Good';
      label = '60 - 64';
    } else if (score < 70) {
      grade = 'B2';
      remark = 'Very Good';
      label = '65 - 69';
    } else if (score < 80) {
      grade = 'B1';
      remark = 'Very Good';
      label = '70 - 79';
    } else if (score < 90) {
      grade = 'A2';
      remark = 'Excellent';
      label = '80 - 89';
    } else {
      grade = 'A1';
      remark = 'Distinction';
      label = '90 - 100';
    }
    return [grade, remark, label];
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
    <Div style={backgroundStyle} minHeight={'1170px'}>
      <Div mx={'auto'} width={'900px'} px={3}>
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
                fontSize={'3xl'}
                fontWeight={'extrabold'}
                textAlign={'center'}
                color={'#ff7900'}
                textShadow={'3px 3px #000'}
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
                fontSize={'3xl'}
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
            fontSize={'lg'}
          >
            <VStack spacing={2} align={'left'}>
              {resultSummary1.map((item) => (
                <LabelText label={item.label} text={item.value} />
              ))}
            </VStack>
            <VStack spacing={2} align={'left'}>
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
                  <th>Subject Position</th>
                  <th>Grade</th>
                  <th>Highest Score</th>
                  <th>Lowest Score</th>
                  <th>Remark</th>
                </tr>
              </thead>
              <tbody>
                {courseResults.map((courseResult) => (
                  <tr key={courseResult.id}>
                    <td style={{ fontWeight: 'bold' }}>
                      {courseResult.course?.title}
                    </td>
                    {assessments.map((assessment) => (
                      <td>
                        {courseResult.assessment_values[assessment.raw_title] ??
                          '-'}
                      </td>
                    ))}
                    <td>{courseResult.exam}</td>
                    <td style={{ fontWeight: 'bold' }}>
                      {courseResult.result}
                    </td>
                    <td>{courseResult.position}</td>
                    <td style={{ fontWeight: 'bold' }}>{courseResult.grade}</td>
                    <td>
                      {courseResultInfoData[courseResult.course_id].max_score}
                    </td>
                    <td>
                      {courseResultInfoData[courseResult.course_id].min_score}
                    </td>
                    <td>{ResultUtil.getRemark(courseResult.grade)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <br />
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
                {[100, 89, 79, 69, 64, 59, 54, 49, 44, 39].map((item) => {
                  const [grade, remark, label] = getGrade(item);
                  return (
                    <tr>
                      <td>{label}</td>
                      <td>{grade}</td>
                      <td>{remark}</td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </VStack>
      </Div>
    </Div>
  );
}
