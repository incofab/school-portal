import React from 'react';
import {
  Button,
  Checkbox,
  FormControl,
  HStack,
  Icon,
  IconButton,
  Input,
  Textarea,
  VStack,
} from '@chakra-ui/react';
import DashboardLayout from '@/layout/dashboard-layout';
import useWebForm from '@/hooks/use-web-form';
import { preventNativeSubmit } from '@/util/util';
import { Inertia } from '@inertiajs/inertia';
import { Classification, ClassificationGroup, Expense, ExpenseCategory } from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import { FormButton } from '@/components/buttons';
import InputForm from '@/components/forms/input-form';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import StaffSelect from '@/components/selectors/staff-select';
import { InstitutionUserType, TermType } from '@/types/types';
import ClassificationGroupSelect from '@/components/selectors/classification-group-select';
import useModalToggle from '@/hooks/use-modal-toggle';
import CreateEditClassGroupModal from '@/components/modals/create-edit-class-group-modal';
import { PlusIcon } from '@heroicons/react/24/solid';
import { Div } from '@/components/semantic';
import AcademicSessionSelect from '@/components/selectors/academic-session-select';
import useSharedProps from '@/hooks/use-shared-props';
import EnumSelect from '@/components/dropdown-select/enum-select';
import ExpenseCategorySelect from '@/components/selectors/expense-category-select';

interface Props {
  expense?: Expense;
  expenseCategories: ExpenseCategory[];
}

export default function CreateEditExpense({
  expense,
  expenseCategories,
}: Props) {
  const { handleResponseToast } = useMyToast();
  const { instRoute } = useInstitutionRoute();
  const { currentAcademicSessionId, currentTerm } = useSharedProps();

  function today() {
    return new Date().toISOString().split('T')[0]; // Get current date in YYYY-MM-DD format
  }

  const webForm = useWebForm({
    title: expense?.title ?? '',
    description: expense?.description ?? '',
    amount: expense?.amount ?? '',
    academic_session_id: expense?.academic_session_id ?? currentAcademicSessionId,
    term: expense?.term ?? currentTerm,
    expense_date: expense?.expense_date ?? today(),
    expense_category_id: expense?.expense_category_id ?? '',
  });

  /*
    const submit = async () => {
      const res = await webForm.submit((data, web) => {
        const postData = {
          ...data,
          form_teacher_id: data.form_teacher_id?.value,
        };
        return classification
          ? web.put(
              instRoute('classifications.update', [classification]),
              postData
            )
          : web.post(instRoute('classifications.store'), postData);
      });

      if (!handleResponseToast(res)) {
        return;
      }
      Inertia.visit(instRoute('classifications.index'));
    };
  */

  const onSubmit = async () => {
    const res = await webForm.submit(async (data, web) => {
      return expense
        ? web.put(instRoute('expenses.update', [expense]), data)
        : web.post(instRoute('expenses.store'), data);
    });

    if (!handleResponseToast(res)) {
      return;
    }
    Inertia.visit(instRoute('expenses.index'));
  };

  return (
    <DashboardLayout>
      <CenteredBox>
        <Slab>
          <SlabHeading
            title={`${expense ? 'Update' : 'Create'} Expense`}
          />
          <SlabBody>
            <VStack
              spacing={4}
              as={'form'}
            // onSubmit={preventNativeSubmit(submit)}
            >
              <FormControlBox
                form={webForm as any}
                title="Title"
                formKey="title"
                isRequired
              >
                <Input
                  type="text"
                  onChange={(e) => webForm.setValue('title', e.currentTarget.value)}
                  value={webForm.data.title}
                />
              </FormControlBox>

              <FormControlBox
                form={webForm as any}
                title="Description [optional]"
                formKey="description"
              >
                <Textarea
                  onChange={(e) =>
                    webForm.setValue('description', e.currentTarget.value)
                  }
                ></Textarea>
              </FormControlBox>

              <FormControlBox
                form={webForm as any}
                title="Amount"
                formKey="amount"
                isRequired
              >
                <Input
                  type="number"
                  onChange={(e) => webForm.setValue('amount', e.currentTarget.value)}
                  value={webForm.data.amount}
                />
              </FormControlBox>

              <FormControlBox
                form={webForm as any}
                title="Academic Session"
                formKey="academicSession"
                isRequired
              >
                <AcademicSessionSelect
                  selectValue={webForm.data.academic_session_id}
                  isMulti={false}
                  isClearable={true}
                  onChange={(e: any) =>
                    webForm.setValue('academic_session_id', e?.value)
                  }
                  required
                />
              </FormControlBox>

              <FormControlBox
                form={webForm as any}
                title="Term"
                formKey="term"
                isRequired
              >
                <EnumSelect
                  enumData={TermType}
                  selectValue={webForm.data.term}
                  isMulti={false}
                  isClearable={true}
                  onChange={(e: any) => webForm.setValue('term', e?.value)}
                  required
                />
              </FormControlBox>

              <FormControlBox
                form={webForm as any}
                title="Expense Date"
                formKey="expense_date"
                isRequired
              >
                <Input
                  type="date"
                  max={today()}
                  value={webForm.data.expense_date}
                  onChange={(e: any) => webForm.setValue('expense_date', e.target.value)}
                />
              </FormControlBox>

              <FormControlBox
                form={webForm as any}
                title="Expense Category"
                formKey="expense_category_id"
                isRequired
              >
                <ExpenseCategorySelect
                  selectValue={webForm.data.expense_category_id}
                  isMulti={false}
                  isClearable={true}
                  onChange={(e: any) =>
                    webForm.setValue('expense_category_id', e?.value)
                  }
                  required
                  expenseCategories={expenseCategories}
                />
              </FormControlBox>

              <FormControl>
                <Button
                  colorScheme={'brand'}
                  onClick={onSubmit}
                  isLoading={webForm.processing}
                >
                  Submit
                </Button>
              </FormControl>
            </VStack>
          </SlabBody>
        </Slab>
      </CenteredBox>
    </DashboardLayout>
  );
}
