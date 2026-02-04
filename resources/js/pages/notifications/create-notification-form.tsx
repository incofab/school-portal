import React, { useMemo, useState } from 'react';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { FormButton, LinkButton } from '@/components/buttons';
import {
  Button,
  FormControl,
  HStack,
  SimpleGrid,
  Text,
  Textarea,
  VStack,
} from '@chakra-ui/react';
import FormControlBox from '@/components/forms/form-control-box';
import InputForm from '@/components/forms/input-form';
import EnumSelect from '@/components/dropdown-select/enum-select';
import ClassificationSelect from '@/components/selectors/classification-select';
import ClassificationGroupSelect from '@/components/selectors/classification-group-select';
import InstitutionUserSelect from '@/components/selectors/institution-user-select';
import StudentSelect from '@/components/selectors/student-select';
import UserSelect from '@/components/selectors/user-select';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import { SelectOptionType } from '@/types/types';
import { Inertia } from '@inertiajs/inertia';
import { preventNativeSubmit } from '@/util/util';

const TargetType = {
  User: 'user',
  InstitutionUser: 'institution-user',
  Student: 'student',
  Classification: 'classification',
  ClassificationGroup: 'classification-group',
};

type TargetOption = SelectOptionType<number>;

interface TargetItem {
  type: string;
  id: number;
  label: string;
}

interface Props {
  title: string;
  submitUrl: string;
  listUrl: string;
  allowedTargetTypes: string[];
}

export default function CreateNotificationForm({
  title,
  submitUrl,
  listUrl,
  allowedTargetTypes,
}: Props) {
  const { handleResponseToast, toastError } = useMyToast();
  const webForm = useWebForm({
    title: '',
    body: '',
    type: '',
    action_url: '',
    targets: [] as TargetItem[],
  });

  const [targetType, setTargetType] = useState<string>(allowedTargetTypes[0]);
  const [targetOption, setTargetOption] = useState<TargetOption | null>(null);

  const targetLabelMap = useMemo(() => {
    return {
      [TargetType.User]: 'User',
      [TargetType.InstitutionUser]: 'Institution User',
      [TargetType.Student]: 'Student',
      [TargetType.Classification]: 'Classification',
      [TargetType.ClassificationGroup]: 'Classification Group',
    } as { [key: string]: string };
  }, []);

  function addTarget() {
    if (!targetType || !targetOption?.value) {
      toastError('Select a target to add');
      return;
    }

    const targetId = Number(targetOption.value);
    const label = targetOption.label ?? targetLabelMap[targetType];

    const exists = webForm.data.targets.some(
      (item) => item.type === targetType && item.id === targetId
    );
    if (exists) {
      toastError('Target already added');
      return;
    }

    webForm.setValue('targets', [
      ...webForm.data.targets,
      { type: targetType, id: targetId, label },
    ]);
    setTargetOption(null);
  }

  function removeTarget(index: number) {
    const items = [...webForm.data.targets];
    items.splice(index, 1);
    webForm.setValue('targets', items);
  }

  const submit = async () => {
    const payload = {
      ...webForm.data,
      targets: webForm.data.targets.map((item) => ({
        type: item.type,
        id: item.id,
      })),
    };

    const res = await webForm.submit((_, web) => web.post(submitUrl, payload));

    if (!handleResponseToast(res)) return;

    Inertia.visit(listUrl);
  };

  function renderTargetSelector() {
    if (targetType === TargetType.Classification) {
      return (
        <ClassificationSelect
          onChange={(e: any) => setTargetOption(e)}
          selectValue={targetOption?.value}
        />
      );
    }

    if (targetType === TargetType.ClassificationGroup) {
      return (
        <ClassificationGroupSelect
          onChange={(e: any) => setTargetOption(e)}
          selectValue={targetOption?.value}
        />
      );
    }

    if (targetType === TargetType.InstitutionUser) {
      return (
        <InstitutionUserSelect
          onChange={(e: any) => setTargetOption(e)}
          valueKey="id"
        />
      );
    }

    if (targetType === TargetType.Student) {
      return (
        <StudentSelect
          onChange={(e: any) => setTargetOption(e)}
          valueKey="id"
        />
      );
    }

    return <UserSelect onChange={(e: any) => setTargetOption(e)} />;
  }

  return (
    <Slab>
      <SlabHeading title={title} />
      <SlabBody>
        <VStack
          spacing={4}
          as={'form'}
          onSubmit={preventNativeSubmit(submit)}
          align={'stretch'}
        >
          <InputForm form={webForm as any} formKey="title" title="Title" />

          <FormControlBox form={webForm as any} title="Message" formKey="body">
            <Textarea
              onChange={(e) => webForm.setValue('body', e.currentTarget.value)}
              value={webForm.data.body}
              noOfLines={4}
            >
              {webForm.data.body}
            </Textarea>
          </FormControlBox>

          <SimpleGrid columns={{ base: 1, md: 2 }} spacing={4}>
            <InputForm
              form={webForm as any}
              formKey="type"
              title="Type (optional)"
            />
            <InputForm
              form={webForm as any}
              formKey="action_url"
              title="Action URL (optional)"
            />
          </SimpleGrid>

          <FormControlBox
            form={webForm as any}
            title="Target Type"
            formKey="targets"
          >
            <EnumSelect
              enumData={TargetType}
              allowedEnum={allowedTargetTypes}
              selectValue={targetType}
              onChange={(e: any) => {
                setTargetType(e.value);
                setTargetOption(null);
              }}
            />
          </FormControlBox>

          <FormControlBox
            form={webForm as any}
            title="Target"
            formKey="targets"
          >
            <HStack align={'start'} spacing={3}>
              <FormControl>{renderTargetSelector()}</FormControl>
              <Button onClick={addTarget} colorScheme="brand">
                Add
              </Button>
            </HStack>
          </FormControlBox>

          {webForm.data.targets.length > 0 && (
            <VStack align={'stretch'} spacing={2}>
              {webForm.data.targets.map((item, index) => (
                <HStack key={`${item.type}-${item.id}`} spacing={3}>
                  <Text fontSize="sm">
                    {targetLabelMap[item.type]}: {item.label}
                  </Text>
                  <Button
                    size="xs"
                    variant="ghost"
                    colorScheme="red"
                    onClick={() => removeTarget(index)}
                  >
                    Remove
                  </Button>
                </HStack>
              ))}
            </VStack>
          )}

          <FormControl>
            <FormButton
              isLoading={webForm.processing}
              isDisabled={webForm.processing}
              title="Send"
            />
          </FormControl>
        </VStack>
      </SlabBody>
    </Slab>
  );
}
