import { Div } from '@/components/semantic';
import route from '@/util/route';
import {
  Button,
  FormControl,
  FormErrorMessage,
  FormLabel,
  Input,
  Spacer,
  Text,
  useColorModeValue,
  VStack,
} from '@chakra-ui/react';
import { InertiaLink } from '@inertiajs/inertia-react';
import React from 'react';
import FormControlBox from '@/components/forms/form-control-box';
import EnumSelect from '@/components/dropdown-select/enum-select';
import { Gender, KeyValue } from '@/types/types';
import { FormProps } from '@/components/forms/input-form';

interface RegistrationData {
  first_name: string;
  last_name: string;
  other_names: string;
  phone: string;
  email: string;
  gender: string;
  password: string;
  password_confirmation: string;
  institution: {
    name: string;
    phone: string;
    email: string;
    address: string;
  };
  reference: string;
}

export default function RegistrationForm({
  form,
}: {
  form: FormProps<
    { [key: 'institution' | string]: string } | { [key: string]: KeyValue }
  >;
}) {
  return (
    <Div w={'full'}>
      <VStack spacing={4} align={'stretch'} px={6} pb={6}>
        <FormControlBox form={form} title="First Name" formKey="first_name">
          <Input
            type="text"
            onChange={(e) => form.setValue('first_name', e.currentTarget.value)}
            value={form.data.first_name as string}
            required
          />
        </FormControlBox>
        <FormControlBox form={form} title="Last Name" formKey="last_name">
          <Input
            type="text"
            onChange={(e) => form.setValue('last_name', e.currentTarget.value)}
            value={form.data.last_name as string}
            required
          />
        </FormControlBox>
        <FormControlBox form={form} title="Other Names" formKey="other_names">
          <Input
            type="text"
            onChange={(e) =>
              form.setValue('other_names', e.currentTarget.value)
            }
            value={form.data.other_names as string}
          />
        </FormControlBox>
        <FormControlBox form={form} title="Phone" formKey="phone">
          <Input
            type="tel"
            onChange={(e) => form.setValue('phone', e.currentTarget.value)}
            value={form.data.phone as string}
          />
        </FormControlBox>
        <FormControlBox form={form} title="Email" formKey="email">
          <Input
            type="email"
            onChange={(e) => form.setValue('email', e.currentTarget.value)}
            value={form.data.email as string}
            required
          />
        </FormControlBox>
        <FormControlBox form={form} title="Gender" formKey="gender">
          <EnumSelect
            enumData={Gender}
            onChange={(e: any) => form.setValue('gender', e.value)}
            selectValue={form.data.gender}
            required
          />
        </FormControlBox>
        <FormControlBox form={form} title="Password" formKey="password">
          <Input
            type="password"
            onChange={(e) => form.setValue('password', e.currentTarget.value)}
            value={form.data.password as string}
            required
          />
        </FormControlBox>
        <FormControlBox
          form={form}
          title="Password"
          formKey="password_confirmation"
        >
          <Input
            type="password"
            onChange={(e) =>
              form.setValue('password_confirmation', e.currentTarget.value)
            }
            value={form.data.password_confirmation as string}
            required
          />
        </FormControlBox>
      </VStack>
      <VStack
        spacing={3}
        p={6}
        background={useColorModeValue('white', 'gray.700')}
      >
        <Text mt={5} fontWeight="bold" fontSize="md">
          Institution details
        </Text>
        <FormControl isInvalid={!!form.errors['institution.name']}>
          <FormLabel>Institution Name</FormLabel>
          <Input
            type="text"
            onChange={(e) =>
              form.setValue('institution', {
                ...(form.data.institution as KeyValue),
                name: e.currentTarget.value,
              })
            }
            value={(form.data.institution as KeyValue).name}
          />
          <FormErrorMessage>{form.errors['institution.name']}</FormErrorMessage>
        </FormControl>
        <FormControl isInvalid={!!form.errors['institution.phone']}>
          <FormLabel>Institution Phone</FormLabel>
          <Input
            type="text"
            onChange={(e) =>
              form.setValue('institution', {
                ...(form.data.institution as KeyValue),
                phone: e.currentTarget.value,
              })
            }
            value={(form.data.institution as KeyValue).phone}
          />
          <FormErrorMessage>
            {form.errors['institution.phone']}
          </FormErrorMessage>
        </FormControl>
        <FormControlBox
          form={form}
          title="Institution Email"
          formKey="institution.email"
        >
          <Input
            type="text"
            onChange={(e) =>
              form.setValue('institution', {
                ...(form.data.institution as KeyValue),
                email: e.currentTarget.value,
              })
            }
            value={(form.data.institution as KeyValue).email}
            required
          />
        </FormControlBox>
        <FormControlBox
          form={form}
          title="Institution Address"
          formKey="institution.address"
        >
          <Input
            type="text"
            onChange={(e) =>
              form.setValue('institution', {
                ...(form.data.institution as KeyValue),
                address: e.currentTarget.value,
              })
            }
            value={(form.data.institution as KeyValue).address}
            required
          />
        </FormControlBox>
        <Spacer />
        <Button
          isLoading={form.processing}
          loadingText="Logging in"
          type="submit"
          colorScheme={'brand'}
          id="login"
        >
          Register
        </Button>
        <Div textAlign={'center'}>
          <InertiaLink href={route('login')}>
            <Button colorScheme={'brand'} variant={'link'}>
              Already have an account? Login
            </Button>
          </InertiaLink>
        </Div>
      </VStack>
    </Div>
  );
}
