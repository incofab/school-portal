import React from 'react';
import {
  FormControl,
  FormLabel,
  VStack,
  Heading,
  HStack,
  BoxProps,
  Spacer,
} from '@chakra-ui/react';
import useSharedProps from '@/hooks/use-shared-props';
import useIsGuardian from '@/hooks/use-is-guardian';
import { Div } from '../semantic';
import useWebForm from '@/hooks/use-web-form';
import DataSelect from '../dropdown-select/data-select';
import InputForm from '../forms/input-form';
import { BrandButton } from '../buttons';
import route from '@/util/route';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import FormControlBox from '../forms/form-control-box';

export default function UpdateBvnNinForm({ ...props }: {} & BoxProps) {
  const { currentUser } = useSharedProps();
  const isGuardian = useIsGuardian();
  const { handleResponseToast } = useMyToast();

  if (!isGuardian || currentUser.has_bvn || currentUser.has_nin) {
    return <></>;
  }

  const webForm = useWebForm({
    type: '',
    value: '',
  });

  async function submit() {
    console.log('Submit called');

    const res = await webForm.submit((data, web) =>
      web.put(route('users.bvn-nin.update'), data)
    );
    if (!handleResponseToast(res)) return;
    Inertia.reload();
  }

  return (
    <Div p={4} borderWidth={1} borderRadius="lg" boxShadow="md" {...props}>
      <Heading size="md" mb={2} textAlign="start">
        Update BVN or NIN
      </Heading>

      <VStack spacing={2} align={'stretch'}>
        <HStack spacing={3} width={'full'}>
          <FormControlBox
            isRequired
            title="NIN/BVN"
            form={webForm as any}
            formKey="type"
          >
            <DataSelect
              data={{
                main: [
                  { label: 'NIN', value: 'nin' },
                  { label: 'BVN', value: 'bvn' },
                ],
                label: 'label',
                value: 'value',
              }}
              selectValue={webForm.data.type}
              onChange={(e: any) => webForm.setValue('type', e.value)}
            />
          </FormControlBox>
          <InputForm
            form={webForm as any}
            title={`Enter ${webForm.data.type?.toUpperCase()}`}
            formKey={'value'}
          />
        </HStack>
        <Spacer height={3} />
        <BrandButton
          title="Submit"
          maxW={'xs'}
          onClick={() => submit()}
          isLoading={webForm.processing}
        />
      </VStack>
    </Div>
  );
}
