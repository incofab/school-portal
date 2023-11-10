import {
  CourseResult,
  TermResult,
  SessionResult,
  Student,
} from '@/types/models';
import React, { useMemo } from 'react';
import ResultUtil from '@/util/result-util';
import { Div } from '@/components/semantic';
import {
  Avatar,
  Grid,
  GridItem,
  HStack,
  Spacer,
  Text,
  VStack,
} from '@chakra-ui/react';
import useSharedProps from '@/hooks/use-shared-props';
import ImagePaths from '@/util/images';
import { LabelText } from '@/components/result-helper-components';
import '@/../../public/style/result-sheet.css';
import '@/../../public/style/result/session-result.css';
import TranscriptUtil, { TranscriptTerm } from '@/util/TranscriptUtil';
import startCase from 'lodash/startCase';

interface Props {
  student: Student;
  courseResults: CourseResult[];
  termResults: TermResult[];
  sessionResults: SessionResult[];
}

export default function StudentTranscript({
  student,
  sessionResults,
  termResults,
  courseResults,
}: Props) {
  const { currentInstitution } = useSharedProps();

  const transcriptUtil = useMemo(function () {
    return new TranscriptUtil(
      student,
      courseResults,
      termResults,
      sessionResults
    );
  }, []);

  const resultSummary1 = [{ label: 'Name', value: student.user?.full_name }];
  const resultSummary2 = [{ label: 'Student Id', value: student.code }];

  return (
    <Div minHeight={'1170px'}>
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
                fontSize={'22px'}
              >
                Student Transcript
              </Text>
            </VStack>
            <Avatar
              size={'2xl'}
              name="Student logo"
              src={student.user?.photo_url}
              visibility={'hidden'}
            />
          </HStack>
          <HStack align={'stretch'}>
            <VStack spacing={2} align={'left'}>
              {resultSummary1.map((item) => (
                <LabelText
                  label={item.label}
                  text={item.value}
                  key={'summary1' + item.label}
                />
              ))}
            </VStack>
            <Spacer />
            <VStack>
              {resultSummary2.map((item) => (
                <LabelText
                  label={item.label}
                  text={item.value}
                  key={'summary2' + item.label}
                />
              ))}
            </VStack>
          </HStack>
        </VStack>
        <VStack align={'stretch'} spacing={4}>
          {Object.entries(transcriptUtil.getTranscript()).map(
            ([sessionId, transcriptSession]) => {
              const sessionResult = transcriptSession.sessionResult;
              return (
                <Div mt={4}>
                  <Text
                    textAlign={'center'}
                    fontSize={'3xl'}
                    fontWeight={'semibold'}
                  >{`${sessionResult.academic_session?.title} Session`}</Text>
                  <Grid
                    templateColumns={'repeat(2, 1fr)'}
                    key={`session${sessionId}`}
                    gap={2}
                  >
                    {Object.entries(transcriptSession.termResultDetail).map(
                      ([term, transcriptTerm]) => (
                        <GridItem>
                          <DisplayTermResult transcriptTerm={transcriptTerm} />
                        </GridItem>
                      )
                    )}
                    <GridItem>
                      <DisplaySessionResult sessionResult={sessionResult} />
                    </GridItem>
                  </Grid>
                </Div>
              );
            }
          )}
        </VStack>
      </Div>
    </Div>
  );
}

function DisplayTermResult({
  transcriptTerm,
}: {
  transcriptTerm: TranscriptTerm;
}) {
  const { courseResults, termResult } = transcriptTerm;

  const summary = [
    { label: 'Total Score', value: termResult.total_score },
    { label: 'Average', value: termResult.average },
    { label: 'Position', value: termResult.position },
    { label: 'Class', value: termResult.classification?.title },
  ];

  return (
    <Div>
      <table className="result-table">
        <thead>
          <tr>
            <td colSpan={10} style={{ fontWeight: 'bold' }}>
              <Text textAlign={'center'}>{`${
                termResult.academic_session?.title
              } - ${startCase(termResult.term)} Term Result`}</Text>
            </td>
          </tr>
          <tr>
            <th>Subject</th>
            <th>Assessment</th>
            <th>Exam</th>
            <th>Result</th>
            <th>Grade</th>
          </tr>
        </thead>
        <tbody>
          {Object.entries(courseResults).map(([courseId, courseResult]) => {
            return (
              <tr key={courseId}>
                <td>
                  <Text fontWeight={'semibold'}>
                    {courseResult.course?.title}
                  </Text>
                </td>
                <td style={{ maxWidth: '110px' }}>
                  {Object.entries(courseResult.assessment_values).map(
                    ([title, value]) => (
                      <Text
                        key={title}
                        whiteSpace={'nowrap'}
                        textAlign={'left'}
                        as={'div'}
                        size={'sm'}
                      >
                        <Text
                          as={'span'}
                          maxW={'70px'}
                          textOverflow={'ellipsis'}
                          size={'sm'}
                        >
                          {startCase(title.split('_')[0])}:
                        </Text>
                        <Text as={'span'} ml={2}>
                          {value?.toFixed(1)}
                        </Text>
                      </Text>
                    )
                  )}
                </td>
                <td>{courseResult.exam}</td>
                <td>{courseResult.result}</td>
                <td>{ResultUtil.getGrade(courseResult.result)[1]}</td>
              </tr>
            );
          })}
          {summary.map(({ label, value }) => (
            <tr key={label}>
              <td colSpan={4}>
                <Text fontWeight={'bold'}>{label}</Text>
              </td>
              <td>{value}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </Div>
  );
}

function DisplaySessionResult({
  sessionResult,
}: {
  sessionResult: SessionResult;
}) {
  if (!sessionResult || Object.keys(sessionResult).length < 1) {
    return null;
  }
  const summary = [
    { label: 'Total Score', value: sessionResult.result },
    { label: 'Average', value: sessionResult.average },
    { label: 'Grade', value: sessionResult.grade },
    { label: 'Remark', value: sessionResult.remark },
    { label: 'Class', value: sessionResult.classification?.title },
  ];

  return (
    <Div>
      <table className="result-table">
        <thead>
          <tr>
            <th
              colSpan={10}
            >{`${sessionResult.academic_session?.title} Session Summary`}</th>
          </tr>
        </thead>
        <tbody>
          {summary.map(({ label, value }) => (
            <tr key={label}>
              <td>
                <Text fontWeight={'semibold'}>{label}</Text>
              </td>
              <td>{value}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </Div>
  );
}
