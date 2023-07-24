import {
  Button,
  Divider,
  HStack,
  Spacer,
  Text,
  VStack,
} from '@chakra-ui/react';
import React, { useState } from 'react';
import { Div } from '@/components/semantic';
import route from '@/util/route';
import { Inertia } from '@inertiajs/inertia';
import { TermResult } from '@/types/models';
import { preventNativeSubmit } from '@/util/util';
import useWebForm from '@/hooks/use-web-form';
import CenteredLayout from '@/components/centered-layout';
import InputForm from '@/components/forms/input-form';
import startCase from 'lodash/startCase';
import useMyToast from '@/hooks/use-my-toast';
import DestructivePopover from '@/components/destructive-popover';

export default function StudentTermResultActivation() {
  const form = useWebForm({
    pin: '',
    student_code: '',
    term_result_id: '',
  });
  const [termResults, setTermResults] = useState([] as TermResult[]);
  const { toastError, toastSuccess } = useMyToast();

  async function submitForm(termResultId?: number, onClose?: () => void) {
    const res = await form.submit((data, web) => {
      return web.post(route('activate-term-result.store'), {
        ...data,
        term_result_id: termResultId,
      });
    });

    if (!res.ok) {
      return void toastError(res.message);
    }

    if (onClose) {
      onClose();
    }

    if (!res.data.has_multiple_results) {
      toastSuccess('Result activated, please wait...');
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
          onSubmit={preventNativeSubmit((e: any) => submitForm())}
        >
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
          background={'white'}
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
