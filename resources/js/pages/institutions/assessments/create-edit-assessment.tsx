import React from 'react';
import {
  Button,
  FormControl,
  HStack,
  Icon,
  IconButton,
  Spacer,
  Text,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { Assessment } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { BrandButton, FormButton } from '@/components/buttons';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import { TermType } from '@/types/types';
import EnumSelect from '@/components/dropdown-select/enum-select';
import { Div } from '@/components/semantic';
import startCase from 'lodash/startCase';
import InputForm from '@/components/forms/input-form';
import SelectMidTerm from '@/components/table-filters/mid-term-select';
import DataTable, { TableHeader } from '@/components/data-table';
import { PencilIcon, PencilSquareIcon } from '@heroicons/react/24/solid';
import { InertiaLink } from '@inertiajs/inertia-react';
import useSharedProps from '@/hooks/use-shared-props';
import { useModalValueToggle } from '@/hooks/use-modal-toggle';
import SetAssessmentDependencyModal from '@/components/modals/set-assessment-dependency-modal';

interface Props {
  assessments: Assessment[];
  assessment?: Assessment;
}

export default function CreateUpdateAssessment({
  assessment,
  assessments,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { usesMidTermResult } = useSharedProps();

  const webForm = useWebForm({
    term: assessment?.term,
    for_mid_term: assessment?.for_mid_term ?? '',
    title: assessment?.title ?? '',
    max: assessment?.max ?? '',
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) =>
      assessment
        ? web.put(instRoute('assessments.update', [assessment]), data)
        : web.post(instRoute('assessments.store'), data)
    );

    if (!handleResponseToast(res)) return;

    Inertia.visit(instRoute('assessments.index'));
  };
  return (
    <DashboardLayout>
      <Div>
        <CenteredBox>
          <Slab>
            <SlabHeading
              title={`${assessment ? 'Update' : 'Record'} Assessment record`}
            />
            <SlabBody>
              <VStack
                spacing={4}
                as={'form'}
                onSubmit={preventNativeSubmit(submit)}
              >
                <FormControlBox
                  form={webForm as any}
                  title="Term"
                  formKey="term"
                >
                  <EnumSelect
                    enumData={TermType}
                    additionalEnumData={{ all: 'all' }}
                    selectValue={webForm.data.term ?? 'all'}
                    isMulti={false}
                    isClearable={true}
                    onChange={(e: any) =>
                      webForm.setValue('term', e.value === 'all' ? '' : e.value)
                    }
                  />
                </FormControlBox>
                <InputForm
                  form={webForm as any}
                  formKey="title"
                  title="Title"
                />
                <InputForm
                  form={webForm as any}
                  formKey="max"
                  title="Max score obtainable"
                />
                {usesMidTermResult && (
                  <FormControlBox
                    form={webForm as any}
                    formKey="for_mid_term"
                    title=""
                  >
                    <SelectMidTerm
                      value={String(webForm.data.for_mid_term)}
                      onChange={(e) => webForm.setValue('for_mid_term', e)}
                      children=""
                    />
                  </FormControlBox>
                )}
                <FormControl>
                  <FormButton isLoading={webForm.processing} />
                </FormControl>
              </VStack>
            </SlabBody>
          </Slab>
        </CenteredBox>
        {assessments && (
          <>
            <Spacer height={4} />
            <ListAssessments assessments={assessments} />
          </>
        )}
      </Div>
    </DashboardLayout>
  );
}

function ListAssessments({ assessments }: { assessments: Assessment[] }) {
  const { instRoute } = useInstitutionRoute();
  const setDependencyModalToggle = useModalValueToggle<Assessment>();
  const headers: TableHeader<Assessment>[] = [
    {
      label: 'Title',
      render: (row) => `${startCase(row.title)}`,
    },
    {
      label: 'Max Obtainable',
      value: 'max',
    },
    {
      label: 'Term',
      render: (row) => `${startCase(row.term ?? 'All')}`,
    },
    {
      label: 'Mid or Full Term',
      render: (row) =>
        `${
          row.for_mid_term === null ? 'Both' : row.for_mid_term ? 'Yes' : 'No'
        }`,
    },
    {
      label: 'Reference',
      render: (row) => (
        <HStack spacing={2}>
          {row.depends_on && <Text>{startCase(row.depends_on)}</Text>}
          <BrandButton
            variant={'link'}
            title="Change"
            onClick={() => setDependencyModalToggle.open(row)}
          />
        </HStack>
      ),
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack spacing={2}>
          <IconButton
            aria-label="Edit"
            icon={<Icon as={PencilIcon} />}
            as={InertiaLink}
            href={instRoute('assessments.index', [row])}
          />
          <Button
            display={'none'}
            variant={'link'}
            colorScheme="brand"
            as={InertiaLink}
            href={instRoute(
              'assessments.insert-score-from-course-result.create',
              [row]
            )}
          >
            Insert Scores
          </Button>
        </HStack>
      ),
    },
  ];

  return (
    <Slab>
      <SlabHeading title="Assessments" />
      <SlabBody>
        <DataTable
          scroll={true}
          headers={headers}
          data={assessments}
          keyExtractor={(row) => row.id}
          hideSearchField={true}
        />
      </SlabBody>
      {setDependencyModalToggle.state && (
        <SetAssessmentDependencyModal
          {...setDependencyModalToggle.props}
          assessment={setDependencyModalToggle.state}
          onSuccess={() => Inertia.reload()}
        />
      )}
    </Slab>
  );
}
