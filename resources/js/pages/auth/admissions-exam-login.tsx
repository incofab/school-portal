import {
  FormControl,
  FormErrorMessage,
  FormLabel,
  Input,
  Spacer,
  VStack,
} from '@chakra-ui/react';
import React from 'react';
import { BrandButton } from '@/components/buttons';
import CenteredLayout from '@/components/centered-layout';
import { useForm } from '@inertiajs/react';
import { preventNativeSubmit } from '@/util/util';
import route from '@/util/route';

export default function AdmissionExamLogin() {
  const { data, setData, post, processing, errors } = useForm({
    event_code: '',
    application_no: '',
  });

  const handleSubmit = () => {
    post(route('admissions.exam.login.store'));
  };

  return (
    <CenteredLayout title="Admission Exam Login">
      <VStack
        spacing={4}
        align={'stretch'}
        as={'form'}
        onSubmit={preventNativeSubmit(handleSubmit)}
      >
        <FormControl isInvalid={!!errors.event_code}>
          <FormLabel>Event Code</FormLabel>
          <Input
            type="text"
            value={data.event_code}
            onChange={(e) => setData('event_code', e.currentTarget.value)}
          />
          <FormErrorMessage>{errors.event_code}</FormErrorMessage>
        </FormControl>
        <FormControl isInvalid={!!errors.application_no}>
          <FormLabel>Application no</FormLabel>
          <Input
            type="text"
            value={data.application_no}
            onChange={(e) => setData('application_no', e.currentTarget.value)}
          />
          <FormErrorMessage>{errors.application_no}</FormErrorMessage>
        </FormControl>
        <Spacer height={2} />
        <BrandButton isLoading={processing} type="submit" title="Start Exam" />
      </VStack>
    </CenteredLayout>
  );
}
