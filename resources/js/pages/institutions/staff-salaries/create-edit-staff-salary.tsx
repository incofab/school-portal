//== THIS VIEW IS NO LONGER BEING USED. IT WAS REPLACED WITH 'CREATE-EDIT-STAFF-SALARY-MODAL.TSX' ==

/*
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
import { Classification, ClassificationGroup, Expense, ExpenseCategory, SalaryType, StaffSalary } from '@/types/models';
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
import SalaryTypeSelect from '@/components/selectors/salary-type-select';
import CreateEditSalaryTypeModal from '@/components/modals/create-edit-salary-type-modal';
import InstitutionUserSelect from '@/components/selectors/institution-user-select';

interface Props {
    staffSalary?: StaffSalary;
    salaryTypes: SalaryType[];
    parentSalaryTypes: SalaryType[];
}

export default function CreateEditStaffSalary({ staffSalary, salaryTypes, parentSalaryTypes }: Props) {
    const { handleResponseToast } = useMyToast();
    const { instRoute } = useInstitutionRoute();
    const createSalaryTypeModal = useModalToggle();

    const webForm = useWebForm({
        salary_type_id: staffSalary?.salary_type_id ?? '',
        description: staffSalary?.description ?? '',
        amount: staffSalary?.amount ?? '',

        institution_user_id: staffSalary?.institution_user
            ? {
                label: staffSalary.institution_user.user?.full_name,
                value: staffSalary.institution_user_id,
            }
            : null,
    });

    const onSubmit = async () => {
        const res = await webForm.submit(async (data, web) => {
            return staffSalary
                ? web.put(instRoute('staff-salaries.update', [staffSalary]), {
                    ...data,
                    institution_user_id: data.institution_user_id?.value
                })
                : web.post(instRoute('staff-salaries.store'), {
                    ...data,
                    institution_user_id: data.institution_user_id?.value
                });
        });

        if (!handleResponseToast(res)) {
            return;
        }
        Inertia.visit(instRoute('staff-salaries.index'));
    };

    return (
        <DashboardLayout>
            <CenteredBox>
                <Slab>
                    <SlabHeading
                        title={`${staffSalary ? 'Update' : 'Create'} Staff Salary`}
                    />
                    <SlabBody>
                        <VStack
                            spacing={4}
                            as={'form'}
                        // onSubmit={preventNativeSubmit(submit)}
                        >

                            <FormControlBox
                                title="Staff"
                                form={webForm as any}
                                formKey="institution_user_id"
                            >
                                <InstitutionUserSelect
                                    value={webForm.data.institution_user_id}
                                    isClearable={true}
                                    rolesIn={[
                                        InstitutionUserType.Admin,
                                        InstitutionUserType.Accountant,
                                        InstitutionUserType.Teacher,
                                    ]}
                                    onChange={(e) => webForm.setValue('institution_user_id', e)}
                                    isMulti={false}
                                    required 
                                    isDisabled={staffSalary ? true : false}
                                />
                            </FormControlBox>

                            <FormControlBox
                                form={webForm as any}
                                title="Salary Type"
                                formKey="salary_type_id"
                                isRequired
                            >
                                <HStack align={'stretch'}>
                                    <Div width={'full'}>
                                        <SalaryTypeSelect
                                            selectValue={webForm.data.salary_type_id}
                                            isMulti={false}
                                            isClearable={true}
                                            onChange={(e: any) =>
                                                webForm.setValue('salary_type_id', e?.value)
                                            }
                                            required
                                            salaryTypes={salaryTypes}
                                            
                                        />
                                    </Div>

                                    <IconButton
                                        aria-label="Add salary type"
                                        icon={<Icon as={PlusIcon} />}
                                        onClick={createSalaryTypeModal.open}
                                        colorScheme="brand"
                                    />
                                </HStack>
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
                                title="Description [optional]"
                                formKey="description"
                            >
                                <Textarea
                                    value={webForm.data.description}
                                    onChange={(e) =>
                                        webForm.setValue('description', e.currentTarget.value)
                                    }
                                ></Textarea>
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

            <CreateEditSalaryTypeModal
                salaryTypes={parentSalaryTypes}
                {...createSalaryTypeModal.props}
                onSuccess={() => window.location.reload()} />
        </DashboardLayout>
    );
}
*/