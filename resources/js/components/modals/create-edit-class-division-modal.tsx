import React from 'react';
import {
  Button,
  FormControl,
  FormLabel,
  HStack,
  VStack,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import InputForm from '../forms/input-form';
import { ClassDivision } from '@/types/models';
import EnumSelect from '../dropdown-select/enum-select';
import { Nullable, ResultTemplate, SelectOptionType } from '@/types/types';
import ClassificationSelect from '../selectors/classification-select';
import { MultiValue } from 'react-select';

interface Props {
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
  classDivision?: ClassDivision;
}

export default function CreateEditClassDivisionModal({
  isOpen,
  onSuccess,
  onClose,
  classDivision,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    title: classDivision?.title ?? '',
    result_template: classDivision?.result_template ?? null,
    classification_ids: null as Nullable<MultiValue<SelectOptionType<number>>>,
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) => {
      var formattedData = {
        ...data,
        classification_ids: data.classification_ids?.map((c) => c.value),
      };
      return classDivision
        ? web.put(
            instRoute('class-divisions.update', [classDivision]),
            formattedData
          )
        : web.post(instRoute('class-divisions.store'), formattedData);
    });

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
      headerContent={'Create Class Division'}
      bodyContent={
        <VStack spacing={3}>
          <InputForm
            form={webForm as any}
            formKey="title"
            title="Class Division Title"
          />

          {classDivision === undefined && (
            <FormControl>
              <FormLabel mb={0}>Link Classes</FormLabel>
              <ClassificationSelect
                isMulti={true}
                value={webForm.data.classification_ids}
                isClearable={true}
                onChange={(e: any) => webForm.setValue('classification_ids', e)}
              />
            </FormControl>
          )}

          <FormControl>
            <FormLabel mb={0}>Result Template [Optional]</FormLabel>
            <EnumSelect
              enumData={ResultTemplate}
              selectValue={webForm.data['result_template']}
              onChange={(e: any) =>
                webForm.setValue('result_template', e.value)
              }
            />
            <small>
              <i>
                Set template if you want all classes under this to have the same
                result template
              </i>
            </small>
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
            Create
          </Button>
        </HStack>
      }
    />
  );
}
