import {
  Avatar,
  BoxProps,
  Button,
  Divider,
  Flex,
  HStack,
  Icon,
  Img,
  Spacer,
  Text,
  VStack,
  Wrap,
  WrapItem,
} from '@chakra-ui/react';
import React, { PropsWithChildren } from 'react';
import { Div } from '@/components/semantic';
import startCase from 'lodash/startCase';
import useSharedProps from '@/hooks/use-shared-props';
import '@/../../public/style/result-sheet.css';
import '@/style/template-6.css';
import ImagePaths from '@/util/images';
import DisplayTermResultEvaluation from '@/components/display-term-result-evaluation-component';
import ResultUtil, { ResultProps, useResultSetting } from '@/util/result-util';
import DataTable, { TableHeader } from '@/components/data-table';
import { CourseResult } from '@/types/models';
import ResultSheetLayout from './result-sheet-layout';
import DateTimeDisplay from '@/components/date-time-display';
import { dateFormat } from '@/util/util';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import ResultDownloadButton from './result-download-button';
import { EnvelopeIcon, MapIcon, PhoneIcon } from '@heroicons/react/24/solid';
import { LabelText } from '@/components/result-helper-components';

const PDF_URL = import.meta.env.VITE_PDF_URL;
export default function Template6({
  termResult,
  courseResults,
  classResultInfo,
  academicSession,
  student,
  courseResultInfoData,
  assessments,
  learningEvaluations,
  resultCommentTemplate,
  termDetail,
  signed_url,
}: ResultProps) {
  const { currentInstitution, currentUser, stamp } = useSharedProps();
  const { hidePosition, showGrade } = useResultSetting();
  const downloadPdfForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

  const resultSummary1 = [
    { label: 'Student Name', value: student.user?.full_name },
    { label: 'Class', value: termResult.classification?.title },
    { label: 'No in Class', value: classResultInfo.num_of_students },
    // { label: 'Average Score', value: termResult.average },
    ...(hidePosition
      ? []
      : [
          {
            label: 'Position in Class',
            value: showGrade
              ? ResultUtil.getGrade(termResult.average, resultCommentTemplate)
                  .grade
              : ResultUtil.formatPosition(termResult.position),
          },
        ]),
    {
      label: 'Term',
      value: startCase(termResult.term),
    },
    // { label: 'Session', value: academicSession.title },
    // { label: 'Student Id', value: student.code },
    ...(classResultInfo.next_term_resumption_date
      ? [
          {
            label: 'Next Term Begins',
            value: (
              <DateTimeDisplay
                as={'span'}
                dateTime={classResultInfo.next_term_resumption_date}
                dateTimeformat={dateFormat}
              />
            ),
          },
        ]
      : []),
    { label: 'Height', value: termResult.height },
    { label: 'Weight', value: termResult.weight },
    { label: 'Attendance', value: termResult.attendance_count },
    {
      label: 'No of Times School Held',
      value: termDetail?.expected_attendance_count,
    },
    {
      label: 'Opening Date',
      value: (
        <DateTimeDisplay
          as={'span'}
          dateTime={termDetail?.start_date}
          dateTimeformat={dateFormat}
        />
      ),
    },
    {
      label: 'Closing Date',
      value: (
        <DateTimeDisplay
          as={'span'}
          dateTime={termDetail?.end_date}
          dateTimeformat={dateFormat}
        />
      ),
    },
  ];

  function LocalLabelText({
    label,
    text,
  }: {
    label: string;
    text: string | number | undefined | React.ReactNode;
  }) {
    return (
      <Div className="cell" width={'full'}>
        <Text as={'span'} textTransform={'uppercase'} width={'120px'}>
          {label.toUpperCase()}:
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

  const principalComment =
    termResult.principal_comment ??
    ResultUtil.getCommentFromTemplate(termResult.average, resultCommentTemplate)
      ?.comment;
  const teacherComment =
    termResult.teacher_comment ??
    ResultUtil.getCommentFromTemplate(termResult.average, resultCommentTemplate)
      ?.comment_2;

  const resultTableHeaders: TableHeader<CourseResult>[] = [
    {
      label: 'Subject',
      render: (row) => <Div className="cell">{row.course?.title}</Div>,
    },
    ...assessments.map((assessment) => ({
      label: assessment.title,
      render: (courseResult: CourseResult) => (
        <Div className="cell">
          {String(courseResult.assessment_values[assessment.raw_title] ?? '')}
        </Div>
      ),
    })),
    {
      label: 'Exam',
      value: 'exam',
      render: (row) => <Div className="cell">{row.exam}</Div>,
    },
    {
      label: 'Total',
      value: 'result',
      render: (row) => <Div className="cell">{row.result}</Div>,
    },
    {
      label: 'Grade',
      render: (courseResult) => (
        <Div className="cell">
          {String(
            ResultUtil.getGrade(courseResult.result, resultCommentTemplate)
              .grade
          )}
        </Div>
      ),
    },
    ...(hidePosition
      ? []
      : [
          {
            label: 'Position',
            render: (courseResult: CourseResult) => (
              <Div className="cell">
                {ResultUtil.formatPosition(courseResult.position)}
              </Div>
            ),
          },
        ]),
    {
      label: 'Average',
      render: (courseResult) => (
        <Div className="cell">
          {String(courseResultInfoData[courseResult.course_id]?.average)}
        </Div>
      ),
    },
    // {
    //   label: 'Highest',
    //   render: (courseResult) => (
    //     <Div className="cell">
    //       {String(courseResultInfoData[courseResult.course_id]?.max_score)}
    //     </Div>
    //   ),
    // },
    // {
    //   label: 'Lowest',
    //   render: (courseResult) => (
    //     <Div className="cell">
    //       {String(courseResultInfoData[courseResult.course_id]?.min_score)}
    //     </Div>
    //   ),
    // },
    {
      label: 'Remark',
      render: (courseResult) => (
        <Div className="cell">
          {String(
            ResultUtil.getGrade(courseResult.result, resultCommentTemplate)
              .remark
          )}
        </Div>
      ),
    },
  ];

  function Header() {
    return (
      <Div className="result-sheet-header">
        <HStack
          p={2}
          align={'stretch'}
          width={'100%'}
          justifyContent={'space-between'}
        >
          <Avatar
            size={'2xl'}
            name="Institution logo"
            src={currentInstitution.photo ?? ImagePaths.default_school_logo}
          />
          <VStack
            width={'full'}
            align={'stretch'}
            textAlign={'center'}
            spacing={1}
          >
            <Text
              as={'p'}
              whiteSpace={'nowrap'}
              fontWeight={'bold'}
              fontSize={'18px'}
            >
              {currentInstitution.name?.toUpperCase()}
            </Text>
            <Text as={'p'} whiteSpace={'nowrap'}>
              <Icon as={MapIcon} /> {currentInstitution.address}
            </Text>
            <Text as={'p'} whiteSpace={'nowrap'}>
              <Icon as={PhoneIcon} /> {currentInstitution.phone} &nbsp;{' '}
              <Icon as={EnvelopeIcon} /> {currentInstitution.email}
            </Text>
            <Text
              textTransform={'uppercase'}
              textAlign={'center'}
              fontSize={'14px'}
              fontWeight={'bold'}
              as={'p'}
            >
              Student {termResult.term} Term Report Sheet |{' '}
              {academicSession.title} Session
            </Text>
          </VStack>
          <Avatar size={'2xl'} name="Student Logo" src={currentUser.photo} />
        </HStack>
        {/* <Divider height={2} backgroundColor={'#550d98'} opacity={1} /> */}
        <Div>
          <Wrap spacing={1} align={'stretch'} fontSize={'16px'}>
            {resultSummary1.map((item) => (
              <WrapItem flex={1} key={'summary1' + item.label}>
                <LocalLabelText label={item.label} text={item.value} />
              </WrapItem>
            ))}
          </Wrap>
        </Div>
      </Div>
    );
  }

  function A4Page({ children, ...props }: PropsWithChildren & BoxProps) {
    return (
      <Div className="a4-page" {...props}>
        <Header />
        <Div>{children}</Div>
      </Div>
    );
  }

  return (
    <ResultSheetLayout>
      <Div style={backgroundStyle} minHeight={'1170px'}>
        <ResultDownloadButton
          signed_url={signed_url}
          student={student}
          termResult={termResult}
        />
        <Div
          mx={'auto'}
          width={'900px'}
          px={3}
          position={'relative'}
          id={'result-sheet'}
        >
          <Div>
            <Header />
            <Div className="table-container">
              <DataTable
                scroll={true}
                headers={resultTableHeaders}
                data={courseResults}
                keyExtractor={(row) => row.id}
                hideSearchField={true}
                tableProps={{ className: 'result-table' }}
              />
            </Div>
            <Div className="cell" my={2}>
              <HStack>
                <LabelText
                  label={'Total'}
                  text={`${termResult.total_score} out of ${classResultInfo.max_obtainable_score}`}
                />
                <Spacer />
                <LabelText
                  label={'Percentage Average'}
                  text={`${termResult.average}%`}
                />
              </HStack>
              <HStack mt={0}>
                <LabelText
                  label={'Overall Grade'}
                  text={String(
                    ResultUtil.getGrade(
                      termResult.average,
                      resultCommentTemplate
                    ).grade
                  )}
                />
                <Spacer />
                <LabelText
                  label={'Overall Grade'}
                  text={`${classResultInfo.average}%`}
                />
              </HStack>
            </Div>
            <DisplayTermResultEvaluation
              termResult={termResult}
              learningEvaluations={learningEvaluations}
            />
            <br />
            <div
              style={{
                minWidth: '240px',
                display: 'flex',
                flexDirection: 'row',
                justifyContent: 'space-between',
              }}
            >
              {resultCommentTemplate && (
                <table
                  className="keys-table"
                  style={{ textAlign: 'center', minWidth: '300px' }}
                >
                  <thead>
                    <tr>
                      <th>
                        <div>Range (%)</div>
                      </th>
                      <th>
                        <div>Remark</div>
                      </th>
                      <th>
                        <div>Letter Grade</div>
                      </th>
                      {/* <th>Point Grade</th> */}
                    </tr>
                  </thead>
                  <tbody>
                    {/* {[90, 89, 69, 59, 49, 39].map((item) => { */}
                    {resultCommentTemplate.map((item) => {
                      const { grade, grade_label } = item;
                      // ResultUtil.getGrade(item, resultCommentTemplate);
                      return (
                        <tr key={grade}>
                          <td>
                            <div>{`${item.min} - ${item.max}`}</div>
                          </td>
                          <td>
                            <div>{grade_label}</div>
                          </td>
                          <td>
                            <div>{grade}</div>
                          </td>
                          {/* <td>{pointsGrade}</td> */}
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
              )}
              <Div ml={3} width={'full'}>
                {teacherComment && (
                  <>
                    <HStack align={'stretch'} width={'full'}>
                      <Text
                        fontWeight={'semibold'}
                        size={'xs'}
                        whiteSpace={'nowrap'}
                      >
                        Teacher's comment:{' '}
                      </Text>
                      <Text>{teacherComment}</Text>
                    </HStack>
                    <Divider />
                  </>
                )}
                {principalComment && (
                  <>
                    <HStack align={'stretch'} width={'full'}>
                      <Text
                        fontWeight={'semibold'}
                        size={'xs'}
                        whiteSpace={'nowrap'}
                      >
                        Head Teacher's comment:{' '}
                      </Text>
                      <Text>{principalComment}</Text>
                    </HStack>
                    <Divider />
                  </>
                )}
                {stamp && (
                  <Div textAlign={'end'}>
                    <Img
                      src={stamp}
                      alt="School stamp"
                      display={'inline-block'}
                    />
                  </Div>
                )}
              </Div>
            </div>
          </Div>
        </Div>
      </Div>
    </ResultSheetLayout>
  );
}
