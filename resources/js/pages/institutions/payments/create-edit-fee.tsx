import React from 'react';
import { FormControl, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { Fee } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import InputForm from '@/components/forms/input-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import EnumSelect from '@/components/dropdown-select/enum-select';
import { FeePaymentInterval } from '@/types/types';

interface Props {
  fee?: Fee;
}

export default function CreateOrUpdateFee({ fee }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    title: fee?.title ?? '',
    amount: fee?.amount ?? '',
    payment_interval: fee?.payment_interval ?? FeePaymentInterval.termly,
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
                  enumData={FeePaymentInterval}
                  onChange={(e: any) =>
                    webForm.setValue('payment_interval', e.value)
                  }
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
