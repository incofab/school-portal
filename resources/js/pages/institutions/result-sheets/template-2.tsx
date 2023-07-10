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
import ImagePaths from '@/util/images';
import ResultUtil from '@/util/result-util';
import { GradingTable } from '@/components/result-helper-components';

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

export default function Template2({
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

  function VerticalText({ text }: { text: string }) {
    return <Text className="vertical-header">{text}</Text>;
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

  function getAssessmentScore(courseResult: CourseResult) {
    let total = 0;
    Object.entries(courseResult.assessment_values).map(
      ([key, val]) => (total += val)
    );
    return total;
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
          <HStack background={'#FAFAFA'} p={2}>
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
                fontWeight={'bold'}
                textAlign={'center'}
                color={'red'}
              >
                {currentInstitution.sub_title}
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
                  {ResultUtil.getClassSection(classification.title)}
                </Text>
              </Div>
            </VStack>
            <Div>
              <Text>Phone: {currentInstitution.phone}</Text>
              <Text>Email: {currentInstitution.email}</Text>
            </Div>
          </HStack>
          <Flex flexDirection={'row'} justifyContent={'space-between'}>
            <LabelText label="Pupil's Name" text={student?.user?.full_name} />
            <LabelText label="Class" text={classification.title} />
            <LabelText label="Gender" text={student.user?.gender} />
          </Flex>
          <Flex mt={1} flexDirection={'row'} justifyContent={'space-between'}>
            <LabelText
              label="Class Population"
              text={classResultInfo.num_of_courses}
            />
            <LabelText
              label="Term"
              text={`${startCase(termResult.term)} ${
                termResult.for_mid_term ? 'Mid Term' : ''
              }`}
            />
            <LabelText label="Session" text={academicSession.title} />
          </Flex>
          <div style={{ display: 'flex', flexDirection: 'row', gap: '10px' }}>
            <div className="table-container" style={{ flexGrow: 2 }}>
              <table className="result-table" width={'100%'}>
                <thead style={{ background: 'red.800', color: '#3f3f3f' }}>
                  <tr>
                    <th>Subject</th>
                    <th>CA</th>
                    <th>Exam</th>
                    <th>Total</th>
                    <th>Subject Position</th>
                    <th>Grade</th>
                  </tr>
                </thead>
                <tbody>
                  {courseResults.map((courseResult) => (
                    <tr key={courseResult.id}>
                      <td>{courseResult.course?.title}</td>
                      <td>{getAssessmentScore(courseResult)}</td>
                      <td>{courseResult.exam}</td>
                      <td>{courseResult.result}</td>
                      <td>{courseResult.position}</td>
                      <td>{courseResult.grade}</td>
                    </tr>
                  ))}
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
              <GradingTable />
            </div>
          </div>
          <Spacer height={'10px'} />
          <LabelText label="Position" text={termResult.position} />
          <HStack>
            <LabelText label="Overal Result" text={termResult.total_score} />
            <Spacer />
            <LabelText
              label="Obtainable"
              text={classResultInfo.max_obtainable_score}
            />
            <Spacer />
            <LabelText label="Obtained" text={classResultInfo.max_score} />
            <Spacer />
            <LabelText label="Average" text={termResult.average} />
          </HStack>
          <Spacer height={'5px'} />
          <div>
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
          </div>
          {termResult.teacher_comment && (
            <>
              <HStack align={'stretch'}>
                <Text fontWeight={'semibold'} size={'xs'}>
                  Teacher's comment:{' '}
                </Text>
                <Text>{termResult.teacher_comment}</Text>
              </HStack>
              <Divider />
            </>
          )}
          {termResult.principal_comment && (
            <>
              <HStack align={'stretch'}>
                <Text fontWeight={'semibold'} size={'xs'}>
                  Principal's comment:{' '}
                </Text>
                <Text>{termResult.principal_comment}</Text>
              </HStack>
              <Divider />
            </>
          )}
        </VStack>
      </Div>
    </Div>
  );
}
