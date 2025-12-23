import React from 'react';
import { HStack, Spinner, Text } from '@chakra-ui/react';
import { Inertia } from '@inertiajs/inertia';
import { CourseTeacher } from '@/types/models';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { Div } from '@/components/semantic';
import MySelect from '@/components/dropdown-select/my-select';

export default function SwitchCourseTeacher({
  courseTeacher,
  teachersCourses,
  selectedCourseTeacherState,
  getUrl,
}: {
  teachersCourses: { [id: number]: CourseTeacher };
  courseTeacher: CourseTeacher;
  getUrl: (courseTeacherId: number) => string;
  selectedCourseTeacherState: [
    CourseTeacher,
    React.Dispatch<React.SetStateAction<CourseTeacher>>
  ];
}) {
  const { instRoute } = useInstitutionRoute();
  const [selectedCourseTeacher, setSelectedCourseTeacher] =
    selectedCourseTeacherState;
  function getValue(ct: CourseTeacher) {
    return {
      label: `${ct.classification?.title} - ${ct.course?.title}`,
      value: ct.id,
    };
  }
  return (
    <Div pt={2} pb={4}>
      <Text>Change Subject</Text>
      <HStack w={'full'} spacing={2}>
        <Div flex={1}>
          <MySelect
            isMulti={false}
            selectValue={getValue(selectedCourseTeacher)}
            getOptions={() =>
              Object.values(teachersCourses).map((ct) => getValue(ct))
            }
            onChange={(e: any) => {
              if (!e || e.value == selectedCourseTeacher.id) return;
              setSelectedCourseTeacher(teachersCourses[e.value]);
              Inertia.visit(getUrl(e.value));
            }}
          />
        </Div>
        {selectedCourseTeacher.id != courseTeacher.id && (
          <Spinner size="md" color="brand.500" />
        )}
      </HStack>
    </Div>
  );
}
