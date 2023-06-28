import React from 'react';
import {
  Button,
  Checkbox,
  Divider,
  FormControl,
  FormLabel,
  HStack,
  Spacer,
  VStack,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { Student } from '@/types/models';
import ClassificationSelect from '../selectors/classification-select';
import Dt from '../dt';

interface Props {
  student: Student;
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function ChangeStudentClassModal({
  isOpen,
  onSuccess,
  onClose,
  student,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    destination_class: '',
    move_to_alumni: false,
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(instRoute('students.change-class', [student]), data)
    );

    if (!handleResponseToast(res)) return;

    onClose();
    webForm.reset();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Change Student Class'}
      bodyContent={
        <VStack spacing={2} align={'stretch'}>
          <Dt
            contentData={[
              { label: 'Student Name', value: student.user?.full_name ?? '' },
              {
                label: 'Student Class',
                value: student.classification?.title ?? '',
              },
            ]}
          />
          <Divider height={4} />
          <Spacer />
          {!webForm.data.move_to_alumni && (
            <FormControl>
              <FormLabel>Class</FormLabel>
              <ClassificationSelect
                value={webForm.data.destination_class}
                isMulti={false}
                isClearable={true}
                onChange={(e: any) =>
                  webForm.setValue('destination_class', e.value)
                }
              />
            </FormControl>
          )}
          <Spacer height={5} />
          <FormControl>
            <Checkbox
              isChecked={webForm.data.move_to_alumni}
              onChange={(e) =>
                webForm.setData({
                  ...webForm.data,
                  move_to_alumni: e.currentTarget.checked,
                  destination_class: '',
                })
              }
              size={'md'}
              colorScheme="brand"
            >
              Move this student to alumni
            </Checkbox>
          </FormControl>
        </VStack>
      }
      footerContent={
        <HStack spacing={2}>
          <Button variant={'ghost'} onClick={onClose}>
            Close
          </Button>
          <Button
            colorScheme={'brand'}
            onClick={onSubmit}
            isLoading={webForm.processing}
          >
            Submit
          </Button>
        </HStack>
      }
    />
  );
}
