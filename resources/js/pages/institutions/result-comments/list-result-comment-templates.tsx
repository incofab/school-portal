import React from 'react';
import {
  Checkbox,
  FormControl,
  HStack,
  Icon,
  IconButton,
  Input,
  Spacer,
  Textarea,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { Classification, ResultCommentTemplate } from '@/types/models';
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
import DestructivePopover from '@/components/destructive-popover';
import EnumSelect from '@/components/dropdown-select/enum-select';
import {
  Grade,
  Nullable,
  ResultCommentTemplateType,
  SelectOptionType,
} from '@/types/types';
import { InertiaLink } from '@inertiajs/inertia-react';
import startCase from 'lodash/startCase';
import ClassificationSelect from '@/components/selectors/classification-select';
import { MultiValue } from 'react-select';

interface Props {
  resultCommentTemplates: ResultCommentTemplate[];
  resultCommentTemplate?: ResultCommentTemplate;
  classifications: Classification[];
}

export default function ListResultCommentTemplates({
  resultCommentTemplates,
  resultCommentTemplate,
  classifications,
}: Props) {
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast } = useMyToast();

  const deleteForm = useWebForm({});
  async function deleteItem(obj: ResultCommentTemplate) {
    const res = await deleteForm.submit((data, web) =>
      web.delete(instRoute('result-comment-templates.destroy', [obj.id]))
    );
    handleResponseToast(res);
    Inertia.reload({ only: ['resultCommentTemplates'] });
  }

  const headers: TableHeader<ResultCommentTemplate>[] = [
    {
      label: 'Type',
      render: (row) => startCase(row.type ?? 'All'),
    },
    {
      label: 'Classes',
      render: (row) =>
        row.classifications && row.classifications.length
          ? row.classifications.map((c) => c.title).join(', ')
          : 'All Classes',
    },
    {
      label: 'Comment (Principal/Head Teacher)',
      value: 'comment',
    },
    {
      label: 'Grade',
      value: 'grade',
    },
    {
      label: 'Label',
      value: 'grade_label',
    },
    {
      label: 'Range',
      render: (row) => `${row.min} - ${row.max}`,
    },
    {
      label: 'Comment (Teacher)',
      value: 'comment_2',
    },
    {
      label: 'Action',
      render: (row) => (
        <HStack spacing={2}>
          <IconButton
            aria-label="Edit"
            icon={<Icon as={PencilIcon} />}
            variant={'ghost'}
            colorScheme={'brand'}
            as={InertiaLink}
            href={instRoute('result-comment-templates.index', [row.id])}
          />
          <DestructivePopover
            label={`Delete "${row.comment}"?`}
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
        <CreateUpdateResultCommentTemplates
          resultCommentTemplate={resultCommentTemplate}
          classifications={classifications}
        />
      </Div>
      <Spacer height={4} />
      {resultCommentTemplates && (
        <Slab>
          <SlabHeading title="Evaluation" />
          <SlabBody>
            <DataTable
              scroll={true}
              headers={headers}
              data={resultCommentTemplates}
              keyExtractor={(row) => row.id}
              hideSearchField={true}
            />
          </SlabBody>
        </Slab>
      )}
    </DashboardLayout>
  );
}

function CreateUpdateResultCommentTemplates({
  resultCommentTemplate,
  classifications,
}: {
  resultCommentTemplate?: ResultCommentTemplate;
  classifications: Classification[];
}) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();

  const webForm = useWebForm({
    comment: resultCommentTemplate?.comment ?? '',
    comment_2: resultCommentTemplate?.comment_2 ?? '',
    grade: resultCommentTemplate?.grade ?? '',
    grade_label: resultCommentTemplate?.grade_label ?? '',
    type: resultCommentTemplate?.type ?? '',
    min: resultCommentTemplate?.min ?? '',
    max: resultCommentTemplate?.max ?? '',
    classification_ids: resultCommentTemplate?.classifications?.map((cd) => ({
      label: cd.title,
      value: cd.id,
    })) as Nullable<MultiValue<SelectOptionType<number>>>,
    for_all_classes: resultCommentTemplate?.classifications?.length
      ? false
      : true,
  });

  const submit = async () => {
    const res = await webForm.submit((data, web) =>
      web.post(
        instRoute(
          'result-comment-templates.store',
          resultCommentTemplate ? [resultCommentTemplate] : []
        ),
        {
          ...data,
          classification_ids: data.classification_ids?.map((c) => c.value),
        }
      )
    );

    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.visit(instRoute('result-comment-templates.index'));
  };

  const grades = Object.keys(Grade).concat([
    'A1',
    'B2',
    'B3',
    'C4',
    'C5',
    'C6',
    'D7',
    'E8',
    'F9',
  ]);

  return (
    <CenteredBox>
      <Slab>
        <SlabHeading
          title={`${
            resultCommentTemplate ? 'Update' : 'Record'
          } Comment Template`}
        />
        <SlabBody>
          <VStack
            spacing={4}
            as={'form'}
            onSubmit={preventNativeSubmit(submit)}
          >
            <FormControlBox form={webForm as any} formKey="type" title="Type">
              <EnumSelect
                selectValue={webForm.data.type}
                enumData={ResultCommentTemplateType}
                isMulti={false}
                onChange={(e: any) => webForm.setValue('type', e.value)}
              />
            </FormControlBox>
            <HStack align={'stretch'} justify={'space-between'} width={'full'}>
              <InputForm
                form={webForm as any}
                formKey="min"
                title="Range From"
              />
              <Spacer />
              <InputForm form={webForm as any} formKey="max" title="Range To" />
            </HStack>
            <HStack align={'stretch'} justify={'space-between'} width={'full'}>
              <FormControlBox
                form={webForm as any}
                formKey="grade"
                title="Grade"
              >
                <Input
                  value={webForm.data.grade}
                  list="grade-options"
                  type="text"
                  placeholder="Eg: A,A1,B,B2,B3..."
                  onChange={(e) =>
                    webForm.setValue('grade', e.currentTarget.value)
                  }
                />
                <datalist id="grade-options">
                  {grades.map((option) => (
                    <option key={option} value={option} />
                  ))}
                </datalist>
                {/*                 
                <EnumSelect
                  selectValue={webForm.data.type}
                  enumData={Grade}
                  isMulti={false}
                  onChange={(e: any) => webForm.setValue('grade', e.value)}
                /> */}
              </FormControlBox>
              <InputForm
                form={webForm as any}
                formKey="grade_label"
                title="Label [optional]"
                placeholder="Eg. Excellent,Fail..."
              />
            </HStack>
            <FormControlBox
              form={webForm as any}
              formKey="comment"
              title="Comment (Principal/Head Teacher)"
            >
              <Textarea
                value={webForm.data.comment}
                onChange={(e) =>
                  webForm.setValue('comment', e.currentTarget.value)
                }
              />
            </FormControlBox>
            <FormControlBox
              form={webForm as any}
              formKey="comment_2"
              title="Comment (Teacher)"
            >
              <Textarea
                value={webForm.data.comment_2}
                onChange={(e) =>
                  webForm.setValue('comment_2', e.currentTarget.value)
                }
              />
            </FormControlBox>

            <FormControl>
              <Checkbox
                isChecked={webForm.data.for_all_classes}
                onChange={(e) => {
                  webForm.setData({
                    ...webForm.data,
                    for_all_classes: e.currentTarget.checked,
                    ...(e.currentTarget.checked == true
                      ? { classification_ids: null }
                      : {}),
                  });
                }}
                size={'md'}
                colorScheme="brand"
              >
                Applicable to All Classes
              </Checkbox>
            </FormControl>

            {!webForm.data.for_all_classes && (
              <FormControlBox
                form={webForm as any}
                formKey="classification_ids"
                title="Classes [Optional]"
              >
                <ClassificationSelect
                  isMulti={true}
                  selectValue={webForm.data.classification_ids}
                  isClearable={true}
                  onChange={(e: any) =>
                    webForm.setValue('classification_ids', e)
                  }
                  classifications={classifications}
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
  );
}
