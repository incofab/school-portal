import React from 'react';
import {
  Alert,
  AlertDescription,
  FormControl,
  FormErrorMessage,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { Inertia } from '@inertiajs/inertia';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton, LinkButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { SelectOptionType, TermType } from '@/types/types';
import { Div } from '@/components/semantic';
import FormControlBox from '@/components/forms/form-control-box';
import EnumSelect from '@/components/dropdown-select/enum-select';
import FileObject from '@/components/file-dropper/file-object';
import FileDropper from '@/components/file-dropper';
import { FileDropperType } from '@/components/file-dropper/common';
import AcademicSessionSelect from '@/components/selectors/academic-session-select';
import ClassificationSelect from '@/components/selectors/classification-select';
import { Classification } from '@/types/models';
import useSharedProps from '@/hooks/use-shared-props';

interface Props {
  classifications: Classification[];
}

export default function UploadClassSheet({ classifications }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { currentAcademicSession } = useSharedProps();

  const webForm = useWebForm({
    academic_session_id: {
      label: currentAcademicSession.title,
      value: currentAcademicSession.id,
    } as null | SelectOptionType<number>,
    term: '',
    classification_id: null as null | SelectOptionType<number>,
    files: [] as FileObject[],
  });

  const submit = async () => {
    if (
      !window.confirm(
        `${webForm.data.classification_id?.label}, ${webForm.data.term} term - ${webForm.data.academic_session_id?.label}. Are you sure you want to upload this file?`
      )
    ) {
      return;
    }
    const res = await webForm.submit((data, web) => {
      const formData = new FormData();
      const file = data.files[0] ?? null;
      formData.append('file', file?.file, file?.getNameWithExtension());
      formData.append(
        'academic_session_id',
        String(data.academic_session_id!.value)
      );
      formData.append(
        'classification_id',
        String(data.classification_id!.value)
      );
      formData.append('term', data.term);
      return web.post(instRoute('course-results.class-sheet.upload'), formData);
    });

    if (!handleResponseToast(res)) {
      return;
    }

    Inertia.visit(instRoute('class-result-info.index'));
  };

  return (
    <DashboardLayout>
      <Div>
        <CenteredBox>
          <Slab>
            <SlabHeading title={`Upload Class Results`} />
            <SlabBody>
              <VStack spacing={4} align={'stretch'}>
                <FormControlBox
                  form={webForm as any}
                  title="Academic Session"
                  formKey="academic_session_id"
                >
                  <AcademicSessionSelect
                    selectValue={webForm.data.academic_session_id}
                    isMulti={false}
                    isClearable={true}
                    onChange={(e: any) =>
                      webForm.setValue('academic_session_id', e)
                    }
                    required
                  />
                </FormControlBox>
                <FormControlBox
                  form={webForm as any}
                  title="term"
                  formKey="Select Term"
                >
                  <EnumSelect
                    enumData={TermType}
                    selectValue={webForm.data.term}
                    isMulti={false}
                    isClearable={true}
                    onChange={(e: any) => webForm.setValue('term', e?.value)}
                    required
                  />
                </FormControlBox>
                <FormControlBox
                  form={webForm as any}
                  title="Class"
                  formKey="classification_id"
                >
                  <ClassificationSelect
                    selectValue={webForm.data.classification_id}
                    isMulti={false}
                    isClearable={true}
                    classifications={classifications}
                    onChange={(e: any) =>
                      webForm.setValue('classification_id', e)
                    }
                    required
                  />
                </FormControlBox>
                <Div>
                  <Alert status={'warning'} mb={1}>
                    <AlertDescription>
                      Column <b>A</b> and <b>B</b> are meant to be the student
                      ID and Name. The rest of the columns are meant for the
                      subjects.
                      <br />
                      <b>Note:</b> The title of the subjects columns must match
                      the subject name as it appears on the
                      <LinkButton
                        href={instRoute('courses.index')}
                        variant={'link'}
                        title={'All Subjects'}
                        fontWeight={'extrabold'}
                        colorScheme="red"
                      />{' '}
                      table
                    </AlertDescription>
                  </Alert>
                  <FormControl isInvalid={!!webForm.errors.files}>
                    <FileDropper
                      files={webForm.data.files}
                      onChange={(files) => webForm.setValue('files', files)}
                      multiple={false}
                      accept={[FileDropperType.Excel]}
                    />
                    <FormErrorMessage>{webForm.errors.files}</FormErrorMessage>
                  </FormControl>
                </Div>
                <FormControl>
                  <FormButton isLoading={webForm.processing} onClick={submit} />
                </FormControl>
              </VStack>
            </SlabBody>
          </Slab>
        </CenteredBox>
      </Div>
    </DashboardLayout>
  );
}
