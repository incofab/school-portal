import React from 'react';
import { AxiosInstance } from 'axios';
import {
  FormControl,
  Button,
  FormErrorMessage,
  HStack,
  VStack,
  FormLabel,
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

interface Props {
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
  courseTeacher?: CourseTeacher;
}

function UploadCourseResultsModal({
  isOpen,
  onSuccess,
  onClose,
  courseTeacher,
}: Props) {
  const isAdmin = useIsAdmin();
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    files: [] as FileObject[],
    course_teacher_id: {
      label: courseTeacher?.user?.full_name,
      value: courseTeacher?.id,
    } as Nullable<SelectOptionType<number>>,
    academic_session_id: '',
    term: '',
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web: AxiosInstance) => {
      const formData = new FormData();
      const file = data.files[0] ?? null;
      formData.append('file', file?.file, file?.getNameWithExtension());
      formData.append('user_id', data.course_teacher_id?.value + '');
      formData.append('academic_session_id', data.academic_session_id);
      formData.append('term', data.term);

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

export default UploadCourseResultsModal;
