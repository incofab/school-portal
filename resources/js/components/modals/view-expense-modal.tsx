import React from 'react';
import { Button, HStack, Input, Text, Textarea, VStack } from '@chakra-ui/react';
import useWebForm from '@/hooks/use-web-form';
import GenericModal from '@/components/generic-modal';
import useMyToast from '@/hooks/use-my-toast';
import FormControlBox from '../forms/form-control-box';
import { Expense } from '@/types/models';
import useInstitutionRoute from '@/hooks/use-institution-route';
import { Div } from '../semantic';
import DateTimeDisplay from '../date-time-display';

interface Props {
    isOpen: boolean;
    onClose(): void;
    onSuccess(): void;

    expense?: Expense;
}

export default function ViewExpenseModal({
    isOpen,
    onSuccess,
    onClose,
    expense,
}: Props) {

    return (
        <GenericModal
            props={{ isOpen, onClose }}
            headerContent={`Expense Details`}
            bodyContent={
                <VStack spacing={2} align="start">
                    <Div>
                        <Text fontWeight="bold">Title:</Text>
                        <Text>{expense?.title}</Text>
                    </Div>
                    <Div>
                        <Text fontWeight="bold">Amount:</Text>
                        <Text>{expense?.amount}</Text>
                    </Div>
                    <Div>
                        <Text fontWeight="bold">Academic Session:</Text>
                        <Text>{expense?.academic_session?.title}</Text>
                    </Div>
                    <Div>
                        <Text fontWeight="bold">Term:</Text>
                        <Text>{expense?.term}</Text>
                    </Div>
                    <Div>
                        <Text fontWeight="bold">Expense Date:</Text>
                        <Text><DateTimeDisplay dateTime={expense?.expense_date || ''} /></Text>
                    </Div>
                    <Div>
                        <Text fontWeight="bold">Expense Category:</Text>
                        <Text>{expense?.expense_category.title}</Text>
                    </Div>
                    <Div>
                        <Text fontWeight="bold">Recorded By:</Text>
                        <Text>{expense?.institution_user?.user?.full_name}</Text>
                    </Div>
                    <Div>
                        <Text fontWeight="bold">Description:</Text>
                        <Text>{expense?.description}</Text>
                    </Div>
                </VStack>
            }
            footerContent={
                <HStack spacing={2}>
                    <Button variant={'ghost'} onClick={onClose}>
                        Close
                    </Button>
                </HStack>
            }
        />
    );
}
