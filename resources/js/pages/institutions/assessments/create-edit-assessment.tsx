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
import { Assessment, ClassDivision } from '@/types/models';
import ClassDivisionSelect from '@/components/selectors/class-division-select';
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
import { PencilIcon, TrashIcon } from '@heroicons/react/24/solid';
import { InertiaLink } from '@inertiajs/inertia-react';
import useSharedProps from '@/hooks/use-shared-props';
import { useModalValueToggle } from '@/hooks/use-modal-toggle';
import SetAssessmentDependencyModal from '@/components/modals/set-assessment-dependency-modal';
import DestructivePopover from '@/components/destructive-popover';

interface Props {
  assessments: Assessment[];
  assessment?: Assessment;
  classDivisions: ClassDivision[];
}

export default function CreateUpdateAssessment({
  assessment,
  assessments,
  classDivisions,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { usesMidTermResult } = useSharedProps();

  const webForm = useWebForm({
    term: assessment?.term,
    for_mid_term: assessment?.for_mid_term ?? '',
    title: assessment?.title ?? '',
    max: assessment?.max ?? '',
    class_division_ids:
      assessment?.class_divisions?.map((cd) => ({
        label: cd.title,
        value: cd.id,
      })) ?? [],
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
                      webForm.setValue(
                        'term',
                        e.value === 'all' ? '' : e?.value
                      )
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

                <FormControlBox
                  form={webForm as any}
                  formKey="class_division_ids"
                  title="Class Divisions"
                >
                  <ClassDivisionSelect
                    isMulti={true}
                    value={webForm.data.class_division_ids}
                    isClearable={true}
                    onChange={(e: any) =>
                      webForm.setValue(
                        'class_division_ids',
                        e.map((item: any) => item.value)
                      )
                    }
                    classDivisions={classDivisions}
                  />
                </FormControlBox>
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
  const { handleResponseToast } = useMyToast();
  const deleteForm = useWebForm({});

  async function deleteItem(obj: Assessment) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('assessments.destroy', [obj.id]))
    );
    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.reload();
  }

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
            size={'sm'}
          />
          <DestructivePopover
            label={'Delete this assessment'}
            onConfirm={() => deleteItem(row)}
            isLoading={deleteForm.processing}
          >
            <IconButton
              aria-label="Delete"
              icon={<Icon as={TrashIcon} />}
              colorScheme="red"
              size={'sm'}
            />
          </DestructivePopover>
          <Button
            display={'none'}
            variant={'link'}
            colorScheme="brand"
            as={InertiaLink}
            size={'sm'}
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
