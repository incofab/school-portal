import React from 'react';
import { Button, HStack, Icon, IconButton, Input, Textarea, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { AdjustmentType, SalaryAdjustment} from '@/types/models';
import FormControlBox from '../forms/form-control-box';
import EnumSelect from '../dropdown-select/enum-select';
import { InstitutionUserType, YearMonth } from '@/types/types';
import AdjustmentTypeSelect from '../selectors/adjustment-type-select';
import { PlusIcon } from '@heroicons/react/24/solid';
import { Div } from '@/components/semantic';
import InstitutionUserSelect from '../selectors/institution-user-select';
import useModalToggle from '@/hooks/use-modal-toggle';
import YearSelect from '../selectors/year-select';
import CreateEditAdjustmentTypeModal from './create-edit-adjustment-type-modal';

interface Props {
    isOpen: boolean;
    onClose(): void;
    onSuccess(): void;
    salaryAdjustment?: SalaryAdjustment;
    adjustmentTypes: AdjustmentType[];
    parentAdjustmentTypes: AdjustmentType[];
}

export default function CreateEditSalaryAdjustmentModal({
    isOpen,
    onSuccess,
    onClose,
    salaryAdjustment,
    adjustmentTypes,
    parentAdjustmentTypes,
}: Props) {
    const { handleResponseToast } = useMyToast();
    const { instRoute } = useInstitutionRoute();
    const createAdjustmentTypeModal = useModalToggle();

    const webForm = useWebForm({
        adjustment_type_id: salaryAdjustment?.adjustment_type_id ?? '',
        description: salaryAdjustment?.description ?? '',
        amount: salaryAdjustment?.amount ?? '',
        month: salaryAdjustment?.month ?? '',
        year: salaryAdjustment?.year ?? '',

        institution_user_id: salaryAdjustment?.institution_user
            ? {
                label: salaryAdjustment.institution_user.user?.full_name,
                value: salaryAdjustment.institution_user_id,
            }
            : null,
    });

    const isUpdate = salaryAdjustment?.id;

    const onSubmit = async () => {
        const res = await webForm.submit(async (data, web) => {
            return isUpdate
                ? web.put(instRoute('salary-adjustments.update', [salaryAdjustment]), {
                    ...data,
                    institution_user_id: data.institution_user_id?.value
                })
                : web.post(instRoute('salary-adjustments.store'), {
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

    const selectedAdjustmentType = webForm.data.adjustment_type_id ? adjustmentTypes.find(item => item.id === webForm.data.adjustment_type_id) : null;


    return (
        <>
            <GenericModal
                props={{ isOpen, onClose }}
                headerContent={`${isUpdate ? 'Update' : 'Create'} Salary Adjustment`}
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
                            title="Adjustment Type"
                            formKey="adjustment_type_id"
                            isRequired
                        >
                            <HStack align={'stretch'}>
                                <Div width={'full'}>
                                    <AdjustmentTypeSelect
                                        selectValue={webForm.data.adjustment_type_id}
                                        isMulti={false}
                                        isClearable={true}
                                        onChange={(e: any) =>
                                            webForm.setValue('adjustment_type_id', e?.value)
                                        }
                                        required
                                        adjustmentTypes={adjustmentTypes}

                                    />
                                </Div>

                                <IconButton
                                    aria-label="Add adjustment type"
                                    icon={<Icon as={PlusIcon} />}
                                    onClick={createAdjustmentTypeModal.open}
                                    colorScheme="brand"
                                />
                            </HStack>
                        </FormControlBox>
                        {!selectedAdjustmentType?.parent_id &&
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
                        }

                        <FormControlBox
                            form={webForm as any}
                            title="Affected Month"
                            formKey="month"
                        >
                            <EnumSelect
                                enumData={YearMonth}
                                selectValue={webForm.data.month}
                                isMulti={false}
                                isClearable={true}
                                onChange={(e: any) => webForm.setValue('month', e?.value)}
                                required
                            />
                        </FormControlBox>

                        <FormControlBox
                            form={webForm as any}
                            title="Affected Year"
                            formKey="year"
                            isRequired
                        >
                            <YearSelect
                                selectValue={webForm.data.year}
                                isMulti={false}
                                isClearable={true}
                                onChange={(e: any) =>
                                    webForm.setValue('year', e?.value)
                                }
                                required
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

            <CreateEditAdjustmentTypeModal
                adjustmentTypes={parentAdjustmentTypes}
                {...createAdjustmentTypeModal.props}
                onSuccess={() => window.location.reload()} />
        </>
    );
}
