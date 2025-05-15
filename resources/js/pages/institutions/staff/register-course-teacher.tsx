import React from 'react';
import { FormControl, VStack, Divider, Text, HStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { User } from '@/types/models';
import { InstitutionUserType, Nullable, SelectOptionType } from '@/types/types';
import { MultiValue } from 'react-select';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { FormButton } from '@/components/buttons';
import CenteredBox from '@/components/centered-box';
import StaffSelect from '@/components/selectors/staff-select';
import FormControlBox from '@/components/forms/form-control-box';
import CourseSelect from '@/components/selectors/course-select';
import ClassificationSelect from '@/components/selectors/classification-select';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface Props {
  user?: User;
}

export default function RegisterCourseTeacher({ user }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute(); 

  const webForm = useWebForm({
    user_id: user ? { label: user.full_name, value: user.id } : null,
    course_ids: null as Nullable<MultiValue<SelectOptionType<number>>>,
    classification_ids: null as Nullable<MultiValue<SelectOptionType<number>>>,
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(instRoute('course-teachers.store', [data.user_id!.value]), {
        ...data,
        classification_ids: data.classification_ids?.map((item) => item.value),
        course_ids: data.course_ids?.map((item) => item.value),
      })
    );
    if (!handleResponseToast(res)) return;
    Inertia.visit(instRoute('course-teachers.index'));
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading title={'Assign Subject'} />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
            >
              {user ? (
                <>
                  <HStack spacing={4} w={'full'} align={'start'}>
                    <VStack spacing={2} align={'start'}>
                      <Text>Teacher:</Text>
                      <Text>Phone:</Text>
                      <Text>Email:</Text>
                    </VStack>
                    <VStack spacing={2} align={'start'}>
                      <Text fontWeight={'semibold'}>{user.full_name}</Text>
                      <Text fontWeight={'semibold'}>{user.phone}</Text>
                      <Text fontWeight={'semibold'}>{user.email}</Text>
                    </VStack>
                  </HStack>
                  <Divider />
                </>
              ) : (
                <FormControlBox
                  title="Teacher"
                  form={webForm as any}
                  formKey="user_id"
                >
                  <StaffSelect
                    value={webForm.data.user_id}
                    isClearable={true}
                    rolesIn={[InstitutionUserType.Teacher]}
                    onChange={(e) => webForm.setValue('user_id', e)}
                    isMulti={false}
                    required
                  />
                </FormControlBox>
              )}
              <FormControlBox
                form={webForm as any}
                title="Subject"
                formKey="course_ids"
              >
                <CourseSelect
                  onChange={(e: any) => webForm.setValue('course_ids', e)}
                  value={webForm.data.course_ids}
                  isMulti={true}
                  required
                />
              </FormControlBox>
              <FormControlBox
                form={webForm as any}
                title="Class"
                formKey="classification_ids"
              >
                <ClassificationSelect
                  onChange={(e: any) =>
                    webForm.setValue('classification_ids', e)
                  }
                  isMulti={true}
                  isClearable={true}
                  required
                />
              </FormControlBox>
              <FormControl>
                <FormButton isLoading={webForm.processing} />
              </FormControl>
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </DashboardLayout>
  );
}
