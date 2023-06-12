import { preventNativeSubmit } from '@/util/util';
import route from '@/util/route';
import { Input, Spacer, VStack } from '@chakra-ui/react';
import React, { useState } from 'react';
import { Inertia } from '@inertiajs/inertia';
import PasswordInput from '@/components/password-input';
import useMyToast from '@/hooks/use-my-toast';
import useWebForm from '@/hooks/use-web-form';
import FormControlBox from '@/components/forms/form-control-box';
import { BrandButton } from '@/components/buttons';
import CenteredLayout from '@/components/centered-layout';

export default function StudentLogin() {
  const { toastError, toastSuccess } = useMyToast();
  const [shouldEnterPassword, setShouldEnterPassword] = useState(false);
  const form = useWebForm({
    student_code: '',
    password: '',
  });

  async function onSubmit() {
    const res = await form.submit((data, web) => {
      return web.post(route('student-login.store', data));
    });
    if (!res.ok) {
      return void toastError(res.message ?? 'Invalid credentials');
    }

    if (res.data.should_enter_password) {
      setShouldEnterPassword(true);
      if (form.data.password) {
        toastError(res.message ?? 'Enter your password correctly');
      }
      return;
    }
    toastSuccess('Login successful');
    Inertia.visit(route('user.dashboard'));
  }

  return (
    <CenteredLayout title="Student Login">
      <VStack
        spacing={4}
        align={'stretch'}
        as={'form'}
        onSubmit={preventNativeSubmit(onSubmit)}
      >
        <FormControlBox
          form={form as any}
          formKey="student_code"
          title="Student Code"
        >
          <Input
            value={form.data.student_code}
            onChange={(e) =>
              form.setValue('student_code', e.currentTarget.value)
            }
          />
        </FormControlBox>
        {shouldEnterPassword && (
          <FormControlBox
            form={form as any}
            formKey="password"
            title="Password"
          >
            <PasswordInput
              id={'password'}
              value={form.data.password}
              onChange={(e) => form.setValue('password', e.currentTarget.value)}
            />
          </FormControlBox>
        )}
        <Spacer height={2} />
        <BrandButton isLoading={form.processing} type="submit" title="Login" />
      </VStack>
    </CenteredLayout>
  );
}
