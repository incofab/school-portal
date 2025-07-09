import React from 'react';
import { Button, HStack, Icon, IconButton, Input, Textarea, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { SalaryType, StaffSalary } from '@/types/models';
import FormControlBox from '../forms/form-control-box';
import SalaryTypeSelect from '../selectors/salary-type-select';
import { InstitutionUserType } from '@/types/types';
import { PlusIcon } from '@heroicons/react/24/solid';
import { Div } from '@/components/semantic';
import InstitutionUserSelect from '../selectors/institution-user-select';
import useModalToggle from '@/hooks/use-modal-toggle';
import CreateEditSalaryTypeModal from './create-edit-salary-type-modal';

interface Props {
    isOpen: boolean;
    onClose(): void;
    onSuccess(): void;
    staffSalary?: StaffSalary;
    salaryTypes: SalaryType[];
    parentSalaryTypes: SalaryType[];
}

export default function CreateEditStaffSalaryModal({
    isOpen,
    onSuccess,
    onClose,
    staffSalary,
    salaryTypes,
    parentSalaryTypes
}: Props) {
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

    const isUpdate = staffSalary?.id;

    const onSubmit = async () => {
        const res = await webForm.submit(async (data, web) => {
            return isUpdate
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

        onClose();
        webForm.reset();
        onSuccess();
    };

    return (
        <>
            <GenericModal
                props={{ isOpen, onClose }}
                headerContent={`${isUpdate ? 'Update' : 'Create'} Staff Salary`}
                bodyContent={
                    <VStack spacing={3}>

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
                                isDisabled={isUpdate ? true : false}
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

                    </VStack>
                }
                footerContent={
                    <HStack spacing={2}>
                        <Button variant={'ghost'} onClick={onClose}>
                            Close
                        </Button>
                        <Button
                            colorScheme={'brand'}
                            onClick={onSubmit}
                            isLoading={webForm.processing}
                        >
                            Create
                        </Button>
                    </HStack>
                }
            />

            <CreateEditSalaryTypeModal
                salaryTypes={parentSalaryTypes}
                {...createSalaryTypeModal.props}
                onSuccess={() => window.location.reload()} />
        </>
    );
}
