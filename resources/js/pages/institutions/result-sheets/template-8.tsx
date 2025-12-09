import {
  Avatar,
  Box,
  SimpleGrid,
  Divider,
  HStack,
  Img,
  Spacer,
  Text,
  VStack,
  Table,
  Thead,
  Tbody,
  Tr,
  Th,
  Td,
  Badge,
  Card,
  CardBody,
} from '@chakra-ui/react';
import React from 'react';
import { Div } from '@/components/semantic';
import startCase from 'lodash/startCase';
import useSharedProps from '@/hooks/use-shared-props';
import '@/../../public/style/result-sheet.css';
import ImagePaths from '@/util/images';
import DisplayTermResultEvaluation from '@/components/display-term-result-evaluation-component';
import ResultUtil, { ResultProps, useResultSetting } from '@/util/result-util';
import ResultSheetLayout from './result-sheet-layout';
import DateTimeDisplay from '@/components/date-time-display';
import { dateFormat, formatAsDate } from '@/util/util';

export default function Template8(props: ResultProps) {
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
    learningEvaluations,
    termDetail,
  } = props;
  const { currentInstitution, stamp } = useSharedProps();
  const { hidePosition } = useResultSetting();
  const nextTermResumptionDate =
    classResultInfo.next_term_resumption_date ??
    termDetail?.next_term_resumption_date;

  // Color Theme
  const themeColor = 'teal.600';
  const themeLight = 'teal.50';

  const principalComment =
    termResult.principal_comment ??
    ResultUtil.getCommentFromTemplate(termResult.average, resultCommentTemplate)
      ?.comment;

  const teacherComment =
    termResult.teacher_comment ??
    ResultUtil.getCommentFromTemplate(termResult.average, resultCommentTemplate)
      ?.comment_2;

  function SummaryCard({
    label,
    value,
  }: {
    label: string;
    value: React.ReactNode;
  }) {
    return (
      <Card variant={'outline'} borderColor={themeColor}>
        <CardBody p={3} textAlign={'center'}>
          <Text
            fontSize={'xs'}
            textTransform={'uppercase'}
            color={'gray.500'}
            fontWeight={'bold'}
          >
            {label}
          </Text>
          <Text fontSize={'md'} fontWeight={'bold'} color={themeColor}>
            {value}
          </Text>
        </CardBody>
      </Card>
    );
  }

  return (
    <ResultSheetLayout resultProps={props}>
      <Div
        mx="auto"
        width="900px"
        px={0}
        position="relative"
        id="result-sheet"
        bg="white"
      >
        {/* Header Section */}
        <HStack
          align="stretch"
          spacing={0}
          mb={6}
          borderBottomWidth={4}
          borderBottomColor={themeColor}
        >
          {/* Left: Institution Branding */}
          <Box
            bg={themeColor}
            color="white"
            px={6}
            py={4}
            flex={1}
            borderTopRightRadius={0}
          >
            <VStack align="start" spacing={3}>
              <HStack spacing={3}>
                <Avatar
                  size="xl"
                  name="School Logo"
                  src={
                    currentInstitution.photo ?? ImagePaths.default_school_logo
                  }
                  bg="white"
                  p={1}
                />
                <VStack align="start" spacing={0}>
                  <Text fontSize="2xl" fontWeight="black" lineHeight={1}>
                    {currentInstitution.name}
                  </Text>
                  <Text fontSize="sm" opacity={0.9}>
                    {currentInstitution.address}
                  </Text>
                  <Text fontSize="xs" opacity={0.8}>
                    {currentInstitution.email}
                  </Text>
                </VStack>
              </HStack>
              <Badge
                colorScheme="white"
                variant="outline"
                px={2}
                py={1}
                fontSize="xs"
              >
                {academicSession.title} Session
              </Badge>
            </VStack>
          </Box>

          {/* Right: Student Info */}
          <Box flex={1} px={6} py={4} bg={themeLight}>
            <HStack w={'full'} spacing={2}>
              <Spacer />
              <VStack align="end" spacing={1}>
                <Text fontSize="2xl" fontWeight="bold" color="gray.700">
                  {startCase(termResult.term)} Term Report
                </Text>
                <Text fontSize="xl" fontWeight="bold" textAlign="right">
                  {student.user?.full_name}
                </Text>
                <Text fontSize="sm" color="gray.600">
                  {classification.title} | {student.code}
                </Text>
              </VStack>
              <Avatar
                size="xl"
                name="Student Photo"
                src={student.user?.photo ?? ''}
                borderColor={themeColor}
                borderWidth={2}
                mb={2}
              />
            </HStack>
          </Box>
        </HStack>

        {/* Summary Cards */}
        <SimpleGrid columns={4} spacing={4} px={6} mb={6}>
          <SummaryCard label="Total Score" value={termResult.total_score} />
          <SummaryCard label="Average" value={`${termResult.average}%`} />
          <SummaryCard
            label="Position"
            value={
              hidePosition
                ? '-'
                : ResultUtil.formatPosition(termResult.position)
            }
          />
          <SummaryCard
            label="Class Size"
            value={classResultInfo.num_of_students}
          />
          {termDetail?.end_date && (
            <SummaryCard
              label="Closing Date"
              value={formatAsDate(termDetail?.end_date)}
            />
          )}
          {nextTermResumptionDate && (
            <SummaryCard
              label="Next Term Begins"
              value={formatAsDate(nextTermResumptionDate)}
            />
          )}
        </SimpleGrid>

        {/* Resumption Date Banner if exists */}
        {classResultInfo.next_term_resumption_date && (
          <Box
            mx={6}
            mb={6}
            p={2}
            bg="orange.50"
            borderLeftWidth={4}
            borderLeftColor="orange.400"
          >
            <Text fontSize="sm" fontWeight="medium" color="orange.800">
              Next Term Begins:{' '}
              <DateTimeDisplay
                as={'span'}
                dateTime={classResultInfo.next_term_resumption_date}
                dateTimeformat={dateFormat}
                fontWeight={'bold'}
              />
            </Text>
          </Box>
        )}

        {/* Results Table */}
        <Box px={6} mb={6}>
          <Table size="sm" variant="striped" colorScheme="teal">
            <Thead bg={themeColor}>
              <Tr>
                <Th color="white" borderTopLeftRadius="md">
                  Subject
                </Th>
                {assessments.map((a) => (
                  <Th key={a.id} isNumeric color="white">
                    {a.title}
                  </Th>
                ))}
                <Th isNumeric color="white">
                  Exam
                </Th>
                <Th isNumeric color="white">
                  Total
                </Th>
                <Th color="white">Grade</Th>
                {!hidePosition && (
                  <Th isNumeric color="white">
                    Pos
                  </Th>
                )}
                <Th isNumeric color="white">
                  Avg
                </Th>
                <Th color="white" borderTopRightRadius="md">
                  Remark
                </Th>
              </Tr>
            </Thead>
            <Tbody>
              {courseResults.map((result) => {
                const { grade, remark } = ResultUtil.getGrade(
                  result.result,
                  resultCommentTemplate
                );
                return (
                  <Tr key={result.id}>
                    <Td fontWeight="medium">{result.course?.title}</Td>
                    {assessments.map((a) => (
                      <Td key={a.id} isNumeric>
                        {result.assessment_values[a.raw_title] ?? '-'}
                      </Td>
                    ))}
                    <Td isNumeric>{result.exam}</Td>
                    <Td isNumeric fontWeight="bold">
                      {result.result}
                    </Td>
                    <Td>
                      <Badge colorScheme={ResultUtil.getGradeColor(grade)}>
                        {grade}
                      </Badge>
                    </Td>
                    {!hidePosition && (
                      <Td isNumeric>
                        {ResultUtil.formatPosition(result.position)}
                      </Td>
                    )}
                    <Td isNumeric color="gray.500">
                      {courseResultInfoData[result.course_id]?.average ?? '-'}
                    </Td>
                    <Td fontSize="xs">{remark}</Td>
                  </Tr>
                );
              })}
            </Tbody>
          </Table>
        </Box>

        {/* Learning Evaluations & Comments */}
        <Box px={6}>
          <DisplayTermResultEvaluation
            termResult={termResult}
            learningEvaluations={learningEvaluations}
          />

          <Divider my={6} borderColor="gray.300" />

          <SimpleGrid columns={2} spacing={8}>
            <VStack align="stretch" spacing={4}>
              {teacherComment && (
                <>
                  <Box>
                    <Text fontWeight="bold" color={themeColor} mb={1}>
                      Teacher's Remark
                    </Text>
                    <Text fontSize="sm" fontStyle="italic" minH="40px">
                      {teacherComment}
                    </Text>
                  </Box>
                  <Divider />
                </>
              )}
              {principalComment && (
                <Box>
                  <Text fontWeight="bold" color={themeColor} mb={1}>
                    Principal/Head Teacher Remark
                  </Text>
                  <Text fontSize="sm" fontStyle="italic" minH="40px">
                    {principalComment}
                  </Text>
                </Box>
              )}
              <Box mt={2}>
                {stamp && (
                  <Img src={stamp} alt="Stamp" maxH="80px" opacity={0.8} />
                )}
              </Box>
            </VStack>

            {/* Grading Keys */}
            <Box bg="gray.50" p={4} borderRadius="md">
              <Text fontWeight="bold" mb={2} fontSize="sm">
                Grading System
              </Text>
              <Table size="xs" variant="simple">
                <Thead>
                  <Tr>
                    <Th>Range</Th>
                    <Th>Grade</Th>
                    <Th>Remark</Th>
                  </Tr>
                </Thead>
                <Tbody>
                  {resultCommentTemplate.map((item) => (
                    <Tr key={item.id}>
                      <Td>
                        {item.min} - {item.max}
                      </Td>
                      <Td fontWeight="bold">{item.grade}</Td>
                      <Td>{item.grade_label}</Td>
                    </Tr>
                  ))}
                </Tbody>
              </Table>
            </Box>
          </SimpleGrid>
        </Box>
        <Spacer height="40px" />
      </Div>
    </ResultSheetLayout>
  );
}
