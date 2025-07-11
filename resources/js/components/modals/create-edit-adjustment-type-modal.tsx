import React from 'react';
import { Button, HStack, Input, Textarea, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import InputForm from '../forms/input-form';
import { AdjustmentType, SalaryType } from '@/types/models';
import FormControlBox from '../forms/form-control-box';
import SalaryTypeSelect from '../selectors/salary-type-select';
import EnumSelect from '../dropdown-select/enum-select';
import { TransactionType } from '@/types/types';
import AdjustmentTypeSelect from '../selectors/adjustment-type-select';

interface Props {
    isOpen: boolean;
    onClose(): void;
    onSuccess(): void;
    adjustmentType?: AdjustmentType;
    adjustmentTypes: AdjustmentType[]; 
}

export default function CreateEditAdjustmentTypeModal({
    isOpen,
    onSuccess,
    onClose,
    adjustmentType,
    adjustmentTypes,
}: Props) {
    const { handleResponseToast } = useMyToast();
    const { instRoute } = useInstitutionRoute();

    const webForm = useWebForm({
        type: adjustmentType?.type ?? '',
        title: adjustmentType?.title ?? '',
        description: adjustmentType?.description ?? '',
        parent_id: adjustmentType?.parent_id ?? '',
        percentage: adjustmentType?.percentage ?? '',
    });

    const isUpdate = adjustmentType?.id;

    const onSubmit = async () => {
        const res = await webForm.submit((data, web) =>
            isUpdate 
                ? web.put(
                    instRoute('adjustment-types.update', [adjustmentType]),
                    data
                )
                : web.post(instRoute('adjustment-types.store'), data)
        );

        if (!handleResponseToast(res)) {
            return;
        }

        onClose();
        webForm.reset();
        onSuccess();
    };

    return (
        <GenericModal
            props={{ isOpen, onClose }}
            headerContent={`${isUpdate ? 'Update' : 'Create'} Adjustment Type`}
            bodyContent={
                <VStack spacing={3}>

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
                        title="Type"
                        formKey="type"
                    >
                        <EnumSelect
                            enumData={TransactionType}
                            selectValue={webForm.data.type}
                            isMulti={false}
                            isClearable={true}
                            onChange={(e: any) => webForm.setValue('type', e?.value)}
                            required
                        />
                    </FormControlBox>

                    <FormControlBox
                        form={webForm as any}
                        title="Parent Adjustment"
                        formKey="parent_id"
                        isRequired
                    >
                        <AdjustmentTypeSelect
                            selectValue={webForm.data.parent_id}
                            isMulti={false}
                            isClearable={true}
                            onChange={(e: any) =>
                                webForm.setValue('parent_id', e?.value)
                            }
                            required
                            adjustmentTypes={adjustmentTypes}
                        />
                    </FormControlBox>

                    {
                        webForm.data.parent_id ? 
                        <FormControlBox
                            form={webForm as any}
                            title="Percentage"
                            formKey="percentage"
                        >
                            <Input
                                type="number"
                                onChange={(e) => webForm.setValue('percentage', e.currentTarget.value)}
                                value={webForm.data.percentage}
                            />
                        </FormControlBox> : ''
                    }

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
    );
}
