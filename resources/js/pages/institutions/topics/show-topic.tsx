import React from 'react';
import { SchemeOfWork, LessonPlan, Topic, LessonNote } from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import { CollapsibleSlab, SlabBody } from '@/components/slab';
import DOMPurify from 'dompurify';
import { Div } from '@/components/semantic';
import { Button, Heading, Stack } from '@chakra-ui/react';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { LinkButton } from '@/components/buttons';
import useIsAdmin from '@/hooks/use-is-admin';
import useIsTeacher from '@/hooks/use-is-teacher';
import { InertiaLink } from '@inertiajs/inertia-react';
import ButtonSwitch from '@/components/button-switch';
import { NoteStatusType } from '@/types/types';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';

interface Props {
  topic: Topic;
  assignedCourseIds?: number[];
}

export default function ShowTopic({ topic, assignedCourseIds }: Props) {
  const { instRoute } = useInstitutionRoute();
  const isAdmin = useIsAdmin();
  const isTeacher = useIsTeacher();
  const schemeOfWorks = topic.scheme_of_works!;

  return (
    <DashboardLayout>
      <CollapsibleSlab
        title={`Topic :: ${topic.title}`}
        {...((isAdmin || isTeacher) && {
          addNewRoute: instRoute('inst-topics.create-or-edit'),
          editRoute: instRoute('inst-topics.create-or-edit', [topic.id]),
          deleteRoute: instRoute('inst-topics.destroy', [topic.id]),
        })}
      >
        <SlabBody>
          <Div
            style={{ marginBottom: '30px' }}
            dangerouslySetInnerHTML={{
              __html: DOMPurify.sanitize(topic.description),
            }}
          />
        </SlabBody>
      </CollapsibleSlab>

      {isAdmin && isTeacher && schemeOfWorks.length === 0 && (
        <Button
          colorScheme="brand"
          variant={'solid'}
          size={'sm'}
          as={InertiaLink}
          href={instRoute('scheme-of-works.create', [topic.id])}
        >
          Create Scheme of Work
        </Button>
      )}

      {schemeOfWorks.map((schemeOfWork, index) => (
        <React.Fragment key={schemeOfWork.id}>
          <CollapsibleSlab
            collapsed={true}
            key={index}
            title={
              schemeOfWork
                ? `Scheme of Work ::  (Week ${schemeOfWork.week_number})`
                : 'Scheme of Work'
            }
            {...((isAdmin || isTeacher) && {
              addNewRoute: instRoute('scheme-of-works.create', [topic.id]),

              ...(schemeOfWork && {
                editRoute: instRoute('scheme-of-works.edit', [schemeOfWork.id]),
                deleteRoute: instRoute('scheme-of-works.destroy', [
                  schemeOfWork.id,
                ]),
              }),
            })}
          >
            <SlabBody>
              {schemeOfWork && (
                <>
                  <Heading size={'sm'} fontWeight={'bold'} paddingBottom="10px">
                    LEARNING OBJECTIVES ::
                  </Heading>
                  <Div
                    style={{ marginBottom: '30px' }}
                    dangerouslySetInnerHTML={{
                      __html: DOMPurify.sanitize(
                        schemeOfWork.learning_objectives
                      ),
                    }}
                  />
                  <Heading size={'sm'} fontWeight={'bold'} paddingBottom="10px">
                    RESOURCES ::
                  </Heading>
                  <Div
                    style={{ marginBottom: '30px' }}
                    dangerouslySetInnerHTML={{
                      __html: DOMPurify.sanitize(schemeOfWork.resources),
                    }}
                  />
                </>
              )}
            </SlabBody>
          </CollapsibleSlab>
          {schemeOfWork.lesson_plans!.length > 0
            ? schemeOfWork.lesson_plans!.map((lessonPlan, index) => (
                <React.Fragment key={lessonPlan.id || index}>
                  <LessonPlanDisplay
                    schemeOfWork={schemeOfWork}
                    lessonPlan={lessonPlan}
                    assignedCourseIds={assignedCourseIds}
                    index={index}
                  />
                </React.Fragment>
              ))
            : schemeOfWork && (
                <CollapsibleSlab
                  collapsed={true}
                  title="Lesson Plan"
                  addNewRoute={instRoute('lesson-plans.create', [
                    schemeOfWork.id,
                  ])}
                >
                  <SlabBody />
                </CollapsibleSlab>
              )}
        </React.Fragment>
      ))}
    </DashboardLayout>
  );
}

function LessonPlanDisplay({
  lessonPlan,
  schemeOfWork,
  assignedCourseIds,
  index,
}: {
  lessonPlan: LessonPlan;
  schemeOfWork: SchemeOfWork;
  assignedCourseIds?: number[];
  index: number;
}) {
  const { instRoute } = useInstitutionRoute();
  const isAdmin = useIsAdmin();
  const isTeacher = useIsTeacher();

  const toggleStatusForm = useWebForm({});
  const { handleResponseToast } = useMyToast();

  async function toggleStatus(obj: LessonNote) {
    if (toggleStatusForm.processing) {
      return;
    }
    if (
      !window.confirm('Are you sure you want to change the publish status?')
    ) {
      return;
    }
    const res = await toggleStatusForm.submit((data, web) =>
      web.post(instRoute('lesson-notes.toggle-publish', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload();
  }

  return (
    <>
      <CollapsibleSlab
        collapsed={true}
        title={'Lesson Plan ' + (index + 1)}
        rightElement={
          (isAdmin || isTeacher) &&
          // (isTeacher && assignedCourseIds?.includes(lessonPlan.course_teacher_id))) &&
          !lessonPlan.lesson_note && (
            <LinkButton
              href={instRoute('lesson-notes.create', [lessonPlan.id])}
              title={'Create Lesson Note'}
              variant={'outline'}
            />
          )
        }
        addNewRoute={instRoute('lesson-plans.create', [schemeOfWork?.id])}
        {...((isAdmin || isTeacher) && {
          // (isTeacher && assignedCourseIds?.includes(lessonPlan.course_teacher_id))) && {
          editRoute: instRoute('lesson-plans.edit', [lessonPlan.id]),
          deleteRoute: instRoute('lesson-plans.destroy', [lessonPlan.id]),
        })}
      >
        <SlabBody>
          {lessonPlan && (
            <>
              <Heading size={'sm'} fontWeight={'bold'} paddingBottom="10px">
                OBJECTIVE ::
              </Heading>
              <Div
                style={{ marginBottom: '30px' }}
                dangerouslySetInnerHTML={{
                  __html: DOMPurify.sanitize(lessonPlan.objective),
                }}
              />

              <Heading size={'sm'} fontWeight={'bold'} paddingBottom="10px">
                ACTIVITIES ::
              </Heading>
              <Div
                style={{ marginBottom: '30px' }}
                dangerouslySetInnerHTML={{
                  __html: DOMPurify.sanitize(lessonPlan.activities),
                }}
              />

              <Heading size={'sm'} fontWeight={'bold'} paddingBottom="10px">
                CONTENT ::
              </Heading>
              <Div
                style={{ marginBottom: '30px' }}
                dangerouslySetInnerHTML={{
                  __html: DOMPurify.sanitize(lessonPlan.content),
                }}
              />
            </>
          )}
        </SlabBody>
      </CollapsibleSlab>

      {/* Additional rendering for lessonPlan.lesson_note */}
      {lessonPlan.lesson_note && (
        <CollapsibleSlab
          collapsed={true}
          title={'Lesson Note for Lesson Plan ' + (index + 1)}
          {...((isAdmin || isTeacher) && {
            // (isTeacher && assignedCourseIds?.includes(lessonPlan.course_teacher_id))) && {
            editRoute: instRoute('lesson-notes.edit', [
              lessonPlan.lesson_note.id,
            ]),
            deleteRoute: instRoute('lesson-notes.destroy', [
              lessonPlan.lesson_note.id,
            ]),
          })}
        >
          <SlabBody>
            <Stack
              direction={{ base: 'column', md: 'row' }}
              justifyContent="space-between"
              alignItems={{ base: 'flex-start', md: 'top' }}
            >
              <Heading size={'sm'} fontWeight={'bold'} paddingBottom="50px">
                TITLE :: {lessonPlan.lesson_note.title}
              </Heading>
              <ButtonSwitch
                items={[
                  {
                    label: 'Draft',
                    value: NoteStatusType.Draft,
                    onClick: isAdmin
                      ? () => toggleStatus(lessonPlan.lesson_note!)
                      : undefined,
                  },
                  {
                    label: 'Publish',
                    value: NoteStatusType.Published,
                    onClick: isAdmin
                      ? () => toggleStatus(lessonPlan.lesson_note!)
                      : undefined,
                  },
                ]}
                value={lessonPlan.lesson_note.status}
                _disabled={toggleStatusForm.processing ? 'disabled' : ''}
              />
            </Stack>

            <Heading size={'sm'} fontWeight={'bold'} paddingBottom="10px">
              CONTENT ::
            </Heading>
            <Div
              style={{ marginBottom: '30px' }}
              dangerouslySetInnerHTML={{
                __html: DOMPurify.sanitize(lessonPlan.lesson_note.content),
              }}
            />
          </SlabBody>
        </CollapsibleSlab>
      )}
    </>
  );
}
