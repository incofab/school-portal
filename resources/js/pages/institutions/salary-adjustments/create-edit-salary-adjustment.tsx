import React from 'react';
import {
    Button,
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
import { Inertia } from '@inertiajs/inertia';
import { AdjustmentType, SalaryAdjustment} from '@/types/models';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import CenteredBox from '@/components/centered-box';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import FormControlBox from '@/components/forms/form-control-box';
import { InstitutionUserType, TermType, YearMonth } from '@/types/types';
import useModalToggle from '@/hooks/use-modal-toggle';
import { PlusIcon } from '@heroicons/react/24/solid';
import { Div } from '@/components/semantic';
import InstitutionUserSelect from '@/components/selectors/institution-user-select';
import AdjustmentTypeSelect from '@/components/selectors/adjustment-type-select';
import CreateEditAdjustmentTypeModal from '@/components/modals/create-edit-adjustment-type-modal';
import EnumSelect from '@/components/dropdown-select/enum-select';
import YearSelect from '@/components/selectors/year-select';

interface Props {
    salaryAdjustment?: SalaryAdjustment;
    adjustmentTypes: AdjustmentType[];
    parentAdjustmentTypes: AdjustmentType[];
}

export default function CreateEditSalaryAdjustment({ salaryAdjustment, adjustmentTypes, parentAdjustmentTypes }: Props) {
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

    const onSubmit = async () => {
        const res = await webForm.submit(async (data, web) => {
            return salaryAdjustment
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
        Inertia.visit(instRoute('salary-adjustments.index'));
    };

    const selectedAdjustmentType = webForm.data.adjustment_type_id ? adjustmentTypes.find(item => item.id === webForm.data.adjustment_type_id) : null;
    return (
        <DashboardLayout>
            <CenteredBox>
                <Slab>
                    <SlabHeading
                        title={`${salaryAdjustment ? 'Update' : 'Create'} Salary Adjustment`}
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
                                    isDisabled={salaryAdjustment ? true : false}
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

            <CreateEditAdjustmentTypeModal
                adjustmentTypes={parentAdjustmentTypes}
                {...createAdjustmentTypeModal.props}
                onSuccess={() => window.location.reload()} />
        </DashboardLayout>
    );
}
