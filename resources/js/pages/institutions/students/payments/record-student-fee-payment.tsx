import React from 'react';
import { Divider, FormControl, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import {
  Classification,
  ClassificationGroup,
  Fee,
  ReceiptType,
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
import ReceiptTypeSelect from '@/components/selectors/receipt-type-select';
import AcademicSessionSelect from '@/components/selectors/academic-session-select';
import useSharedProps from '@/hooks/use-shared-props';
import ClassificationSelect from '@/components/selectors/classification-select';
import ClassificationGroupSelect from '@/components/selectors/classification-group-select';
import { FeeItemSelector } from '@/components/payments/fee-item-selector';

interface Props {
  student: Student;
  fees: Fee[];
  receiptTypes: ReceiptType[];
  classificationGroups: ClassificationGroup[];
  classifications: Classification[];
}

export default function RecordStudentFeePayment({
  student,
  fees,
  receiptTypes,
  classificationGroups,
  classifications,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { currentAcademicSessionId, currentTerm } = useSharedProps();
  const webForm = useWebForm({
    term: currentTerm,
    academic_session_id: currentAcademicSessionId,
    fee_ids: [] as number[],
    classification_id: String(student.classification_id ?? ''),
    classification_group_id: String(
      student.classification?.classification_group_id ?? ''
    ),
    receipt_type_id: '',
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(instRoute('students.fee-payments.store', [student]), data)
    );
    if (!handleResponseToast(res)) return;
    // console.log('Res', res);
    // return;
    window.location.href = res.data.authorization_url;
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading title={`Multi Fee Payment`} />
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
                <ReceiptTypeSelect
                  selectValue={webForm.data.receipt_type_id}
                  isMulti={false}
                  isClearable={true}
                  onChange={(e: any) =>
                    webForm.setValue('receipt_type_id', e?.value)
                  }
                  receiptTypes={receiptTypes}
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
              <FormControlBox
                form={webForm as any}
                title="Class Group"
                formKey="classification_group_id"
              >
                <ClassificationGroupSelect
                  selectValue={webForm.data.classification_group_id}
                  isMulti={false}
                  isClearable={true}
                  classificationGroups={classificationGroups}
                  onChange={(e: any) =>
                    webForm.setData({
                      ...webForm.data,
                      classification_group_id: e?.value,
                      classification_id: '',
                    })
                  }
                />
              </FormControlBox>
              {webForm.data.classification_group_id && (
                <FormControlBox
                  form={webForm as any}
                  title="Class"
                  formKey="classification_id"
                >
                  <ClassificationSelect
                    selectValue={webForm.data.classification_id}
                    classifications={classifications}
                    classGroupId={webForm.data.classification_group_id}
                    isMulti={false}
                    isClearable={true}
                    onChange={(e: any) =>
                      webForm.setValue('classification_id', e?.value)
                    }
                  />
                </FormControlBox>
              )}
              <Divider />
              <FeeItemSelector
                fees={fees}
                selected_fee_ids={webForm.data.fee_ids}
                updateSelection={(feeIds: number[]) =>
                  webForm.setValue('fee_ids', feeIds)
                }
                receipt_type_id={parseInt(webForm.data.receipt_type_id)}
                classification_group_id={parseInt(
                  webForm.data.classification_group_id
                )}
                classification_id={parseInt(webForm.data.classification_id)}
              />
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
