import React from 'react';
import { AxiosInstance } from 'axios';
import { FormControl, Textarea, VStack } from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { generateRandomString, preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import FormControlBox from '@/components/forms/form-control-box';
import { NotificationChannelsType } from '@/types/types';
import useInstitutionRoute from '@/hooks/use-institution-route';
import EnumSelect from '@/components/dropdown-select/enum-select';
import { Fee } from '@/types/models';
import FeeSelect from '@/components/selectors/fee-select';

interface Props {
  fees: Fee[];
}

export default function CreateOrUpdateStudent({ fees }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();

  const webForm = useWebForm({
    fee_id: '',
    channel: '',
    message: '',
    reference: generateRandomString(15, true),
  });

  const submit = async () => {
    const res = await webForm.submit((data, web: AxiosInstance) =>
      web.post(instRoute('payment-notifications.store'), data)
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
                title="Fee"
                formKey="fee_id"
              >
                <FeeSelect
                  selectValue={webForm.data.fee_id}
                  isMulti={false}
                  isClearable={true}
                  onChange={(e: any) => webForm.setValue('fee_id', e?.value)}
                  fees={fees}
                  required
                />
              </FormControlBox>

              {/* <FormControlBox
                form={webForm as any}
                title="Message"
                formKey="message"
              >
                <Textarea
                  onChange={(e) =>
                    webForm.setValue('message', e.currentTarget.value)
                  }
                >
                  {webForm.data.message}
                </Textarea>
              </FormControlBox> */}

              <FormControlBox
                isRequired
                form={webForm as any}
                title="Notification Channel"
                formKey="channel"
              >
                <EnumSelect
                  enumData={NotificationChannelsType}
                  onChange={(e: any) => webForm.setValue('channel', e.value)}
                  selectValue={webForm.data.channel}
                  required
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
