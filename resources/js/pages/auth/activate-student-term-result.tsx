import {
  Box,
  Button,
  Divider,
  HStack,
  Icon,
  Spacer,
  Text,
  VStack,
  useColorModeValue,
} from '@chakra-ui/react';
import React, { useState } from 'react';
import { Div } from '@/components/semantic';
import route from '@/util/route';
import { Inertia } from '@inertiajs/inertia';
import { TermResult } from '@/types/models';
import { preventNativeSubmit, stripInitials } from '@/util/util';
import useWebForm from '@/hooks/use-web-form';
import CenteredLayout from '@/components/centered-layout';
import InputForm from '@/components/forms/input-form';
import startCase from 'lodash/startCase';
import useMyToast from '@/hooks/use-my-toast';
import DestructivePopover from '@/components/destructive-popover';
import { ChatBubbleLeftRightIcon } from '@heroicons/react/24/outline';

function whatsappLink(phoneNumber?: string | null) {
  if (!phoneNumber) {
    return null;
  }

  const normalizedPhone = phoneNumber.replace(/\D+/g, '');
  if (!normalizedPhone) {
    return null;
  }

  return `https://wa.me/${normalizedPhone}?text=${encodeURIComponent(
    'I want to check my results.'
  )}`;
}

export default function StudentTermResultActivation() {
  const form = useWebForm({
    pin: '',
    student_code: '',
    term_result_id: '',
  });
  const [termResults, setTermResults] = useState([] as TermResult[]);
  const { toastError, toastSuccess } = useMyToast();
  const whatsappPhoneNumber = import.meta.env.VITE_WHATSAPP_PHONE_NUMBER as
    | string
    | undefined;
  const whatsappHref = whatsappLink(whatsappPhoneNumber);
  const whatsappCardBg = useColorModeValue('green.50', 'green.900');
  const whatsappCardBorder = useColorModeValue('green.200', 'green.700');
  const whatsappIconBg = useColorModeValue('green.500', 'green.300');
  const whatsappIconColor = useColorModeValue('white', 'gray.900');
  const mutedTextColor = useColorModeValue('gray.600', 'gray.300');

  async function submitForm(termResultId?: number, onClose?: () => void) {
    const res = await form.submit((data, web) => {
      return web.post(route('activate-term-result.store'), {
        ...data,
        term_result_id: termResultId,
        student_code: stripInitials(data.student_code),
      });
    });

    if (!res.ok) {
      return void toastError(res.message);
    }

    if (onClose) {
      onClose();
    }

    if (!res.data.has_multiple_results) {
      toastSuccess(res.message ?? 'Result activated, please wait...');
      Inertia.visit(res.data.redirect_url);
      return;
    }
    setTermResults(res.data.term_results);
  }

  return (
    <CenteredLayout title="Activate result">
      {termResults.length === 0 ? (
        <VStack
          as={'form'}
          spacing={4}
          align={'stretch'}
          onSubmit={preventNativeSubmit(() => submitForm())}
        >
          {whatsappPhoneNumber && (
            <Box
              borderWidth={1}
              borderColor={whatsappCardBorder}
              bg={whatsappCardBg}
              rounded={'md'}
              p={4}
            >
              <HStack align={'start'} spacing={3}>
                <Box
                  rounded={'full'}
                  bg={whatsappIconBg}
                  color={whatsappIconColor}
                  p={2}
                  flexShrink={0}
                >
                  <Icon as={ChatBubbleLeftRightIcon} boxSize={5} />
                </Box>
                <VStack align={'stretch'} spacing={2}>
                  <Text fontWeight={'bold'}>Check your result on WhatsApp</Text>
                  <Text fontSize={'sm'} color={mutedTextColor}>
                    You can now check your result by chatting with us on
                    WhatsApp, as long as your phone number is saved on the
                    portal as the student's phone number or as the guardian's
                    phone number.
                  </Text>
                  <Text fontSize={'sm'} color={mutedTextColor}>
                    WhatsApp number:{' '}
                    <Text
                      as={'span'}
                      fontWeight={'semibold'}
                      color={'green.700'}
                    >
                      {whatsappPhoneNumber}
                    </Text>
                  </Text>
                  {whatsappHref && (
                    <Button
                      as={'a'}
                      href={whatsappHref}
                      target="_blank"
                      rel="noopener noreferrer"
                      colorScheme={'green'}
                      size={'sm'}
                      alignSelf={'flex-start'}
                    >
                      Open WhatsApp to check result
                    </Button>
                  )}
                </VStack>
              </HStack>
            </Box>
          )}
          <InputForm
            form={form as any}
            formKey="student_code"
            title="Student Id"
          />
          <InputForm form={form as any} formKey="pin" title="Pin" />
          <Spacer height={2} />
          <Div>
            <Button
              type="submit"
              colorScheme={'brand'}
              isLoading={form.processing}
            >
              Submit
            </Button>
          </Div>
        </VStack>
      ) : (
        <VStack
          background={useColorModeValue('white', 'gray.800')}
          divider={<Divider />}
          spacing={3}
          width={'full'}
          align={'stretch'}
          mt={3}
        >
          <Text fontWeight={'semibold'}>
            Select the result you want to activate:
          </Text>
          {termResults.map((termResult) => (
            <HStack key={termResult.id} width={'full'}>
              <Text>{termResult.academic_session?.title}</Text>
              <Spacer />
              <Text>{startCase(termResult.term)}</Text>
              <Spacer />
              <Text>{termResult.classification?.title}</Text>
              <Spacer />
              <DestructivePopover
                label={'Activate this result?'}
                onConfirm={(onClose) => submitForm(termResult.id, onClose)}
                isLoading={form.processing}
                positiveButtonLabel="Yes"
              >
                <Button variant={'solid'} colorScheme="brand">
                  Activate
                </Button>
              </DestructivePopover>
            </HStack>
          ))}
        </VStack>
      )}
    </CenteredLayout>
  );
}
