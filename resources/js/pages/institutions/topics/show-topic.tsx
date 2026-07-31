import React from 'react';
import {
  Alert,
  AlertIcon,
  Badge,
  Box,
  BoxProps,
  Button,
  Divider,
  HStack,
  Heading,
  Icon,
  IconButton,
  SimpleGrid,
  Stack,
  Tab,
  TabList,
  TabPanel,
  TabPanels,
  Tabs,
  Text,
  VStack,
  Wrap,
  WrapItem,
  useColorModeValue,
} from '@chakra-ui/react';
import {
  DocumentPlusIcon,
  EyeIcon,
  PencilIcon,
  TrashIcon,
} from '@heroicons/react/24/outline';
import { CheckCircleIcon } from '@heroicons/react/24/solid';
import { InertiaLink } from '@inertiajs/inertia-react';
import { Inertia } from '@inertiajs/inertia';
import DOMPurify from 'dompurify';
import DashboardLayout from '@/layout/dashboard-layout';
import { Div } from '@/components/semantic';
import DestructivePopover from '@/components/destructive-popover';
import MediaAttachmentsList from '@/components/media-attachments-list';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useIsAdmin from '@/hooks/use-is-admin';
import useIsTeacher from '@/hooks/use-is-teacher';
import useMyToast from '@/hooks/use-my-toast';
import useWebForm from '@/hooks/use-web-form';
import { LessonNote, LessonPlan, SchemeOfWork, Topic } from '@/types/models';
import { NoteStatusType } from '@/types/types';
import { ucFirst } from '@/util/util';

interface Props {
  topic: Topic;
  assignedCourseIds?: number[];
}

export default function ShowTopic({ topic, assignedCourseIds = [] }: Props) {
  const isAdmin = useIsAdmin();
  const isTeacher = useIsTeacher();
  const canManage = isAdmin || isTeacher;
  const schemeOfWorks = topic.scheme_of_works ?? [];
  const lessonPlans = schemeOfWorks.flatMap(
    (schemeOfWork) => schemeOfWork.lesson_plans ?? []
  );
  const lessonNotes = lessonPlans
    .map((lessonPlan) => lessonPlan.lesson_note)
    .filter(Boolean) as LessonNote[];

  return (
    <DashboardLayout>
      <Box px={{ base: 3, md: 6 }} py={4}>
        <VStack align={'stretch'} spacing={5}>
          <TopicHeader topic={topic} canManage={canManage} isAdmin={isAdmin} />

          <Tabs colorScheme="brand" variant="enclosed" isLazy>
            <TabList overflowX={'auto'} overflowY={'hidden'}>
              <Tab flexShrink={0}>Overview</Tab>
              <Tab flexShrink={0}>Schemes & Plans</Tab>
              <Tab flexShrink={0}>Notes & Files</Tab>
            </TabList>
            <TabPanels>
              <TabPanel px={0}>
                <OverviewPanel topic={topic} />
              </TabPanel>
              <TabPanel px={0}>
                <SchemesPanel
                  topic={topic}
                  schemeOfWorks={schemeOfWorks}
                  assignedCourseIds={assignedCourseIds}
                  canManage={canManage}
                  isAdmin={isAdmin}
                />
              </TabPanel>
              <TabPanel px={0}>
                <NotesPanel
                  lessonPlans={lessonPlans}
                  lessonNotes={lessonNotes}
                  canManage={canManage}
                  isAdmin={isAdmin}
                />
              </TabPanel>
            </TabPanels>
          </Tabs>
        </VStack>
      </Box>
    </DashboardLayout>
  );
}

function TopicHeader({
  topic,
  canManage,
  isAdmin,
}: {
  topic: Topic;
  canManage: boolean;
  isAdmin: boolean;
}) {
  const { instRoute } = useInstitutionRoute();

  return (
    <Box>
      <Stack
        direction={{ base: 'column', lg: 'row' }}
        align={{ base: 'stretch', lg: 'start' }}
        justify={'space-between'}
        gap={4}
      >
        <VStack align={'start'} spacing={3}>
          <Wrap spacing={2}>
            <WrapItem>
              <Badge colorScheme="brand">
                {topic.parent_topic ? 'Sub Topic' : 'Topic'}
              </Badge>
            </WrapItem>
            {topic.course && (
              <WrapItem>
                <Badge>{topic.course.title}</Badge>
              </WrapItem>
            )}
            {topic.classification_group && (
              <WrapItem>
                <Badge>{topic.classification_group.title}</Badge>
              </WrapItem>
            )}
            {topic.parent_topic && (
              <WrapItem>
                <Badge variant="outline">
                  Parent: {topic.parent_topic.title}
                </Badge>
              </WrapItem>
            )}
          </Wrap>
          <Heading size={{ base: 'md', md: 'lg' }}>{topic.title}</Heading>
          <Text color={'blackAlpha.700'}>
            Review curriculum coverage, planning records, lesson notes, and
            attachments for this topic.
          </Text>
        </VStack>

        {canManage && (
          <Wrap justify={{ base: 'start', lg: 'end' }} spacing={2}>
            <WrapItem>
              <Button
                as={InertiaLink}
                href={instRoute('inst-topics.create-or-edit')}
                leftIcon={<Icon as={DocumentPlusIcon} />}
                size="sm"
                variant="outline"
              >
                New Topic
              </Button>
            </WrapItem>
            <WrapItem>
              <Button
                as={InertiaLink}
                href={instRoute('inst-topics.create-or-edit', [topic.id])}
                leftIcon={<Icon as={PencilIcon} />}
                size="sm"
                colorScheme="brand"
              >
                Edit
              </Button>
            </WrapItem>
            {isAdmin && (
              <WrapItem>
                <DeleteButton
                  route={instRoute('inst-topics.destroy', [topic.id])}
                  label="Delete this topic?"
                />
              </WrapItem>
            )}
          </Wrap>
        )}
      </Stack>
      <Divider mt={5} />
    </Box>
  );
}

function OverviewPanel({ topic }: { topic: Topic }) {
  return (
    <SimpleGrid columns={{ base: 1, lg: 3 }} spacing={4}>
      <Section
        title="Topic Details"
        gridColumn={{ base: 'auto', lg: 'span 2' }}
      >
        <ContentBlock
          html={topic.description}
          emptyText="No description added."
        />
      </Section>
      <Section title="Context">
        <VStack align={'stretch'} spacing={3}>
          <MetaItem
            label="Class Group"
            value={topic.classification_group?.title}
          />
          <MetaItem label="Subject" value={topic.course?.title} />
          <MetaItem label="Parent Topic" value={topic.parent_topic?.title} />
          <MetaItem
            label="Institution Group"
            value={topic.institution_group_id ? 'Shared' : 'Institution only'}
          />
        </VStack>
      </Section>
    </SimpleGrid>
  );
}

function SchemesPanel({
  topic,
  schemeOfWorks,
  assignedCourseIds,
  canManage,
  isAdmin,
}: {
  topic: Topic;
  schemeOfWorks: SchemeOfWork[];
  assignedCourseIds: number[];
  canManage: boolean;
  isAdmin: boolean;
}) {
  const { instRoute } = useInstitutionRoute();

  if (schemeOfWorks.length === 0) {
    return (
      <EmptyState
        title="No scheme of work yet"
        description="Create a scheme to define the term, week, objectives, resources, and lesson plans for this topic."
        action={
          canManage ? (
            <Button
              as={InertiaLink}
              href={instRoute('scheme-of-works.create', [topic.id])}
              size="sm"
              colorScheme="brand"
            >
              Create Scheme of Work
            </Button>
          ) : null
        }
      />
    );
  }

  return (
    <VStack align={'stretch'} spacing={4}>
      {canManage && (
        <HStack justify={'flex-end'}>
          <Button
            as={InertiaLink}
            href={instRoute('scheme-of-works.create', [topic.id])}
            leftIcon={<Icon as={DocumentPlusIcon} />}
            size="sm"
            variant="outline"
          >
            Add Scheme
          </Button>
        </HStack>
      )}
      {schemeOfWorks.map((schemeOfWork) => (
        <Section
          key={schemeOfWork.id}
          title={`Week ${schemeOfWork.week_number}`}
          rightElement={
            <RecordActions
              canEdit={canManage}
              canDelete={isAdmin}
              editRoute={instRoute('scheme-of-works.edit', [schemeOfWork.id])}
              deleteRoute={instRoute('scheme-of-works.destroy', [
                schemeOfWork.id,
              ])}
              deleteLabel="Delete this scheme of work?"
            />
          }
        >
          <Wrap mb={4} spacing={2}>
            <WrapItem>
              <Badge colorScheme="brand">
                {ucFirst(String(schemeOfWork.term))} Term
              </Badge>
            </WrapItem>
            <WrapItem>
              <Badge>
                {schemeOfWork.lesson_plans?.length ?? 0} lesson plans
              </Badge>
            </WrapItem>
          </Wrap>

          <SimpleGrid columns={{ base: 1, lg: 2 }} spacing={4}>
            <Box>
              <Text fontWeight={'semibold'} mb={2}>
                Learning Objectives
              </Text>
              <ContentBlock
                html={schemeOfWork.learning_objectives}
                emptyText="No learning objectives added."
              />
            </Box>
            <Box>
              <Text fontWeight={'semibold'} mb={2}>
                Resources
              </Text>
              <ContentBlock
                html={schemeOfWork.resources}
                emptyText="No resources added."
              />
            </Box>
          </SimpleGrid>

          <Divider my={4} />
          <LessonPlanList
            schemeOfWork={schemeOfWork}
            assignedCourseIds={assignedCourseIds}
            canManage={canManage}
            isAdmin={isAdmin}
          />
        </Section>
      ))}
    </VStack>
  );
}

function LessonPlanList({
  schemeOfWork,
  assignedCourseIds,
  canManage,
  isAdmin,
}: {
  schemeOfWork: SchemeOfWork;
  assignedCourseIds: number[];
  canManage: boolean;
  isAdmin: boolean;
}) {
  const { instRoute } = useInstitutionRoute();
  const lessonPlans = schemeOfWork.lesson_plans ?? [];

  if (lessonPlans.length === 0) {
    return (
      <EmptyState
        title="No lesson plan yet"
        description="Create a lesson plan to document objectives, activities, content, and supporting files."
        action={
          canManage ? (
            <Button
              as={InertiaLink}
              href={instRoute('lesson-plans.create', [schemeOfWork.id])}
              size="sm"
              colorScheme="brand"
            >
              Create Lesson Plan
            </Button>
          ) : null
        }
      />
    );
  }

  return (
    <VStack align={'stretch'} spacing={3}>
      <HStack justify={'space-between'} align={'center'}>
        <Text fontWeight={'semibold'}>Lesson Plans</Text>
        {canManage && (
          <Button
            as={InertiaLink}
            href={instRoute('lesson-plans.create', [schemeOfWork.id])}
            size="sm"
            variant="outline"
          >
            Add Lesson Plan
          </Button>
        )}
      </HStack>

      {lessonPlans.map((lessonPlan, index) => (
        <Box
          key={lessonPlan.id}
          borderWidth={'1px'}
          borderColor={'gray.200'}
          rounded={'md'}
          p={4}
        >
          <Stack
            direction={{ base: 'column', md: 'row' }}
            justify={'space-between'}
            gap={3}
          >
            <VStack align={'start'} spacing={1}>
              <HStack>
                <Badge>Plan {index + 1}</Badge>
                {lessonPlan.lesson_note ? (
                  <Badge colorScheme="green">Lesson note ready</Badge>
                ) : (
                  <Badge colorScheme="orange">No lesson note</Badge>
                )}
              </HStack>
              <Text fontWeight={'semibold'}>
                {lessonPlan.course_teacher?.user?.full_name ??
                  'No teacher selected'}
              </Text>
              <Text fontSize={'sm'} color={'blackAlpha.700'}>
                {lessonPlan.course_teacher?.classification?.title ??
                  'Class not available'}
              </Text>
            </VStack>
            <Wrap spacing={2}>
              {(isAdmin ||
                assignedCourseIds.includes(lessonPlan.course_teacher_id)) &&
                !lessonPlan.lesson_note && (
                  <WrapItem>
                    <Button
                      as={InertiaLink}
                      href={instRoute('lesson-notes.create', [lessonPlan.id])}
                      size="sm"
                      colorScheme="brand"
                    >
                      Create Lesson Note
                    </Button>
                  </WrapItem>
                )}
              <WrapItem>
                <Button
                  as={InertiaLink}
                  href={instRoute('lesson-plans.show', [lessonPlan.id])}
                  leftIcon={<Icon as={EyeIcon} />}
                  size="sm"
                  variant="outline"
                >
                  View
                </Button>
              </WrapItem>
              {canManage && (
                <WrapItem>
                  <IconButton
                    as={InertiaLink}
                    href={instRoute('lesson-plans.edit', [lessonPlan.id])}
                    aria-label="Edit lesson plan"
                    icon={<Icon as={PencilIcon} />}
                    size="sm"
                    variant="ghost"
                  />
                </WrapItem>
              )}
              {isAdmin && (
                <WrapItem>
                  <DeleteButton
                    route={instRoute('lesson-plans.destroy', [lessonPlan.id])}
                    label="Delete this lesson plan?"
                    iconOnly
                  />
                </WrapItem>
              )}
            </Wrap>
          </Stack>

          <SimpleGrid columns={{ base: 1, lg: 3 }} spacing={4} mt={4}>
            <PlanPreview title="Objective" html={lessonPlan.objective} />
            <PlanPreview title="Activities" html={lessonPlan.activities} />
            <PlanPreview title="Content" html={lessonPlan.content} />
          </SimpleGrid>

          <Box mt={4}>
            <Text fontWeight={'semibold'} mb={2}>
              Files
            </Text>
            <MediaAttachmentsList
              media={lessonPlan.media}
              emptyText="No lesson plan files uploaded."
            />
          </Box>
        </Box>
      ))}
    </VStack>
  );
}

function NotesPanel({
  lessonPlans,
  lessonNotes,
  canManage,
  isAdmin,
}: {
  lessonPlans: LessonPlan[];
  lessonNotes: LessonNote[];
  canManage: boolean;
  isAdmin: boolean;
}) {
  const { instRoute } = useInstitutionRoute();

  if (lessonPlans.length === 0) {
    return (
      <EmptyState
        title="No lesson plans available"
        description="Lesson notes and files appear here after lesson plans are created."
      />
    );
  }

  if (lessonNotes.length === 0) {
    return (
      <Alert status="info">
        <AlertIcon />
        Lesson plans exist for this topic, but no lesson notes have been
        created.
      </Alert>
    );
  }

  return (
    <SimpleGrid columns={{ base: 1, xl: 2 }} spacing={4}>
      {lessonNotes.map((lessonNote) => (
        <Section
          key={lessonNote.id}
          title={lessonNote.title}
          rightElement={
            <RecordActions
              canEdit={canManage}
              canDelete={isAdmin}
              editRoute={instRoute('lesson-notes.edit', [lessonNote.id])}
              deleteRoute={instRoute('lesson-notes.destroy', [lessonNote.id])}
              deleteLabel="Delete this lesson note?"
            />
          }
        >
          <Stack
            direction={{ base: 'column', md: 'row' }}
            justify={'space-between'}
            align={{ base: 'start', md: 'center' }}
            mb={4}
          >
            <Badge
              colorScheme={
                lessonNote.status === NoteStatusType.Published
                  ? 'green'
                  : 'orange'
              }
            >
              {lessonNote.status}
            </Badge>
            <HStack>
              <Button
                as={InertiaLink}
                href={instRoute('lesson-notes.show', [lessonNote.id])}
                leftIcon={<Icon as={EyeIcon} />}
                size="sm"
                variant="outline"
              >
                View
              </Button>
              {isAdmin && <PublishToggle lessonNote={lessonNote} />}
            </HStack>
          </Stack>

          <ContentBlock
            html={lessonNote.content}
            emptyText="No content added."
          />

          <Box mt={4}>
            <Text fontWeight={'semibold'} mb={2}>
              Files
            </Text>
            <MediaAttachmentsList
              media={lessonNote.media}
              emptyText="No lesson note files uploaded."
            />
          </Box>
        </Section>
      ))}
    </SimpleGrid>
  );
}

function PublishToggle({ lessonNote }: { lessonNote: LessonNote }) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const toggleForm = useWebForm({});
  const isPublished = lessonNote.status === NoteStatusType.Published;

  async function toggle(onClose: () => void) {
    const res = await toggleForm.submit((data, web) =>
      web.post(instRoute('lesson-notes.toggle-publish', [lessonNote.id]))
    );
    onClose();
    if (!handleResponseToast(res)) return;
    Inertia.reload({ preserveScroll: true });
  }

  return (
    <DestructivePopover
      label={`Are you sure you want to ${
        isPublished ? 'move this note back to draft' : 'publish this note'
      }?`}
      positiveButtonLabel={isPublished ? 'Move to Draft' : 'Publish'}
      onConfirm={toggle}
      isLoading={toggleForm.processing}
    >
      <Button
        leftIcon={<Icon as={CheckCircleIcon} />}
        size="sm"
        colorScheme={isPublished ? 'orange' : 'green'}
        variant="outline"
      >
        {isPublished ? 'Move to Draft' : 'Publish'}
      </Button>
    </DestructivePopover>
  );
}

function RecordActions({
  canEdit,
  canDelete,
  editRoute,
  deleteRoute,
  deleteLabel,
}: {
  canEdit: boolean;
  canDelete: boolean;
  editRoute: string;
  deleteRoute: string;
  deleteLabel: string;
}) {
  if (!canEdit && !canDelete) {
    return null;
  }

  return (
    <HStack spacing={1}>
      {canEdit && (
        <IconButton
          as={InertiaLink}
          href={editRoute}
          aria-label="Edit record"
          icon={<Icon as={PencilIcon} />}
          size="sm"
          variant="ghost"
          colorScheme="brand"
        />
      )}
      {canDelete && (
        <DeleteButton route={deleteRoute} label={deleteLabel} iconOnly />
      )}
    </HStack>
  );
}

function DeleteButton({
  route,
  label,
  iconOnly,
}: {
  route: string;
  label: string;
  iconOnly?: boolean;
}) {
  const deleteForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

  async function deleteRecord(onClose: () => void) {
    const res = await deleteForm.submit((data, web) => web.delete(route));
    onClose();
    if (!handleResponseToast(res)) return;
    Inertia.reload({ preserveScroll: true });
  }

  return (
    <DestructivePopover
      label={label}
      onConfirm={deleteRecord}
      isLoading={deleteForm.processing}
    >
      {iconOnly ? (
        <IconButton
          aria-label="Delete record"
          icon={<Icon as={TrashIcon} />}
          size="sm"
          variant="ghost"
          colorScheme="red"
        />
      ) : (
        <Button
          leftIcon={<Icon as={TrashIcon} />}
          size="sm"
          colorScheme="red"
          variant="outline"
        >
          Delete
        </Button>
      )}
    </DestructivePopover>
  );
}

function Section({
  title,
  rightElement,
  children,
  ...props
}: {
  title: string;
  rightElement?: React.ReactNode;
  children: React.ReactNode;
} & BoxProps) {
  return (
    <Box
      borderWidth={'1px'}
      borderColor={useColorModeValue('gray.200', 'gray.700')}
      bg={useColorModeValue('white', 'gray.800')}
      rounded={'md'}
      p={{ base: 4, md: 5 }}
      {...props}
    >
      <HStack justify={'space-between'} align={'start'} mb={4}>
        <Heading size={'sm'}>{title}</Heading>
        {rightElement}
      </HStack>
      {children}
    </Box>
  );
}

function PlanPreview({ title, html }: { title: string; html?: string }) {
  return (
    <Box>
      <Text fontWeight={'semibold'} mb={2}>
        {title}
      </Text>
      <ContentBlock
        html={html}
        emptyText={`No ${title.toLowerCase()} added.`}
      />
    </Box>
  );
}

function ContentBlock({
  html,
  emptyText,
}: {
  html?: string | null;
  emptyText: string;
}) {
  const cleanedHtml = DOMPurify.sanitize(html ?? '');

  if (!cleanedHtml || cleanedHtml === 'NA') {
    return <Text color={'blackAlpha.700'}>{emptyText}</Text>;
  }

  return (
    <Div
      className="curriculum-rich-content"
      dangerouslySetInnerHTML={{ __html: cleanedHtml }}
    />
  );
}

function MetaItem({ label, value }: { label: string; value?: string | null }) {
  return (
    <Box>
      <Text fontSize={'sm'} color={'blackAlpha.700'}>
        {label}
      </Text>
      <Text fontWeight={'semibold'}>{value || '-'}</Text>
    </Box>
  );
}

function EmptyState({
  title,
  description,
  action,
}: {
  title: string;
  description: string;
  action?: React.ReactNode;
}) {
  return (
    <Box
      borderWidth={'1px'}
      borderStyle={'dashed'}
      borderColor={'gray.300'}
      rounded={'md'}
      p={6}
      textAlign={'center'}
    >
      <VStack spacing={3}>
        <Heading size={'sm'}>{title}</Heading>
        <Text color={'blackAlpha.700'} maxW={'560px'}>
          {description}
        </Text>
        {action}
      </VStack>
    </Box>
  );
}
