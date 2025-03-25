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

export default function StudentLogin() {
  const { data, setData, post, processing, errors } = useForm({
    student_code: '',
    event_code: '',
  });

  const handleSubmit = () => {
    post(route('student.exam.login.store'));
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
        <FormControl isInvalid={!!errors.event_code}>
          <FormLabel>Event Code</FormLabel>
          <Input
            type="text"
            value={data.event_code}
            onChange={(e) => setData('event_code', e.currentTarget.value)}
          />
          <FormErrorMessage>{errors.event_code}</FormErrorMessage>
        </FormControl>
        <Spacer height={2} />
        <BrandButton isLoading={processing} type="submit" title="Start Exam" />
      </VStack>
    </CenteredLayout>
  );
}
