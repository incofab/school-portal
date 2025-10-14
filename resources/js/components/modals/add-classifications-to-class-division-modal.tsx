import React from 'react';
import { Button, FormControl, FormLabel, HStack } from '@chakra-ui/react';
import GenericModal from '@/components/generic-modal';
import { ClassDivision } from '@/types/models';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { MultiValue } from 'react-select';
import { Nullable, SelectOptionType } from '@/types/types';
import ClassificationSelect from '../selectors/classification-select';

interface AddClassificationsToClassDivisionModalProps {
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
  classDivision: ClassDivision;
}

export default function AddClassificationsToClassDivisionModal({
  isOpen,
  onSuccess,
  onClose,
  classDivision,
}: AddClassificationsToClassDivisionModalProps) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    classification_ids: ((classDivision.classifications?.length ?? 0) > 0
      ? classDivision.classifications?.map((c) => ({
          label: c.title,
          value: c.id,
        }))
      : null) as Nullable<MultiValue<SelectOptionType<number>>>,
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(
        instRoute('class-divisions.classifications.store', [classDivision]),
        {
          ...data,
          classification_ids: data.classification_ids?.map((c) => c.value),
        }
      )
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
      headerContent={`Add Classifications to ${classDivision.title} Division`}
      bodyContent={
        <FormControl>
          <FormLabel>Select Classes</FormLabel>
          <ClassificationSelect
            isMulti={true}
            value={webForm.data.classification_ids}
            isClearable={true}
            onChange={(e: any) =>
              webForm.setValue('classification_ids', e?.value)
            }
          />
        </FormControl>
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
            Add
          </Button>
        </HStack>
      }
    />
  );
}
