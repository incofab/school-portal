import React from 'react';
import {
  Checkbox,
  FormControl,
  FormErrorMessage,
  FormLabel,
  HStack,
  Input,
  Select,
  Text,
  Textarea,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import FormControlBox from '@/components/forms/form-control-box';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { Inertia } from '@inertiajs/inertia';
import { Designation, Classification } from '@/types/models';
import { preventNativeSubmit } from '@/util/util';

interface Props {
  designation?: Designation;
  classifications: Classification[];
  canManageStaffDesignations: boolean;
}

export default function CreateEditDesignation({
  designation,
  classifications,
  canManageStaffDesignations,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const form = useWebForm({
    title: designation?.title ?? '',
    description: designation?.description ?? '',
    is_for_students: designation?.is_for_students ?? true,
    scope: designation?.scope ?? 'institution',
    max_holders: designation?.max_holders ?? 1,
    is_active: designation?.is_active ?? true,
    eligible_class_ids:
      designation?.eligible_classes?.map((item) => item.id) ?? [],
  });
  async function submit() {
    const result = await form.submit((data, web) =>
      designation
        ? web.put(instRoute('designations.update', [designation]), data)
        : web.post(instRoute('designations.store'), data)
    );
    if (handleResponseToast(result))
      Inertia.visit(instRoute('designations.index'));
  }
  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading
            title={`${designation ? 'Edit' : 'Create'} designation`}
          />
          <SlabBody>
            <VStack
              as="form"
              align="stretch"
              spacing={4}
              onSubmit={preventNativeSubmit(submit)}
            >
              <FormControlBox title="Title" form={form as any} formKey="title">
                <Input
                  value={form.data.title}
                  onChange={(e) => form.setValue('title', e.target.value)}
                  isRequired
                />
              </FormControlBox>
              <FormControlBox
                title="Description (optional)"
                form={form as any}
                formKey="description"
              >
                <Textarea
                  value={form.data.description}
                  onChange={(e) => form.setValue('description', e.target.value)}
                />
              </FormControlBox>
              <FormControlBox
                title="Designation type"
                form={form as any}
                formKey="is_for_students"
              >
                <Select
                  value={form.data.is_for_students ? 'true' : 'false'}
                  isDisabled={!canManageStaffDesignations}
                  onChange={(e) => {
                    const student = e.target.value === 'true';
                    form.setValue('is_for_students', student);
                    if (!student) {
                      form.setValue('eligible_class_ids', []);
                      form.setValue('scope', 'institution');
                    }
                  }}
                >
                  <option value="true">Student designation</option>
                  {canManageStaffDesignations && (
                    <option value="false">Staff designation</option>
                  )}
                </Select>
              </FormControlBox>
              {form.data.is_for_students && (
                <FormControl isInvalid={!!form.errors.eligible_class_ids}>
                  <FormLabel>Eligible classes</FormLabel>
                  <VStack
                    align="start"
                    maxH="220px"
                    overflowY="auto"
                    borderWidth="1px"
                    borderRadius="md"
                    p={3}
                  >
                    {classifications.map((item) => (
                      <Checkbox
                        key={item.id}
                        isChecked={form.data.eligible_class_ids.includes(
                          item.id
                        )}
                        onChange={(e) =>
                          form.setValue(
                            'eligible_class_ids',
                            e.target.checked
                              ? [...form.data.eligible_class_ids, item.id]
                              : form.data.eligible_class_ids.filter(
                                  (id) => id !== item.id
                                )
                          )
                        }
                      >
                        {item.title}
                      </Checkbox>
                    ))}
                  </VStack>
                  <Text fontSize="sm" color="gray.600" mt={1}>
                    Eligible classes define which students can be assigned.
                    Select at least one class.
                  </Text>
                  <FormErrorMessage>
                    {form.errors.eligible_class_ids}
                  </FormErrorMessage>
                </FormControl>
              )}
              <FormControlBox
                title="Holder limit scope"
                form={form as any}
                formKey="scope"
              >
                <Select
                  value={form.data.scope}
                  isDisabled={!form.data.is_for_students}
                  onChange={(e) =>
                    form.setValue(
                      'scope',
                      e.target.value as 'institution' | 'classification'
                    )
                  }
                >
                  <option value="institution">Institution-wide</option>
                  <option value="classification">
                    Separately within each eligible class
                  </option>
                </Select>
                <Text fontSize="sm" color="gray.600" mt={1}>
                  {form.data.scope === 'classification'
                    ? 'Each eligible class has its own holder limit.'
                    : 'The holder limit applies across the institution for each academic session.'}
                </Text>
              </FormControlBox>
              <FormControlBox
                title="Maximum holders per scope and session"
                form={form as any}
                formKey="max_holders"
              >
                <Input
                  type="number"
                  min={1}
                  value={form.data.max_holders}
                  onChange={(e) =>
                    form.setValue('max_holders', Number(e.target.value))
                  }
                />
              </FormControlBox>
              <FormControl>
                <Checkbox
                  isChecked={form.data.is_active}
                  onChange={(e) => form.setValue('is_active', e.target.checked)}
                >
                  Active and available for new assignments
                </Checkbox>
              </FormControl>
              <HStack justify="end">
                <FormButton isLoading={form.processing} />
              </HStack>
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </DashboardLayout>
  );
}
