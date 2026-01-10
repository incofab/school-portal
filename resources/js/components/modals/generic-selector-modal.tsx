import React from 'react';
import { Button, Checkbox, HStack, VStack } from '@chakra-ui/react';
import GenericModal from '@/components/generic-modal';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { preventNativeSubmit } from '@/util/util';
import FormControlBox from '@/components/forms/form-control-box';
import AcademicSessionSelect from '@/components/selectors/academic-session-select';
import ClassificationSelect from '@/components/selectors/classification-select';
import StudentSelect from '@/components/selectors/student-select';
import CourseSelect from '@/components/selectors/course-select';
import EnumSelect from '@/components/dropdown-select/enum-select';
import { TermType } from '@/types/types';
import useSharedProps from '@/hooks/use-shared-props';

export type ReportFilterFieldKey =
  | 'classification'
  | 'academicSession'
  | 'term'
  | 'student'
  | 'course'
  | 'forMidTerm';

export interface ReportFilterFieldConfig {
  key: ReportFilterFieldKey;
  label?: string;
  isRequired?: boolean;
}

export interface ReportFilterValues {
  [key: string]: any;
}

interface Props {
  isOpen: boolean;
  onClose(): void;
  title: string;
  fields: ReportFilterFieldConfig[];
  submitLabel?: string;
  initialValues?: Partial<ReportFilterValues>;
  onSubmit(values: ReportFilterValues): void;
}

export default function GenericSelectorModal({
  isOpen,
  onClose,
  title,
  fields,
  submitLabel,
  initialValues,
  onSubmit,
}: Props) {
  const { toastError } = useMyToast();
  const { currentAcademicSessionId, currentTerm, usesMidTermResult } =
    useSharedProps();
  const webForm = useWebForm({
    classification: '',
    academicSession: currentAcademicSessionId ?? '',
    term: currentTerm ?? '',
    student: null,
    course: '',
    forMidTerm: false,
    ...(initialValues ?? {}),
  });

  const normalizedFields = fields.filter((field) => {
    if (field.key === 'forMidTerm' && !usesMidTermResult) {
      return false;
    }
    return true;
  });

  const fieldConfigByKey = normalizedFields.reduce(
    (acc, field) => ({ ...acc, [field.key]: field }),
    {} as Record<ReportFilterFieldKey, ReportFilterFieldConfig>
  );

  const getValue = (key: ReportFilterFieldKey) => {
    const value = (webForm.data as any)[key];
    if (value && typeof value === 'object' && 'value' in value) {
      return value.value;
    }
    return value;
  };

  const isMissingRequired = (key: ReportFilterFieldKey) => {
    const value = getValue(key);
    return value === '' || value === null || value === undefined;
  };

  const validateRequiredFields = () => {
    for (const field of normalizedFields) {
      if (field.isRequired && isMissingRequired(field.key)) {
        toastError(`Select ${field.label ?? field.key}`);
        return false;
      }
    }
    return true;
  };

  const handleSubmit = () => {
    if (!validateRequiredFields()) {
      return;
    }
    const payload: ReportFilterValues = {};
    normalizedFields.forEach((field) => {
      payload[field.key] = getValue(field.key);
    });
    onSubmit(payload);
    onClose();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={title}
      bodyContent={
        <VStack>
          {fieldConfigByKey.academicSession && (
            <FormControlBox
              form={webForm as any}
              title={
                fieldConfigByKey.academicSession.label ?? 'Academic Session'
              }
              formKey="academicSession"
              isRequired={fieldConfigByKey.academicSession.isRequired}
            >
              <AcademicSessionSelect
                selectValue={webForm.data.academicSession}
                isMulti={false}
                isClearable={true}
                onChange={(e: any) =>
                  webForm.setValue('academicSession', e?.value)
                }
                required={fieldConfigByKey.academicSession.isRequired}
              />
            </FormControlBox>
          )}
          {fieldConfigByKey.classification && (
            <FormControlBox
              form={webForm as any}
              title={fieldConfigByKey.classification.label ?? 'Class'}
              formKey="classification"
              isRequired={fieldConfigByKey.classification.isRequired}
            >
              <ClassificationSelect
                selectValue={webForm.data.classification}
                isMulti={false}
                isClearable={true}
                onChange={(e: any) =>
                  webForm.setValue('classification', e?.value)
                }
                required={fieldConfigByKey.classification.isRequired}
              />
            </FormControlBox>
          )}
          {fieldConfigByKey.term && (
            <FormControlBox
              form={webForm as any}
              title={fieldConfigByKey.term.label ?? 'Term'}
              formKey="term"
              isRequired={fieldConfigByKey.term.isRequired}
            >
              <EnumSelect
                enumData={TermType}
                selectValue={webForm.data.term}
                isMulti={false}
                isClearable={true}
                onChange={(e: any) => webForm.setValue('term', e?.value)}
                required={fieldConfigByKey.term.isRequired}
              />
            </FormControlBox>
          )}
          {fieldConfigByKey.student && (
            <FormControlBox
              form={webForm as any}
              title={fieldConfigByKey.student.label ?? 'Student'}
              formKey="student"
              isRequired={fieldConfigByKey.student.isRequired}
            >
              <StudentSelect
                value={webForm.data.student}
                isMulti={false}
                isClearable={true}
                onChange={(e: any) => webForm.setValue('student', e)}
                classification={
                  webForm.data.classification
                    ? Number(webForm.data.classification)
                    : undefined
                }
                required={fieldConfigByKey.student.isRequired}
              />
            </FormControlBox>
          )}
          {fieldConfigByKey.course && (
            <FormControlBox
              form={webForm as any}
              title={fieldConfigByKey.course.label ?? 'Subject'}
              formKey="course"
              isRequired={fieldConfigByKey.course.isRequired}
            >
              <CourseSelect
                selectValue={webForm.data.course}
                isMulti={false}
                isClearable={true}
                onChange={(e: any) => webForm.setValue('course', e?.value)}
                required={fieldConfigByKey.course.isRequired}
              />
            </FormControlBox>
          )}
          {fieldConfigByKey.forMidTerm && (
            <FormControlBox
              form={webForm as any}
              formKey="forMidTerm"
              title={fieldConfigByKey.forMidTerm.label ?? ''}
            >
              <Checkbox
                isChecked={webForm.data.forMidTerm}
                onChange={(e) =>
                  webForm.setValue('forMidTerm', e.currentTarget.checked)
                }
              >
                {fieldConfigByKey.forMidTerm.label ?? 'For Mid-Term Result'}
              </Checkbox>
            </FormControlBox>
          )}
        </VStack>
      }
      footerContent={
        <HStack spacing={2}>
          <Button variant={'ghost'} onClick={onClose}>
            Close
          </Button>
          <Button
            colorScheme={'brand'}
            onClick={preventNativeSubmit(handleSubmit)}
            isLoading={webForm.processing}
          >
            {submitLabel ?? 'Submit'}
          </Button>
        </HStack>
      }
    />
  );
}
