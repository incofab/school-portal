import React, { useState } from 'react';
import { Button, HStack, VStack} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import { User} from '@/types/models';
import { SelectOptionType, TermType } from '@/types/types';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '../forms/form-control-box';
import AcademicSessionSelect from '../selectors/academic-session-select';
import EnumSelect from '../dropdown-select/enum-select';
import useSharedProps from '@/hooks/use-shared-props';
import StudentSelect from '../selectors/student-select';
import { Inertia } from '@inertiajs/inertia';

interface Props {
  user?: User;
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function UniversalReceiptModal({
  isOpen,
  onSuccess,
  onClose,
  user,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { currentAcademicSessionId, currentTerm } = useSharedProps();

  const webForm = useWebForm({
    term: currentTerm,
    academic_session_id: currentAcademicSessionId,
    user_id: {} as SelectOptionType<number>,
  });

  const onSubmit = async () => {
    Inertia.visit(
      instRoute('receipts.print-universal-receipt', [user?.id ?? webForm.data.user_id.value]) + 
      `?academic_session_id=${webForm.data.academic_session_id}&term=${webForm.data.term}`
    );
    
    onClose();
  };


  return (
    <>
      <GenericModal
        props={{ isOpen, onClose }}
        headerContent={`Universal Receipt`}
        bodyContent={
          <VStack spacing={3} align={'stretch'} mb={5}>
            {!user &&
              <FormControlBox
                form={webForm as any}
                title="Student"
                formKey="student"
              >
                <StudentSelect
                  value={webForm.data.user_id}
                  isMulti={false}
                  isClearable={true}
                  valueKey={'user_id'}
                  onChange={(e: any) => webForm.setValue('user_id', e)}
                  required
                />
              </FormControlBox>
            }

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
              />
            </FormControlBox>
            <FormControlBox form={webForm as any} title="Term" formKey="term">
              <EnumSelect
                enumData={TermType}
                selectValue={webForm.data.term}
                isMulti={false}
                isClearable={true}
                onChange={(e: any) => webForm.setValue('term', e?.value)}
              />
            </FormControlBox>
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
              Submit
            </Button>
          </HStack>
        }
      />
    </>
  );
}
