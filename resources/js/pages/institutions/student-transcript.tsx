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
import TranscriptUtil, { TranscriptSession } from '@/util/TranscriptUtil';
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
          <Grid templateColumns={'repeat(2, 1fr)'} gap={3} mt={4}>
            {/* {Object.entries(transcriptUtil.getTranscript()).map( */}
            {transcriptUtil
              .getSortedTranscriptArr()
              .map((transcriptSession) => {
                return (
                  <GridItem
                    key={`session${transcriptSession.sessionResult.id}`}
                  >
                    <DisplaySessionResult
                      transcriptSession={transcriptSession}
                      transcriptUtil={transcriptUtil}
                    />
                  </GridItem>
                );
              })}
          </Grid>
        </VStack>
      </Div>
    </Div>
  );
}

function DisplaySessionResult({
  transcriptSession,
  transcriptUtil,
}: {
  transcriptSession: TranscriptSession;
  transcriptUtil: TranscriptUtil;
}) {
  const { sessionResult, termResultDetail } = transcriptSession;
  // console.log('termResultDetail', termResultDetail);

  const subjects = transcriptUtil.getSessionSubjects(
    sessionResult.academic_session_id
  );
  if (!subjects || Object.keys(subjects).length < 1) {
    return null;
  }
  if (!sessionResult || Object.keys(sessionResult).length < 1) {
    return null;
  }

  return (
    <Div>
      <Text
        fontWeight={'semibold'}
        textAlign={'center'}
      >{`${sessionResult.classification?.title} - ${sessionResult.academic_session?.title} Session`}</Text>
      <table className="result-table">
        <thead>
          <tr>
            <th></th>
            {Object.entries(termResultDetail).map(([term]) => (
              <th colSpan={2} key={term}>
                <Text fontSize={'small'}>{startCase(term)} Term</Text>
              </th>
            ))}
          </tr>
          <tr>
            <th>Subjects</th>
            {Object.entries(termResultDetail).map(([term, transcriptTerm]) => (
              <React.Fragment key={`result${term}`}>
                <th>Result</th>
                <th>Grade</th>
              </React.Fragment>
            ))}
          </tr>
        </thead>
        <tbody>
          {Object.entries(subjects).map(([courseId, course]) => {
            return (
              <tr key={course.title}>
                <td>
                  <Text>{course.title}</Text>
                </td>
                {Object.entries(termResultDetail).map(
                  ([term, transcriptTerm]) => {
                    const courseResult =
                      transcriptTerm.courseResults?.[course.id];
                    return (
                      <React.Fragment key={`result${term}`}>
                        <td>{courseResult?.result}</td>
                        <td>{ResultUtil.getGrade(courseResult?.result)[0]}</td>
                      </React.Fragment>
                    );
                  }
                )}
              </tr>
            );
          })}
        </tbody>
      </table>
    </Div>
  );
}
