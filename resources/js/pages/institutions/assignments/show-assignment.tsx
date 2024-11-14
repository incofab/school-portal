import React from 'react';
import { Assignment } from '@/types/models';
import { FormControl, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useIsStudent from '@/hooks/use-is-student';
import DOMPurify from 'dompurify';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import { preventNativeSubmit } from '@/util/util';
import FormControlBox from '@/components/forms/form-control-box';
import { Editor } from '@tinymce/tinymce-react';
import { FormButton } from '@/components/buttons';

interface Props {
  assignment: Assignment;
}

const tinymceApiKey = import.meta.env.VITE_TINYMCE_API_KEY;

export default function ShowAssignment({ assignment }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const isStudent = useIsStudent();
  const sanitizedContent = DOMPurify.sanitize(assignment.content);

  const webForm = useWebForm({
    assignment_id: assignment.id,
    answer: '',
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      return web.post(instRoute('assignment-submissions.store'), data);
    });
    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.visit(instRoute('assignments.index'));
  };

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading
          title={
            assignment.course?.title +
            ' Assignment - ( ' +
            assignment.max_score +
            ' Marks )'
          }
        />

        <SlabBody>
          <div
            style={{ marginBottom: '30px' }}
            dangerouslySetInnerHTML={{ __html: sanitizedContent }}
          />

          {isStudent && (
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
            >
              <FormControlBox
                title="Enter Your Answer"
                form={webForm as any}
                formKey="content"
                isRequired
              >
                <Editor
                  // onInit={(evt, editor) => (editorRef.current = editor)}
                  apiKey={tinymceApiKey}
                  initialValue=""
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
                  value={webForm.data.answer}
                  onEditorChange={(answer: string) =>
                    webForm.setValue('answer', answer)
                  }
                />
              </FormControlBox>

              <FormControl>
                <FormButton isLoading={webForm.processing} />
              </FormControl>
            </VStack>
          )}
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
