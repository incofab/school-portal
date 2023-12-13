import {
  Avatar,
  Button,
  Divider,
  Flex,
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
import '@/../../public/style/result/template-4.css';
import ImagePaths from '@/util/images';
import DisplayTermResultEvaluation from '@/components/display-term-result-evaluation-component';
import ResultUtil, { ResultProps, useResultSetting } from '@/util/result-util';
import ResultSheetLayout from './result-sheet-layout';
import DateTimeDisplay from '@/components/date-time-display';
import { dateFormat } from '@/util/util';

export default function Template4({
  termResult,
  courseResults,
  classResultInfo,
  academicSession,
  classification,
  student,
  courseResultInfoData,
  assessments,
  learningEvaluations,
}: ResultProps) {
  const { currentInstitution, stamp } = useSharedProps();
  const { hidePosition, showGrade } = useResultSetting();

  const resultSummary1 = [
    { label: 'Name of Pupil', value: student.user?.full_name },
    { label: 'Student Id', value: student.code },
    ...(hidePosition
      ? []
      : [
          {
            label: 'Position',
            value: showGrade
              ? getGrade(termResult.average)[0]
              : ResultUtil.formatPosition(termResult.position),
          },
        ]),
  ];
  const resultSummary2 = [
    { label: 'Class', value: classification.title },
    {
      label: 'Term',
      value: `${startCase(termResult.term)} Term, ${academicSession.title}`,
    },
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

  function getGrade(score: number) {
    let grade = '';
    let pointsGrade = 0;
    let remark = '';
    let label = '';
    if (score < 40) {
      grade = 'F';
      remark = 'Progressing';
      label = '1.0% - 39.0%';
      pointsGrade = 0;
    } else if (score < 50) {
      grade = 'E';
      remark = 'Fair';
      label = '40.0% - 49.0%';
      pointsGrade = 2;
    } else if (score < 60) {
      grade = 'D';
      remark = 'Pass';
      label = '50.0% - 59.0%';
      pointsGrade = 3;
    } else if (score < 70) {
      grade = 'C';
      remark = 'Good';
      label = '60.0% - 69.0%';
      pointsGrade = 4;
    } else if (score < 90) {
      grade = 'B';
      remark = 'Very Good';
      label = '70.0% - 89.0%';
      pointsGrade = 4;
    } else {
      grade = 'A';
      remark = 'Excellent';
      label = '90.0% - Above';
      pointsGrade = 5;
    }
    return [grade, remark, label, pointsGrade];
  }

  function LabelText({
    label,
    text,
  }: {
    label: string;
    text: string | number | undefined | React.ReactNode;
  }) {
    return (
      <Div fontWeight={'bold'}>
        <Text as={'span'} textTransform={'uppercase'}>
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
          <VStack align={'stretch'}>
            <HStack background={'#FCFCFC'} p={2}>
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
                  color={'#ff7900'}
                  textShadow={'3px 3px #000'}
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
                <Text textAlign={'center'}>
                  {[currentInstitution.website, currentInstitution.email]
                    .filter((item) => Boolean(item))
                    .join(' | ')
                    .trim()}
                </Text>
              </VStack>
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
            <Spacer height={3} />
            <div className="table-container">
              <table
                className="result-table"
                width={'100%'}
                style={{ borderColor: '#5b9bd5' }}
              >
                <thead
                  style={{
                    background: 'red.800',
                    color: '#3f3f3f',
                    fontWeight: 'bold',
                    textTransform: 'uppercase',
                    fontSize: '14px',
                  }}
                >
                  <tr>
                    <th>Subject</th>
                    {assessments.map((assessment) => (
                      <th
                        style={{
                          background: '#5b9bd5',
                          border: '1px solid #FFF',
                          color: '#FFF',
                        }}
                        key={'result-header' + assessment.title}
                      >
                        {startCase(assessment.title)}
                      </th>
                    ))}
                    <th
                      style={{
                        background: '#5b9bd5',
                        border: '1px solid #FFF',
                        color: '#FFF',
                      }}
                    >
                      Exam
                    </th>
                    <th>Total</th>
                    <th>Subject Position</th>
                    <th>Remark</th>
                  </tr>
                </thead>
                <tbody>
                  {courseResults.map((courseResult) => (
                    <tr key={courseResult.id}>
                      <td style={{ fontWeight: 'bold' }}>
                        {courseResult.course?.title}
                      </td>
                      {assessments.map((assessment) => (
                        <td
                          key={
                            'assessment-val' +
                            courseResult.id +
                            assessment.title
                          }
                        >
                          {courseResult.assessment_values[
                            assessment.raw_title
                          ] ?? '-'}
                        </td>
                      ))}
                      <td>{courseResult.exam}</td>
                      <td style={{ fontWeight: 'bold' }}>
                        {courseResult.result}
                      </td>
                      <td>
                        {showGrade
                          ? getGrade(courseResult.result)[0]
                          : ResultUtil.formatPosition(courseResult.position)}
                      </td>
                      {/* <td>{ResultUtil.getRemark(courseResult.grade)}</td> */}
                      <td>{getGrade(courseResult.result)[1]}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
            <br />
            <Flex
              flexDirection={'row'}
              justifyContent={'space-between'}
              fontSize={'lg'}
            >
              <VStack spacing={2} align={'left'}>
                <LabelText
                  label={'Grand Total'}
                  text={termResult.total_score}
                />
                <LabelText
                  label={'Class Average'}
                  text={classResultInfo.average}
                />
              </VStack>
              <VStack spacing={2} align={'left'}>
                <LabelText label={'Average'} text={termResult.average} />
                <LabelText
                  label={'Remark'}
                  text={getGrade(termResult.average)[1]}
                />
              </VStack>
            </Flex>
            <Spacer height={'10px'} />
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
                    Head Teacher's comment:{' '}
                  </Text>
                  <Text>{termResult.principal_comment}</Text>
                </HStack>
                <Divider />
              </>
            )}
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
                    const [grade, remark, label, pointsGrade] = getGrade(item);
                    return (
                      <tr key={item}>
                        <td>{label}</td>
                        <td>{grade}</td>
                        <td>{remark}</td>
                        <td>{pointsGrade}</td>
                      </tr>
                    );
                  })}
                </tbody>
              </table>
              <DisplayTermResultEvaluation
                termResult={termResult}
                learningEvaluations={learningEvaluations}
              />
            </div>
          </VStack>
        </Div>
      </Div>
    </ResultSheetLayout>
  );
}
