import {
  AcademicSession,
  Classification,
  Course,
  SessionResult,
  Student,
} from '@/types/models';
import React from 'react';
import { Div } from '@/components/semantic';
import DataTable, { TableHeader } from '@/components/data-table';
import { ucFirst } from '@/util/util';
import { Avatar, HStack, Text, VStack } from '@chakra-ui/react';
import useSharedProps from '@/hooks/use-shared-props';
import ImagePaths from '@/util/images';
import '@/../../public/style/result-sheet.css';
import ResultSheetLayout from '../result-sheets/result-sheet-layout';

interface CourseSessionResultData {
  [course_id: number]: {
    first_term: number;
    second_term: number;
    third_term: number;
    total: number;
    student: Student;
    average: number;
  };
  session_result: SessionResult;
  session_result_data: {
    total: number;
    average: number;
    position: number | null;
  };
}

interface Props {
  courseSessionResults: {
    [student_id: number]: CourseSessionResultData;
  };
  classification: Classification;
  academicSession: AcademicSession;
  courses: Course[];
}

export default function CourseSessionResult({
  courseSessionResults,
  classification,
  academicSession,
  courses,
}: Props) {
  const { currentInstitution } = useSharedProps();
  const headers: TableHeader<CourseSessionResultData>[] = [
    {
      label: 'Name',
      render: (row) => ucFirst(Object.values(row)[0].student.user?.full_name),
    },
    ...courses.map((course) => ({
      label: course.code,
      render: (row: CourseSessionResultData) => {
        const courseSessionResult = row[course.id] ?? null;
        return String(courseSessionResult?.average ?? '');
      },
    })),
    {
      label: 'Total',
      value: 'session_result_data.total',
    },
    {
      label: 'Average',
      value: 'session_result_data.average',
    },
    // {
    //   label: 'Grade',
    //   value: 'session_result.grade',
    // },
    {
      label: 'Position',
      value: 'session_result_data.position',
    },
  ];

  return (
    <ResultSheetLayout useBgStyle={true}>
      <Div
        mx={'auto'}
        maxWidth={'1200px'}
        px={3}
        position={'relative'}
        id={'result-sheet'}
      >
        <style>
          {`
            /* Only affect th text */
            // .result-table th > * {
            //   height: 170px;                     
            // }
            .result-table th > * {
              text-align: center;
              font-weight: bold;
            }
            .result-table th:not(:first-child) > * {
              height: 170px;                     
              display: inline-block;            /* needed for width + overflow */
              max-width: 12ch;                  /* limit-ish to 10 characters */
              white-space: nowrap;              /* prevent wrapping */
              overflow: hidden;
              text-overflow: ellipsis;          /* show "…" when truncated */

              writing-mode: vertical-rl;        /* make text vertical */
              transform: rotate(180deg);        /* flip so it reads top-to-bottom */
              /* optionally center nicely */
              padding: 4px 2px;
              line-height: 1;
            }
        `}
        </style>
        <Div className="result-sheet-header">
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
                {classification.title} Annual Result Summary for{' '}
                {academicSession.title}
              </Text>
            </VStack>
          </HStack>
        </Div>
        <Div>
          <DataTable
            scroll={true}
            headers={headers}
            data={Object.values(courseSessionResults)}
            keyExtractor={(row) => row.session_result.id}
            hideSearchField={true}
            tableProps={{ className: 'result-table' }}
          />
        </Div>
        <br />
      </Div>
      <br />
      <br />
    </ResultSheetLayout>
  );
}
