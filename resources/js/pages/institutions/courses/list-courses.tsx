import React, { useState } from 'react';
import { Course, PracticeQuestion } from '@/types/models';
import { HStack, IconButton, Icon, Button, Text, RadioGroup, VStack, Radio, Box } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import ServerPaginatedTable from '@/components/server-paginated-table';
import { PaginationResponse } from '@/types/types';
import { PencilIcon } from '@heroicons/react/24/outline';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { BrandButton, LinkButton } from '@/components/buttons';
import { ServerPaginatedTableHeader } from '@/components/server-paginated-table';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { InertiaLink } from '@inertiajs/inertia-react';
import {
  XCircleIcon,
  CheckCircleIcon,
  CloudArrowUpIcon,
  PlusIcon,
  TrashIcon,
} from '@heroicons/react/24/solid';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import DestructivePopover from '@/components/destructive-popover';
import useIsAdmin from '@/hooks/use-is-admin';
import useIsStudent from '@/hooks/use-is-student';
import { useModalValueToggle } from '@/hooks/use-modal-toggle';
import PracticeQuestionModal from '@/components/modals/practice-question-modal';
import { Div } from '@/components/semantic';

interface Props {
  courses: PaginationResponse<Course>;
}

export default function ListCourse({ courses }: Props) {
  const [answers, setAnswers] = useState<{[key: number]: string }>({});
  const [submitted, setSubmitted] = useState(false);
  const { instRoute } = useInstitutionRoute();
  const [practiceQuestions, setPracticeQuestions] = useState<PracticeQuestion[]>();
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();
  const isAdmin = useIsAdmin();
  const isStudent = useIsStudent();
  const practiceQuestionModalToggle = useModalValueToggle<Course>();

  const handleSelect = (index: number, value: string) => {
    setAnswers({ ...answers, [index]: value });
  };

  const handleSubmit = () => {
    setSubmitted(true);
  };

  // Calculate statistics
  const totalQuestions = practiceQuestions?.length ?? 0;
  const attemptedQuestions = Object.keys(answers).length; // Number of answered questions
  const correctAnswers = practiceQuestions?.filter(
    (q, index) => answers[index] === q.correct_answer
  ).length ?? 0;

  async function deleteItem(obj: Course) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('courses.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['courses'] });
  }

  const headers: ServerPaginatedTableHeader<Course>[] = [
    {
      label: 'Title',
      value: 'title',
    },
    ...(isStudent
      ? [
          {
            label: 'Action',
            render: (row: Course) => (
              <HStack>
                <BrandButton
                  onClick={() => practiceQuestionModalToggle.open(row)}
                  leftIcon={<Icon as={PlusIcon} />}
                  variant={'ghost'}
                  colorScheme={'brand'}
                  title="Practice Question"
                />
              </HStack>
            ),
          },
        ]
      : []),
    ...(isAdmin
      ? [
          {
            label: 'Action',
            render: (row: Course) => (
              <HStack>
                <BrandButton
                  onClick={() => practiceQuestionModalToggle.open(row)}
                  leftIcon={<Icon as={PlusIcon} />}
                  variant={'ghost'}
                  colorScheme={'brand'}
                  title="Practice Question"
                />

                <Button
                  as={'a'}
                  href={instRoute('course-sessions.index', [row.id])}
                  variant={'link'}
                  colorScheme={'brand'}
                >
                  Question Bank
                </Button>
                <IconButton
                  aria-label={'Upload Content'}
                  icon={<Icon as={CloudArrowUpIcon} />}
                  as={'a'}
                  href={instRoute('courses.upload-content.create', [row.id])}
                  variant={'ghost'}
                  colorScheme={'brand'}
                />
                <IconButton
                  aria-label={'Edit Subject'}
                  icon={<Icon as={PencilIcon} />}
                  as={InertiaLink}
                  href={instRoute('courses.edit', [row.id])}
                  variant={'ghost'}
                  colorScheme={'brand'}
                />
                <DestructivePopover
                  label={'Delete this subject'}
                  onConfirm={() => deleteItem(row)}
                  isLoading={deleteForm.processing}
                >
                  <IconButton
                    aria-label={'Delete subject'}
                    icon={<Icon as={TrashIcon} />}
                    variant={'ghost'}
                    colorScheme={'red'}
                  />
                </DestructivePopover>
              </HStack>
            ),
          },
        ]
      : []),
  ];

  return (
    <DashboardLayout>
      <Slab>
        {practiceQuestions ? (
          <>
            <SlabHeading title="Practice Questions" />
            <SlabBody>
              {practiceQuestions.map((q, index) => (
                <Box key={index} mb={5}>
                  <Text><strong>Q{index + 1}:</strong> {q.question}</Text>

                  <RadioGroup
                    mt={2}
                    onChange={(val) => handleSelect(index, val)}
                    value={answers[index] || ''}
                    isDisabled={submitted}
                  >
                    <VStack align="stretch" ml={10}>
                      {['a', 'b', 'c', 'd'].map((optionKey) => {
                        const isCorrect = q.correct_answer === optionKey;
                        const isSelected = answers[index] === optionKey;

                        return (
                          <HStack key={optionKey}>
                            <Radio value={optionKey} isDisabled={submitted}>
                              {q[optionKey]}
                            </Radio>

                            {submitted && (
                              isCorrect ? (
                                <Icon as={CheckCircleIcon} color="green" boxSize={5} />
                              ) : isSelected ? (
                                <Icon as={XCircleIcon} color="red" boxSize={5} />
                              ) : null
                            )}
                          </HStack>
                        );
                      })}
                    </VStack>
                  </RadioGroup>
                </Box>
              ))}

              {/* Display statistics after submission */}
              <Div alignContent={'center'} justifyContent={'center'}>
              {submitted ? (
                <Box mt={8} p={4} border="2px solid green" borderRadius="8px" w={600}>
                  <Text><strong>Total Questions:</strong> {totalQuestions}</Text>
                  <Text><strong>Attempted Questions:</strong> {attemptedQuestions}</Text>
                  <Text><strong>Correctly Answered Questions:</strong> {correctAnswers}</Text>
                  <Text><strong>Score:</strong> {Math.round((correctAnswers / totalQuestions) * 100)}%</Text>
                </Box>
              ):(
                <BrandButton onClick={handleSubmit} mt={4}>
                  Submit
                </BrandButton>
              )}   </Div>           
            </SlabBody>
          </>
        ) : (
          <>
            <SlabHeading
              title="List Subjects"
              rightElement={
                isAdmin ? (
                  <LinkButton
                    href={instRoute('courses.create')}
                    title={'New'}
                  />
                ) : (
                  ''
                )
              }
            />
            <SlabBody>
              <ServerPaginatedTable
                scroll={true}
                headers={headers}
                data={courses.data}
                keyExtractor={(row) => row.id}
                paginator={courses}
              />
            </SlabBody>
          </>
        )}
      </Slab>

      {practiceQuestionModalToggle.state && (
        <PracticeQuestionModal
          {...practiceQuestionModalToggle.props}
          course={practiceQuestionModalToggle.state}
          onSuccess={(practiceQuestions) => setPracticeQuestions(practiceQuestions)}
        />
      )}
    </DashboardLayout>
  );
}
