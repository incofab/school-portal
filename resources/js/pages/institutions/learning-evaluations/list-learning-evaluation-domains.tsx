import React from 'react';
import {
  FormControl,
  HStack,
  Icon,
  IconButton,
  Spacer,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { LearningEvaluationDomain } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import { LearningEvaluationDomainType } from '@/types/types';
import EnumSelect from '@/components/dropdown-select/enum-select';
import { Div } from '@/components/semantic';
import startCase from 'lodash/startCase';
import InputForm from '@/components/forms/input-form';
import DataTable, { TableHeader } from '@/components/data-table';
import { PencilIcon, TrashIcon } from '@heroicons/react/24/solid';
import { InertiaLink } from '@inertiajs/inertia-react';
import DestructivePopover from '@/components/destructive-popover';

interface Props {
  learningEvaluationDomains: LearningEvaluationDomain[];
  learningEvaluationDomain?: LearningEvaluationDomain;
}

export default function ListLearningEvaluationDomains({
  learningEvaluationDomains,
  learningEvaluationDomain,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();

  const deleteForm = useWebForm({});
  async function deleteItem(obj: LearningEvaluationDomain) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('learning-evaluation-domains.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['learningEvaluationDomains'] });
  }

  const headers: TableHeader<LearningEvaluationDomain>[] = [
    {
      label: 'Title',
      value: 'title',
    },
    {
      label: 'Type',
      render: (row) => `${startCase(row.type)}`,
    },
    {
      label: 'Max Obtainable',
      value: 'max',
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack spacing={2}>
          <IconButton
            aria-label="Edit"
            icon={<Icon as={PencilIcon} />}
            as={InertiaLink}
            href={instRoute('learning-evaluation-domains.index', [row])}
            colorScheme={'brand'}
            variant={'ghost'}
            size={'sm'}
          />

          <DestructivePopover
            label={`Delete ${row.title}?`}
            onConfirm={(onClose) => deleteItem(row)}
            isLoading={deleteForm.processing}
            positiveButtonLabel="Delete"
          >
            <IconButton
              aria-label="Delete"
              icon={<Icon as={TrashIcon} />}
              variant={'ghost'}
              colorScheme={'red'}
            />
          </DestructivePopover>
        </HStack>
      ),
    },
  ];

  return (
    <DashboardLayout>
      <Div>
        <CreateUpdateLearningEvaluationDomain
          learningEvaluationDomain={learningEvaluationDomain}
        />
      </Div>
      <Spacer height={4} />
      {learningEvaluationDomains && (
        <Slab>
          <SlabHeading title="Evaluation Types" />
          <SlabBody>
            <DataTable
              scroll={true}
              headers={headers}
              data={learningEvaluationDomains}
              keyExtractor={(row) => row.id}
              hideSearchField={true}
            />
          </SlabBody>
        </Slab>
      )}
    </DashboardLayout>
  );
}

function CreateUpdateLearningEvaluationDomain({
  learningEvaluationDomain,
}: {
  learningEvaluationDomain?: LearningEvaluationDomain;
}) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();

  const webForm = useWebForm({
    title: learningEvaluationDomain?.title ?? '',
    type: learningEvaluationDomain?.type ?? '',
    max: learningEvaluationDomain?.max ?? '',
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(
        instRoute(
          'learning-evaluation-domains.store',
          learningEvaluationDomain ? [learningEvaluationDomain] : []
        ),
        data
      )
    );

    if (!handleResponseToast(res)) return;

    Inertia.visit(instRoute('learning-evaluation-domains.index'));
  };

  return (
    <CenteredBox>
      <Slab>
        <SlabHeading
          title={`${
            learningEvaluationDomain ? 'Update' : 'Record'
          } Evaluation Type`}
        />
        <SlabBody>
          <VStack
            spacing={4}
            as={'form'}
            onSubmit={preventNativeSubmit(submit)}
          >
            <InputForm form={webForm as any} formKey="title" title="Title" />
            <FormControlBox form={webForm as any} title="Type" formKey="type">
              <EnumSelect
                enumData={LearningEvaluationDomainType}
                selectValue={webForm.data.type}
                isMulti={false}
                isClearable={true}
                onChange={(e: any) => webForm.setValue('type', e?.value)}
              />
            </FormControlBox>
            {webForm.data.type === LearningEvaluationDomainType.Number && (
              <InputForm
                form={webForm as any}
                formKey="max"
                title="Max Range for Type"
                isRequired
              />
            )}
            <FormControl>
              <FormButton isLoading={webForm.processing} />
            </FormControl>
          </VStack>
        </SlabBody>
      </Slab>
    </CenteredBox>
  );
}
