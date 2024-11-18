import {
  CourseResult,
  TermResult,
  SessionResult,
  CourseResultInfo,
  Course,
} from '@/types/models';
import React, { useMemo } from 'react';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { TermType } from '@/types/types';
import ResultUtil from '@/util/result-util';
import { Div } from '@/components/semantic';
import { Avatar, Flex, HStack, Text, VStack } from '@chakra-ui/react';
import useSharedProps from '@/hooks/use-shared-props';
import ImagePaths from '@/util/images';
import { LabelText } from '@/components/result-helper-components';
import '@/../../public/style/result-sheet.css';
import '@/../../public/style/result/session-result.css'; 

interface Props {
  sessionResult: SessionResult;
  termResultDetails: {
    [term: string]: {
      termResult: TermResult;
      courseResults: { [courseId: number]: CourseResult };
      courseResultInfo: { [courseId: number]: CourseResultInfo };
    };
  };
}

export default function SessionResultTemplate1({
  sessionResult,
  termResultDetails,
}: Props) {
  const { currentInstitution } = useSharedProps();
  const { instRoute } = useInstitutionRoute();

  type TermRow = {
    courseResult: CourseResult;
    termResult?: TermResult;
    courseResultInfo: CourseResultInfo;
  };

  type Row = {
    [courseId: number]: {
      termCourseResult: { [term: string]: TermRow };
      course: Course;
    };
  };

  const rows = useMemo(function () {
    const rows = {} as Row;

    Object.entries(TermType).map(([key, term]) => {
      const termDetail = termResultDetails[term];
      if (!termDetail) {
        return;
      }
      Object.entries(termDetail.courseResults).map(
        ([courseId, courseResult]) => {
          const courseIdInt = parseInt(courseId);
          const row = rows[courseIdInt] ?? {
            course: courseResult.course!,
            termCourseResult: {},
          };
          row.termCourseResult[term] = {
            courseResult: courseResult,
            courseResultInfo: termDetail.courseResultInfo[courseIdInt],
          };
          rows[courseIdInt] = row;
        }
      );
    });
    return rows;
  }, []);

  function getSessionTotal(courseDetail: { [term: string]: TermRow }) {
    let count = 0;
    let total = 0;
    Object.entries(courseDetail).map(([term, termRowObj]) => {
      total += parseFloat(termRowObj.courseResult.result + '');
      count += 1;
    });
    if (count <= 0) {
      return [0, 0];
    }
    const average = Math.round((total / count) * 100) / 100;
    return [total, average];
  }

  const student = sessionResult.student!;
  const classification = sessionResult.classification!;
  const academicSession = sessionResult.academic_session!;
  const resultSummary1 = [
    { label: 'Name', value: student.user?.full_name },
    { label: 'Student Id', value: student.code },
  ];
  const resultSummary2 = [
    { label: 'Class', value: classification.title },
    {
      label: 'Session',
      value: `${academicSession.title}`,
    },
  ];

  return (
    <Div minHeight={'1170px'}>
      <Div mx={'auto'} width={'900px'} px={3}>
        <VStack align={'stretch'}>
          {/* <HStack background={'#FCFCFC'} p={2}>
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
          </HStack> */}
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
                Annual Result for {academicSession?.title} Academic Session
              </Text>
            </VStack>
            <Avatar
              size={'2xl'}
              name="Student logo"
              src={student.user?.photo_url}
              visibility={'hidden'}
            />
          </HStack>
          <Flex
            flexDirection={'row'}
            justifyContent={'space-between'}
            fontSize={'lg'}
          >
            <VStack spacing={2} align={'left'}>
              {resultSummary1.map((item) => (
                <LabelText
                  label={item.label}
                  text={item.value}
                  key={'summary1' + item.label}
                />
              ))}
            </VStack>
            <VStack spacing={2} align={'left'}>
              {resultSummary2.map((item) => (
                <LabelText
                  label={item.label}
                  text={item.value}
                  key={'summary2' + item.label}
                />
              ))}
            </VStack>
          </Flex>
        </VStack>
        <table className="result-table">
          <thead>
            <tr>
              <th>Subject</th>
              {/* <th>CA</th>
              <th>Exam</th> */}
              <th>First Term (100%)</th>
              {/* <th>Average</th> */}
              {/* <th>CA</th>
              <th>Exam</th> */}
              <th>Second Term (100%)</th>
              {/* <th>Average</th>
              <th>CA</th>
              <th>Exam</th> */}
              <th>Third Term (100%)</th>
              <th>Total (300%)</th>
              <th>Average</th>
              <th>Grade</th>
            </tr>
          </thead>
          <tbody>
            {Object.entries(rows).map(([courseId, termRow]) => {
              const [sessionTotal, sessionTotalAverage] = getSessionTotal(
                termRow.termCourseResult
              );
              return (
                <tr key={'row' + courseId}>
                  <td>{termRow.course?.title}</td>
                  {termDataCells(termRow.termCourseResult[TermType.First])}
                  {termDataCells(termRow.termCourseResult[TermType.Second])}
                  {termDataCells(termRow.termCourseResult[TermType.Third])}
                  <td>{sessionTotal}</td>
                  <td>{sessionTotalAverage}</td>
                  <td>{ResultUtil.getGrade(sessionTotalAverage).grade}</td>
                </tr>
              );
            })}
          </tbody>
        </table>
      </Div>
    </Div>
  );

  function termDataCells(termRowObj: TermRow) {
    return (
      <>
        {/* <td>
          {termRowObj
            ? ResultUtil.getAssessmentScore(termRowObj?.courseResult)
            : ''}
        </td>
        <td>{termRowObj?.courseResult.exam}</td> */}
        <td>{termRowObj?.courseResult.result}</td>
        {/* <td>{termRowObj?.courseResultInfo.average}</td> */}
      </>
    );
  }
}
