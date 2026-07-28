import React from 'react';
import {
  Accordion,
  AccordionButton,
  AccordionIcon,
  AccordionItem,
  AccordionPanel,
  Box,
  Button,
  HStack,
  VStack,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import InputForm from '../forms/input-form';
import { ClassificationGroup } from '@/types/models';

interface Props {
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
  classificationGroup?: ClassificationGroup;
}

export default function CreateEditClassGroupModal({
  isOpen,
  onSuccess,
  onClose,
  classificationGroup,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    title: classificationGroup?.title ?? '',
    head_of_school_title:
      classificationGroup?.head_of_school_title ?? 'Principal',
    head_of_class_title:
      classificationGroup?.head_of_class_title ?? 'Form Teacher',
    student_title: classificationGroup?.student_title ?? 'Students',
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      classificationGroup
        ? web.put(
            instRoute('classification-groups.update', [classificationGroup]),
            data
          )
        : web.post(instRoute('classification-groups.store'), data)
    );

    if (!handleResponseToast(res)) {
      return;
    }

    onClose();
    webForm.reset();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Create Class Group'}
      bodyContent={
        <VStack spacing={2}>
          <InputForm
            form={webForm as any}
            formKey="title"
            title="Class Group Title"
          />
          <Accordion width="full" allowToggle>
            <AccordionItem>
              <AccordionButton px={0}>
                <Box as="span" flex="1" textAlign="left" fontWeight="bold">
                  More Options
                </Box>
                <AccordionIcon />
              </AccordionButton>
              <AccordionPanel px={0} pb={0}>
                <VStack spacing={2}>
                  <InputForm
                    form={webForm as any}
                    formKey="head_of_school_title"
                    title="Head of School Title"
                  />
                  <InputForm
                    form={webForm as any}
                    formKey="head_of_class_title"
                    title="Head of Class Title"
                  />
                  <InputForm
                    form={webForm as any}
                    formKey="student_title"
                    title="Student Title"
                  />
                </VStack>
              </AccordionPanel>
            </AccordionItem>
          </Accordion>
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
            Create
          </Button>
        </HStack>
      }
    />
  );
}
