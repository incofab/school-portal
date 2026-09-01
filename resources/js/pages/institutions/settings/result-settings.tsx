import React from 'react';
import {
  Box,
  Checkbox,
  FormControl,
  FormLabel,
  Grid,
  GridItem,
  HStack,
  Text,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import { Inertia } from '@inertiajs/inertia';
import { BrandButton, LinkButton } from '@/components/buttons';
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
import {
  SettingsSection,
  default as SettingsGroup,
} from '@/components/settings/settings-group';

export interface ResultSettingsData {
  [key: string]: string | boolean;
}

export function getResultSettingsData(
  resultSetting?: ResultSettingsData
): ResultSettingsData {
  return {
    [ResultSettingType.PositionDisplayType]:
      resultSetting?.[ResultSettingType.PositionDisplayType] ??
      PositionDisplayType.Position,
    [ResultSettingType.Template]:
      resultSetting?.[ResultSettingType.Template] ?? ResultTemplate.Template1,
    [ResultSettingType.ExamMode]:
      resultSetting?.[ResultSettingType.ExamMode] ?? ResultExamMode.Both,
    [ResultSettingType.UseSessionResultAsThirdTerm]: Boolean(
      resultSetting?.[ResultSettingType.UseSessionResultAsThirdTerm]
    ),
  };
}

interface Props {
  embedded?: boolean;
  data?: ResultSettingsData;
  onChange?: (key: ResultSettingType, value: string | boolean) => void;
  showActions?: boolean;
}

export default function ResultSettings({
  embedded = false,
  data,
  onChange,
  showActions = true,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { resultSetting } = useSharedProps();
  const webForm = useWebForm(getResultSettingsData(resultSetting));
  const values = data ?? webForm.data;

  const setValue = (key: ResultSettingType, value: string | boolean) => {
    if (onChange) {
      onChange(key, value);
      return;
    }

    webForm.setValue(key, value);
  };

  const submit = async () => {
    const res = await webForm.submit((_, web) => {
      return web.post(instRoute('settings.store'), {
        key: InstitutionSettingType.Result,
        value: values,
        type: 'array',
      });
    });
    if (!handleResponseToast(res)) return;
    Inertia.reload({ only: ['settings'] });
  };

  const action = showActions ? (
    <BrandButton
      title="Save result presentation"
      onClick={() => submit()}
      isLoading={webForm.processing}
      size="md"
    />
  ) : undefined;

  const content = (
    <Grid templateColumns={{ base: '1fr', lg: 'repeat(2, 1fr)' }} gap={4}>
      <GridItem>
        <FormControl>
          <FormLabel>Display position</FormLabel>
          <EnumSelect
            enumData={PositionDisplayType}
            selectValue={values[ResultSettingType.PositionDisplayType]}
            onChange={(e: any) =>
              setValue(ResultSettingType.PositionDisplayType, e.value)
            }
          />
        </FormControl>
      </GridItem>
      <GridItem>
        <FormControl>
          <FormLabel>Template</FormLabel>
          <HStack align="stretch" spacing={2}>
            <Box flex={1} minW={0}>
              <EnumSelect
                enumData={ResultTemplate}
                selectValue={values[ResultSettingType.Template]}
                onChange={(e: any) =>
                  setValue(ResultSettingType.Template, e.value)
                }
              />
            </Box>
            <LinkButton
              title="Preview template"
              href={instRoute('result-sheets.dummy')}
              variant="outline"
              whiteSpace="nowrap"
            />
          </HStack>
        </FormControl>
      </GridItem>
      <GridItem>
        <FormControl>
          <FormLabel>Show exam result</FormLabel>
          <EnumSelect
            enumData={ResultExamMode}
            selectValue={values[ResultSettingType.ExamMode]}
            onChange={(e: any) => setValue(ResultSettingType.ExamMode, e.value)}
          />
        </FormControl>
      </GridItem>
      <GridItem>
        <FormControl>
          <FormLabel>Show Annual Result in 3rd Term</FormLabel>
          <Checkbox
            colorScheme="brand"
            isChecked={Boolean(
              values[ResultSettingType.UseSessionResultAsThirdTerm]
            )}
            onChange={(event) =>
              setValue(
                ResultSettingType.UseSessionResultAsThirdTerm,
                event.target.checked
              )
            }
          >
            Use session result
          </Checkbox>
          {/* <Text fontSize="sm" color="gray.500" mt={1}>
            Show the session result when students view third-term results.
          </Text> */}
        </FormControl>
      </GridItem>
    </Grid>
  );

  if (embedded) {
    return (
      <SettingsSection
        title="Result presentation"
        description="Choose how results are displayed and whether the session result is used for third term."
        action={action}
      >
        {content}
      </SettingsSection>
    );
  }

  return (
    <SettingsGroup
      title="Result presentation"
      description="Choose how results are displayed and whether the session result is used for third term."
      action={action}
    >
      {content}
    </SettingsGroup>
  );
}
