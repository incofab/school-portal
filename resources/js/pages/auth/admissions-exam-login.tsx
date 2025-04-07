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
    student_code: '',
    application_no: '',
  });

  const handleSubmit = () => {
    post(route('admissions.exam.login.store'));
  };

  return (
    <CenteredLayout title="Student Login">
      <VStack
        spacing={4}
        align={'stretch'}
        as={'form'}
        onSubmit={preventNativeSubmit(handleSubmit)}
      >
        <FormControl isInvalid={!!errors.student_code}>
          <FormLabel>Student Code</FormLabel>
          <Input
            type="text"
            value={data.student_code}
            onChange={(e) => setData('student_code', e.currentTarget.value)}
          />
          <FormErrorMessage>{errors.student_code}</FormErrorMessage>
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
