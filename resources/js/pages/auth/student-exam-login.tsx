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
import { preventNativeSubmit } from '@/util/util';
import route from '@/util/route';
import { InstitutionGroup } from '@/types/models';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';

export default function StudentExamLogin({
  institutionGroup,
}: {
  institutionGroup?: InstitutionGroup;
}) {
  const { handleResponseToast } = useMyToast();
  const webForm = useWebForm({
    student_code: '',
    event_code: '',
  });

  const handleSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(route('student.exam.login.store'), data)
    );

    if (!handleResponseToast(res)) return;

    window.location.href = route('institutions.display-exam-page', [
      res.data.institution.uuid,
      res.data.exam.exam_no,
    ]);
  };

  return (
    <CenteredLayout title="Student Login" bgImage={institutionGroup?.banner}>
      <VStack
        spacing={4}
        align={'stretch'}
        as={'form'}
        onSubmit={preventNativeSubmit(handleSubmit)}
      >
        <FormControl isInvalid={!!webForm.errors.student_code}>
          <FormLabel>Student Code</FormLabel>
          <Input
            type="text"
            value={webForm.data.student_code}
            onChange={(e) =>
              webForm.setValue('student_code', e.currentTarget.value)
            }
          />
          <FormErrorMessage>{webForm.errors.student_code}</FormErrorMessage>
        </FormControl>
        <FormControl isInvalid={!!webForm.errors.event_code}>
          <FormLabel>Event Code</FormLabel>
          <Input
            type="text"
            value={webForm.data.event_code}
            onChange={(e) =>
              webForm.setValue('event_code', e.currentTarget.value)
            }
          />
          <FormErrorMessage>{webForm.errors.event_code}</FormErrorMessage>
        </FormControl>
        <Spacer height={2} />
        <BrandButton
          isLoading={webForm.processing}
          type="submit"
          title="Start Exam"
        />
      </VStack>
    </CenteredLayout>
  );
}
