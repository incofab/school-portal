import React from 'react';
import DashboardLayout from '@/layout/dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { Div } from '@/components/semantic';
import {
  Button,
  FormControl,
  FormLabel,
  HStack,
  Input,
  Switch,
  VStack,
} from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { Inertia } from '@inertiajs/inertia';
import { LiveClass } from '@/types/models';
import ClassificationSelect from '@/components/selectors/classification-select';
import ClassificationGroupSelect from '@/components/selectors/classification-group-select';
import ClassDivisionSelect from '@/components/selectors/class-division-select';
import { SelectOptionType } from '@/types/types';
import InputForm from '@/components/forms/input-form';
import MySelect from '@/components/dropdown-select/my-select';
import { dateFormat, dateTimeFormat } from '@/util/util';
import { format } from 'date-fns';

type LiveableType =
  | 'classification'
  | 'classification_group'
  | 'class_division';

const liveableTypeOptions: SelectOptionType<LiveableType>[] = [
  { label: 'Class', value: 'classification' },
  { label: 'Class Group', value: 'classification_group' },
  { label: 'Class Division', value: 'class_division' },
];

interface Props {
  liveClass?: LiveClass;
}

export default function CreateEditLiveClass({ liveClass }: Props) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();
  const form = useWebForm({
    title: liveClass?.title || '',
    meet_url: liveClass?.meet_url || '',
    liveable_type:
      liveClass?.liveable_type || ('classification' as LiveableType),
    liveable_id: liveClass?.liveable_id || '',
    starts_at: liveClass?.starts_at
      ? format(new Date(liveClass?.starts_at), dateTimeFormat)
      : '',
    ends_at: liveClass?.ends_at
      ? format(new Date(liveClass?.ends_at), dateTimeFormat)
      : '',
    is_active: true,
  });

  const resolveLiveableType = () => {
    if (form.data.liveable_type === 'classification') {
      return 'App\\Models\\Classification';
    }
    if (form.data.liveable_type === 'classification_group') {
      return 'App\\Models\\ClassificationGroup';
    }
    return 'App\\Models\\ClassDivision';
  };

  const submit = async () => {
    const res = await form.submit((data, web) => {
      return liveClass
        ? web.put(instRoute('live-classes.update', [liveClass.id]), {
            ...data,
            liveable_type: resolveLiveableType(),
          })
        : web.post(instRoute('live-classes.store'), {
            ...data,
            liveable_type: resolveLiveableType(),
          });
    });
    if (!handleResponseToast(res)) return;
    Inertia.visit(instRoute('live-classes.index'));
    form.reset();
  };

  const renderLiveableSelect = () => {
    if (form.data.liveable_type === 'classification') {
      return (
        <ClassificationSelect
          selectValue={form.data.liveable_id}
          isClearable={true}
          onChange={(e: any) => form.setValue('liveable_id', e?.value)}
          required
        />
      );
    }
    if (form.data.liveable_type === 'classification_group') {
      return (
        <ClassificationGroupSelect
          selectValue={form.data.liveable_id}
          isClearable={true}
          onChange={(e: any) => form.setValue('liveable_id', e?.value)}
          required
        />
      );
    }
    return (
      <ClassDivisionSelect
        selectValue={form.data.liveable_id}
        isClearable={true}
        onChange={(e: any) => form.setValue('liveable_id', e?.value)}
        required
      />
    );
  };

  return (
    <DashboardLayout>
      <Slab>
        <SlabHeading title="Live Classes" />
        <SlabBody>
          <VStack spacing={3}>
            <InputForm title="Title" formKey="title" form={form as any} />
            <InputForm
              title="Google Meet Link"
              formKey="meet_url"
              form={form as any}
            />
            <FormControl>
              <FormLabel>Target Type</FormLabel>
              <MySelect
                getOptions={() => liveableTypeOptions}
                selectValue={form.data.liveable_type}
                onChange={(e: any) => form.setValue('liveable_type', e?.value)}
              />
            </FormControl>
            <FormControl>
              <FormLabel>Target</FormLabel>
              {renderLiveableSelect()}
            </FormControl>
            <FormControl>
              <FormLabel>Starts At</FormLabel>
              <Input
                type="datetime-local"
                value={form.data.starts_at}
                onChange={(e) =>
                  form.setValue('starts_at', e.currentTarget.value)
                }
              />
            </FormControl>
            <FormControl>
              <FormLabel>Ends At</FormLabel>
              <Input
                type="datetime-local"
                value={form.data.ends_at}
                onChange={(e) =>
                  form.setValue('ends_at', e.currentTarget.value)
                }
              />
            </FormControl>
            <FormControl>
              <FormLabel>Active</FormLabel>
              <Switch
                isChecked={form.data.is_active}
                onChange={(e) =>
                  form.setValue('is_active', e.currentTarget.checked)
                }
                colorScheme="brand"
              />
            </FormControl>
            <FormControl>
              <Button colorScheme="brand" onClick={submit}>
                Create
              </Button>
            </FormControl>
          </VStack>
        </SlabBody>
      </Slab>
    </DashboardLayout>
  );
}
