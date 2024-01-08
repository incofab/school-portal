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
import ResultUtil, { ResultProps } from '@/util/result-util';
import ResultSheetLayout from './result-sheet-layout';
import ResultDownloadButton from './result-download-button';

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

export default function Template1({
  termResult,
  courseResults,
  classResultInfo,
  academicSession,
  classification,
  student,
  courseResultInfoData,
  assessments,
  resultCommentTemplate,
  signed_url,
}: ResultProps) {
  const { currentInstitution, stamp } = useSharedProps();

  const principalComment =
    termResult.principal_comment ??
    ResultUtil.getCommentFromTemplate(termResult.average, resultCommentTemplate)
      ?.comment;

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
        <ResultDownloadButton
          signed_url={signed_url}
          student={student}
          termResult={termResult}
        />
        <Div mx={'auto'} width={'900px'} px={3}>
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
                  <br /> {currentInstitution.email}
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
                  termResult.position +
                  ResultUtil.getPositionSuffix(termResult.position)
                }
              />
              <Spacer />
              <LabelText
                label="Out of"
                text={classResultInfo.num_of_students}
              />
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
                  {courseResults.map((courseResult) => (
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
                      <td>{courseResult.grade}</td>
                      <td>{courseResult.position}</td>
                      <td>
                        {courseResultInfoData[courseResult.course_id]?.average}
                      </td>
                      <td>{ResultUtil.getRemark(courseResult.grade)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <Spacer height={'10px'} />
            <Div>
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
              {principalComment && (
                <>
                  <HStack align={'stretch'}>
                    <Text fontWeight={'semibold'} size={'xs'}>
                      Principal's comment:{' '}
                    </Text>
                    <Text>{principalComment}</Text>
                  </HStack>
                  <Divider />
                </>
              )}
            </Div>
            <Spacer height={'10px'} />
            <HStack align={'stretch'} justify={'space-between'}>
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
              <Div textAlign={'center'}>
                <Img
                  src={stamp}
                  alt="School stamp"
                  display={'inline-block'}
                  mt={3}
                />
              </Div>
              <table className="result-analysis-table">
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
                  <tr>
                    <td>70 - 100</td>
                    <td>A</td>
                    <td>{ResultUtil.getRemark('A')}</td>
                  </tr>
                  <tr>
                    <td>60 - 69</td>
                    <td>B</td>
                    <td>{ResultUtil.getRemark('B')}</td>
                  </tr>
                  <tr>
                    <td>50 - 59</td>
                    <td>C</td>
                    <td>{ResultUtil.getRemark('C')}</td>
                  </tr>
                  <tr>
                    <td>45 - 49</td>
                    <td>D</td>
                    <td>{ResultUtil.getRemark('D')}</td>
                  </tr>
                  <tr>
                    <td>40 - 44</td>
                    <td>E</td>
                    <td>{ResultUtil.getRemark('E')}</td>
                  </tr>
                  <tr>
                    <td>0 - 39</td>
                    <td>F</td>
                    <td>{ResultUtil.getRemark('F')}</td>
                  </tr>
                </tbody>
              </table>
            </HStack>
          </VStack>
        </Div>
      </Div>
    </ResultSheetLayout>
  );
}
