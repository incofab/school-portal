import React, { useState } from 'react';
import {
  Badge,
  Box,
  Button,
  Card,
  CardBody,
  FormControl,
  FormErrorMessage,
  FormLabel,
  HStack,
  Icon,
  Input,
  SimpleGrid,
  Spacer,
  Stack,
  Text,
  useColorModeValue,
  Wrap,
  WrapItem,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { Inertia } from '@inertiajs/inertia';
import {
  Assessment,
  CourseResult,
  CourseTeacher,
  Student,
} from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { SelectOptionType } from '@/types/types';
import Dt from '@/components/dt';
import useSharedProps from '@/hooks/use-shared-props';
import { Div } from '@/components/semantic';
import startCase from 'lodash/startCase';
import SwitchCourseTeacher from './switch-course-teacher-component';
import {
  getResultTotalScore,
  hasResultScoreChanged,
  removeExamFromResultEntry,
  validateResultScore,
} from '@/util/result-recording-util';
import {
  AcademicCapIcon,
  CheckCircleIcon,
  ClipboardDocumentCheckIcon,
} from '@heroicons/react/24/outline';

type ResultMode = 'full-term' | 'mid-term' | '';

interface ResultEntry {
  [studentId: string]: {
    ass: { [key: string]: string | number };
    exam: string | number;
    student_id: number;
  };
}

interface Props {
  courseTeacher: CourseTeacher;
  students: Student[];
  assessmentGroups: {
    fullTerm: Assessment[];
    midTerm: Assessment[];
  };
  showExamInput?: {
    fullTerm: boolean;
    midTerm: boolean;
  };
  teachersCourses: { [id: number]: CourseTeacher };
  forMidTerm: boolean;
}

export default function RecordClassCourseResult({
  courseTeacher,
  students,
  assessmentGroups,
  showExamInput = { fullTerm: true, midTerm: true },
  teachersCourses,
  forMidTerm,
}: Props) {
  const { handleResponseToast, toastError } = useMyToast();
  const { currentAcademicSession, currentTerm, usesMidTermResult } =
    useSharedProps();
  const [selectedResultMode, setSelectedResultMode] = useState<ResultMode>(
    usesMidTermResult ? '' : forMidTerm ? 'mid-term' : 'full-term'
  );
  const selectedCourseTeacherState = useState<CourseTeacher>(courseTeacher);
  const { instRoute } = useInstitutionRoute();
  const hasSelectedResultMode = !usesMidTermResult || selectedResultMode !== '';

  const webForm = useWebForm({
    academic_session_id: currentAcademicSession.id,
    term: currentTerm,
    for_mid_term: forMidTerm,
    result: {} as ResultEntry,
  });
  const isMidTermSelected = Boolean(webForm.data.for_mid_term);
  const selectedAssessments = isMidTermSelected
    ? assessmentGroups.midTerm
    : assessmentGroups.fullTerm;
  const shouldShowExamInput = isMidTermSelected
    ? showExamInput.midTerm
    : showExamInput.fullTerm;
  const initialResults = buildInitialResults(students, isMidTermSelected);
  const resultModeLabel = isMidTermSelected
    ? 'Mid-Term Result'
    : 'Full Term Result';
  const resultModeScheme = isMidTermSelected ? 'yellow' : 'blue';
  const selectionPanelBg = useColorModeValue('white', 'gray.800');
  const selectionPanelMuted = useColorModeValue('gray.600', 'gray.300');
  const selectionPanelBorder = useColorModeValue('brand.100', 'gray.700');
  const optionBg = useColorModeValue('gray.50', 'gray.700');
  const optionSelectedBg = useColorModeValue('brand.50', 'whiteAlpha.100');
  const optionBorder = useColorModeValue('gray.200', 'gray.600');
  const optionSelectedBorder = useColorModeValue('brand.500', 'brand.300');
  const optionShadow = useColorModeValue(
    '0 12px 30px rgba(0, 0, 0, 0.08)',
    '0 12px 30px rgba(0, 0, 0, 0.28)'
  );

  const submit = async () => {
    if (!hasSelectedResultMode) {
      toastError('Select full term or mid-term recording before continuing.');
      return;
    }

    const result = Object.entries(webForm.data.result)
      .filter(([studentId, item]) =>
        hasResultScoreChanged(
          item,
          initialResults[Number(studentId)],
          shouldShowExamInput
        )
      )
      .map(([, item]) =>
        shouldShowExamInput ? item : removeExamFromResultEntry(item)
      );

    if (result.length < 1) {
      Inertia.visit(instRoute('course-results.index'));
      return;
    }

    const res = await webForm.submit((data, web) => {
      return web.post(
        instRoute('record-class-results.store', [courseTeacher]),
        { ...data, result }
      );
    });

    if (!handleResponseToast(res)) {
      return;
    }

    Inertia.visit(instRoute('course-results.index'));
  };

  const details: SelectOptionType[] = [
    { label: 'Subject', value: courseTeacher.course?.title ?? '' },
    { label: 'Class', value: courseTeacher.classification?.title ?? '' },
    { label: 'Teacher', value: courseTeacher.user?.full_name ?? '' },
    { label: 'Session', value: currentAcademicSession.title },
    { label: 'Term', value: startCase(String(currentTerm)) },
    ...(usesMidTermResult
      ? [
          {
            label: 'For Mid Term',
            value: hasSelectedResultMode
              ? isMidTermSelected
                ? 'Yes'
                : 'No'
              : 'Not selected',
          },
        ]
      : []),
  ];

  function isValidScore(score: number | string, maxScore?: number) {
    const validation = validateResultScore(score, maxScore);

    if (!validation.ok) {
      toastError(validation.message);
      return false;
    }

    return true;
  }

  return (
    <DashboardLayout>
      <Div>
        <CenteredBox>
          {usesMidTermResult && (
            <Box
              bg={selectionPanelBg}
              borderColor={selectionPanelBorder}
              borderWidth={1}
              borderRadius={'lg'}
              boxShadow={'0px 2px 6px rgba(0, 0, 0, 0.1)'}
              p={{ base: 4, md: 6 }}
            >
              <Stack spacing={5}>
                <HStack justify={'space-between'} align={'start'} spacing={4}>
                  <Box>
                    <Badge colorScheme={'brand'} mb={2}>
                      Required First Step
                    </Badge>
                    <Text
                      fontSize={{ base: 'lg', md: 'xl' }}
                      fontWeight={'bold'}
                    >
                      Select result recording type
                    </Text>
                    <Text color={selectionPanelMuted} fontSize={'sm'} mt={1}>
                      Choose full term or mid-term to start recording.
                    </Text>
                  </Box>
                  <Badge
                    colorScheme={
                      hasSelectedResultMode ? resultModeScheme : 'red'
                    }
                    variant={hasSelectedResultMode ? 'subtle' : 'solid'}
                    flexShrink={0}
                  >
                    {hasSelectedResultMode
                      ? resultModeLabel
                      : 'Selection required'}
                  </Badge>
                </HStack>

                <FormControl isInvalid={!!webForm.errors.for_mid_term}>
                  <SimpleGrid columns={{ base: 1, md: 2 }} spacing={4}>
                    <ResultModeOption
                      title="Full Term"
                      description="Record for full term"
                      icon={ClipboardDocumentCheckIcon}
                      isSelected={selectedResultMode === 'full-term'}
                      isDisabled={webForm.processing}
                      bg={optionBg}
                      selectedBg={optionSelectedBg}
                      borderColor={optionBorder}
                      selectedBorderColor={optionSelectedBorder}
                      selectedShadow={optionShadow}
                      onClick={() => {
                        setSelectedResultMode('full-term');
                        webForm.setValue('for_mid_term', false);
                        webForm.setValue('result', {});
                      }}
                    />
                    <ResultModeOption
                      title="Mid-Term"
                      description="Record for mid term"
                      icon={AcademicCapIcon}
                      isSelected={selectedResultMode === 'mid-term'}
                      isDisabled={webForm.processing}
                      bg={optionBg}
                      selectedBg={optionSelectedBg}
                      borderColor={optionBorder}
                      selectedBorderColor={optionSelectedBorder}
                      selectedShadow={optionShadow}
                      onClick={() => {
                        setSelectedResultMode('mid-term');
                        webForm.setValue('for_mid_term', true);
                        webForm.setValue('result', {});
                      }}
                    />
                  </SimpleGrid>
                  <FormErrorMessage>
                    {webForm.errors.for_mid_term}
                  </FormErrorMessage>
                </FormControl>
              </Stack>
            </Box>
          )}
          <Spacer height={3} />

          {hasSelectedResultMode && (
            <>
              <Slab>
                <SlabHeading title={`Record Class Result`} />
                <SlabBody>
                  <SwitchCourseTeacher
                    courseTeacher={courseTeacher}
                    teachersCourses={teachersCourses}
                    selectedCourseTeacherState={selectedCourseTeacherState}
                    getUrl={(courseTeacherId) =>
                      instRoute('record-class-results.create', [
                        courseTeacherId,
                      ])
                    }
                  />
                  <Dt contentData={details} />
                </SlabBody>
              </Slab>
              <Spacer height={3} />
            </>
          )}

          {selectedCourseTeacherState[0].id === courseTeacher.id &&
            hasSelectedResultMode &&
            students.map((student) => {
              const result = webForm.data.result[student.id] ?? {
                ...initialResults[student.id],
              };
              result.student_id = student.id;
              const studentTotalScore = getResultTotalScore(
                result,
                shouldShowExamInput
              );
              return (
                <Card
                  key={student.id + 'exam' + webForm.data.term}
                  mt={2}
                  bg={'white'}
                >
                  <CardBody>
                    <HStack align={'stretch'}>
                      <Text display={'block'} fontWeight={'semibold'} mb={3}>
                        {student.user!.full_name}
                      </Text>
                      <Spacer />
                      <Text
                        color={'brand.700'}
                        fontSize={'sm'}
                        // fontWeight={'semibold'}
                        display={studentTotalScore ? undefined : 'none'}
                      >
                        Total {studentTotalScore}
                      </Text>
                    </HStack>
                    <Wrap spacing={3}>
                      {selectedAssessments.map((assessment) => {
                        return (
                          <WrapItem
                            mt={2}
                            width={'120px'}
                            key={
                              student.id +
                              assessment.raw_title +
                              webForm.data.term
                            }
                          >
                            <FormControl>
                              <FormLabel
                                fontWeight={'normal'}
                                m={0}
                                whiteSpace={'nowrap'}
                                textOverflow={'ellipsis'}
                                overflow={'hidden'}
                                fontSize={'sm'}
                              >
                                {startCase(assessment.raw_title)}
                              </FormLabel>
                              <Input
                                value={result.ass[assessment.raw_title] ?? ''}
                                type="number"
                                onChange={(e) => {
                                  if (
                                    !isValidScore(
                                      e.currentTarget.value,
                                      assessment.max
                                    )
                                  ) {
                                    return;
                                  }
                                  webForm.setValue('result', {
                                    ...webForm.data.result,
                                    [student.id]: {
                                      ...result,
                                      ass: {
                                        ...result.ass,
                                        [assessment.raw_title]:
                                          e.currentTarget.value,
                                      },
                                    },
                                  });
                                }}
                              />
                            </FormControl>
                          </WrapItem>
                        );
                      })}
                      {shouldShowExamInput && (
                        <WrapItem mt={2} width={'120px'}>
                          <FormControl>
                            <FormLabel
                              fontWeight={'normal'}
                              m={0}
                              whiteSpace={'nowrap'}
                              textOverflow={'ellipsis'}
                              overflow={'hidden'}
                              fontSize={'sm'}
                            >
                              Exam
                            </FormLabel>
                            <Input
                              value={result.exam}
                              type="number"
                              onChange={(e) => {
                                if (
                                  !isValidScore(
                                    e.currentTarget.value,
                                    100 -
                                      (Number(studentTotalScore) -
                                        Number(result.exam))
                                  )
                                ) {
                                  return;
                                }
                                webForm.setValue('result', {
                                  ...webForm.data.result,
                                  [student.id]: {
                                    ...result,
                                    exam: e.currentTarget.value,
                                  },
                                });
                              }}
                            />
                          </FormControl>
                        </WrapItem>
                      )}
                    </Wrap>
                  </CardBody>
                </Card>
              );
            })}
          {hasSelectedResultMode && (
            <FormControl mt={3}>
              <FormButton
                isLoading={
                  selectedCourseTeacherState[0].id !== courseTeacher.id ||
                  webForm.processing
                }
                onClick={submit}
              />
            </FormControl>
          )}
        </CenteredBox>
      </Div>
    </DashboardLayout>
  );
}

interface ResultModeOptionProps {
  title: string;
  description: string;
  icon: React.ElementType;
  isSelected: boolean;
  isDisabled: boolean;
  bg: string;
  selectedBg: string;
  borderColor: string;
  selectedBorderColor: string;
  selectedShadow: string;
  onClick: () => void;
}

function ResultModeOption({
  title,
  description,
  icon,
  isSelected,
  isDisabled,
  bg,
  selectedBg,
  borderColor,
  selectedBorderColor,
  selectedShadow,
  onClick,
}: ResultModeOptionProps) {
  return (
    <Button
      type="button"
      onClick={onClick}
      isDisabled={isDisabled}
      variant={'outline'}
      h={'auto'}
      minH={'132px'}
      whiteSpace={'normal'}
      justifyContent={'stretch'}
      textAlign={'left'}
      p={4}
      borderWidth={2}
      borderRadius={'lg'}
      borderColor={isSelected ? selectedBorderColor : borderColor}
      bg={isSelected ? selectedBg : bg}
      boxShadow={isSelected ? selectedShadow : undefined}
      _hover={{
        borderColor: selectedBorderColor,
        bg: selectedBg,
      }}
      _active={{
        bg: selectedBg,
      }}
    >
      <HStack align={'start'} spacing={3} w={'full'}>
        <Box
          h={10}
          minW={10}
          borderRadius={'full'}
          display={'grid'}
          placeItems={'center'}
          bg={isSelected ? 'brand.500' : 'brand.50'}
          color={isSelected ? 'white' : 'brand.600'}
        >
          <Icon as={isSelected ? CheckCircleIcon : icon} fontSize={'xl'} />
        </Box>
        <Box>
          <Text fontWeight={'bold'} mb={1}>
            {title}
          </Text>
          <Text fontSize={'sm'} fontWeight={'normal'} lineHeight={'1.55'}>
            {description}
          </Text>
        </Box>
      </HStack>
    </Button>
  );
}

// function SwitchCourseTeacher({
//   courseTeacher,
//   teachersCourses,
//   selectedCourseTeacherState,
// }: {
//   teachersCourses: { [id: number]: CourseTeacher };
//   courseTeacher: CourseTeacher;
//   selectedCourseTeacherState: [
//     CourseTeacher,
//     React.Dispatch<React.SetStateAction<CourseTeacher>>
//   ];
// }) {
//   const { instRoute } = useInstitutionRoute();
//   const [selectedCourseTeacher, setSelectedCourseTeacher] =
//     selectedCourseTeacherState;
//   function getValue(ct: CourseTeacher) {
//     return {
//       label: `${ct.classification?.title} - ${ct.course?.title}`,
//       value: ct.id,
//     };
//   }
//   return (
//     <Div pt={2} pb={4}>
//       <Text>Change Subject</Text>
//       <HStack w={'full'} spacing={2}>
//         <Div flex={1}>
//           <MySelect
//             isMulti={false}
//             selectValue={getValue(selectedCourseTeacher)}
//             getOptions={() =>
//               Object.values(teachersCourses).map((ct) => getValue(ct))
//             }
//             onChange={(e: any) => {
//               if (!e || e.value == selectedCourseTeacher.id) return;
//               setSelectedCourseTeacher(teachersCourses[e.value]);
//               Inertia.visit(
//                 instRoute('record-class-results.create', [e.value])
//               );
//             }}
//           />
//         </Div>
//         {selectedCourseTeacher.id != courseTeacher.id && (
//           <Spinner size="md" color="brand.500" />
//         )}
//       </HStack>
//     </Div>
//   );
// }

function buildInitialResults(students: Student[], isMidTermSelected: boolean) {
  return students.reduce((result, student) => {
    const existingResult =
      student.course_results?.find(
        (item) => Boolean(item.for_mid_term) === isMidTermSelected
      ) ?? ({} as CourseResult);

    result[student.id] = {
      ass: existingResult?.assessment_values ?? {},
      exam: existingResult?.exam ?? '',
      student_id: student.id,
    };

    return result;
  }, {} as ResultEntry);
}
