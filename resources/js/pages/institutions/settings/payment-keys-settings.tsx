import React from 'react';
import {
  Divider,
  FormControl,
  FormLabel,
  Input,
  Spacer,
  Text,
  VStack,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import { Inertia } from '@inertiajs/inertia';
import { BrandButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { InstitutionSettingType } from '@/types/types';
import useSharedProps from '@/hooks/use-shared-props';
import { Div } from '@/components/semantic';

interface Props {}

export default function PaymentKeysSettings({}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { paymentKeys } = useSharedProps();

  const webForm = useWebForm(
    paymentKeys ?? { paystack: { private_key: '', public_key: '' } }
  );
  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      return web.post(instRoute('settings.store'), {
        key: InstitutionSettingType.PaymentKeys,
        value: data,
        type: 'array',
      });
    });
    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.reload({ only: ['settings'] });
  };

  return (
    <VStack align={'stretch'} spacing={2}>
      <Text fontWeight={'bold'}>Paystack Payment Keys</Text>
      <FormControl>
        <FormLabel>Public Key</FormLabel>
        <Input
          onChange={(e) =>
            webForm.setValue('paystack', {
              ...webForm.data['paystack'],
              public_key: e.currentTarget.value,
            })
          }
          value={webForm.data['paystack']?.public_key}
        />
      </FormControl>
      <FormControl>
        <FormLabel>Private Key</FormLabel>
        <Input
          onChange={(e) =>
            webForm.setValue('paystack', {
              ...webForm.data['paystack'],
              private_key: e.currentTarget.value,
            })
          }
          value={webForm.data['paystack']?.private_key}
        />
      </FormControl>
      <Div>
        <BrandButton
          title="Update"
          onClick={() => submit()}
          isLoading={webForm.processing}
          size={'md'}
          mt={'10px'}
          float={'right'}
        />
      </Div>
      <Spacer height={4} />
    </VStack>
  );
}
