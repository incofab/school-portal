import React from 'react';
import {
  FormControl,
  Button,
  FormErrorMessage,
  HStack,
  Icon,
  VStack,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import FileDropper from '@/components/file-dropper';
import FileObject from '@/components/file-dropper/file-object';
import { FileDropperType } from '@/components/file-dropper/common';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { CloudArrowDownIcon } from '@heroicons/react/24/solid';
import { Div } from '../semantic';
import FormControlBox from '../forms/form-control-box';
import AcademicSessionSelect from '../selectors/academic-session-select';
import EnumSelect from '../dropdown-select/enum-select';
import { TermType } from '@/types/types';
import { preventNativeSubmit } from '@/util/util';
import { ReceiptType } from '@/types/models';
import ClassificationSelect from '../selectors/classification-select';
import ReceiptTypeSelect from '../selectors/receipt-type-select';
import { BrandButton } from '../buttons';
import useSharedProps from '@/hooks/use-shared-props';

interface Props {
  receiptTypes: ReceiptType[];
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function UploadFeePaymentModal({
  receiptTypes,
  isOpen,
  onSuccess,
  onClose,
}: Props) {
  const { handleResponseToast, toastError } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { currentAcademicSessionId, currentTerm } = useSharedProps();
  const webForm = useWebForm({
    files: [] as FileObject[],
    term: currentTerm,
    academic_session_id: currentAcademicSessionId,
    receipt_type_id: '',
    classification_id: '',
  });

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) => {
      const formData = new FormData();
      const file = data.files[0] ?? null;
      formData.append('file', file?.file, file?.getNameWithExtension());
      formData.append('term', data.term);
      formData.append('academic_session_id', String(data.academic_session_id));
      formData.append('receipt_type_id', data.receipt_type_id);
      return web.post(instRoute('fee-payments.upload'), formData);
    });

    if (!handleResponseToast(res)) return;

    onClose();
    webForm.reset();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose }}
      headerContent={'Upload Payments'}
      bodyContent={
        <VStack spacing={2}>
          <FormControlBox
            form={webForm as any}
            title="Receipt Category"
            formKey="receipt_type_id"
          >
            <ReceiptTypeSelect
              selectValue={webForm.data.receipt_type_id}
              receiptTypes={receiptTypes}
              isMulti={false}
              isClearable={true}
              required
              onChange={(e: any) =>
                webForm.setValue('receipt_type_id', e?.value)
              }
            />
          </FormControlBox>
          <Div
            padding={3}
            border={'solid 1px #CCCCCC'}
            borderRadius={'md'}
            w={'full'}
          >
            <FormControlBox
              form={webForm as any}
              title="Class"
              formKey="classification_id"
            >
              <ClassificationSelect
                selectValue={webForm.data.classification_id}
                isMulti={false}
                isClearable={true}
                required
                onChange={(e: any) =>
                  webForm.setValue('classification_id', e?.value)
                }
              />
            </FormControlBox>
            <BrandButton
              mt={3}
              leftIcon={<Icon as={CloudArrowDownIcon} />}
              variant={'solid'}
              colorScheme={'brand'}
              title="Download Recording Template"
              onClick={preventNativeSubmit(() => {
                if (
                  !webForm.data.classification_id ||
                  !webForm.data.receipt_type_id
                ) {
                  toastError('You must select the class and receipt category');
                  return;
                }
                window.location.href = instRoute('fee-payments.download', [
                  webForm.data.classification_id,
                  webForm.data.receipt_type_id,
                ]);
              })}
            />
          </Div>
          <FormControlBox
            form={webForm as any}
            title="Academic Session"
            formKey="academic_session_id"
          >
            <AcademicSessionSelect
              selectValue={webForm.data.academic_session_id}
              isMulti={false}
              isClearable={true}
              onChange={(e: any) =>
                webForm.setValue('academic_session_id', e?.value)
              }
            />
          </FormControlBox>
          <FormControlBox form={webForm as any} title="Term" formKey="term">
            <EnumSelect
              enumData={TermType}
              selectValue={webForm.data.term}
              isClearable={true}
              onChange={(e: any) => webForm.setValue('term', e?.value)}
            />
          </FormControlBox>
          <FormControl isInvalid={!!webForm.errors.files}>
            <FileDropper
              files={webForm.data.files}
              onChange={(files) => webForm.setValue('files', files)}
              multiple={false}
              accept={[FileDropperType.Excel]}
            />
            <FormErrorMessage>{webForm.errors.files}</FormErrorMessage>
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
            Upload
          </Button>
        </HStack>
      }
    />
  );
}
