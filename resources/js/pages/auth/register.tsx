import { Div } from '@/components/semantic';
import { generateRandomString, preventNativeSubmit } from '@/util/util';
import route from '@/util/route';
import {
  Button,
  FormControl,
  FormErrorMessage,
  FormLabel,
  Image,
  Input,
  Spacer,
  Text,
  useColorModeValue,
  useToast,
  VStack,
} from '@chakra-ui/react';
import { InertiaLink, useForm } from '@inertiajs/inertia-react';
import React, { useEffect } from 'react';
import useSharedProps from '@/hooks/use-shared-props';
import FormControlBox from '@/components/forms/form-control-box';
import EnumSelect from '@/components/dropdown-select/enum-select';
import { Gender } from '@/types/types';
import { InstitutionGroup, User } from '@/types/models';
import Slab, { SlabBody } from '@/components/slab';
import CenteredLayout from '@/components/centered-layout';

export default function Register({
  user,
  institutionGroup,
}: {
  user?: User;
  institutionGroup?: InstitutionGroup;
}) {
  const { message } = useSharedProps();
  const toast = useToast();
  const form = useForm({
    first_name: '',
    last_name: '',
    other_names: '',
    phone: '',
    email: '',
    gender: '',
    password: '',
    password_confirmation: '',
    institution: {
      name: '',
      phone: '',
      email: '',
      address: '',
    },
    reference: Date.now().toPrecision() + generateRandomString(15),
  });

  function onSubmit() {
    form.post(route('registration-requests.store', user ? [user.id] : []));
  }

  useEffect(() => {
    message?.error &&
      toast({
        title: message.error,
        status: 'error',
      });

    message?.success &&
      toast({
        title: message.success,
        status: 'success',
      });
  }, [message]);

  // const bgImage = user ? '/partners/' + user.username + '.webp' : undefined;

  const imageUrl = institutionGroup?.banner;
  return (
    <CenteredLayout
      title="Register Your Institution"
      boxProps={{ maxW: 'lg' }}
      bgImage={imageUrl}
    >
      <Div w={'full'} as={'form'} onSubmit={preventNativeSubmit(onSubmit)}>
        <Slab>
          <SlabBody>
            <Text fontSize={'lg'} fontWeight={'semibold'} color={'brand.500'}>
              Bring your school into one simple digital workspace.
            </Text>
            <Text mt={3}>
              EduManager helps schools manage daily academic and administrative
              work from one platform, including students, staff, classes,
              attendance, assessments, results, fees, admissions, messaging,
              payroll, expenses, and reports.
            </Text>
            <Text mt={3}>
              Registering your institution gives your school a clear path to
              reduce paperwork, improve record keeping, make payments and result
              processing easier, and give staff, students, and parents faster
              access to the information they need.
            </Text>
            <Text mt={3}>
              This form should only be completed by an owner, director,
              administrator, or authorised staff member of the institution being
              registered. Fill in your details and your school information
              below, and our team will contact you to continue the onboarding
              process.
            </Text>
          </SlabBody>
        </Slab>
        <VStack spacing={4} align={'stretch'} px={4} pb={6}>
          {imageUrl && (
            <Image
              src={imageUrl}
              alt="Institution registration banner"
              style={{
                width: '100%',
                height: 'auto',
                marginBottom: '20px',
              }}
            />
          )}
          <br />
          <FormControlBox form={form} title="First Name" formKey="first_name">
            <Input
              type="text"
              onChange={(e) =>
                form.setData('first_name', e.currentTarget.value)
              }
              value={form.data.first_name}
              required
            />
          </FormControlBox>
          <FormControlBox form={form} title="Last Name" formKey="last_name">
            <Input
              type="text"
              onChange={(e) => form.setData('last_name', e.currentTarget.value)}
              value={form.data.last_name}
              required
            />
          </FormControlBox>
          <FormControlBox form={form} title="Other Names" formKey="other_names">
            <Input
              type="text"
              onChange={(e) =>
                form.setData('other_names', e.currentTarget.value)
              }
              value={form.data.other_names}
            />
          </FormControlBox>
          <FormControlBox form={form} title="Phone" formKey="phone">
            <Input
              type="tel"
              onChange={(e) => form.setData('phone', e.currentTarget.value)}
              value={form.data.phone}
            />
          </FormControlBox>
          <FormControlBox form={form} title="Email" formKey="email">
            <Input
              type="email"
              onChange={(e) => form.setData('email', e.currentTarget.value)}
              value={form.data.email}
              required
            />
          </FormControlBox>
          <FormControlBox form={form} title="Gender" formKey="gender">
            <EnumSelect
              enumData={Gender}
              onChange={(e: any) => form.setData('gender', e.value)}
              required
            />
          </FormControlBox>
          <FormControlBox form={form} title="Password" formKey="password">
            <Input
              type="password"
              onChange={(e) => form.setData('password', e.currentTarget.value)}
              value={form.data.password}
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
                form.setData('password_confirmation', e.currentTarget.value)
              }
              value={form.data.password_confirmation}
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
                form.setData('institution', {
                  ...form.data.institution,
                  name: e.currentTarget.value,
                })
              }
              value={form.data.institution.name}
            />
            <FormErrorMessage>
              {form.errors['institution.name']}
            </FormErrorMessage>
          </FormControl>
          <FormControl isInvalid={!!form.errors['institution.phone']}>
            <FormLabel>Institution Phone</FormLabel>
            <Input
              type="text"
              onChange={(e) =>
                form.setData('institution', {
                  ...form.data.institution,
                  phone: e.currentTarget.value,
                })
              }
              value={form.data.institution.phone}
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
                form.setData('institution', {
                  ...form.data.institution,
                  email: e.currentTarget.value,
                })
              }
              value={form.data.institution.email}
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
                form.setData('institution', {
                  ...form.data.institution,
                  address: e.currentTarget.value,
                })
              }
              value={form.data.institution.address}
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
    </CenteredLayout>
  );
}
