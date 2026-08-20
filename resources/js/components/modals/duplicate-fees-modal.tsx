import React from 'react';
import {
  Box,
  Button,
  Checkbox,
  Divider,
  HStack,
  Spinner,
  Text,
  VStack,
} from '@chakra-ui/react';
import startCase from 'lodash/startCase';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useWebForm from '@/hooks/use-web-form';
import web from '@/util/web';
import { AcademicSession, Fee } from '@/types/models';
import { formatAsCurrency } from '@/util/util';
import feeableUtil from '@/util/feeable-util';

interface Props {
  isOpen: boolean;
  onClose(): void;
  onSuccess(): void;
}

export default function DuplicateFeesModal({
  isOpen,
  onClose,
  onSuccess,
}: Props) {
  const { handleResponseToast, toastError } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const [loading, setLoading] = React.useState(false);
  const [term, setTerm] = React.useState<string | null>(null);
  const [academicSession, setAcademicSession] =
    React.useState<AcademicSession | null>(null);
  const [fees, setFees] = React.useState<Fee[]>([]);
  const webForm = useWebForm({ fee_ids: [] as number[] });

  React.useEffect(() => {
    if (!isOpen) return;

    setLoading(true);
    webForm.setValue('fee_ids', []);
    web
      .get(instRoute('fees.previous-term'))
      .then((res) => {
        setTerm(res.data.term);
        setAcademicSession(res.data.academic_session);
        setFees(res.data.fees ?? []);
      })
      .catch(() => toastError('Unable to load previous term fees'))
      .finally(() => setLoading(false));
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [isOpen]);

  const toggleFee = (feeId: number, checked: boolean) => {
    webForm.setValue(
      'fee_ids',
      checked
        ? [...webForm.data.fee_ids, feeId]
        : webForm.data.fee_ids.filter((id) => id !== feeId)
    );
  };

  const onSubmit = async () => {
    const res = await webForm.submit((data, axiosWeb) =>
      axiosWeb.post(instRoute('fees.duplicate'), data)
    );
    if (!handleResponseToast(res)) return;

    onClose();
    onSuccess();
  };

  return (
    <GenericModal
      props={{ isOpen, onClose, size: 'lg' }}
      headerContent={'Duplicate Fees'}
      bodyContent={
        <VStack align={'stretch'} spacing={3}>
          <Text color={'gray.600'} fontSize={'sm'}>
            {term && academicSession
              ? `Select fees from ${startCase(term)} Term, ${
                  academicSession.title
                } to duplicate into the current term.`
              : 'Select the fees to duplicate into the current term.'}
          </Text>
          {loading ? (
            <HStack justify={'center'} py={6}>
              <Spinner />
            </HStack>
          ) : fees.length === 0 ? (
            <Text color={'gray.500'} py={4}>
              No fees were found for the previous term.
            </Text>
          ) : (
            <VStack align={'stretch'} spacing={2}>
              <Checkbox
                isChecked={webForm.data.fee_ids.length === fees.length}
                colorScheme={'brand'}
                onChange={(e) =>
                  webForm.setValue(
                    'fee_ids',
                    e.currentTarget.checked ? fees.map((fee) => fee.id) : []
                  )
                }
              >
                Select All
              </Checkbox>
              <Divider />
              <VStack
                align={'stretch'}
                spacing={2}
                maxH={'320px'}
                overflowY={'auto'}
              >
                {fees.map((fee) => (
                  <Checkbox
                    key={fee.id}
                    colorScheme={'brand'}
                    isChecked={webForm.data.fee_ids.includes(fee.id)}
                    onChange={(e) => toggleFee(fee.id, e.currentTarget.checked)}
                  >
                    <Box>
                      <Text fontWeight={'medium'}>
                        {fee.title} - {formatAsCurrency(fee.amount)}
                      </Text>
                      <Text fontSize={'xs'} color={'gray.500'}>
                        {fee.fee_categories
                          ?.map((item) =>
                            feeableUtil(item.feeable, item.feeable_type).getName()
                          )
                          .join(', ')}
                      </Text>
                    </Box>
                  </Checkbox>
                ))}
              </VStack>
            </VStack>
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
            isDisabled={webForm.data.fee_ids.length === 0}
          >
            Duplicate
          </Button>
        </HStack>
      }
    />
  );
}
