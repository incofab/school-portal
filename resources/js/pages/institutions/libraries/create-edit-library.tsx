import React, { useState } from 'react';
import {
  Alert,
  AlertIcon,
  Box,
  Button,
  FormControl,
  HStack,
  Input,
  Select,
  SimpleGrid,
  Switch,
  Text,
  Textarea,
  VStack,
} from '@chakra-ui/react';
import { Inertia } from '@inertiajs/inertia';
import { MultiValue } from 'react-select';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import FormControlBox from '@/components/forms/form-control-box';
import ClassificationSelect from '@/components/selectors/classification-select';
import CourseSelect from '@/components/selectors/course-select';
import { FormButton } from '@/components/buttons';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useMyToast from '@/hooks/use-my-toast';
import useSharedProps from '@/hooks/use-shared-props';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Library } from '@/types/models';
import { Nullable, SelectOptionType } from '@/types/types';

interface Props {
  library?: Library;
}

const materialTypes = [
  ['document', 'Document'],
  ['pdf', 'PDF'],
  ['image', 'Image'],
  ['video', 'Video'],
  ['audio', 'Audio'],
  ['presentation', 'Presentation'],
  ['spreadsheet', 'Spreadsheet'],
  ['other', 'Other'],
];

export default function CreateEditLibrary({ library }: Props) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const { currentInstitutionUser } = useSharedProps();
  const [file, setFile] = useState<File | null>(null);

  const webForm = useWebForm({
    title: library?.title ?? '',
    course_id: library?.course_id ?? '',
    material_type: library?.material_type ?? 'document',
    source_type: library?.source_type ?? 'upload',
    description: library?.description ?? '',
    external_url: library?.external_url ?? '',
    is_published: library?.is_published ?? true,
    classification_ids: library?.classifications?.map((item) => ({
      label: item.title,
      value: item.id,
    })) as Nullable<MultiValue<SelectOptionType<number>>>,
  });

  const submit = async () => {
    const formData = new FormData();
    formData.append('title', webForm.data.title);
    formData.append('course_id', String(webForm.data.course_id ?? ''));
    formData.append('material_type', webForm.data.material_type);
    formData.append('source_type', webForm.data.source_type);
    formData.append('description', webForm.data.description ?? '');
    formData.append('external_url', webForm.data.external_url ?? '');
    formData.append('is_published', webForm.data.is_published ? '1' : '0');
    formData.append('institution_user_id', String(currentInstitutionUser.id));

    webForm.data.classification_ids?.forEach((item, index) => {
      formData.append(`classification_ids[${index}]`, String(item.value));
    });

    if (file) {
      formData.append('file', file);
    }

    if (library) {
      formData.append('_method', 'PUT');
    }

    const res = await webForm.submit((data, web) =>
      library
        ? web.post(instRoute('libraries.update', [library.id]), formData)
        : web.post(instRoute('libraries.store'), formData)
    );

    if (!handleResponseToast(res)) {
      return;
    }

    Inertia.visit(instRoute('libraries.index'));
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading
            title={`${library ? 'Update' : 'Add'} Library Material`}
          />
          <SlabBody>
            <VStack
              spacing={5}
              as="form"
              align="stretch"
              onSubmit={preventNativeSubmit(submit)}
            >
              <Alert status="info" borderRadius="md">
                <AlertIcon />
                <Text fontSize="sm">
                  Uploaded files must be 1MB or smaller. For larger resources,
                  choose external link and paste the hosted URL.
                </Text>
              </Alert>

              <SimpleGrid columns={{ base: 1, md: 2 }} spacing={4}>
                <FormControlBox
                  form={webForm as any}
                  title="Title"
                  formKey="title"
                  isRequired
                >
                  <Input
                    value={webForm.data.title}
                    onChange={(e) =>
                      webForm.setValue('title', e.currentTarget.value)
                    }
                  />
                </FormControlBox>

                <FormControlBox
                  form={webForm as any}
                  title="Material Type"
                  formKey="material_type"
                  isRequired
                >
                  <Select
                    value={webForm.data.material_type}
                    onChange={(e) =>
                      webForm.setValue('material_type', e.currentTarget.value)
                    }
                  >
                    {materialTypes.map(([value, label]) => (
                      <option key={value} value={value}>
                        {label}
                      </option>
                    ))}
                  </Select>
                </FormControlBox>

                <FormControlBox
                  form={webForm as any}
                  title="Source"
                  formKey="source_type"
                  isRequired
                >
                  <HStack>
                    <Button
                      type="button"
                      size="sm"
                      colorScheme={
                        webForm.data.source_type === 'upload' ? 'brand' : 'gray'
                      }
                      variant={
                        webForm.data.source_type === 'upload'
                          ? 'solid'
                          : 'outline'
                      }
                      onClick={() => webForm.setValue('source_type', 'upload')}
                    >
                      Upload
                    </Button>
                    <Button
                      type="button"
                      size="sm"
                      colorScheme={
                        webForm.data.source_type === 'external'
                          ? 'brand'
                          : 'gray'
                      }
                      variant={
                        webForm.data.source_type === 'external'
                          ? 'solid'
                          : 'outline'
                      }
                      onClick={() =>
                        webForm.setValue('source_type', 'external')
                      }
                    >
                      External Link
                    </Button>
                  </HStack>
                </FormControlBox>

                <FormControlBox
                  form={webForm as any}
                  title="Course [Optional]"
                  formKey="course_id"
                >
                  <CourseSelect
                    selectValue={webForm.data.course_id}
                    isMulti={false}
                    isClearable
                    onChange={(e: any) =>
                      webForm.setValue('course_id', e?.value ?? '')
                    }
                  />
                </FormControlBox>
              </SimpleGrid>

              {webForm.data.source_type === 'upload' ? (
                <FormControlBox
                  form={webForm as any}
                  title="File"
                  formKey="file"
                  isRequired={!library}
                >
                  <Input
                    type="file"
                    p={1}
                    onChange={(e) =>
                      setFile(e.currentTarget.files?.[0] ?? null)
                    }
                  />
                  {library?.file_name && (
                    <Text fontSize="sm" color="gray.600" mt={2}>
                      Current file: {library.file_name}
                    </Text>
                  )}
                </FormControlBox>
              ) : (
                <FormControlBox
                  form={webForm as any}
                  title="External URL"
                  formKey="external_url"
                  isRequired
                >
                  <Input
                    type="url"
                    value={webForm.data.external_url}
                    onChange={(e) =>
                      webForm.setValue('external_url', e.currentTarget.value)
                    }
                  />
                </FormControlBox>
              )}

              <FormControlBox
                form={webForm as any}
                title="Assign to Class(es) [Optional]"
                formKey="classification_ids"
              >
                <ClassificationSelect
                  value={webForm.data.classification_ids}
                  selectValue={webForm.data.classification_ids}
                  isMulti={true}
                  isClearable={true}
                  onChange={(e: any) =>
                    webForm.setValue('classification_ids', e)
                  }
                />
                <Text fontSize="sm" color="gray.600" mt={2}>
                  Leave empty to make this material available to every student
                  in the institution.
                </Text>
              </FormControlBox>

              <FormControlBox
                form={webForm as any}
                title="Description [Optional]"
                formKey="description"
              >
                <Textarea
                  minH="120px"
                  value={webForm.data.description}
                  onChange={(e) =>
                    webForm.setValue('description', e.currentTarget.value)
                  }
                />
              </FormControlBox>

              <Box>
                <HStack>
                  <Switch
                    isChecked={webForm.data.is_published}
                    onChange={(e) =>
                      webForm.setValue('is_published', e.currentTarget.checked)
                    }
                  />
                  <Text>Publish for students</Text>
                </HStack>
              </Box>

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
