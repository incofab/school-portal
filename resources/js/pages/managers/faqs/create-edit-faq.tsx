import React from 'react';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import CenteredBox from '@/components/centered-box';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { Faq } from '@/types/models';
import { FaqType } from '@/types/types';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import route from '@/util/route';
import {
  FormControl,
  FormLabel,
  HStack,
  Input,
  Select,
  Switch,
  Text,
  VStack,
} from '@chakra-ui/react';
import { preventNativeSubmit } from '@/util/util';
import InputForm from '@/components/forms/input-form';
import FormControlBox from '@/components/forms/form-control-box';
import { FormButton } from '@/components/buttons';
import TinyMceEditor from '@/components/tinymce-editor';

interface Props {
  faq?: Faq;
}

export default function CreateEditFaq({ faq }: Props) {
  const { handleResponseToast } = useMyToast();
  const webForm = useWebForm({
    name: faq?.name ?? '',
    code: faq?.code ?? '',
    type: faq?.type ?? FaqType.Faq,
    description: faq?.description ?? '',
    video_url: faq?.video_url ?? '',
    is_active: faq?.is_active ?? true,
    sort_order:
      faq?.sort_order === null || faq?.sort_order === undefined
        ? ''
        : String(faq.sort_order),
  });

  const submit = async () => {
    const payload = {
      ...webForm.data,
      sort_order:
        webForm.data.sort_order === '' ? null : Number(webForm.data.sort_order),
    };
    const res = await webForm.submit((data, web) => {
      return faq
        ? web.put(route('managers.faqs.update', [faq]), payload)
        : web.post(route('managers.faqs.store'), payload);
    });

    if (!handleResponseToast(res)) {
      return;
    }

    Inertia.visit(route('managers.faqs.index'));
  };

  return (
    <ManagerDashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading
            title={`${faq ? 'Update' : 'Create'} ${
              faq?.type === FaqType.KnowledgeBase
                ? 'Knowledge Base Article'
                : 'FAQ'
            }`}
          />
          <SlabBody>
            <VStack
              spacing={4}
              as="form"
              onSubmit={preventNativeSubmit(submit)}
              align="stretch"
            >
              <InputForm
                form={webForm as any}
                formKey="name"
                title="Question"
              />
              <InputForm
                form={webForm as any}
                formKey="code"
                title="Code"
                placeholder="e.g. login-main-dashboard"
              />
              <FormControlBox form={webForm as any} formKey="type" title="Type">
                <Select
                  value={webForm.data.type}
                  onChange={(e) =>
                    webForm.setValue('type', e.currentTarget.value as FaqType)
                  }
                >
                  <option value={FaqType.Faq}>FAQ</option>
                  <option value={FaqType.KnowledgeBase}>
                    Knowledge Base article
                  </option>
                </Select>
                <Text fontSize="sm" color="gray.500" mt={1}>
                  Choose where this content should appear publicly.
                </Text>
              </FormControlBox>
              <FormControlBox
                form={webForm as any}
                formKey="description"
                title="Answer"
              >
                <TinyMceEditor
                  value={webForm.data.description}
                  onEditorChange={(content: string) =>
                    webForm.setValue('description', content)
                  }
                />
              </FormControlBox>
              <FormControlBox
                form={webForm as any}
                formKey="video_url"
                title="YouTube Video URL"
              >
                <Input
                  value={webForm.data.video_url}
                  onChange={(e) =>
                    webForm.setValue('video_url', e.currentTarget.value)
                  }
                  placeholder="https://www.youtube.com/watch?v=..."
                />
                <Text fontSize="sm" color="gray.500" mt={1}>
                  Optional. Watch, youtu.be, Shorts, embed, and live YouTube
                  URLs are accepted.
                </Text>
              </FormControlBox>
              <InputForm
                form={webForm as any}
                formKey="sort_order"
                title="Sort Order"
                type="number"
                min={0}
                placeholder="Auto"
              />
              <FormControl>
                <HStack justify="space-between">
                  <FormLabel mb={0}>Active</FormLabel>
                  <Switch
                    isChecked={webForm.data.is_active}
                    onChange={(e) =>
                      webForm.setValue('is_active', e.currentTarget.checked)
                    }
                    colorScheme="brand"
                  />
                </HStack>
              </FormControl>
              <FormControl>
                <FormButton isLoading={webForm.processing} />
              </FormControl>
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </ManagerDashboardLayout>
  );
}
