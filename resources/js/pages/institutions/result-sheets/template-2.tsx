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
import ImagePaths from '@/util/images';
import ResultUtil, { ResultProps, useResultSetting } from '@/util/result-util';
import { GradingTable } from '@/components/result-helper-components';
import ResultSheetLayout from './result-sheet-layout';

export default function Template2(props: ResultProps) {
  const {
    termResult,
    courseResults,
    classResultInfo,
    academicSession,
    classification,
    student,
    resultCommentTemplate,
  } = props;
  const { currentInstitution } = useSharedProps();
  const { hidePosition, showGrade } = useResultSetting();

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

  const principalComment =
    termResult.principal_comment ??
    ResultUtil.getCommentFromTemplate(termResult.average, resultCommentTemplate)
      ?.comment;
  const teacherComment =
    termResult.teacher_comment ??
    ResultUtil.getCommentFromTemplate(termResult.average, resultCommentTemplate)
      ?.comment_2;

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
      ([, val]) => (total += Number(val))
    );
    return total;
  }

  return (
    <ResultSheetLayout resultProps={props}>
      <Div mx={'auto'} width={'900px'} px={3}>
        <VStack align={'stretch'}>
          <HStack background={'#FAFAFA'} p={2}>
            <Avatar
              size={'2xl'}
              name="Institution logo"
              src={currentInstitution.photo ?? ImagePaths.default_school_logo}
            />
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
                  {currentInstitution.phone} | {currentInstitution.email}
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
            <Avatar size="xl" name="Student" src={student.user?.photo ?? ''} />
          </HStack>
          <Div>
            <Flex flexDirection={'row'} justifyContent={'space-between'}>
              <LabelText label="Student Name" text={student?.user?.full_name} />
              <LabelText label="Class" text={classification.title} />
            </Flex>
            <Flex mt={1} flexDirection={'row'} justifyContent={'space-between'}>
              <LabelText
                label="No of Class"
                text={classResultInfo.num_of_students}
              />
              <LabelText label="Gender" text={student.user?.gender} />
              {/* <LabelText
              label="Term"
              text={`${startCase(termResult.term)} ${
                termResult.for_mid_term ? 'Mid Term' : ''
              }`}
            />
            <LabelText label="Session" text={academicSession.title} /> */}
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
                    <th>{hidePosition ? 'Remark' : 'Subject Position'}</th>
                  </tr>
                </thead>
                <tbody>
                  {courseResults.map((courseResult) => {
                    const { grade, remark } = ResultUtil.getGrade(
                      courseResult.result,
                      resultCommentTemplate
                    );
                    return (
                      <tr key={'results-' + courseResult.id}>
                        <td>{courseResult.course?.title}</td>
                        <td>{getAssessmentScore(courseResult)}</td>
                        <td>{courseResult.exam}</td>
                        <td>{courseResult.result}</td>
                        <td>{grade}</td>
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
          {hidePosition ? (
            <></>
          ) : (
            <LabelText label="Position" text={termResult.position} />
          )}
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
                  <tr key={'result analysis' + label}>
                    <td style={{ width: '250px' }}>{label}</td>
                    <td>{value}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
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
                  Principal's comment:{' '}
                </Text>
                <Text>{principalComment}</Text>
              </HStack>
              <Divider />
            </>
          )}
        </VStack>
      </Div>
    </ResultSheetLayout>
  );
}
