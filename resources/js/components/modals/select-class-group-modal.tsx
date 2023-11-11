import React, { useState } from 'react';
import { Button, FormControl, FormLabel, HStack } from '@chakra-ui/react';
import GenericModal from '@/components/generic-modal';
import { ClassificationGroup } from '@/types/models';
import ClassificationGroupSelect from '../selectors/classification-group-select';

interface Props {
  isOpen: boolean;
  onClose(): void;
  onSuccess(classificationGroupId: number | string): void;
  classificationGroups: ClassificationGroup[];
  headerTitle?: string;
  label?: string;
}

export default function SelectClassGroupModal({
  isOpen,
  onSuccess,
  onClose,
  classificationGroups,
  headerTitle,
  label,
}: Props) {
  const [classificationGroupId, setClassificationGroupId] = useState<
    number | string
  >();

  const onSubmit = async () => {
    if (!classificationGroupId) {
      return;
    }
    onClose();
    onSuccess(classificationGroupId);
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={headerTitle ?? 'Select Class Group'}
      bodyContent={
        <FormControl>
          <FormLabel>{label ?? `Select Class Group`}</FormLabel>
          <ClassificationGroupSelect
            classificationGroups={classificationGroups}
            selectValue={classificationGroupId}
            value={classificationGroupId}
            isMulti={false}
            isClearable={true}
            onChange={(e: any) => setClassificationGroupId(e.value)}
          />
        </FormControl>
      }
      footerContent={
        <HStack spacing={2}>
          <Button variant={'ghost'} onClick={onClose}>
            Close
          </Button>
          <Button colorScheme={'brand'} onClick={onSubmit}>
            Submit
          </Button>
        </HStack>
      }
    />
  );
}
