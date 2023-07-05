import { Div } from '@/components/semantic';
import { generateRandomString, preventNativeSubmit } from '@/util/util';
import route from '@/util/route';
import {
  Avatar,
  Divider,
  FormControl,
  HStack,
  Input,
  Spacer,
  Text,
  VStack,
} from '@chakra-ui/react';
import React from 'react';
import FormControlBox from '@/components/forms/form-control-box';
import EnumSelect from '@/components/dropdown-select/enum-select';
import { Gender, Nationality, Religion } from '@/types/types';
import { AdmissionApplication, Institution } from '@/types/models';
import InputForm from '@/components/forms/input-form';
import { FormButton } from '@/components/buttons';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import { PageTitle } from '@/components/page-header';

interface Props {
  institution: Institution;
}

export default function AdmissionApplicationPage({ institution }: Props) {
  const form = useWebForm({
    reference: String(institution.id) + generateRandomString(16),
  } as AdmissionApplication);
  const { handleResponseToast } = useMyToast();

  async function onSubmit() {
    const res = await form.submit((data, web) =>
      web.post(route('institutions.admissions.store', [institution.uuid]), data)
    );

    if (!handleResponseToast(res)) return;

    Inertia.visit(
      route('institutions.admissions.success', [
        institution.uuid,
        res.data.data.id,
      ])
    );
  }

  function maxYear() {
    const maxYear = new Date().getFullYear() - 3;
    return `${maxYear}-01-01`;
  }

  return (
    <Div bg={'brand.50'} minH={'100vh'}>
      <Div shadow={'md'} py={5} px={5} background={'white'}>
        <HStack align={'stretch'} spacing={5}>
          <Avatar
            src={institution.photo}
            aria-label={institution.name + ' Logo'}
          />
          <Text fontWeight={'bold'} fontSize={'2xl'}>
            {institution.name}
          </Text>
        </HStack>
      </Div>
      <Div
        bg={'white'}
        mx={'auto'}
        shadow={'md'}
        rounded={'md'}
        as={'form'}
        my={12}
        onSubmit={preventNativeSubmit(onSubmit)}
        mt={3}
        maxW={{ base: '400px', md: '650px' }}
      >
        <PageTitle py={4} px={4} fontWeight={'semibold'} fontSize={'26px'}>
          Admission Application
        </PageTitle>
        <Divider mb={2} />
        <VStack spacing={4} align={'stretch'} p={6}>
          <FormControlBox
            form={form as any}
            title="First Name"
            formKey="first_name"
          >
            <Input
              type="text"
              onChange={(e) =>
                form.setValue('first_name', e.currentTarget.value)
              }
              value={form.data.first_name}
              required
            />
          </FormControlBox>
          <FormControlBox
            form={form as any}
            title="Last Name"
            formKey="last_name"
          >
            <Input
              type="text"
              onChange={(e) =>
                form.setValue('last_name', e.currentTarget.value)
              }
              value={form.data.last_name}
              required
            />
          </FormControlBox>
          <FormControlBox
            form={form as any}
            title="Other Names"
            formKey="other_names"
          >
            <Input
              type="text"
              onChange={(e) =>
                form.setValue('other_names', e.currentTarget.value)
              }
              value={form.data.other_names}
            />
          </FormControlBox>
          <FormControlBox form={form as any} title="Phone" formKey="phone">
            <Input
              type="phone"
              onChange={(e) => form.setValue('phone', e.currentTarget.value)}
              value={form.data.phone}
            />
          </FormControlBox>
          <FormControlBox form={form as any} title="Email" formKey="email">
            <Input
              type="email"
              onChange={(e) => form.setValue('email', e.currentTarget.value)}
              value={form.data.email}
              required
            />
          </FormControlBox>
          <FormControlBox form={form as any} title="Gender" formKey="gender">
            <EnumSelect
              enumData={Gender}
              onChange={(e: any) => form.setValue('gender', e.value)}
              required
            />
          </FormControlBox>
          <HStack align={'stretch'}>
            <InputForm
              form={form as any}
              title="Father's name"
              formKey="fathers_name"
            />
            <Spacer />
            <InputForm
              form={form as any}
              title="Father's Occupation"
              formKey="fathers_occupation"
            />
          </HStack>
          <HStack align={'stretch'}>
            <InputForm
              form={form as any}
              title="Mother's name"
              formKey="mothers_name"
            />
            <Spacer />
            <InputForm
              form={form as any}
              title="Mother's Occupation"
              formKey="mothers_occupation"
            />
          </HStack>
          <InputForm
            form={form as any}
            title="Parents Phone"
            formKey="guardian_phone"
          />
          <InputForm form={form as any} title="Address" formKey="address" />
          <InputForm
            form={form as any}
            title="Previous School Attended"
            formKey="previous_school_attended"
          />
          <InputForm
            form={form as any}
            title="Date of Birth"
            formKey="dob"
            type="date"
            max={maxYear()}
          />
          <FormControlBox
            form={form as any}
            title="Religion"
            formKey="religion"
          >
            <EnumSelect
              enumData={Religion}
              onChange={(e: any) => form.setValue('religion', e.value)}
              required
            />
          </FormControlBox>
          <FormControlBox
            form={form as any}
            title="Nationality"
            formKey="nationality"
          >
            <EnumSelect
              enumData={Nationality}
              onChange={(e: any) => form.setValue('nationality', e.value)}
              required
            />
          </FormControlBox>
          <FormControl>
            <FormButton isLoading={form.processing} title="Apply" />
          </FormControl>
        </VStack>
      </Div>
      <Spacer height={'30px'} />
    </Div>
  );
}
