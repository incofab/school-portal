import React, {useState} from 'react';
import { Button, HStack, Input, Textarea, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import FormControlBox from '../forms/form-control-box';
import { ExpenseCategory } from '@/types/models';
import useInstitutionRoute from '@/hooks/use-institution-route';

interface Props {
    isOpen: boolean;
    onClose(): void;
    onSuccess(): void;

    expenseCategory: ExpenseCategory;
}

export default function CreateEditExpenseCategoryModal({
    isOpen,
    onSuccess,
    onClose,
    expenseCategory,
}: Props) {
    const { handleResponseToast } = useMyToast();
    const { instRoute } = useInstitutionRoute();
    
    const webForm = useWebForm({
        title: expenseCategory.title ?? '',
        description: expenseCategory.description ?? '',
    });

    const isUpdate = expenseCategory.id;

    const onSubmit = async () => {
        const res = await webForm.submit((data, web) =>
            isUpdate
                ? web.put(instRoute('expense-categories.update', [expenseCategory]), data)
                : web.post(instRoute('expense-categories.store'), data)
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
            headerContent={`${isUpdate ? 'Update' : 'Create'} Expense Category`}
            bodyContent={
                <VStack spacing={2}>
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
                        Submit
                    </Button>
                </HStack>
            }
        />
    );
}
