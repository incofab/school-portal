import React from 'react';
import DOMPurify from 'dompurify';
import {
  Alert,
  AlertIcon,
  Badge,
  Box,
  Button,
  Divider,
  FormControl,
  FormHelperText,
  FormLabel,
  Heading,
  HStack,
  SimpleGrid,
  Stack,
  Text,
  VStack,
} from '@chakra-ui/react';
import { Inertia } from '@inertiajs/inertia';
import { InertiaLink } from '@inertiajs/inertia-react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Classification, Course, LessonPlan } from '@/types/models';
import { NoteStatusType } from '@/types/types';
import DateTimeDisplay from '@/components/date-time-display';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useIsAdmin from '@/hooks/use-is-admin';
import useSharedProps from '@/hooks/use-shared-props';
import ClassificationSelect from '@/components/selectors/classification-select';

interface Props {
  course: Course;
  classifications: Classification[];
  selectedClassificationId?: number | null;
  lessonPlans: LessonPlan[];
}

export default function ListCourseLessonPlans({
  course,
  classifications,
  selectedClassificationId,
  lessonPlans,
}: Props) {
  const { instRoute } = useInstitutionRoute();

  function visitWithClassification(classificationId?: number) {
    Inertia.visit(instRoute('courses.lesson-plans', [course.id]), {
      data: classificationId ? { classification_id: classificationId } : {},
      preserveScroll: true,
    });
  }

  return (
    <DashboardLayout>
      <Box px={{ base: 3, md: 6 }} py={4}>
        <VStack align="stretch" spacing={5}>
          <Stack
            direction={{ base: 'column', md: 'row' }}
            justify="space-between"
            align={{ base: 'stretch', md: 'start' }}
            gap={3}
          >
            <Box>
              <Heading size={{ base: 'md', md: 'lg' }}>
                Lesson Plans · {course.title}
              </Heading>
              <Text color="blackAlpha.700" mt={1}>
                Review weekly teaching plans and their linked lesson notes for
                this subject.
              </Text>
            </Box>
            <Box
              as={InertiaLink}
              href={instRoute('courses.index')}
              color="brand.600"
              fontWeight="semibold"
              alignSelf={{ base: 'start', md: 'center' }}
            >
              Back to Subjects
            </Box>
          </Stack>

          <FormControl maxW={{ base: '100%', md: '360px' }}>
            <FormLabel htmlFor="lesson-plans-class">Class</FormLabel>
            <ClassificationSelect
              inputId="lesson-plans-class"
              placeholder="Select a class"
              classifications={classifications}
              selectValue={selectedClassificationId ?? undefined}
              isClearable
              isDisabled={classifications.length === 0}
              onChange={(option: any) =>
                visitWithClassification(
                  option?.value ? Number(option.value) : undefined
                )
              }
            />
            <FormHelperText>
              {classifications.length === 0
                ? 'No classes are assigned to this subject yet.'
                : 'Select a class to view its lesson plans and notes.'}
            </FormHelperText>
          </FormControl>

          {classifications.length === 0 ? (
            <Alert status="info" rounded="md">
              <AlertIcon />
              <Box>
                <Text fontWeight="semibold">No classes available</Text>
                <Text fontSize="sm">
                  Assign this subject to a class before creating or viewing its
                  lesson plans.
                </Text>
              </Box>
            </Alert>
          ) : !selectedClassificationId ? (
            <Alert status="info" rounded="md">
              <AlertIcon />
              <Box>
                <Text fontWeight="semibold">Select a class to continue</Text>
                <Text fontSize="sm">
                  Choose a class above to view lesson plans for {course.title}.
                </Text>
              </Box>
            </Alert>
          ) : lessonPlans.length === 0 ? (
            <Alert status="info" rounded="md">
              <AlertIcon />
              <Box>
                <Text fontWeight="semibold">
                  No lesson plans found for this class
                </Text>
                <Text fontSize="sm">
                  Lesson plans created for this class will appear here.
                </Text>
              </Box>
            </Alert>
          ) : (
            <VStack align="stretch" spacing={4}>
              {lessonPlans.map((lessonPlan) => (
                <LessonPlanCard key={lessonPlan.id} lessonPlan={lessonPlan} />
              ))}
            </VStack>
          )}
        </VStack>
      </Box>
    </DashboardLayout>
  );
}

function LessonPlanCard({ lessonPlan }: { lessonPlan: LessonPlan }) {
  const { instRoute } = useInstitutionRoute();
  const { currentUser } = useSharedProps();
  const isAdmin = useIsAdmin();
  const lessonNote = lessonPlan.lesson_note;
  const canManage =
    isAdmin || lessonPlan.course_teacher?.user_id === currentUser.id;

  return (
    <Box borderWidth="1px" rounded="lg" p={{ base: 4, md: 5 }} shadow="sm">
      <Stack
        direction={{ base: 'column', lg: 'row' }}
        justify="space-between"
        align={{ base: 'stretch', lg: 'start' }}
        gap={4}
      >
        <Box minW={0}>
          <HStack spacing={2} flexWrap="wrap">
            <Badge colorScheme="brand">
              Week {lessonPlan.scheme_of_work?.week_number ?? '—'}
            </Badge>
            <Badge variant="outline">
              {lessonPlan.scheme_of_work?.term ?? 'Term not set'}
            </Badge>
            <Badge colorScheme={lessonNote ? 'green' : 'orange'}>
              {lessonNote ? 'Lesson note attached' : 'No lesson note'}
            </Badge>
          </HStack>
          <Heading as="h2" size="md" mt={2} noOfLines={2}>
            {lessonPlan.scheme_of_work?.topic?.title || 'Untitled topic'}
          </Heading>
        </Box>

        <HStack spacing={2} flexWrap="wrap">
          <Button
            as={InertiaLink}
            href={instRoute('lesson-plans.show', [lessonPlan.id])}
            size="sm"
            variant="outline"
            colorScheme="brand"
          >
            View plan
          </Button>
          {lessonNote ? (
            <Button
              as={InertiaLink}
              href={instRoute('lesson-notes.show', [lessonNote.id])}
              size="sm"
              variant="outline"
              colorScheme="green"
            >
              View note
            </Button>
          ) : canManage ? (
            <Button
              as={InertiaLink}
              href={instRoute('lesson-notes.create', [lessonPlan.id])}
              size="sm"
              colorScheme="brand"
            >
              Create note
            </Button>
          ) : null}
          {canManage && (
            <Button
              as={InertiaLink}
              href={instRoute('lesson-plans.edit', [lessonPlan.id])}
              size="sm"
              variant="ghost"
              colorScheme="brand"
            >
              Edit plan
            </Button>
          )}
        </HStack>
      </Stack>

      <SimpleGrid columns={{ base: 1, sm: 3 }} spacing={3} mt={4}>
        <LessonPlanMeta
          label="Class"
          value={lessonPlan.course_teacher?.classification?.title}
        />
        <LessonPlanMeta
          label="Teacher"
          value={lessonPlan.course_teacher?.user?.full_name}
        />
        <Box>
          <Text fontSize="xs" fontWeight="bold" color="blackAlpha.600">
            LAST UPDATED
          </Text>
          <DateTimeDisplay dateTime={lessonPlan.updated_at} fontSize="sm" />
        </Box>
      </SimpleGrid>

      <Divider my={4} />

      <SimpleGrid columns={{ base: 1, lg: 2 }} spacing={4}>
        <Box>
          <Text fontWeight="bold" mb={3}>
            Lesson plan
          </Text>
          <SimpleGrid columns={{ base: 1, sm: 2 }} spacing={3}>
            <RichTextPreview label="Objective" value={lessonPlan.objective} />
            <RichTextPreview label="Activities" value={lessonPlan.activities} />
            <Box gridColumn={{ base: 'auto', sm: '1 / -1' }}>
              <RichTextPreview label="Content" value={lessonPlan.content} />
            </Box>
          </SimpleGrid>
        </Box>

        <Box borderWidth="1px" rounded="md" p={4} bg="blackAlpha.50">
          <HStack justify="space-between" align="start" mb={3} gap={2}>
            <Text fontWeight="bold">Lesson note</Text>
            {lessonNote && (
              <Badge
                colorScheme={
                  lessonNote.status === NoteStatusType.Published
                    ? 'green'
                    : 'gray'
                }
              >
                {lessonNote.status}
              </Badge>
            )}
          </HStack>
          {lessonNote ? (
            <>
              <Text fontWeight="semibold" noOfLines={2}>
                {lessonNote.title}
              </Text>
              <Box mt={3}>
                <RichTextPreview value={lessonNote.content} />
              </Box>
            </>
          ) : (
            <Text fontSize="sm" color="blackAlpha.700">
              This lesson plan does not have a lesson note yet.
            </Text>
          )}
        </Box>
      </SimpleGrid>
    </Box>
  );
}

function LessonPlanMeta({ label, value }: { label: string; value?: string }) {
  return (
    <Box>
      <Text fontSize="xs" fontWeight="bold" color="blackAlpha.600">
        {label.toUpperCase()}
      </Text>
      <Text fontSize="sm">{value || 'Not available'}</Text>
    </Box>
  );
}

function RichTextPreview({ label, value }: { label?: string; value?: string }) {
  const content = DOMPurify.sanitize(value ?? '');

  return (
    <Box>
      {label && (
        <Text fontSize="xs" fontWeight="bold" color="blackAlpha.600" mb={1}>
          {label.toUpperCase()}
        </Text>
      )}
      {content ? (
        <Box
          fontSize="sm"
          lineHeight="tall"
          maxH="84px"
          overflow="hidden"
          overflowWrap="anywhere"
          sx={{
            '& p': { marginBottom: 2 },
            '& ul, & ol': { paddingLeft: 5 },
            '& img': { maxWidth: '100%', height: 'auto' },
          }}
          dangerouslySetInnerHTML={{ __html: content }}
        />
      ) : (
        <Text fontSize="sm" color="blackAlpha.600">
          Not provided
        </Text>
      )}
    </Box>
  );
}
