import { AcademicSession, Institution, InstitutionGroup } from '@/types/models';
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
} from '@chakra-ui/react';
import React, { useState } from 'react';
import route from '@/util/route';
import { TermType } from '@/types/types';
import EnumSelect from '@/components/dropdown-select/enum-select';
import AcademicSessionSelect from '@/components/selectors/academic-session-select';

interface Props {
  isOpen: boolean;
  onClose: () => void;
  institutionGroup: InstitutionGroup & { institutions: Institution[] };
  academicSessions: AcademicSession[];
}

export default function GenerateInvoiceModal({
  isOpen,
  onClose,
  institutionGroup,
  academicSessions,
}: Props) {
  const [selectedSession, setSelectedSession] = useState('');
  const [selectedTerm, setSelectedTerm] = useState('');

  function handleGenerateInvoice() {
    if (!institutionGroup || !selectedSession) {
      alert('Please select an academic session.');
      return;
    }
    if (institutionGroup.institutions.length === 0) {
      alert('This institution group has no institutions.');
      return;
    }

    const url = route('managers.institution-groups.invoice.generate', [
      institutionGroup.id,
      selectedSession,
      selectedTerm,
    ]);
    window.open(url, '_blank');
    onClose();
  }

  return (
    <Modal isOpen={isOpen} onClose={onClose}>
      <ModalOverlay />
      <ModalContent>
        <ModalHeader>Generate Invoice for {institutionGroup?.name}</ModalHeader>
        <ModalCloseButton />
        <ModalBody>
          <FormControl isRequired>
            <FormLabel>Academic Session</FormLabel>
            <AcademicSessionSelect
              academicSessions={academicSessions}
              selectValue={selectedSession}
              onChange={(e: any) => setSelectedSession(e.value)}
            />
          </FormControl>
          <FormControl mt={4}>
            <FormLabel>Term</FormLabel>
            <EnumSelect
              enumData={TermType}
              selectValue={selectedTerm}
              onChange={(e: any) => setSelectedTerm(e.value)}
            />
          </FormControl>
        </ModalBody>
        <ModalFooter>
          <Button variant="ghost" mr={3} onClick={onClose}>
            Cancel
          </Button>
          <Button colorScheme="blue" onClick={handleGenerateInvoice}>
            Generate
          </Button>
        </ModalFooter>
      </ModalContent>
    </Modal>
  );
}
