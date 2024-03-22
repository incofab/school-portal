import React from 'react';
import {
  Button,
  Checkbox,
  Divider,
  FormControl,
  FormLabel,
  HStack,
  Icon,
  Spacer,
  VStack,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { Classification } from '@/types/models';
import ClassificationSelect from '../selectors/classification-select';
import { LinkButton } from '../buttons';
import { Div } from '../semantic';
import { ArrowRightIcon } from '@heroicons/react/24/outline';

interface Props {
  classification: Classification;
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function MigrateClassStudentsModal({
  isOpen,
  onSuccess,
  onClose,
  classification,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    destination_class: '',
    move_to_alumni: false,
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(
        instRoute('classifications.migrate-students', [classification]),
        data
      )
    );

    if (!handleResponseToast(res)) return;

    onClose();
    webForm.reset();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Migrate Class Students'}
      bodyContent={
        <VStack spacing={2} align={'stretch'}>
          <Div>
            <LinkButton
              title={'Select Students'}
              variant={'outline'}
              href={instRoute('change-multi-student-class.create', [
                classification,
              ])}
              rightIcon={<Icon as={ArrowRightIcon} />}
            />
          </Div>
          <Divider />
          <Spacer height={3} />
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
          <Spacer height={4} />
          <FormControl>
            <Checkbox
              isChecked={webForm.data.move_to_alumni}
              onChange={(e) =>
                webForm.setValue('move_to_alumni', e.currentTarget.checked)
              }
              size={'md'}
              colorScheme="brand"
            >
              Move students in {classification.title} to alumni
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
