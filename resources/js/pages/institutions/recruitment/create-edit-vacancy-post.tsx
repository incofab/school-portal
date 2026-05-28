import React from 'react';
import {
  Checkbox,
  FormControl,
  SimpleGrid,
  Textarea,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import FormControlBox from '@/components/forms/form-control-box';
import InputForm from '@/components/forms/input-form';
import { FormButton } from '@/components/buttons';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { VacancyPost } from '@/types/models';

interface Props {
  vacancyPost?: VacancyPost;
}

export default function CreateEditVacancyPost({ vacancyPost }: Props) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const form = useWebForm({
    title: vacancyPost?.title ?? '',
    department: vacancyPost?.department ?? '',
    employment_type: vacancyPost?.employment_type ?? 'full-time',
    location: vacancyPost?.location ?? '',
    summary: vacancyPost?.summary ?? '',
    description: vacancyPost?.description ?? '',
    requirements: vacancyPost?.requirements ?? '',
    responsibilities: vacancyPost?.responsibilities ?? '',
    salary_range: vacancyPost?.salary_range ?? '',
    positions_available: vacancyPost?.positions_available ?? 1,
    application_deadline: vacancyPost?.application_deadline ?? '',
    is_published: vacancyPost?.is_published ?? false,
  });

  async function submit() {
    const res = await form.submit((data, web) =>
      vacancyPost
        ? web.put(instRoute('vacancy-posts.update', [vacancyPost.id]), data)
        : web.post(instRoute('vacancy-posts.store'), data)
    );

    if (!handleResponseToast(res)) return;

    Inertia.visit(instRoute('vacancy-posts.index'));
  }

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title={`${vacancyPost ? 'Update' : 'Create'} Vacancy Post`}
        />
        <SlabBody>
          <VStack spacing={4} as="form" onSubmit={preventNativeSubmit(submit)}>
            <SimpleGrid columns={{ base: 1, md: 2 }} gap={4} width="full">
              <InputForm form={form as any} formKey="title" title="Job Title" />
              <InputForm
                form={form as any}
                formKey="department"
                title="Department"
              />
              <InputForm
                form={form as any}
                formKey="employment_type"
                title="Employment Type"
              />
              <InputForm
                form={form as any}
                formKey="location"
                title="Location"
              />
              <InputForm
                form={form as any}
                formKey="salary_range"
                title="Salary Range"
              />
              <InputForm
                form={form as any}
                formKey="positions_available"
                title="Positions Available"
                type="number"
              />
              <InputForm
                form={form as any}
                formKey="application_deadline"
                title="Application Deadline"
                type="date"
              />
            </SimpleGrid>
            <FormControlBox
              form={form as any}
              title="Short Summary [Optional]"
              formKey="summary"
            >
              <Textarea
                minH="90px"
                value={form.data.summary}
                onChange={(e) =>
                  form.setValue('summary', e.currentTarget.value)
                }
              />
            </FormControlBox>
            <FormControlBox
              form={form as any}
              title="Job Description"
              formKey="description"
            >
              <Textarea
                minH="140px"
                value={form.data.description}
                onChange={(e) =>
                  form.setValue('description', e.currentTarget.value)
                }
                required
              />
            </FormControlBox>
            <FormControlBox
              form={form as any}
              title="Requirements [Optional]"
              formKey="requirements"
            >
              <Textarea
                minH="120px"
                value={form.data.requirements}
                onChange={(e) =>
                  form.setValue('requirements', e.currentTarget.value)
                }
              />
            </FormControlBox>
            <FormControlBox
              form={form as any}
              title="Responsibilities [Optional]"
              formKey="responsibilities"
            >
              <Textarea
                minH="120px"
                value={form.data.responsibilities}
                onChange={(e) =>
                  form.setValue('responsibilities', e.currentTarget.value)
                }
              />
            </FormControlBox>
            <FormControlBox form={form as any} formKey="is_published" title="">
              <Checkbox
                isChecked={form.data.is_published}
                onChange={(e) =>
                  form.setValue('is_published', e.currentTarget.checked)
                }
              >
                Publish vacancy so the public can apply
              </Checkbox>
            </FormControlBox>
            <FormControl>
              <FormButton isLoading={form.processing} />
            </FormControl>
          </VStack>
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
