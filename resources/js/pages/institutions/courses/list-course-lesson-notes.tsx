import React from 'react';
import DOMPurify from 'dompurify';
import {
  Alert,
  AlertIcon,
  Badge,
  Box,
  Divider,
  FormControl,
  FormHelperText,
  FormLabel,
  Heading,
  Select,
  SimpleGrid,
  Stack,
  Text,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Inertia } from '@inertiajs/inertia';
import { Classification, Course, LessonNote } from '@/types/models';
import MediaAttachmentsList from '@/components/media-attachments-list';
import DateTimeDisplay from '@/components/date-time-display';
import { NoteStatusType } from '@/types/types';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { InertiaLink } from '@inertiajs/inertia-react';

interface Props {
  course: Course;
  classifications: Classification[];
  selectedClassificationId?: number | null;
  lessonNotes: LessonNote[];
}

export default function ListCourseLessonNotes({
  course,
  classifications,
  selectedClassificationId,
  lessonNotes,
}: Props) {
  const { instRoute } = useInstitutionRoute();

  function visitWithClassification(classificationId?: number) {
    Inertia.visit(instRoute('courses.lesson-notes', [course.id]), {
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
                Lesson Notes · {course.title}
              </Heading>
              <Text color="blackAlpha.700" mt={1}>
                Review all available lesson notes and supporting files for this
                subject.
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
            <FormLabel htmlFor="lesson-notes-class">Class</FormLabel>
            <Select
              id="lesson-notes-class"
              placeholder="Select a class"
              value={selectedClassificationId ?? ''}
              onChange={(event) =>
                visitWithClassification(
                  event.target.value ? Number(event.target.value) : undefined
                )
              }
            >
              {classifications.map((classification) => (
                <option key={classification.id} value={classification.id}>
                  {classification.title}
                </option>
              ))}
            </Select>
            <FormHelperText>
              Select a class to view its lesson notes.
            </FormHelperText>
          </FormControl>

          {!selectedClassificationId ? (
            <Alert status="info" rounded="md">
              <AlertIcon />
              <Box>
                <Text fontWeight="semibold">Select a class to continue</Text>
                <Text fontSize="sm">
                  Choose a class above to view its lesson notes for{' '}
                  {course.title}.
                </Text>
              </Box>
            </Alert>
          ) : lessonNotes.length === 0 ? (
            <Alert status="info" rounded="md">
              <AlertIcon />
              <Box>
                <Text fontWeight="semibold">
                  No lesson notes found for this class
                </Text>
                <Text fontSize="sm">
                  Lesson notes created for this class will appear here.
                </Text>
              </Box>
            </Alert>
          ) : (
            <VStack align="stretch" spacing={5}>
              {lessonNotes.map((lessonNote) => (
                <LessonNoteCard key={lessonNote.id} lessonNote={lessonNote} />
              ))}
            </VStack>
          )}
        </VStack>
      </Box>
    </DashboardLayout>
  );
}

function LessonNoteCard({ lessonNote }: { lessonNote: LessonNote }) {
  const content = DOMPurify.sanitize(lessonNote.content ?? '');
  const term = lessonNote.lesson_plan?.scheme_of_work?.term;
  const topic = lessonNote.lesson_plan?.scheme_of_work?.topic?.title;
  const teacher = lessonNote.course_teacher?.user?.full_name;
  const isPublished = lessonNote.status === NoteStatusType.Published;

  return (
    <Box borderWidth="1px" rounded="lg" p={{ base: 4, md: 6 }} shadow="sm">
      <Stack
        direction={{ base: 'column', md: 'row' }}
        justify="space-between"
        align={{ base: 'start', md: 'center' }}
        gap={3}
      >
        <Box>
          <Heading as="h2" size="md">
            {lessonNote.title}
          </Heading>
          <Text color="blackAlpha.700" mt={1}>
            {topic || 'Lesson note content'}
          </Text>
        </Box>
        <Badge colorScheme={isPublished ? 'green' : 'gray'}>
          {lessonNote.status}
        </Badge>
      </Stack>

      <SimpleGrid columns={{ base: 1, sm: 2, lg: 4 }} spacing={3} mt={5}>
        <LessonNoteMeta
          label="Class"
          value={lessonNote.classification?.title}
        />
        <LessonNoteMeta label="Term" value={term} />
        <LessonNoteMeta label="Teacher" value={teacher} />
        <Box>
          <Text fontSize="xs" fontWeight="bold" color="blackAlpha.600">
            LAST UPDATED
          </Text>
          <DateTimeDisplay dateTime={lessonNote.updated_at} fontSize="sm" />
        </Box>
      </SimpleGrid>

      <Divider my={5} />
      <Text fontWeight="bold" mb={2}>
        Lesson content
      </Text>
      <Box
        lineHeight="tall"
        overflowWrap="anywhere"
        sx={{
          '& img': {
            maxWidth: '100%',
            height: 'auto',
            borderRadius: 'md',
          },
          '& table': {
            display: 'block',
            maxWidth: '100%',
            overflowX: 'auto',
          },
          '& p': { marginBottom: 3 },
        }}
        dangerouslySetInnerHTML={{ __html: content }}
      />

      <Text fontWeight="bold" mt={5} mb={3}>
        Attachments
      </Text>
      <MediaAttachmentsList
        media={lessonNote.media}
        emptyText="No supporting files attached."
      />
    </Box>
  );
}

function LessonNoteMeta({ label, value }: { label: string; value?: string }) {
  return (
    <Box>
      <Text fontSize="xs" fontWeight="bold" color="blackAlpha.600">
        {label.toUpperCase()}
      </Text>
      <Text fontSize="sm">{value || 'Not available'}</Text>
    </Box>
  );
}
