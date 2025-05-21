import React, { useState } from 'react';
import {
  Button,
  Checkbox,
  FormControl,
  FormLabel,
  HStack,
  Text,
  VStack,
} from '@chakra-ui/react';
import GenericModal from '@/components/generic-modal';
import {
  Association,
  Classification,
  ClassificationGroup,
} from '@/types/models';
import ClassificationGroupSelect from '../selectors/classification-group-select';
import ClassificationSelect from '../selectors/classification-select';
import AssociationSelect from '../selectors/association-select';
import { FeeCategoryType } from '@/types/types';
import useSharedProps from '@/hooks/use-shared-props';

interface FeeableTypeSelect {
  institution: boolean;
  class: boolean;
  classGroup: boolean;
  association: boolean;
}

interface FeeableTypeIdItem {
  morphClass: string;
  value: number;
  label: string;
}
interface FeeableTypeId {
  institution: FeeableTypeIdItem[];
  class: FeeableTypeIdItem[];
  classGroup: FeeableTypeIdItem[];
  association: FeeableTypeIdItem[];
}

interface FeeCategoryMorph {
  feeable_id: number;
  feeable_type: string;
  label: string;
  value: number;
}

interface Props {
  feeCategories: FeeCategoryMorph[];
  associations: Association[];
  classificationGroups: ClassificationGroup[];
  classifications: Classification[];
  isOpen: boolean;
  onClose(): void;
  onSuccess(feeCategoriesFormatted: FeeCategoryMorph[]): void;
}

export default function SelectFeeCategoryModal({
  isOpen,
  onSuccess,
  onClose,
  feeCategories,
  associations,
  classificationGroups,
  classifications,
}: Props) {
  const { currentInstitution } = useSharedProps();
  const initialFeeableTypeIds = {
    institution: [],
    class: [],
    classGroup: [],
    association: [],
  } as FeeableTypeId;
  for (let i = 0; i < feeCategories.length; i++) {
    const feeCategory = feeCategories[i];
    let arr = [];
    if (feeCategory.feeable_type === FeeCategoryType.Association) {
      arr = initialFeeableTypeIds.association;
    } else if (feeCategory.feeable_type === FeeCategoryType.Classification) {
      arr = initialFeeableTypeIds.class;
    } else if (
      feeCategory.feeable_type === FeeCategoryType.ClassificationGroup
    ) {
    } else if (feeCategory.feeable_type === FeeCategoryType.Institution) {
      arr = initialFeeableTypeIds.institution;
    }
    arr.push({
      morphClass: feeCategory.feeable_type,
      value: feeCategory.feeable_id,
      label: feeCategory.label,
    });
  }

  const [feeableTypeCheck, setFeeableTypeCheck] = useState({
    institution: initialFeeableTypeIds.institution.length === 0 ? false : true,
    class: initialFeeableTypeIds.class.length === 0 ? false : true,
    classGroup: initialFeeableTypeIds.classGroup.length === 0 ? false : true,
    association: initialFeeableTypeIds.association.length === 0 ? false : true,
  } as FeeableTypeSelect);

  const [feeableTypeIds, setFeeableTypeIds] = useState(initialFeeableTypeIds);

  const onSubmit = async () => {
    const feeCategoriesFormatted = [] as FeeCategoryMorph[];
    Object.entries(feeableTypeIds).forEach(([key, value]) => {
      if (!Boolean(feeableTypeCheck[key])) {
        return;
      }
      value.forEach((item: FeeableTypeIdItem) => {
        feeCategoriesFormatted.push({
          feeable_id: item.value,
          feeable_type: item.morphClass,
          label: item.label,
          value: item.value,
        });
      });
    });
    if (feeableTypeCheck.institution) {
      feeCategoriesFormatted.push({
        feeable_id: currentInstitution.id,
        feeable_type: 'institution',
        label: 'Everyone',
        value: currentInstitution.id,
      });
    }

    onClose();
    onSuccess(feeCategoriesFormatted);
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={"Students' Category"}
      bodyContent={
        <VStack spacing={2} align={'stretch'}>
          <Text>Who are these payment meant for?</Text>
          <VStack spacing={2} align={'stretch'}>
            <Checkbox
              isChecked={feeableTypeCheck.institution}
              onChange={(e) => {
                const isChecked = e.currentTarget.checked;
                setFeeableTypeCheck({
                  institution: isChecked,
                  class: isChecked ? false : feeableTypeCheck.class,
                  classGroup: isChecked ? false : feeableTypeCheck.classGroup,
                  association: isChecked ? false : feeableTypeCheck.association,
                });
              }}
              size={'md'}
              colorScheme="brand"
            >
              All students
            </Checkbox>
            <Checkbox
              isChecked={feeableTypeCheck.class}
              onChange={(e) => {
                const isChecked = e.currentTarget.checked;
                setFeeableTypeCheck({
                  institution: isChecked ? false : feeableTypeCheck.institution,
                  class: isChecked,
                  classGroup: isChecked ? false : feeableTypeCheck.classGroup,
                  association: feeableTypeCheck.association,
                });
              }}
              size={'md'}
              colorScheme="brand"
            >
              Class
            </Checkbox>
            <Checkbox
              isChecked={feeableTypeCheck.classGroup}
              onChange={(e) => {
                const isChecked = e.currentTarget.checked;
                setFeeableTypeCheck({
                  institution: isChecked ? false : feeableTypeCheck.institution,
                  classGroup: isChecked,
                  class: isChecked ? false : feeableTypeCheck.class,
                  association: feeableTypeCheck.association,
                });
              }}
              size={'md'}
              colorScheme="brand"
            >
              Class Group
            </Checkbox>
            <Checkbox
              isChecked={feeableTypeCheck.association}
              onChange={(e) => {
                const isChecked = e.currentTarget.checked;
                setFeeableTypeCheck({
                  ...feeableTypeCheck,
                  institution: isChecked ? false : feeableTypeCheck.institution,
                  association: isChecked,
                });
              }}
              size={'md'}
              colorScheme="brand"
            >
              Student Grouping
            </Checkbox>
          </VStack>
          <VStack spacing={2} align={'stretch'}>
            {feeableTypeCheck.class && (
              <FormControl>
                <FormLabel mb={0}>{'Class'}</FormLabel>
                <ClassificationSelect
                  selectValue={feeableTypeIds.class}
                  isMulti={true}
                  isClearable={true}
                  classifications={classifications}
                  onChange={(e: any) => {
                    setFeeableTypeIds({
                      ...feeableTypeIds,
                      class: e.map((item: any) => ({
                        morphClass: FeeCategoryType.Classification,
                        ...item,
                      })),
                    });
                  }}
                />
              </FormControl>
            )}
            {feeableTypeCheck.classGroup && (
              <FormControl>
                <FormLabel mb={0}>{'Class Group'}</FormLabel>
                <ClassificationGroupSelect
                  selectValue={feeableTypeIds.classGroup}
                  isMulti={true}
                  isClearable={true}
                  classificationGroups={classificationGroups}
                  onChange={(e: any) => {
                    setFeeableTypeIds({
                      ...feeableTypeIds,
                      classGroup: e.map((item: any) => ({
                        morphClass: FeeCategoryType.ClassificationGroup,
                        ...item,
                      })),
                    });
                  }}
                />
              </FormControl>
            )}
            {feeableTypeCheck.association && (
              <FormControl>
                <FormLabel mb={0}>{'Association'}</FormLabel>
                <AssociationSelect
                  selectValue={feeableTypeIds.association}
                  isMulti={true}
                  isClearable={true}
                  associations={associations}
                  onChange={(e: any) => {
                    setFeeableTypeIds({
                      ...feeableTypeIds,
                      association: e.map((item: any) => ({
                        morphClass: FeeCategoryType.Association,
                        ...item,
                      })),
                    });
                  }}
                />
              </FormControl>
            )}
          </VStack>
        </VStack>
      }
      footerContent={
        <HStack spacing={2}>
          <Button variant={'ghost'} onClick={onClose}>
            Close
          </Button>
          <Button colorScheme={'brand'} onClick={onSubmit}>
            Okay
          </Button>
        </HStack>
      }
    />
  );
}
