import {
  Avatar,
  BoxProps,
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
import ResultSheetLayout, {
  ClosingDate,
  NextTermDate,
} from './result-sheet-layout';
import { formatAsDate } from '@/util/util';

const PDF_URL = import.meta.env.VITE_PDF_URL;
export default function Template5(props: ResultProps) {
  const {
    termResult,
    courseResults,
    classResultInfo,
    academicSession,
    classification,
    student,
    assessments,
    resultCommentTemplate,
    courseResultInfoData,
    signed_url,
    learningEvaluations,
    termDetail,
  } = props;
  const { currentInstitution, stamp } = useSharedProps();
  const { hidePosition, showGrade } = useResultSetting();
  const nextTermResumptionDate =
    classResultInfo.next_term_resumption_date ??
    termDetail?.next_term_resumption_date;

  const resultSummary1 = [
    { label: 'Name', value: student.user?.full_name },
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
    ...(termDetail?.end_date
      ? [{ label: 'Closing Date', value: formatAsDate(termDetail.end_date) }]
      : []),
  ];
  const resultSummary2 = [
    {
      label: 'Term',
      value: startCase(termResult.term),
    },
    { label: 'Session', value: academicSession.title },
    { label: 'Portal Id', value: student.code },
    ...(nextTermResumptionDate
      ? [
          {
            label: 'Next Term Begins',
            value: formatAsDate(nextTermResumptionDate),
          },
        ]
      : []),
  ];

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
  const teacherComment =
    termResult.teacher_comment ??
    ResultUtil.getCommentFromTemplate(termResult.average, resultCommentTemplate)
      ?.comment_2;

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
    ...(hidePosition
      ? []
      : [
          {
            label: 'Position',
            render: (courseResult: CourseResult) =>
              ResultUtil.formatPosition(courseResult.position),
          },
        ]),
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
          Performance Report Sheet
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
    <ResultSheetLayout resultProps={props}>
      <Div
        mx={'auto'}
        width={'900px'}
        px={3}
        position={'relative'}
        id={'result-sheet'}
      >
        <Div>
          {/* <A4Page> */}
          <Header />
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
          {/* </A4Page>
            <A4Page> */}
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
                    <th>Range (%)</th>
                    <th>Remark</th>
                    <th>Letter Grade</th>
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
                        <td>{`${item.min} - ${item.max}`}</td>
                        <td>{grade_label}</td>
                        <td>{grade}</td>
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
                      Administrator's comment:{' '}
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
          {/* </A4Page> */}
        </Div>
      </Div>
    </ResultSheetLayout>
  );
}
