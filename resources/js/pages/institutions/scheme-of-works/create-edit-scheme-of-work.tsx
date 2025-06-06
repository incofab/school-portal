import React from 'react';
import { Checkbox, FormControl, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { Topic, SchemeOfWork } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import { Editor } from '@tinymce/tinymce-react';
import { Input } from '@chakra-ui/react';
import TopicSelect from '@/components/selectors/topic-select';
import EnumSelect from '@/components/dropdown-select/enum-select';
import { TermType } from '@/types/types';
import useSharedProps from '@/hooks/use-shared-props';

interface Props {
  schemeOfWork?: SchemeOfWork;
  topicId?: number;
}

const tinymceApiKey = import.meta.env.VITE_TINYMCE_API_KEY;

export default function CreateOrUpdateTopic({ schemeOfWork, topicId }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { currentTerm } = useSharedProps();

  const webForm = useWebForm({
    topic_id: schemeOfWork ? schemeOfWork.topic_id : topicId,
    term: schemeOfWork ? schemeOfWork.term : currentTerm,
    week_number: schemeOfWork ? schemeOfWork.week_number : '',
    learning_objectives: schemeOfWork ? schemeOfWork.learning_objectives : '',
    resources: schemeOfWork ? schemeOfWork.resources : '',

    is_used_by_institution_group: schemeOfWork
      ? schemeOfWork.institution_group_id !== null
        ? true
        : false
      : false,
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      return schemeOfWork
        ? web.put(instRoute('scheme-of-works.update', [schemeOfWork]), data)
        : web.post(instRoute('scheme-of-works.store'), data);
    });

    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.visit(instRoute('inst-topics.show', [webForm.data.topic_id]));
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading
            title={`${schemeOfWork ? 'Update' : 'Create'} Scheme of Work`}
          />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
            >
              <FormControlBox
                form={webForm as any}
                title="Term"
                formKey="term"
                isRequired
              >
                <EnumSelect
                  selectValue={webForm.data.term}
                  enumData={TermType}
                  onChange={(e: any) => webForm.setValue('term', e?.value)}
                />
              </FormControlBox>

              <FormControlBox
                form={webForm as any}
                title="Week Number"
                formKey="week_number"
                isRequired
              >
                <Input
                  type="number"
                  onChange={(e) =>
                    webForm.setValue('week_number', e.currentTarget.value)
                  }
                  value={webForm.data.week_number}
                  required
                />
              </FormControlBox>

              <FormControlBox
                title="Learning Objective"
                form={webForm as any}
                formKey="learning_objectives"
                isRequired
              >
                <Editor
                  // onInit={(evt, editor) => (editorRef.current = editor)}
                  apiKey={tinymceApiKey}
                  initialValue={schemeOfWork?.learning_objectives}
                  init={{
                    height: 300,
                    menubar: true,
                    plugins: [
                      'advlist autolink lists link image charmap print preview anchor',
                      'searchreplace visualblocks code fullscreen',
                      'insertdatetime media table paste code help wordcount',
                    ],
                    toolbar:
                      'undo redo | formatselect | bold italic backcolor | alignleft aligncenter  alignright alignjustify | bullist numlist outdent indent |  removeformat',
                    content_style:
                      'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
                  }}
                  value={webForm.data.learning_objectives}
                  onEditorChange={(learningObjectives: string) =>
                    webForm.setValue('learning_objectives', learningObjectives)
                  }
                />
              </FormControlBox>

              <FormControlBox
                title="Resources"
                form={webForm as any}
                formKey="resources"
                isRequired
              >
                <Editor
                  apiKey={tinymceApiKey}
                  initialValue={schemeOfWork?.resources}
                  init={{
                    height: 200,
                    menubar: true,
                    plugins: [
                      'advlist autolink lists link image charmap print preview anchor',
                      'searchreplace visualblocks code fullscreen',
                      'insertdatetime media table paste code help wordcount',
                    ],
                    toolbar:
                      'undo redo | formatselect | bold italic backcolor | alignleft aligncenter  alignright alignjustify | bullist numlist outdent indent |  removeformat',
                    content_style:
                      'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
                  }}
                  value={webForm.data.resources}
                  onEditorChange={(resources: string) =>
                    webForm.setValue('resources', resources)
                  }
                />
              </FormControlBox>

              <FormControl>
                <Checkbox
                  isChecked={webForm.data.is_used_by_institution_group}
                  onChange={(e) =>
                    webForm.setData({
                      ...webForm.data,
                      is_used_by_institution_group: e.currentTarget.checked,
                    })
                  }
                  size={'md'}
                  colorScheme="brand"
                >
                  Applies to entire Institution Group.
                </Checkbox>
              </FormControl>

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
