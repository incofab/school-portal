import React, { useState } from 'react';
import { AxiosInstance } from 'axios';
import {
  Divider,
  FormControl,
  FormErrorMessage,
  FormLabel,
  Text,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { generateRandomString, preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import UserInputForm from '@/components/user-input-form';
import { InstitutionUser, ReceiptType, Student, User } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import { Div } from '@/components/semantic';
import FormControlBox from '@/components/forms/form-control-box';
import ClassificationSelect from '@/components/selectors/classification-select';
import InputForm from '@/components/forms/input-form';
import {
  InstitutionUserType,
  NotificationChannelsType,
  NotificationReceiversType,
  Nullable,
  SelectOptionType,
} from '@/types/types';
import useInstitutionRoute from '@/hooks/use-institution-route';
import EnumSelect from '@/components/dropdown-select/enum-select';
import ReceiptTypeSelect from '@/components/selectors/receipt-type-select';
import { MultiValue } from 'react-select';

interface Props {
  receiptTypes: ReceiptType[];
}

export default function CreateOrUpdateStudent({ receiptTypes }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const [shouldBeDisabled, setShouldBeDisabled] = useState(false);

  const webForm = useWebForm({
    receipt_type_id: '',
    receiver: '',
    classification_ids: null as Nullable<MultiValue<SelectOptionType<number>>>,
    channel: '',
    reference: Date.now().toPrecision() + generateRandomString(15),
  });

  const updateForm = (receiver: string) => {
    if (receiver == NotificationReceiversType.AllClasses) {
      webForm.setValue('classification_ids', null);
      setShouldBeDisabled(true);
    } else {
      setShouldBeDisabled(false);
    }

    webForm.setValue('receiver', receiver);
    return receiver;
  };

  const submit = async () => {
    const res = await webForm.submit((data, web: AxiosInstance) =>
      web.post(instRoute('payment-notifications.store'), {
        ...data,
        classification_ids: data.classification_ids?.map((item) => item.value),
      })
    );

    if (!handleResponseToast(res)) return;

    Inertia.visit(instRoute('payment-notifications.create'));
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab w={'full'}>
          <SlabHeading title={`Send Notification`} />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
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

              <FormControl isRequired isInvalid={!!webForm.errors.receiver}>
                <FormLabel>Receivers</FormLabel>
                <EnumSelect
                  enumData={NotificationReceiversType}
                  //   onChange={(e: any) => webForm.setValue('receiver', e.value)}
                  onChange={(e: any) => {
                    updateForm(e?.value);
                  }}
                  selectValue={webForm.data.receiver}
                  required
                />
              </FormControl>

              <FormControlBox
                form={webForm as any}
                title="Class"
                formKey="classification_ids"
              >
                <ClassificationSelect
                  onChange={(e: any) =>
                    webForm.setValue('classification_ids', e)
                  }
                  isMulti={true}
                  isClearable={true}
                  isDisabled={shouldBeDisabled}
                />
              </FormControlBox>

              <FormControl isRequired isInvalid={!!webForm.errors.channel}>
                <FormLabel>Notification Channel</FormLabel>
                <EnumSelect
                  enumData={NotificationChannelsType}
                  onChange={(e: any) => webForm.setValue('channel', e.value)}
                  selectValue={webForm.data.channel}
                  required
                />
              </FormControl>

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
