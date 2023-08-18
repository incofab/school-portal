import { preventNativeSubmit } from '@/util/util';
import route from '@/util/route';
import {
  Button,
  FormControl,
  FormErrorMessage,
  FormLabel,
  Input,
  VStack,
} from '@chakra-ui/react';
import { useForm } from '@inertiajs/inertia-react';
import React from 'react';
import CenteredLayout from '@/components/centered-layout';
import { Institution } from '@/types/models';

export default function ExamLogin({
  institution,
}: {
  institution: Institution;
}) {
  const form = useForm({
    exam_no: '',
  });

  function onSubmit() {
    form.get(route('display-exam-page', [institution.uuid, form.data.exam_no]));
  }

  return (
    <CenteredLayout title="Exam Login">
      <VStack
        spacing={4}
        align={'stretch'}
        as={'form'}
        onSubmit={preventNativeSubmit(onSubmit)}
      >
        <FormControl isInvalid={!!form.errors.exam_no}>
          <FormLabel>Exam No</FormLabel>
          <Input
            id="email"
            type="text"
            value={form.data.exam_no}
            onChange={(e) => form.setData('exam_no', e.currentTarget.value)}
          />
          <FormErrorMessage>{form.errors.exam_no}</FormErrorMessage>
        </FormControl>
        <Button
          isLoading={form.processing}
          loadingText="Logging in"
          type="submit"
          colorScheme={'brand'}
          id="login"
        >
          Login
        </Button>
      </VStack>
    </CenteredLayout>
  );
}
