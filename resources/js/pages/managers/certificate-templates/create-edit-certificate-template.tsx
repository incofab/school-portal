import React from 'react';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import CenteredBox from '@/components/centered-box';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import FormControlBox from '@/components/forms/form-control-box';
import InputForm from '@/components/forms/input-form';
import { FormButton } from '@/components/buttons';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import route from '@/util/route';
import {
  FormControl,
  FormLabel,
  Select,
  Switch,
  Text,
  Textarea,
  VStack,
} from '@chakra-ui/react';
import { preventNativeSubmit } from '@/util/util';
import { CertificateTemplate } from '@/types/models';
interface Props {
  template?: CertificateTemplate;
  designs: Record<string, string>;
}

export default function CreateEditCertificateTemplate({
  template,
  designs,
}: Props) {
  const form = useWebForm({
    slug: template?.slug ?? '',
    name: template?.name ?? '',
    template_path: template?.template_path ?? Object.keys(designs)[0] ?? '',
    description: template?.description ?? '',
    is_active: template?.is_active ?? true,
  });
  const { handleResponseToast } = useMyToast();
  async function submit() {
    const result = await form.submit((data, web) =>
      template
        ? web.put(
            route('managers.certificate-templates.update', [template]),
            data
          )
        : web.post(route('managers.certificate-templates.store'), data)
    );
    if (handleResponseToast(result))
      Inertia.visit(route('managers.certificate-templates.index'));
  }
  return (
    <ManagerDashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading
            title={`${template ? 'Edit' : 'Create'} certificate design`}
          />
          <SlabBody>
            <VStack
              as="form"
              align="stretch"
              spacing={4}
              onSubmit={preventNativeSubmit(submit)}
            >
              <InputForm
                form={form as any}
                formKey="name"
                title="Design name"
              />
              <InputForm form={form as any} formKey="slug" title="Slug" />
              <FormControlBox
                form={form as any}
                formKey="template_path"
                title="Design layout"
              >
                <Select
                  value={form.data.template_path}
                  onChange={(e) =>
                    form.setValue('template_path', e.target.value)
                  }
                >
                  {Object.entries(designs).map(([path, label]) => (
                    <option key={path} value={path}>
                      {label}
                    </option>
                  ))}
                </Select>
                <Text color="gray.500" fontSize="sm" mt={1}>
                  Choose one of the built-in certificate designs.
                </Text>
              </FormControlBox>
              <FormControlBox
                form={form as any}
                formKey="description"
                title="Description"
              >
                <Textarea
                  value={form.data.description}
                  onChange={(e) => form.setValue('description', e.target.value)}
                />
              </FormControlBox>
              <FormControl>
                <FormLabel>Available to institutions</FormLabel>
                <Switch
                  isChecked={form.data.is_active}
                  onChange={(e) => form.setValue('is_active', e.target.checked)}
                />
              </FormControl>
              <FormButton isLoading={form.processing}>
                {template ? 'Save design' : 'Create design'}
              </FormButton>
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </ManagerDashboardLayout>
  );
}
