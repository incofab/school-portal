import React from 'react';
import {
  Accordion,
  AccordionButton,
  AccordionIcon,
  AccordionItem,
  AccordionPanel,
  Box,
  FormControl,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { ClassificationGroup } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import InputForm from '@/components/forms/input-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface Props {
  classificationGroup?: ClassificationGroup;
}

export default function CreateOrUpdateClassification({
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

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      const postData = {
        ...data,
      };
      return classificationGroup
        ? web.put(
            instRoute('classification-groups.update', [classificationGroup]),
            postData
          )
        : web.post(instRoute('classification-groups.store'), postData);
    });
    if (!handleResponseToast(res)) return;
    Inertia.visit(instRoute('classification-groups.index'));
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading
            title={`${classificationGroup ? 'Update' : 'Create'} Class Group`}
          />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
            >
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
                    <VStack spacing={4}>
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

              <FormControl>
                <FormButton isLoading={webForm.processing} />
              </FormControl>
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </DashboardLayout>
  );
}
