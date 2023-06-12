import {
  Button,
  FormControl,
  FormErrorMessage,
  FormLabel,
  Input,
  Spacer,
  useToast,
  VStack,
} from '@chakra-ui/react';
import React from 'react';
import { Div } from '@/components/semantic';
import route from '@/util/route';
import { Inertia } from '@inertiajs/inertia';
import { User } from '@/types/models';
import DashboardLayout from '@/layout/dashboard-layout';
import CenteredBox from '@/components/centered-box';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { preventNativeSubmit } from '@/util/util';
import useWebForm from '@/hooks/use-web-form';
import CenteredLayout from '@/components/centered-layout';

const CHANGE_PASSWORD_FORM = {
  current_password: '',
  new_password: '',
  new_password_confirmation: '',
};
interface Props {
  user: User;
}

export default function ChangePassword({ user }: Props) {
  const form = useWebForm(CHANGE_PASSWORD_FORM);
  const toast = useToast();

  async function submitForm() {
    const { ok, message } = await form.submit((data, web) =>
      web.put(route('users.password.update', [user]), data)
    );

    if (!ok) {
      return void toast({
        title: 'Password change failed',
        status: 'error',
      });
    }

    toast({
      title: message ?? 'Password changed successfully',
      status: 'success',
    });

    Inertia.visit(route('home'));
  }

  return (
    <CenteredLayout title="Change your password">
      <VStack
        as={'form'}
        spacing={4}
        align={'stretch'}
        onSubmit={preventNativeSubmit(submitForm)}
      >
        <FormControl isInvalid={!!form.errors.current_password}>
          <FormLabel>Current Password</FormLabel>
          <Input
            id={'current_password'}
            value={form.data.current_password}
            type="password"
            onChange={(e) =>
              form.setValue('current_password', e.currentTarget.value)
            }
          />
          <FormErrorMessage>{form.errors.current_password}</FormErrorMessage>
        </FormControl>
        <FormControl isInvalid={!!form.errors.new_password}>
          <FormLabel>New Password</FormLabel>
          <Input
            id={'new_password'}
            value={form.data.new_password}
            type="password"
            onChange={(e) =>
              form.setValue('new_password', e.currentTarget.value)
            }
          />
          <FormErrorMessage>{form.errors.new_password}</FormErrorMessage>
        </FormControl>
        <FormControl isInvalid={!!form.errors.new_password_confirmation}>
          <FormLabel>Confirm Password</FormLabel>
          <Input
            id={'new_password_confirmation'}
            value={form.data.new_password_confirmation}
            type="password"
            onChange={(e) =>
              form.setValue('new_password_confirmation', e.currentTarget.value)
            }
          />
          <FormErrorMessage>
            {form.errors.new_password_confirmation}
          </FormErrorMessage>
        </FormControl>
        <Spacer height={2} />
        <Div>
          <Button
            type="submit"
            colorScheme={'brand'}
            isLoading={form.processing}
          >
            Submit
          </Button>
        </Div>
      </VStack>
    </CenteredLayout>
  );
}
