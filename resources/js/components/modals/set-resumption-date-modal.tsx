import React from 'react';
import {
  Alert,
  AlertDescription,
  AlertIcon,
  Button,
  Checkbox,
  HStack,
  Input,
  VStack,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '../forms/form-control-box';
import AcademicSessionSelect from '../selectors/academic-session-select';
import EnumSelect from '../dropdown-select/enum-select';
import { TermType } from '@/types/types';
import { preventNativeSubmit } from '@/util/util';
import useSharedProps from '@/hooks/use-shared-props';
import ClassificationGroupSelect from '../selectors/classification-group-select';
import { ClassificationGroup } from '@/types/models';

interface Props {
  isOpen: boolean;
  classificationGroups: ClassificationGroup[];
  onClose(): void;
  onSuccess(): void;
}

export default function SetResumptionDateModal({
  isOpen,
  classificationGroups,
  onSuccess,
  onClose,
}: Props) {
  const { handleResponseToast, toastError } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { currentAcademicSessionId, currentTerm, usesMidTermResult } =
    useSharedProps();
  const webForm = useWebForm({
    academic_session_id: currentAcademicSessionId,
    term: currentTerm,
    classificationGroup: '',
    next_term_resumption_date: '',
    forMidTerm: false,
    for_all_classes: false,
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(
        instRoute('class-result-info.set-resumption-date', [
          data.classificationGroup,
        ]),
        data
      )
    );

    if (!handleResponseToast(res)) {
      return;
    }

    onClose();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Set next term resumption'}
      bodyContent={
        <VStack spacing={2}>
          <Alert status="info" my={1}>
            <AlertIcon></AlertIcon>
            <AlertDescription>
              Use this page to set next term's resumption date on the student's
              result sheet. <br />
              This should be done only after all students' results have been
              recorded and calculated.
            </AlertDescription>
          </Alert>
          <HStack w={'full'} justify={'space-between'}>
            <FormControlBox
              form={webForm as any}
              title="Academic Session"
              formKey="academicSession"
              isRequired
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
            <FormControlBox
              form={webForm as any}
              title="Term"
              formKey="term"
              isRequired
            >
              <EnumSelect
                enumData={TermType}
                selectValue={webForm.data.term}
                isMulti={false}
                isClearable={true}
                onChange={(e: any) => webForm.setValue('term', e.value)}
                required
              />
            </FormControlBox>
          </HStack>
          {!webForm.data.for_all_classes && (
            <FormControlBox
              form={webForm as any}
              title="Class Group"
              formKey="classificationGroup"
            >
              <ClassificationGroupSelect
                selectValue={webForm.data.classificationGroup}
                isMulti={false}
                isClearable={true}
                onChange={(e: any) =>
                  webForm.setValue('classificationGroup', e.value)
                }
                classificationGroups={classificationGroups}
                required
              />
            </FormControlBox>
          )}
          <FormControlBox
            form={webForm as any}
            title="Next Term Resumption Date"
            formKey="next_term_resumption_date"
            isRequired
          >
            <Input
              type={'date'}
              value={webForm.data.next_term_resumption_date}
              onChange={(e) =>
                webForm.setValue(
                  'next_term_resumption_date',
                  e.currentTarget.value
                )
              }
              required
            />
          </FormControlBox>
          <FormControlBox
            form={webForm as any}
            formKey="for_all_classes"
            title=""
          >
            <Checkbox
              isChecked={webForm.data.for_all_classes}
              onChange={(e) =>
                webForm.setData({
                  ...webForm.data,
                  for_all_classes: e.currentTarget.checked,
                  classificationGroup: '',
                })
              }
            >
              For Mid-Term Result
            </Checkbox>
          </FormControlBox>
          {/* {usesMidTermResult && (
            <FormControlBox form={webForm as any} formKey="forMidTerm" title="">
              <Checkbox
                isChecked={webForm.data.forMidTerm}
                onChange={(e) =>
                  webForm.setValue('forMidTerm', e.currentTarget.checked)
                }
              >
                For Mid-Term Result
              </Checkbox>
            </FormControlBox>
          )} */}
        </VStack>
      }
      footerContent={
        <HStack spacing={2}>
          <Button variant={'ghost'} onClick={onClose}>
            Close
          </Button>
          <Button
            colorScheme={'brand'}
            onClick={preventNativeSubmit(onSubmit)}
            isLoading={webForm.processing}
          >
            Submite
          </Button>
        </HStack>
      }
    />
  );
}
