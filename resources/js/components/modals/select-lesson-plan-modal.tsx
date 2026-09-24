import React, { useEffect, useState } from 'react';
import {
  Button,
  FormControl,
  FormLabel,
  Modal,
  ModalBody,
  ModalCloseButton,
  ModalContent,
  ModalFooter,
  ModalHeader,
  ModalOverlay,
  Select,
  Text,
} from '@chakra-ui/react';
import { LessonPlan } from '@/types/models';
import { ModalToggleProps } from '@/components/generic-modal';

interface Props extends ModalToggleProps {
  lessonPlans: LessonPlan[];
  onContinue(lessonPlanId: number): void;
}

function getLessonPlanLabel(lessonPlan: LessonPlan): string {
  const topic = lessonPlan.scheme_of_work?.topic;
  const details = [
    topic?.title,
    topic?.course?.title,
    topic?.classification_group?.title,
    lessonPlan.scheme_of_work?.term
      ? `${lessonPlan.scheme_of_work.term} term`
      : null,
    lessonPlan.scheme_of_work?.week_number
      ? `Week ${lessonPlan.scheme_of_work.week_number}`
      : null,
  ].filter(Boolean);

  return details.join(' · ') || `Lesson plan #${lessonPlan.id}`;
}

export default function SelectLessonPlanModal({
  lessonPlans,
  onContinue,
  isOpen,
  onClose,
}: Props) {
  const [selectedLessonPlanId, setSelectedLessonPlanId] = useState('');

  useEffect(() => {
    if (isOpen) {
      setSelectedLessonPlanId('');
    }
  }, [isOpen]);

  const hasLessonPlans = lessonPlans.length > 0;

  function continueToLessonNote() {
    if (!selectedLessonPlanId) {
      return;
    }

    onContinue(Number(selectedLessonPlanId));
  }

  return (
    <Modal isOpen={isOpen} onClose={onClose} isCentered scrollBehavior="inside">
      <ModalOverlay />
      <ModalContent>
        <ModalHeader>New Lesson Note</ModalHeader>
        <ModalCloseButton />
        <ModalBody>
          <Text mb={4} color="blackAlpha.700">
            Select the lesson plan this note should document.
          </Text>

          {hasLessonPlans ? (
            <FormControl isRequired>
              <FormLabel>Lesson plan</FormLabel>
              <Select
                value={selectedLessonPlanId}
                onChange={(event) =>
                  setSelectedLessonPlanId(event.currentTarget.value)
                }
                placeholder="Choose a lesson plan"
              >
                {lessonPlans.map((lessonPlan) => (
                  <option key={lessonPlan.id} value={lessonPlan.id}>
                    {getLessonPlanLabel(lessonPlan)}
                  </option>
                ))}
              </Select>
            </FormControl>
          ) : (
            <Text color="blackAlpha.700">
              There are no lesson plans available for a new lesson note.
            </Text>
          )}
        </ModalBody>
        <ModalFooter gap={3}>
          <Button variant="ghost" onClick={onClose} type="button">
            Cancel
          </Button>
          <Button
            colorScheme="brand"
            onClick={continueToLessonNote}
            isDisabled={!hasLessonPlans || !selectedLessonPlanId}
            type="button"
          >
            Continue
          </Button>
        </ModalFooter>
      </ModalContent>
    </Modal>
  );
}
