import React from 'react';
import DOMPurify from 'dompurify';
import {
  Alert,
  AlertIcon,
  Badge,
  Box,
  Divider,
  Heading,
  SimpleGrid,
  Stack,
  Text,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Course, LessonNote } from '@/types/models';
import MediaAttachmentsList from '@/components/media-attachments-list';
import DateTimeDisplay from '@/components/date-time-display';
import { NoteStatusType } from '@/types/types';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { InertiaLink } from '@inertiajs/inertia-react';

interface Props {
  course: Course;
  lessonNotes: LessonNote[];
}

export default function ListCourseLessonNotes({ course, lessonNotes }: Props) {
  const { instRoute } = useInstitutionRoute();

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

          {lessonNotes.length === 0 ? (
            <Alert status="info" rounded="md">
              <AlertIcon />
              <Box>
                <Text fontWeight="semibold">No lesson notes found</Text>
                <Text fontSize="sm">
                  Lesson notes created for {course.title} will appear here.
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
