import React from 'react';
import { Button, HStack, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '../forms/form-control-box';
import { User } from '@/types/models';
import { GuardianRelationship } from '@/types/types';
import EnumSelect from '../dropdown-select/enum-select';
import StudentSelect from '../selectors/student-select';

interface Props {
  user: User;
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function AssignGuardianStudentModal({
  isOpen,
  onSuccess,
  onClose,
  user,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    relationship: '',
    student_id: '',
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(instRoute('guardians.assign-student', [user]), data)
    );

    if (!handleResponseToast(res)) return;

    onClose();
    webForm.reset();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Attach student to guardian'}
      bodyContent={
        <VStack spacing={2}>
          <FormControlBox
            form={webForm as any}
            title="Student"
            formKey="student_id"
          >
            <StudentSelect
              value={webForm.data.student_id}
              isMulti={false}
              isClearable={true}
              valueKey={'id'}
              onChange={(e: any) => webForm.setValue('student_id', e)}
              required
            />
          </FormControlBox>
          <FormControlBox
            form={webForm as any}
            title="Relationship"
            formKey="relationship"
          >
            <EnumSelect
              enumData={GuardianRelationship}
              selectValue={webForm.data.relationship}
              isMulti={false}
              isClearable={true}
              onChange={(e: any) => webForm.setValue('relationship', e?.value)}
              required
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
  );
}
