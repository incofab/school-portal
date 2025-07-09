import React from 'react';
import { Button, HStack, Input, Textarea, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import useInstitutionRoute from '@/hooks/use-institution-route';
import InputForm from '../forms/input-form';
import { SalaryType } from '@/types/models';
import FormControlBox from '../forms/form-control-box';
import SalaryTypeSelect from '../selectors/salary-type-select';
import EnumSelect from '../dropdown-select/enum-select';
import { TransactionType } from '@/types/types';

interface Props {
    isOpen: boolean;
    onClose(): void;
    onSuccess(): void;
    salaryType?: SalaryType;
    salaryTypes: SalaryType[];
}

export default function CreateEditSalaryTypeModal({
    isOpen,
    onSuccess,
    onClose,
    salaryType,
    salaryTypes,
}: Props) {
    const { handleResponseToast } = useMyToast();
    const { instRoute } = useInstitutionRoute();

    const webForm = useWebForm({
        type: salaryType?.type ?? '',
        title: salaryType?.title ?? '',
        description: salaryType?.description ?? '',
        parent_id: salaryType?.parent_id ?? '',
        percentage: salaryType?.percentage ?? '',
    });

    const isUpdate = salaryType?.id;

    const onSubmit = async () => {
        const res = await webForm.submit((data, web) =>
            isUpdate
                ? web.put(
                    instRoute('salary-types.update', [salaryType]),
                    data
                )
                : web.post(instRoute('salary-types.store'), data)
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
            headerContent={`${isUpdate ? 'Update' : 'Create'} Salary Type`}
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
                        title="Parent Salary"
                        formKey="parent_id"
                        isRequired
                    >
                        <SalaryTypeSelect
                            selectValue={webForm.data.parent_id}
                            isMulti={false}
                            isClearable={true}
                            onChange={(e: any) =>
                                webForm.setValue('parent_id', e?.value)
                            }
                            required
                            salaryTypes={salaryTypes}
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
