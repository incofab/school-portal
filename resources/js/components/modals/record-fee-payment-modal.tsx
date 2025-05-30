import React, { useState } from 'react';
import {
  Button,
  FormControl,
  FormLabel,
  HStack,
  VStack,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '../forms/form-control-box';
import useSharedProps from '@/hooks/use-shared-props';
import { generateRandomString } from '@/util/util';
import InputForm from '../forms/input-form';
import { Fee } from '@/types/models';
import FeeSelect from '../selectors/fee-select';
import { SelectOptionType, TermType } from '@/types/types';
import AcademicSessionSelect from '../selectors/academic-session-select';
import EnumSelect from '../dropdown-select/enum-select';
import StudentSelect from '../selectors/student-select';
import ClassificationSelect from '../selectors/classification-select';

interface Props {
  fees: Fee[];
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function RecordFeePaymentModal({
  isOpen,
  onSuccess,
  onClose,
  fees,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { currentInstitution, currentAcademicSessionId, currentTerm } =
    useSharedProps();
  const { instRoute } = useInstitutionRoute();
  const [classId, setClassId] = useState<undefined | number>(undefined);
  const webForm = useWebForm({
    academic_session_id: currentAcademicSessionId,
    term: currentTerm,
    fee_id: '',
    reference: `${currentInstitution.id} - ${generateRandomString(16)}`,
    user_id: {} as SelectOptionType<number>,
    amount: '',
    method: '',
    transaction_reference: '',
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(instRoute('fee-payments.store'), {
        ...data,
        user_id: data.user_id.value,
      })
    );

    if (!handleResponseToast(res)) return;

    onClose();
    webForm.setData({
      ...webForm.data,
      amount: '',
      reference: `G${currentInstitution.id}${generateRandomString(16)}`,
      method: '',
      user_id: {} as SelectOptionType<number>,
    });
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Record Student Payment'}
      bodyContent={
        <VStack spacing={2}>
          <FormControlBox form={webForm as any} title="Fee" formKey="fee">
            <FeeSelect
              value={webForm.data.fee_id}
              isMulti={false}
              isClearable={true}
              onChange={(e: any) => webForm.setValue('fee_id', e?.value)}
              fees={fees}
              required
            />
          </FormControlBox>
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
              selectValue={webForm.data.term}
              enumData={TermType}
              isMulti={false}
              isClearable={true}
              onChange={(e: any) => webForm.setValue('term', e?.value)}
            />
          </FormControlBox>
          <FormControl>
            <FormLabel>Class</FormLabel>
            <ClassificationSelect
              selectValue={classId}
              isMulti={false}
              isClearable={true}
              onChange={(e: any) => setClassId(e?.value)}
            />
          </FormControl>
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
              classification={classId}
              required
            />
          </FormControlBox>
          <InputForm
            form={webForm as any}
            formKey="amount"
            title="Amount"
            isRequired
          />
          <InputForm
            form={webForm as any}
            formKey="transaction_reference"
            title="Transaction Id / Receipt No / Teller No etc..."
          />
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
