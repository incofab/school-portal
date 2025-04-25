import React from 'react';
import { Divider, FormControl, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import {
  Classification,
  ClassificationGroup,
  Fee,
  Student,
} from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import EnumSelect from '@/components/dropdown-select/enum-select';
import { TermType } from '@/types/types';
import AcademicSessionSelect from '@/components/selectors/academic-session-select';
import useSharedProps from '@/hooks/use-shared-props';
import FeeSelect from '@/components/selectors/fee-select';
import InputForm from '@/components/forms/input-form';

interface Props {
  student: Student;
  fees: Fee[];
}

export default function RecordStudentFeePayment({ student, fees }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { currentAcademicSessionId, currentTerm } = useSharedProps();
  console.log('dkskdks', fees);

  const webForm = useWebForm({
    term: currentTerm,
    academic_session_id: currentAcademicSessionId,
    fee_id: '',
    amount: 0,
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(instRoute('students.fee-payments.store', [student.id]), data)
    );

    if (!handleResponseToast(res)) return;

    window.location.href = res.data.authorization_url;
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading title={`Pay Fees`} />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
              align={'stretch'}
            >
              <FormControlBox
                form={webForm as any}
                title="Fee Category"
                formKey="receipt_type_id"
              >
                <FeeSelect
                  selectValue={webForm.data.fee_id}
                  isMulti={false}
                  isClearable={true}
                  onChange={(e: any) => {
                    const feeId = e.value;
                    const fee = fees.find((item) => item.id == feeId);
                    webForm.setData({
                      ...webForm.data,
                      fee_id: feeId,
                      amount: fee?.amount ?? 0,
                    });
                  }}
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
                  enumData={TermType}
                  selectValue={webForm.data.term}
                  isMulti={false}
                  isClearable={true}
                  onChange={(e: any) => webForm.setValue('term', e?.value)}
                />
              </FormControlBox>
              <InputForm
                form={webForm as any}
                title={'Amount'}
                formKey={'amount'}
              />
              <Divider />
              <FormControl>
                <FormButton
                  isLoading={webForm.processing}
                  title="Pay Now"
                  float={'right'}
                />
              </FormControl>
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </DashboardLayout>
  );
}
