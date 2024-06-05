import React from 'react';
import { FormControl, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import {
  Classification,
  ClassificationGroup,
  Fee,
  ReceiptType,
} from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import InputForm from '@/components/forms/input-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import EnumSelect from '@/components/dropdown-select/enum-select';
import { FeePaymentInterval } from '@/types/types';
import ReceiptTypeSelect from '@/components/selectors/receipt-type-select';
import ClassificationGroupSelect from '@/components/selectors/classification-group-select';
import ClassificationSelect from '@/components/selectors/classification-select';

interface Props {
  fee?: Fee;
  receiptTypes: ReceiptType[];
  classificationGroups: ClassificationGroup[];
  classifications: Classification[];
}

export default function CreateOrUpdateFee({
  fee,
  receiptTypes,
  classificationGroups,
  classifications,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    title: fee?.title ?? '',
    receipt_type_id: fee?.receipt_type_id ?? '',
    amount: fee?.amount ?? '',
    payment_interval: fee?.payment_interval ?? FeePaymentInterval.Termly,
    classification_group_id: fee?.classification_group_id ?? '',
    classification_id: fee?.classification_id ?? '',
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) =>
      fee
        ? web.put(instRoute('fees.update', [fee]), data)
        : web.post(instRoute('fees.store'), data)
    );
    if (!handleResponseToast(res)) return;
    Inertia.visit(instRoute('fees.index'));
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading title={`${fee ? 'Update' : 'Create'} Fee`} />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
            >
              <InputForm
                form={webForm as any}
                formKey="title"
                title="Fee title"
              />

              <InputForm
                form={webForm as any}
                formKey="amount"
                title="Amount"
              />

              <FormControlBox
                form={webForm as any}
                formKey="payment_interval"
                title="Payment Interval"
              >
                <EnumSelect
                  selectValue={webForm.data.payment_interval}
                  enumData={FeePaymentInterval}
                  onChange={(e: any) =>
                    webForm.setValue('payment_interval', e.value)
                  }
                />
              </FormControlBox>

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
                title="Class Group (Required if this payment is perculiar to this class group)"
                formKey="classification_group_id"
              >
                <ClassificationGroupSelect
                  selectValue={webForm.data.classification_group_id}
                  isMulti={false}
                  isClearable={true}
                  onChange={(e: any) =>
                    webForm.setData({
                      ...webForm.data,
                      classification_group_id: e?.value,
                      classification_id: '',
                    })
                  }
                  classificationGroups={classificationGroups}
                />
              </FormControlBox>

              <FormControlBox
                form={webForm as any}
                title="Class (Required if this payment is perculiar to this class)"
                formKey="classification_id"
              >
                <ClassificationSelect
                  selectValue={webForm.data.classification_id}
                  isMulti={false}
                  isClearable={true}
                  onChange={(e: any) =>
                    webForm.setData({
                      ...webForm.data,
                      classification_group_id: '',
                      classification_id: e?.value,
                    })
                  }
                  classifications={classifications}
                />
              </FormControlBox>

              <FormControl>
                <FormButton isLoading={webForm.processing} />
              </FormControl>
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </DashboardLayout>
  );
}
