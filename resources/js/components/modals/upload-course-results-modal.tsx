import React from 'react';
import { AxiosInstance } from 'axios';
import {
  FormControl,
  Button,
  FormErrorMessage,
  HStack,
  VStack,
  FormLabel,
  Checkbox,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import FileDropper from '@/components/file-dropper';
import FileObject from '@/components/file-dropper/file-object';
import { Nullable, SelectOptionType, TermType } from '@/types/types';
import useIsAdmin from '@/hooks/use-is-admin';
import { FileDropperType } from '@/components/file-dropper/common';
import { CourseTeacher } from '@/types/models';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import CourseTeacherSelect from '../selectors/course-teacher-select';
import FormControlBox from '../forms/form-control-box';
import AcademicSessionSelect from '../selectors/academic-session-select';
import EnumSelect from '../dropdown-select/enum-select';
import useSharedProps from '@/hooks/use-shared-props';

interface Props {
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
  courseTeacher?: CourseTeacher;
}

export default function UploadCourseResultsModal({
  isOpen,
  onSuccess,
  onClose,
  courseTeacher,
}: Props) {
  const isAdmin = useIsAdmin();
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { currentAcademicSession, currentTerm, usesMidTermResult } =
    useSharedProps();
  const webForm = useWebForm({
    academic_session_id: currentAcademicSession,
    term: currentTerm,
    for_mid_term: false,
    files: [] as FileObject[],
    course_teacher_id: {
      label: courseTeacher?.user?.full_name,
      value: courseTeacher?.id,
    } as Nullable<SelectOptionType<number>>,
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web: AxiosInstance) => {
      const formData = new FormData();
      const file = data.files[0] ?? null;
      formData.append('file', file?.file, file?.getNameWithExtension());
      // formData.append('user_id', data.course_teacher_id?.value + '');
      formData.append('academic_session_id', String(data.academic_session_id));
      formData.append('term', data.term);
      formData.append('for_mid_term', data.for_mid_term ? '1' : '0');

      return web.post(
        instRoute('course-results.upload', [data.course_teacher_id!.value]),
        formData
      );
    });

    if (!handleResponseToast(res)) return;

    onClose();
    webForm.reset();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Upload course results'}
      bodyContent={
        <VStack>
          {isAdmin && (
            <FormControl
              isRequired
              isInvalid={!!webForm.errors.course_teacher_id}
            >
              <FormLabel>Teacher</FormLabel>
              <CourseTeacherSelect
                value={webForm.data.course_teacher_id}
                isMulti={false}
                isClearable={true}
                onChange={(e) => webForm.setValue('course_teacher_id', e)}
                required
              />
              <FormErrorMessage>
                {webForm.errors.course_teacher_id}
              </FormErrorMessage>
            </FormControl>
          )}
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
                webForm.setValue('academic_session_id', e.value)
              }
              required
            />
          </FormControlBox>
          <FormControlBox form={webForm as any} title="Term" formKey="term">
            <EnumSelect
              enumData={TermType}
              selectValue={webForm.data.term}
              isClearable={true}
              onChange={(e: any) => webForm.setValue('term', e.value)}
              required
            />
          </FormControlBox>
          {usesMidTermResult && (
            <FormControlBox
              form={webForm as any}
              formKey="for_mid_term"
              title=""
            >
              <Checkbox
                isChecked={webForm.data.for_mid_term}
                onChange={(e) =>
                  webForm.setValue('for_mid_term', e.currentTarget.checked)
                }
              >
                For Mid-Term Result
              </Checkbox>
            </FormControlBox>
          )}
          <FormControl isInvalid={!!webForm.errors.files}>
            <FileDropper
              files={webForm.data.files}
              onChange={(files) => webForm.setValue('files', files)}
              multiple={false}
              accept={[FileDropperType.Excel]}
            />
            <FormErrorMessage>{webForm.errors.files}</FormErrorMessage>
          </FormControl>
        </VStack>
      }
      footerContent={
        <HStack spacing={2}>
          <Button variant={'ghost'} onClick={onClose}>
            Close
          </Button>
          <Button
            colorScheme={'brand'}
            onClick={onSubmit}
            isLoading={webForm.processing}
          >
            Save
          </Button>
        </HStack>
      }
    />
  );
}
