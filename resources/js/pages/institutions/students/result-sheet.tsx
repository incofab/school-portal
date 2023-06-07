import {
  AcademicSession,
  ClassResultInfo,
  Classification,
  CourseResult,
  CourseResultInfo,
  Student,
  TermResult,
} from '@/types/models';
import { Avatar, HStack, Spacer, Text, VStack } from '@chakra-ui/react';
import React from 'react';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { Div } from '@/components/semantic';
import startCase from 'lodash/startCase';
import useSharedProps from '@/hooks/use-shared-props';
import '@/style/result-sheet.css';

interface Props {
  termResult: TermResult;
  courseResults: CourseResult[];
  classResultInfo: ClassResultInfo;
  courseResultInfoData: { [key: string | number]: CourseResultInfo };
  academicSession: AcademicSession;
  classification: Classification;
  student: Student;
}

export default function ResultSheet({
  termResult,
  courseResults,
  classResultInfo,
  academicSession,
  classification,
  student,
  courseResultInfoData,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const { currentInstitution } = useSharedProps();
  console.log('courseResultInfoData', courseResultInfoData);

  function VerticalText({ text }: { text: string }) {
    return <Text className="vertical-header">{text}</Text>;
  }
  function getPositionSuffix(position: number) {
    const lastChar = position % 10;
    switch (lastChar) {
      case 1:
        return 'st';
      case 2:
        return 'nd';
      case 3:
        return 'rd';
      default:
        return 'th';
    }
  }

  function getRemark(grade: string) {
    switch (grade) {
      case 'A':
        return 'Excellent';
      case 'B':
        return 'Very Good';
      case 'C':
        return 'Good';
      case 'D':
        return 'Fair';
      case 'E':
        return 'Poor';
      default:
        return 'Unknown';
    }
  }

  const resultDetail = [
    { label: "Student's Total Score", value: termResult.total_score },
    {
      label: 'Maximum Total Score',
      value: classResultInfo.max_obtainable_score,
    },
    { label: "Student's Average Score", value: termResult.average },
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

  const svgCode = `<svg xmlns='http://www.w3.org/2000/svg' width='140' height='100' opacity='0.08' viewBox='0 0 100 100' transform='rotate(45)'><text x='0' y='50' font-size='18' fill='%23000'>${currentInstitution.name}</text></svg>`;
  const backgroundStyle = {
    backgroundImage: `url("data:image/svg+xml;charset=utf-8,${encodeURIComponent(
      svgCode
    )}")`,
    backgroundRepeat: 'repeat',
    backgroundColor: 'white',
  };
  return (
    <Div style={backgroundStyle}>
      <Div mx={'auto'} width={'900px'} px={3}>
        <VStack align={'stretch'}>
          <HStack background={'#FAFAFA'} p={2}>
            <Avatar
              size={'2xl'}
              name="Institution logo"
              src={currentInstitution.phone}
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
                <br /> {currentInstitution.email}
              </Text>
              <Text
                fontWeight={'semibold'}
                textAlign={'center'}
                fontSize={'18px'}
              >
                {academicSession?.title} - {startCase(termResult.term)} Term
                Result
              </Text>
            </VStack>
            <Avatar
              size={'2xl'}
              name="Student logo"
              src={student.user?.photo_url}
            />
          </HStack>
          <HStack>
            <LabelText
              label="Name of Student"
              text={student?.user?.full_name}
            />
            <Spacer />
            <LabelText label="Gender" text={student.user?.gender} />
          </HStack>
          <HStack mt={1}>
            <LabelText label="Class" text={classification.title} />
            <Spacer />
            <LabelText
              label="Position"
              text={
                termResult.position + getPositionSuffix(termResult.position)
              }
            />
            <Spacer />
            <LabelText label="Out of" text={classResultInfo.num_of_students} />
          </HStack>
          <div className="table-container">
            <table className="result-table" width={'100%'}>
              <thead>
                <tr>
                  <th>Subjects</th>
                  <th>
                    <VerticalText text="Assessment 1" />
                  </th>
                  <th>
                    <VerticalText text="Assessment 2" />
                  </th>
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
                {courseResults.map((courseResult) => (
                  <tr key={courseResult.id}>
                    <td>{courseResult.course?.title}</td>
                    <td>{courseResult.first_assessment}</td>
                    <td>{courseResult.second_assessment}</td>
                    <td>{courseResult.exam}</td>
                    <td>{courseResult.result}</td>
                    <td>{courseResult.grade}</td>
                    <td>{courseResult.position}</td>
                    <td>
                      {courseResultInfoData[courseResult.course_id]?.average}
                    </td>
                    <td>{getRemark(courseResult.grade)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
          <Div>
            <table className="result-analysis-table">
              <tbody>
                {resultDetail.map(({ label, value }) => (
                  <tr key={label}>
                    <td style={{ width: '250px' }}>{label}</td>
                    <td>{value}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </Div>
        </VStack>
      </Div>
    </Div>
  );
}
