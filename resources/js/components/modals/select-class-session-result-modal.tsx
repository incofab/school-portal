import React, { useState } from 'react';
import { Button, FormControl, FormLabel, HStack } from '@chakra-ui/react';
import GenericModal from '@/components/generic-modal';
import { AcademicSession, Classification } from '@/types/models';
import ClassificationSelect from '../selectors/classification-select';
import { Div } from '../semantic';
import AcademicSessionSelect from '../selectors/academic-session-select';
import useMyToast from '@/hooks/use-my-toast';
import { Inertia } from '@inertiajs/inertia';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useSharedProps from '@/hooks/use-shared-props';

interface Props {
  isOpen: boolean;
  onClose(): void;
  classifications: Classification[];
  academicSessions: AcademicSession[];
}

export default function SelectClassSessionResultModal({
  isOpen,
  onClose,
  classifications,
  academicSessions,
}: Props) {
  const { currentAcademicSessionId } = useSharedProps();
  const { toastError } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const [data, setData] = useState({
    classificationId: '',
    academicSessionId: currentAcademicSessionId,
  });

  const onSubmit = async () => {
    if (!data.classificationId) {
      toastError('Please select a class');
      return;
    }
    onClose();
    Inertia.visit(
      instRoute('classifications.session-results.index', [
        data.classificationId,
        data.academicSessionId,
      ])
    );
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Select Class Group'}
      bodyContent={
        <Div>
          <FormControl>
            <FormLabel>{`Select Class`}</FormLabel>
            <ClassificationSelect
              classifications={classifications}
              selectValue={data?.classificationId}
              isMulti={false}
              isClearable={true}
              onChange={(e: any) =>
                setData({ ...data, classificationId: e?.value })
              }
            />
          </FormControl>
          <FormControl>
            <FormLabel>{`Academic Session`}</FormLabel>
            <AcademicSessionSelect
              academicSessions={academicSessions}
              selectValue={data?.academicSessionId}
              isMulti={false}
              isClearable={true}
              onChange={(e: any) =>
                setData({ ...data, academicSessionId: e?.value })
              }
            />
          </FormControl>
        </Div>
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
