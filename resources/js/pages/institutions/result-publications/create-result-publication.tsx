import { Classification } from '@/types/models';
import React, { useState } from 'react';

import { VStack, Checkbox, FormControl, Text } from '@chakra-ui/react';

import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface Props {
  classifications: Classification[];
}

export default function CreateResultPublications({ classifications }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();

  //== Set up the state to track which checkboxes are selected
  const [selectedClassifications, setSelectedClassifications] = useState<
    number[]
  >(classifications.map((classification) => classification.id));

  const webForm = useWebForm({});

  //== Handle the checkbox change event
  const handleCheckboxChange = (event: React.ChangeEvent<HTMLInputElement>) => {
    const classificationId = Number(event.target.value); // Ensure the ID is treated as a number

    if (event.target.checked) {
      setSelectedClassifications((prev) => [...prev, classificationId]);
    } else {
      setSelectedClassifications((prev) =>
        prev.filter((id) => id !== classificationId)
      );
    }
  };

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      const postData = {
        classifications: selectedClassifications,
      };

      return web.post(instRoute('result-publications.store'), postData);
    });
    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.visit(instRoute('dashboard'));
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading title="Result Publication" />

          <SlabBody>
            <VStack
              spacing={2}
              as={'form'}
              onSubmit={preventNativeSubmit(submit)}
            >
              <Text>Select classes whose results should be published.</Text>
              {classifications.map((classification) => (
                <FormControl key={classification.id}>
                  <Checkbox
                    isChecked={selectedClassifications.includes(
                      classification.id
                    )}
                    onChange={handleCheckboxChange}
                    value={classification.id}
                    size={'md'}
                    colorScheme="brand"
                  >
                    {classification.title}
                  </Checkbox>
                </FormControl>
              ))}

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
