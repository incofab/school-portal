import React, { useState } from 'react';
import { AxiosInstance } from 'axios';
import {
  Badge,
  Box,
  FormControl,
  Button,
  FormErrorMessage,
  HStack,
  VStack,
  FormLabel,
  Radio,
  RadioGroup,
  Text,
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

type ResultMode = 'full-term' | 'mid-term' | '';

export default function UploadCourseResultsModal({
  isOpen,
  onSuccess,
  onClose,
  courseTeacher,
}: Props) {
  const isAdmin = useIsAdmin();
  const { handleResponseToast, toastError } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const {
    currentAcademicSessionId,
    currentTerm,
    usesMidTermResult,
    lockTermSession,
  } = useSharedProps();
  const [selectedResultMode, setSelectedResultMode] = useState<ResultMode>(
    usesMidTermResult ? '' : 'full-term'
  );
  const webForm = useWebForm({
    academic_session_id: currentAcademicSessionId,
    term: currentTerm,
    for_mid_term: false,
    files: [] as FileObject[],
    course_teacher_id: {
      label: courseTeacher?.user?.full_name,
      value: courseTeacher?.id,
    } as Nullable<SelectOptionType<number>>,
  });
  const hasSelectedResultMode = !usesMidTermResult || selectedResultMode !== '';
  const isMidTermSelected = Boolean(webForm.data.for_mid_term);
  const resultModeLabel = isMidTermSelected
    ? 'Mid-Term Result'
    : 'Full Term Result';
  const visibleResultModeLabel = hasSelectedResultMode
    ? resultModeLabel
    : 'Select Result Type';
  const resultModeScheme = isMidTermSelected ? 'yellow' : 'blue';
  const resultModeBg = !hasSelectedResultMode
    ? 'brand.50'
    : isMidTermSelected
    ? 'yellow.50'
    : 'blue.50';
  const resultModeBorder = !hasSelectedResultMode
    ? 'brand.200'
    : isMidTermSelected
    ? 'yellow.200'
    : 'blue.200';

  function resetResultMode() {
    setSelectedResultMode(usesMidTermResult ? '' : 'full-term');
    webForm.setValue('for_mid_term', false);
  }

  function handleClose() {
    resetResultMode();
    onClose();
  }

  const onSubmit = async () => {
    if (!hasSelectedResultMode) {
      toastError('Select full term or mid-term upload before continuing.');
      return;
    }

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

    handleClose();
    webForm.reset();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose: handleClose }}
      headerContent={'Upload course results'}
      bodyContent={
        <VStack>
          {(isAdmin || !courseTeacher) && (
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
                webForm.setValue('academic_session_id', e?.value)
              }
              isDisabled={lockTermSession}
              required
            />
          </FormControlBox>
          <FormControlBox form={webForm as any} title="Term" formKey="term">
            <EnumSelect
              enumData={TermType}
              selectValue={webForm.data.term}
              isClearable={true}
              onChange={(e: any) => webForm.setValue('term', e?.value)}
              isDisabled={lockTermSession}
              required
            />
          </FormControlBox>
          {usesMidTermResult && (
            <Box
              bg={resultModeBg}
              borderColor={resultModeBorder}
              borderWidth={1}
              borderRadius={'md'}
              p={3}
              w={'full'}
            >
              <HStack justify={'space-between'} align={'center'} mb={2}>
                <Text fontWeight={'semibold'} color={'gray.700'}>
                  Uploading {visibleResultModeLabel}
                </Text>
                <Badge
                  colorScheme={
                    hasSelectedResultMode ? resultModeScheme : 'brand'
                  }
                >
                  {hasSelectedResultMode ? resultModeLabel : 'Required'}
                </Badge>
              </HStack>
              <FormControlBox
                form={webForm as any}
                formKey="for_mid_term"
                title="Result Type"
              >
                <RadioGroup
                  value={selectedResultMode}
                  onChange={(value) => {
                    const nextMode = value as ResultMode;
                    const nextForMidTerm = nextMode === 'mid-term';
                    setSelectedResultMode(nextMode);
                    webForm.setValue('for_mid_term', nextForMidTerm);
                    webForm.setValue('files', []);
                  }}
                  isDisabled={webForm.processing}
                >
                  <HStack spacing={6}>
                    <Radio value="full-term">Full term</Radio>
                    <Radio value="mid-term">Mid-term</Radio>
                  </HStack>
                </RadioGroup>
                <Box color={'gray.600'} fontSize={'sm'} mt={1}>
                  {hasSelectedResultMode
                    ? isMidTermSelected
                      ? 'You are uploading only mid-term result scores.'
                      : 'You are uploading only full-term result scores.'
                    : 'Choose the result type first. The upload field will appear after this selection.'}
                </Box>
              </FormControlBox>
            </Box>
          )}
          {hasSelectedResultMode && (
            <FormControl isInvalid={!!webForm.errors.files}>
              <FileDropper
                files={webForm.data.files}
                onChange={(files) => webForm.setValue('files', files)}
                multiple={false}
                accept={[FileDropperType.Excel]}
              />
              <FormErrorMessage>{webForm.errors.files}</FormErrorMessage>
            </FormControl>
          )}
        </VStack>
      }
      footerContent={
        <HStack spacing={2}>
          <Button variant={'ghost'} onClick={handleClose}>
            Close
          </Button>
          {hasSelectedResultMode && (
            <Button
              colorScheme={'brand'}
              onClick={onSubmit}
              isLoading={webForm.processing}
            >
              Save
            </Button>
          )}
        </HStack>
      }
    />
  );
}
