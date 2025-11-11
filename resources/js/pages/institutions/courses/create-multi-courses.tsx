import React from 'react';
import {
  Button,
  Divider,
  FormControl,
  HStack,
  Icon,
  IconButton,
  Input,
  SimpleGrid,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { PlusIcon, TrashIcon } from '@heroicons/react/24/solid';
import FormControlBox from '@/components/forms/form-control-box';
import { Div } from '@/components/semantic';

interface Props {}

const newCourse = () => ({
  title: '',
  description: '',
});

export default function CreateMultiCourses({}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    courses: [newCourse()],
  });

  const handleCourseChange = (
    index: number,
    field: keyof ReturnType<typeof newCourse>,
    value: any
  ) => {
    const updatedCourses = webForm.data.courses.map((item, i) => {
      if (i === index) {
        return { ...item, [field]: value };
      }
      return item;
    });
    webForm.setValue('courses', updatedCourses);
  };

  const addCourse = () => {
    webForm.setValue('courses', [...webForm.data.courses, newCourse()]);
  };

  const removeCourse = (index: number) => {
    const newCourses = [...webForm.data.courses];
    newCourses.splice(index, 1);
    webForm.setValue('courses', newCourses);
  };

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      const postData = {
        courses: data.courses.map((course) => ({
          ...course,
          code: course.title,
        })),
      };
      return web.post(instRoute('courses.multi-store'), postData);
    });
    if (!handleResponseToast(res)) return;
    Inertia.visit(instRoute('courses.index'));
  };

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title={`Create Subjects`} />
        <SlabBody>
          <VStack
            spacing={6}
            as={'form'}
            onSubmit={preventNativeSubmit(submit)}
            w={'full'}
          >
            <VStack spacing={6} divider={<Divider />} width={'100%'}>
              {webForm.data.courses.map((course, index) => (
                <Div
                  border={'1px solid'}
                  borderColor={'brand.200'}
                  borderRadius="md"
                  px={4}
                  py={3}
                  w={'full'}
                >
                  <HStack align={'start'}>
                    <SimpleGrid
                      flex={1}
                      columns={{ base: 1, md: 2, lg: 2 }}
                      key={index}
                      spacing={3}
                      justifyContent={'space-between'}
                    >
                      <FormControlBox
                        title="Subject Title"
                        form={webForm as any}
                        formKey={`courses.${index}.title`}
                      >
                        <Input
                          value={course.title}
                          onChange={(e) =>
                            handleCourseChange(index, 'title', e.target.value)
                          }
                          required
                        />
                      </FormControlBox>
                      <FormControlBox
                        title="Description [optional]"
                        form={webForm as any}
                        formKey={`courses.${index}.description`}
                      >
                        <Input
                          value={course.description}
                          onChange={(e) =>
                            handleCourseChange(
                              index,
                              'description',
                              e.target.value
                            )
                          }
                        />
                      </FormControlBox>
                    </SimpleGrid>
                    {webForm.data.courses.length > 1 && (
                      <IconButton
                        aria-label={'Remove Subject'}
                        icon={<Icon as={TrashIcon} />}
                        onClick={() => removeCourse(index)}
                        variant={'ghost'}
                        colorScheme={'red'}
                      />
                    )}
                  </HStack>
                </Div>
              ))}
            </VStack>
            <Button
              variant={'ghost'}
              colorScheme={'brand'}
              onClick={addCourse}
              leftIcon={<Icon as={PlusIcon} />}
            >
              Add another subject
            </Button>
            <FormControl>
              <FormButton isLoading={webForm.processing} size={'md'}>
                Submit
              </FormButton>
            </FormControl>
          </VStack>
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
