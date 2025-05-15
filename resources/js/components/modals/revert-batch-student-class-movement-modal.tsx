import React, { useEffect, useState } from 'react';
import {
  Button,
  HStack,
  Spinner,
  Text,
  Checkbox,
  VStack,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { Div } from '../semantic';
import { StudentClassMovement } from '@/types/models';
import FormControlBox from '../forms/form-control-box';
import ClassificationSelect from '../selectors/classification-select'; 

interface Props {
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
  changeClass: boolean;
  batchNo: string;
}

export default function RevertBatchStudentClassMovementModal({
  isOpen,
  onSuccess,
  onClose,
  changeClass,
  batchNo,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const [batchMovements, setBatchMovements] =
    useState<StudentClassMovement[]>();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    change_class: changeClass,
    batch_no: batchNo,
    destination_classification_id: '',
    move_to_alumni: false,
  });

  useEffect(
    function () {
      webForm
        .submit((data, web) => {
          return web.get(
            instRoute('student-class-movements.search', { batchNo })
          );
        })
        .then(({ ok, data }) => {
          if (!ok) {
            return;
          }
          setBatchMovements(data.result);
        });
    },
    [batchNo]
  );

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) => {
      return web.post(instRoute('student-class-movements.batch-revert'), data);
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
      headerContent={`${changeClass ? 'Change' : 'Revert'} batch changes`}
      bodyContent={
        <VStack spacing={3}>
          <Div>
            {batchMovements ? (
              <Text
                as={'p'}
                background={'green.100'}
                rounded={'md'}
                px={3}
                py={2}
                color={'green.700'}
              >
                {`${changeClass ? 'Change' : 'Revert'}`} all the{' '}
                <b>{setBatchMovements.length} students'</b> class changes in
                this class
              </Text>
            ) : (
              <Spinner color={'brand'}></Spinner>
            )}
          </Div>
          {changeClass && (
            <>
              {!webForm.data.move_to_alumni && (
                <FormControlBox
                  title="Destination Class"
                  form={webForm as any}
                  formKey="destination_class"
                >
                  <ClassificationSelect
                    value={webForm.data.destination_classification_id}
                    isMulti={false}
                    isClearable={true}
                    onChange={(e: any) =>
                      webForm.setValue(
                        'destination_classification_id',
                        e?.value
                      )
                    }
                    required
                  />
                </FormControlBox>
              )}
              <FormControlBox
                form={webForm as any}
                formKey="move_to_alumni"
                title=""
              >
                <Checkbox
                  isChecked={webForm.data.move_to_alumni}
                  onChange={(e) =>
                    webForm.setData({
                      ...webForm.data,
                      move_to_alumni: e.currentTarget.checked,
                      destination_classification_id: '',
                    })
                  }
                  size={'md'}
                  colorScheme="brand"
                >
                  Move this batch to alumni
                </Checkbox>
              </FormControlBox>
            </>
          )}
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
