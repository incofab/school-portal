import React from 'react';
import {
  Box,
  Button,
  Checkbox,
  Divider,
  FormControl,
  Grid,
  HStack,
  Icon,
  IconButton,
  Input,
  SimpleGrid,
  Stack,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { ClassificationGroup } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import StaffSelect from '@/components/selectors/staff-select';
import { InstitutionUserType } from '@/types/types';
import ClassificationGroupSelect from '@/components/selectors/classification-group-select';
import useModalToggle from '@/hooks/use-modal-toggle';
import { PlusIcon, TrashIcon } from '@heroicons/react/24/solid';
import { Div } from '@/components/semantic';

interface Props {
  classificationGroups: ClassificationGroup[];
}

const newClassification = () => ({
  title: '',
  has_equal_subjects: true,
  form_teacher_id: null,
  classification_group_id: '',
});

export default function CreateMultiClassifications({
  classificationGroups,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();

  const webForm = useWebForm({
    classifications: [newClassification()],
  });

  const handleClassificationChange = (
    index: number,
    field: keyof ReturnType<typeof newClassification>,
    value: any
  ) => {
    const updatedClassifications = webForm.data.classifications.map(
      (item, i) => {
        if (i === index) {
          return { ...item, [field]: value };
        }
        return item;
      }
    );
    webForm.setValue('classifications', updatedClassifications);
  };

  const addClassification = () => {
    webForm.setValue('classifications', [
      ...webForm.data.classifications,
      newClassification(),
    ]);
  };

  const removeClassification = (index: number) => {
    const newClassifications = [...webForm.data.classifications];
    newClassifications.splice(index, 1);
    webForm.setValue('classifications', newClassifications);
  };

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      const postData = {
        classifications: data.classifications.map((classification) => ({
          ...classification,
          form_teacher_id: (classification.form_teacher_id as any)?.value,
        })),
      };
      return web.post(instRoute('classifications.multi-store'), postData);
    });

    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.visit(instRoute('classifications.index'));
  };

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title={'Create Classes'} />
        <SlabBody>
          <VStack
            spacing={6}
            as={'form'}
            onSubmit={preventNativeSubmit(submit)}
          >
            <VStack spacing={6} divider={<Divider />} width={'100%'}>
              {webForm.data.classifications.map((classification, index) => (
                <Div
                  border={'1px solid'}
                  borderColor={'brand.200'}
                  borderRadius="md"
                  px={4}
                  py={3}
                >
                  <HStack>
                    <SimpleGrid
                      columns={{ base: 2, md: 3, lg: 3 }}
                      key={index}
                      spacing={3}
                      justifyContent={'space-between'}
                    >
                      <FormControlBox
                        title="Class Group"
                        form={webForm as any}
                        formKey={`classifications.${index}.classification_group_id`}
                      >
                        <ClassificationGroupSelect
                          classificationGroups={classificationGroups}
                          selectValue={classification.classification_group_id}
                          isMulti={false}
                          isClearable={true}
                          onChange={(e: any) =>
                            handleClassificationChange(
                              index,
                              'classification_group_id',
                              e?.value
                            )
                          }
                          required
                        />
                      </FormControlBox>

                      <FormControlBox
                        title="Class Title"
                        form={webForm as any}
                        formKey={`classifications.${index}.title`}
                      >
                        <Input
                          value={classification.title}
                          onChange={(e) =>
                            handleClassificationChange(
                              index,
                              'title',
                              e.target.value
                            )
                          }
                          required
                        />
                      </FormControlBox>

                      <FormControlBox
                        title="Form Teacher"
                        form={webForm as any}
                        formKey={`classifications.${index}.form_teacher_id`}
                      >
                        <StaffSelect
                          value={classification.form_teacher_id}
                          isClearable={true}
                          rolesIn={[InstitutionUserType.Teacher]}
                          onChange={(e) =>
                            handleClassificationChange(
                              index,
                              'form_teacher_id',
                              e
                            )
                          }
                          isMulti={false}
                        />
                      </FormControlBox>

                      <FormControl>
                        <Checkbox
                          isChecked={classification.has_equal_subjects}
                          onChange={(e) =>
                            handleClassificationChange(
                              index,
                              'has_equal_subjects',
                              e.currentTarget.checked
                            )
                          }
                          size={'md'}
                          colorScheme="brand"
                        >
                          All students offer the same number of subjects
                        </Checkbox>
                      </FormControl>
                    </SimpleGrid>

                    {webForm.data.classifications.length > 1 && (
                      <IconButton
                        aria-label={'Remove Class'}
                        icon={<Icon as={TrashIcon} />}
                        onClick={() => removeClassification(index)}
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
              onClick={addClassification}
              leftIcon={<Icon as={PlusIcon} />}
            >
              Add another class
            </Button>

            <FormControl>
              <FormButton isLoading={webForm.processing} size={'lg'}>
                Submit
              </FormButton>
            </FormControl>
          </VStack>
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
