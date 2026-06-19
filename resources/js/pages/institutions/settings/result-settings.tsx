import React from 'react';
import {
  Divider,
  FormControl,
  FormLabel,
  Grid,
  GridItem,
  HStack,
  Spacer,
  Text,
  VStack,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import { Inertia } from '@inertiajs/inertia';
import { BrandButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import EnumSelect from '@/components/dropdown-select/enum-select';
import {
  InstitutionSettingType,
  PositionDisplayType,
  ResultExamMode,
  ResultSettingType,
  ResultTemplate,
} from '@/types/types';
import useSharedProps from '@/hooks/use-shared-props';
import { Div } from '@/components/semantic';

export default function ResultSettings() {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { resultSetting } = useSharedProps();

  const webForm = useWebForm({
    [ResultSettingType.PositionDisplayType]:
      resultSetting?.[ResultSettingType.PositionDisplayType] ??
      PositionDisplayType.Position,
    [ResultSettingType.Template]:
      resultSetting?.[ResultSettingType.Template] ?? ResultTemplate.Template1,
    [ResultSettingType.ExamMode]:
      resultSetting?.[ResultSettingType.ExamMode] ?? ResultExamMode.Both,
  } as { [key: string]: string });

  const submit = async () => {
    const res = await webForm.submit((data, web) => {
      return web.post(instRoute('settings.store'), {
        key: InstitutionSettingType.Result,
        value: data,
        type: 'array',
      });
    });
    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.reload({ only: ['settings'] });
  };

  return (
    <VStack align={'stretch'}>
      <Divider my={2} />
      <Text fontWeight={'bold'}>Result Setting</Text>
      <Grid templateColumns={{ lg: 'repeat(3, 1fr)' }} gap={4}>
        {/* <HStack align={'stretch'} spacing={2}> */}
        <GridItem>
          <FormControl>
            <FormLabel>Display Position</FormLabel>
            <EnumSelect
              enumData={PositionDisplayType}
              selectValue={webForm.data[ResultSettingType.PositionDisplayType]}
              onChange={(e: any) =>
                webForm.setValue(ResultSettingType.PositionDisplayType, e.value)
              }
            />
          </FormControl>
        </GridItem>
        <GridItem>
          <FormControl>
            <FormLabel>Template</FormLabel>
            <EnumSelect
              enumData={ResultTemplate}
              selectValue={webForm.data[ResultSettingType.Template]}
              onChange={(e: any) =>
                webForm.setValue(ResultSettingType.Template, e.value)
              }
            />
          </FormControl>
        </GridItem>
        <GridItem>
          <FormControl>
            <FormLabel>Show Exam Result</FormLabel>
            <EnumSelect
              enumData={ResultExamMode}
              selectValue={webForm.data[ResultSettingType.ExamMode]}
              onChange={(e: any) =>
                webForm.setValue(ResultSettingType.ExamMode, e.value)
              }
            />
          </FormControl>
        </GridItem>
        <Div>
          <BrandButton
            title="Update"
            onClick={() => submit()}
            isLoading={webForm.processing}
            size={'md'}
            mt={'30px'}
          />
        </Div>
      </Grid>
      <Divider my={2} />
      <Spacer height={5} />
    </VStack>
  );
}
