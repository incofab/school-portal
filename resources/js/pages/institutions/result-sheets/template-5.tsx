import {
  Avatar,
  BoxProps,
  Button,
  Divider,
  Flex,
  HStack,
  Img,
  Spacer,
  Text,
  VStack,
} from '@chakra-ui/react';
import React, { PropsWithChildren } from 'react';
import { Div } from '@/components/semantic';
import startCase from 'lodash/startCase';
import useSharedProps from '@/hooks/use-shared-props';
import '@/../../public/style/result-sheet.css';
import '@/style/template-5.css';
import ImagePaths from '@/util/images';
import DisplayTermResultEvaluation from '@/components/display-term-result-evaluation-component';
import ResultUtil, { ResultProps, useResultSetting } from '@/util/result-util';
import DataTable, { TableHeader } from '@/components/data-table';
import { CourseResult } from '@/types/models';
import ResultSheetLayout from './result-sheet-layout';
import DateTimeDisplay from '@/components/date-time-display';
import { dateFormat } from '@/util/util';

export default function Template5({
  termResult,
  courseResults,
  classResultInfo,
  academicSession,
  student,
  courseResultInfoData,
  assessments,
  learningEvaluations,
  resultCommentTemplate,
}: ResultProps) {
  const { currentInstitution, stamp } = useSharedProps();
  const { hidePosition, showGrade } = useResultSetting();

  const resultSummary1 = [
    { label: 'Student Name', value: student.user?.full_name },
    { label: 'Class', value: termResult.classification?.title },
    { label: 'No in Class', value: classResultInfo.num_of_students },
    { label: 'Average Score', value: termResult.average },
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
  ];
  const resultSummary2 = [
    {
      label: 'Term',
      value: startCase(termResult.term),
    },
    { label: 'Session', value: academicSession.title },
    { label: 'Student Id', value: student.code },
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
  ];

  // function getGrade(score: number) {
  //   const comment = ResultUtil.getCommentFromTemplate(
  //     score,
  //     resultCommentTemplate
  //   );
  //   let grade = '';
  //   let pointsGrade = 0;
  //   let remark = '';
  //   let label = '';
  //   if (comment) {
  //     grade = comment.grade;
  //     pointsGrade = 0;
  //     remark = comment.comment;
  //     label = comment.grade_label;
  //   } else {
  //     if (score < 40) {
  //       grade = 'F';
  //       remark = 'Progressing';
  //       label = '1.0% - 39.0%';
  //       pointsGrade = 0;
  //     } else if (score < 50) {
  //       grade = 'E';
  //       remark = 'Fair';
  //       label = '40.0% - 49.0%';
  //       pointsGrade = 2;
  //     } else if (score < 60) {
  //       grade = 'D';
  //       remark = 'Pass';
  //       label = '50.0% - 59.0%';
  //       pointsGrade = 3;
  //     } else if (score < 70) {
  //       grade = 'C';
  //       remark = 'Good';
  //       label = '60.0% - 69.0%';
  //       pointsGrade = 4;
  //     } else if (score < 90) {
  //       grade = 'B';
  //       remark = 'Very Good';
  //       label = '70.0% - 89.0%';
  //       pointsGrade = 4;
  //     } else {
  //       grade = 'A';
  //       remark = 'Excellent';
  //       label = '90.0% - Above';
  //       pointsGrade = 5;
  //     }
  //   }
  //   return [grade, remark, label, pointsGrade];
  // }

  function LabelText({
    label,
    text,
  }: {
    label: string;
    text: string | number | undefined | React.ReactNode;
  }) {
    return (
      <Div>
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

  const resultTableHeaders: TableHeader<CourseResult>[] = [
    {
      label: 'Subject',
      value: 'course.title',
    },
    ...assessments.map((assessment) => ({
      label: assessment.title,
      render: (courseResult: CourseResult) =>
        String(courseResult.assessment_values[assessment.raw_title] ?? ''),
    })),
    {
      label: 'Exam',
      value: 'exam',
    },
    {
      label: 'Total',
      value: 'result',
    },
    {
      label: 'Grade',
      render: (courseResult) =>
        String(
          ResultUtil.getGrade(courseResult.result, resultCommentTemplate).grade
        ),
    },
    {
      label: 'Position',
      render: (courseResult) =>
        ResultUtil.formatPosition(courseResult.position),
    },
    {
      label: 'Average',
      render: (courseResult) =>
        String(courseResultInfoData[courseResult.course_id]?.average),
    },
    {
      label: 'Highest',
      render: (courseResult) =>
        String(courseResultInfoData[courseResult.course_id]?.max_score),
    },
    {
      label: 'Lowest',
      render: (courseResult) =>
        String(courseResultInfoData[courseResult.course_id]?.min_score),
    },
    {
      label: 'Remark',
      render: (courseResult) =>
        String(
          ResultUtil.getGrade(courseResult.result, resultCommentTemplate).remark
        ),
    },
  ];
  function Header() {
    return (
      <Div className="result-sheet-header">
        <Text
          fontWeight={'bold'}
          fontSize={'2xl'}
          textAlign={'center'}
          color={'#00008b'}
        >
          {currentInstitution.name}
        </Text>
        <Text fontWeight={'normal'} textAlign={'center'}>
          {currentInstitution.subtitle}
        </Text>
        <HStack
          p={2}
          align={'stretch'}
          width={'100%'}
          justifyContent={'space-between'}
        >
          <Div mt={5} width={'full'}>
            <Text as={'span'} whiteSpace={'nowrap'}>
              Postal Address:
            </Text>
            <Text as={'span'}>{currentInstitution.address}</Text>
          </Div>
          <Avatar
            size={'2xl'}
            name="Institution logo"
            src={currentInstitution.photo ?? ImagePaths.default_school_logo}
          />
          <Div width={'full'}>
            <Spacer height={5} />
            <VStack
              width={'full'}
              align={'stretch'}
              textAlign={'right'}
              spacing={0}
            >
              <Text whiteSpace={'nowrap'}>
                Email: {currentInstitution.email}
              </Text>
              <Text whiteSpace={'nowrap'}>
                Website: {currentInstitution.website}
              </Text>
              <Text whiteSpace={'nowrap'}>
                Phone: {currentInstitution.phone}
              </Text>
            </VStack>
          </Div>
        </HStack>
        <Divider height={2} backgroundColor={'#550d98'} opacity={1} />
        <Flex
          flexDirection={'row'}
          justifyContent={'space-between'}
          fontSize={'lg'}
        >
          <VStack spacing={1} align={'left'} fontSize={'16px'}>
            {resultSummary1.map((item) => (
              <LabelText
                label={item.label}
                text={item.value}
                key={'summary1' + item.label}
              />
            ))}
          </VStack>
          <VStack spacing={1} align={'left'} fontSize={'16px'}>
            {resultSummary2.map((item) => (
              <LabelText
                label={item.label}
                text={item.value}
                key={'summary2' + item.label}
              />
            ))}
          </VStack>
        </Flex>
        <Text
          textTransform={'uppercase'}
          textAlign={'center'}
          fontSize={'18px'}
        >
          Student Report Sheet
        </Text>
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
        <Button
          id={'download-btn'}
          onClick={() =>
            ResultUtil.exportAsPdf(
              'result-sheet',
              student.user?.full_name + 'result-sheet'
            )
          }
          size={'sm'}
          variant={'outline'}
          colorScheme="brand"
        >
          Download
        </Button>
        <Div
          mx={'auto'}
          width={'900px'}
          px={3}
          position={'relative'}
          id={'result-sheet'}
        >
          <Div position={'absolute'} bottom={'130px'} right={0} opacity={0.65}>
            <Img src={stamp} />
          </Div>
          <Div>
            <A4Page>
              <div className="table-container">
                <DataTable
                  scroll={true}
                  headers={resultTableHeaders}
                  data={courseResults}
                  keyExtractor={(row) => row.id}
                  hideSearchField={true}
                  tableProps={{ className: 'result-table' }}
                />
                <br />
              </div>
            </A4Page>
            <A4Page>
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
                <table className="keys-table" style={{ textAlign: 'center' }}>
                  <thead>
                    <tr>
                      <th>Percentage Range</th>
                      <th>Remark</th>
                      <th>Letter Grade</th>
                      <th>Point Grade</th>
                    </tr>
                  </thead>
                  <tbody>
                    {[90, 89, 69, 59, 49, 39].map((item) => {
                      const { grade, remark, range, pointsGrade } =
                        ResultUtil.getGrade(item, resultCommentTemplate);
                      return (
                        <tr key={item}>
                          <td>{range}</td>
                          <td>{grade}</td>
                          <td>{remark}</td>
                          <td>{pointsGrade}</td>
                        </tr>
                      );
                    })}
                  </tbody>
                </table>
                <Div ml={3} width={'full'}>
                  {termResult.teacher_comment && (
                    <>
                      <HStack align={'stretch'} width={'full'}>
                        <Text
                          fontWeight={'semibold'}
                          size={'xs'}
                          whiteSpace={'nowrap'}
                        >
                          Teacher's comment:{' '}
                        </Text>
                        <Text>{termResult.teacher_comment}</Text>
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
                </Div>
              </div>
            </A4Page>
          </Div>
        </Div>
      </Div>
    </ResultSheetLayout>
  );
}
