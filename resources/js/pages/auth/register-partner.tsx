import { Div } from '@/components/semantic';
import { generateRandomString, preventNativeSubmit } from '@/util/util';
import route from '@/util/route';
import { FormControl, Icon, Input, Text, useToast, VStack } from '@chakra-ui/react';
import React, { useState } from 'react';
import useSharedProps from '@/hooks/use-shared-props';
import FormControlBox from '@/components/forms/form-control-box';
import EnumSelect from '@/components/dropdown-select/enum-select';
import { Gender } from '@/types/types';
import CenteredLayout from '@/components/centered-layout';
import useWebForm from '@/hooks/use-web-form';
import { Inertia } from '@inertiajs/inertia';
import { FormButton, LinkButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import { ArrowDownIcon, XMarkIcon } from '@heroicons/react/24/solid';

interface Props {}

export default function RegisterPartner({}: Props) {
  const { message } = useSharedProps();
  const toast = useToast();
  const { handleResponseToast } = useMyToast();

  const [completed, setCompleted] = useState<boolean>(false);

  const form = useWebForm({
    first_name: '',
    last_name: '',
    other_names: '',
    username: '',
    phone: '',
    email: '',
    gender: '',
    password: '',
    password_confirmation: '',
    referral_email: '',
    reference: Date.now().toPrecision() + generateRandomString(15),
  });

  const submit = async () => {
    const res = await form.submit((data, web) => {
      return web.post(route('partner-registration-requests.store'), data);
    });

    if (!handleResponseToast(res)) {
      return;
    }

    setCompleted(true);
    // Inertia.reload();
    // Inertia.visit(route('managers.index'));
  };

  return (
    completed
    ? <ShowMessage />
    :
    <CenteredLayout title="Partner Registration" boxProps={{ maxW: 'lg' }}>
      <Div as={'form'} onSubmit={preventNativeSubmit(submit)} p={6}>
        <VStack spacing={4} align={'stretch'}>
          <FormControlBox form={form} title="First Name" formKey="first_name">
            <Input
              type="text"
              onChange={(e) =>
                form.setValue('first_name', e.currentTarget.value)
              }
              value={form.data.first_name}
              required
            />
          </FormControlBox>
          <FormControlBox form={form} title="Last Name" formKey="last_name">
            <Input
              type="text"
              onChange={(e) =>
                form.setValue('last_name', e.currentTarget.value)
              }
              value={form.data.last_name}
              required
            />
          </FormControlBox>
          <FormControlBox form={form} title="Other Names" formKey="other_names">
            <Input
              type="text"
              onChange={(e) =>
                form.setValue('other_names', e.currentTarget.value)
              }
              value={form.data.other_names}
            />
          </FormControlBox>
          <FormControlBox form={form} title="Phone" formKey="phone">
            <Input
              type="tel"
              onChange={(e) => form.setValue('phone', e.currentTarget.value)}
              value={form.data.phone}
            />
          </FormControlBox>
          <FormControlBox form={form} title="Email" formKey="email">
            <Input
              type="email"
              onChange={(e) => form.setValue('email', e.currentTarget.value)}
              value={form.data.email}
              required
            />
          </FormControlBox>
          <FormControlBox form={form} title="Username" formKey="username">
            <Input
              type="text"
              onChange={(e) => form.setValue('username', e.currentTarget.value)}
              value={form.data.username}
              required
            />
          </FormControlBox>
          <FormControlBox form={form} title="Gender" formKey="gender">
            <EnumSelect
              enumData={Gender}
              onChange={(e: any) => form.setValue('gender', e.value)}
              required
            />
          </FormControlBox>
          <FormControlBox form={form} title="Password" formKey="password">
            <Input
              type="password"
              onChange={(e) => form.setValue('password', e.currentTarget.value)}
              value={form.data.password}
              required
            />
          </FormControlBox>
          <FormControlBox
            form={form}
            title="Confirm Password"
            formKey="password_confirmation"
          >
            <Input
              type="password"
              onChange={(e) =>
                form.setValue('password_confirmation', e.currentTarget.value)
              }
              value={form.data.password_confirmation}
              required
            />
          </FormControlBox>
          <FormControlBox
            form={form}
            title="Referral User Email"
            formKey="referral_email"
          >
            <Input
              type="email"
              onChange={(e) =>
                form.setValue('referral_email', e.currentTarget.value)
              }
              value={form.data.referral_email}
            />
          </FormControlBox>
        </VStack>
        <FormControl mt={2}>
          <FormButton isLoading={form.processing} />
        </FormControl>
      </Div>
    </CenteredLayout>
  );
}

function ShowMessage() {
  const message = 'We have received your registration application. Our team will contact you shortly to proceed with the onboarding process.';
  return (<CenteredLayout boxProps={{ maxW: '800px' }}>
  <Text fontSize={'2xl'} color={'green.600'}>
    Registration Successful
  </Text>
  <Icon as={ArrowDownIcon} w={10} h={10} mt={6} />
  <Text
    my={5}
    fontSize={'2xl'}
    dangerouslySetInnerHTML={{ __html: message }}
  />
  <LinkButton
    variant={'outline'}
    colorScheme="brand"
    leftIcon={<Icon as={XMarkIcon} />}
    mt={4}
    href={route('home')}
    title={'Home'}
  />
</CenteredLayout>);
}