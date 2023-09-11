import React from 'react';
import DashboardLayout from '@/layout/dashboard-layout';
import { Event, Student, User } from '@/types/models';
import CreateExamComponent from './component/create-exam-component';

interface Props {
  event: Event;
  examable_type: string;
  student: Student;
}

export default function CreateExam({ event, student, examable_type }: Props) {
  // const { handleResponseToast } = useMyToast();
  // const { instRoute } = useInstitutionRoute();
  // const [selectedEventCourseables, setSelectedEventCourseables] = useState<{
  //   [key: number]: EventCourseable;
  // }>();

  // const webForm = useWebForm({
  //   examable_type: examable_type,
  //   examable_id: user.id,
  // });

  // const submit = async () => {
  //   const res = await webForm.submit((data, web) => {
  //     return web.post(instRoute('exams.store', [event.id]), {
  //       ...data,
  //       courseables: Object.entries(selectedEventCourseables ?? {}).map(
  //         ([key, eventCourseable]) => ({
  //           courseable_type: eventCourseable.courseable_type,
  //           courseable_id: eventCourseable.courseable_id,
  //         })
  //       ),
  //     });
  //   });
  //   if (!handleResponseToast(res)) return;
  //   Inertia.visit(instRoute('display-exam-page', [res.data.exam.exam_no]));
  // };

  return (
    <DashboardLayout>
      <CreateExamComponent
        event={event}
        examable={student}
        examable_type={examable_type}
      />
      {/* <CenteredBox>
        <Slab>
          <SlabHeading title={`Start Exam`} />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
              align={'stretch'}
            >
              {event.event_courseables?.map((eventCourseable) => (
                <Checkbox
                  key={eventCourseable.id}
                  checked={
                    selectedEventCourseables &&
                    Boolean(selectedEventCourseables[eventCourseable.id])
                  }
                  onChange={(e) =>
                    setSelectedEventCourseables({
                      ...selectedEventCourseables,
                      [eventCourseable.id]: eventCourseable,
                    })
                  }
                >{`${eventCourseable.courseable?.course?.title} - ${eventCourseable.courseable?.session}`}</Checkbox>
              ))}
              <FormControl>
                <FormButton isLoading={webForm.processing} />
              </FormControl>
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox> */}
    </DashboardLayout>
  );
}
