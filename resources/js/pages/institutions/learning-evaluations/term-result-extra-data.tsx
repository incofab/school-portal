import React, { useMemo } from 'react';
import { Divider, Text, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import { TermResult } from '@/types/models';
import { BrandButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import InputForm from '@/components/forms/input-form';

interface Props {
  termResult: TermResult;
}

export function TermResultExtraData({ termResult }: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const webForm = useWebForm({
    height: String(termResult.height ?? ''),
    weight: String(termResult.weight ?? ''),
    attendance_count: String(termResult.attendance_count ?? ''),
  });

  useMemo(() => {
    webForm.setData({
      height: String(termResult.height ?? ''),
      weight: String(termResult.weight ?? ''),
      attendance_count: String(termResult.attendance_count ?? ''),
    });
  }, [termResult.id]);

  const onSubmit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(instRoute('term-results.extra-data.update', [termResult]), data)
    );
    if (!handleResponseToast(res)) return;
    termResult.height = Number(webForm.data.height);
    termResult.weight = Number(webForm.data.weight);
    termResult.attendance_count = Number(webForm.data.attendance_count);
  };

  return (
    <VStack spacing={2} align={'start'}>
      <Text fontWeight={'bold'} fontSize={'16px'}>
        Other Attributes
      </Text>
      <Divider />
      <InputForm
        form={webForm as any}
        formKey="attendance_count"
        title="Attendance"
        onChange={(e) =>
          webForm.setValue('attendance_count', e.currentTarget.value)
        }
        key={`attendance-count-${termResult.id}`}
      />
      <InputForm
        form={webForm as any}
        formKey="height"
        title="Height (CM)"
        onChange={(e) => webForm.setValue('height', e.currentTarget.value)}
        key={`height-${termResult.id}`}
      />
      <InputForm
        form={webForm as any}
        formKey="weight"
        title="Weight (Kg)"
        onChange={(e) => webForm.setValue('weight', e.currentTarget.value)}
        key={`weight-${termResult.id}`}
      />
      <BrandButton
        colorScheme={'brand'}
        onClick={onSubmit}
        isLoading={webForm.processing}
        title={'Save'}
      />
    </VStack>
  );
}
