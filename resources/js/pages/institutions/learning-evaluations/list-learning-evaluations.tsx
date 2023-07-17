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
import { LearningEvaluation, LearningEvaluationDomain } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import { Div } from '@/components/semantic';
import InputForm from '@/components/forms/input-form';
import DataTable, { TableHeader } from '@/components/data-table';
import { PencilIcon, TrashIcon } from '@heroicons/react/24/solid';
import { InertiaLink } from '@inertiajs/inertia-react';
import DestructivePopover from '@/components/destructive-popover';
import SingleQuerySelect from '@/components/dropdown-select/single-query-select';

interface Props {
  learningEvaluations: LearningEvaluation[];
  learningEvaluation?: LearningEvaluation;
  learningEvaluationDomains: LearningEvaluationDomain[];
}

export default function ListLearningEvaluationDomains({
  learningEvaluations,
  learningEvaluation,
  learningEvaluationDomains,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();

  const deleteForm = useWebForm({});
  async function deleteItem(obj: LearningEvaluation) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('learning-evaluations.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['learningEvaluations'] });
  }

  const headers: TableHeader<LearningEvaluation>[] = [
    {
      label: 'Title',
      value: 'title',
    },
    {
      label: 'Domain',
      value: 'learning_evaluation_domain.title',
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack spacing={2}>
          <IconButton
            aria-label="Edit"
            icon={<Icon as={PencilIcon} />}
            as={InertiaLink}
            href={instRoute('learning-evaluations.index', [row])}
            colorScheme={'brand'}
            variant={'ghost'}
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
        <CreateUpdateLearningEvaluation
          learningEvaluation={learningEvaluation}
          learningEvaluationDomains={learningEvaluationDomains}
        />
      </Div>
      <Spacer height={4} />
      {learningEvaluationDomains && (
        <Slab>
          <SlabHeading title="Evaluation" />
          <SlabBody>
            <DataTable
              scroll={true}
              headers={headers}
              data={learningEvaluations}
              keyExtractor={(row) => row.id}
              hideSearchField={true}
            />
          </SlabBody>
        </Slab>
      )}
    </DashboardLayout>
  );
}

function CreateUpdateLearningEvaluation({
  learningEvaluation,
  learningEvaluationDomains,
}: {
  learningEvaluation?: LearningEvaluation;
  learningEvaluationDomains: LearningEvaluationDomain[];
}) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();

  const webForm = useWebForm({
    title: learningEvaluation?.title ?? '',
    learning_evaluation_domain_id:
      learningEvaluation?.learning_evaluation_domain_id ?? '',
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(
        instRoute(
          'learning-evaluations.store',
          learningEvaluation ? [learningEvaluation] : []
        ),
        data
      )
    );

    if (!handleResponseToast(res)) return;

    Inertia.visit(instRoute('learning-evaluations.index'));
  };

  return (
    <CenteredBox>
      <Slab>
        <SlabHeading
          title={`${learningEvaluation ? 'Update' : 'Record'} Evaluation`}
        />
        <SlabBody>
          <VStack
            spacing={4}
            as={'form'}
            onSubmit={preventNativeSubmit(submit)}
          >
            <InputForm form={webForm as any} formKey="title" title="Title" />
            <FormControlBox
              form={webForm as any}
              formKey="learning_evaluation_domain_id"
              title="Type"
            >
              <SingleQuerySelect
                selectValue={webForm.data.learning_evaluation_domain_id}
                dataList={learningEvaluationDomains}
                searchUrl={''}
                label={'title'}
                onChange={(e: any) =>
                  webForm.setValue('learning_evaluation_domain_id', e.value)
                }
              />
            </FormControlBox>
            <FormControl>
              <FormButton isLoading={webForm.processing} />
            </FormControl>
          </VStack>
        </SlabBody>
      </Slab>
    </CenteredBox>
  );
}
