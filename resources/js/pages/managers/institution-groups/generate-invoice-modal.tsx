import { Institution, InstitutionGroup } from '@/types/models';
import {
  Button,
  FormControl,
  FormLabel,
  HStack,
  IconButton,
  Input,
  Modal,
  ModalBody,
  ModalCloseButton,
  ModalContent,
  ModalFooter,
  ModalHeader,
  ModalOverlay,
  VStack,
} from '@chakra-ui/react';
import React, { useState } from 'react';
import route from '@/util/route';
import { TermType } from '@/types/types';
import EnumSelect from '@/components/dropdown-select/enum-select';
import AcademicSessionSelect from '@/components/selectors/academic-session-select';
import { MinusIcon, PlusIcon } from '@heroicons/react/24/outline';

interface Props {
  isOpen: boolean;
  onClose: () => void;
  institutionGroup: InstitutionGroup & { institutions: Institution[] };
}

type ExtraItem = { title: string; amount: string };

export default function GenerateInvoiceModal({
  isOpen,
  onClose,
  institutionGroup,
}: Props) {
  const [selectedSession, setSelectedSession] = useState('');
  const [selectedTerm, setSelectedTerm] = useState('');
  const [extraItems, setExtraItems] = useState<ExtraItem[]>([]);

  function addExtraItem() {
    setExtraItems((items) => [...items, { title: '', amount: '' }]);
  }

  function removeExtraItem(index: number) {
    setExtraItems((items) => items.filter((_, idx) => idx !== index));
  }

  function updateExtraItem(index: number, key: keyof ExtraItem, value: string) {
    setExtraItems((items) =>
      items.map((item, idx) =>
        idx === index ? { ...item, [key]: value } : item
      )
    );
  }

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
    const sanitizedExtras = extraItems
      .map((item) => ({
        title: item.title.trim(),
        amount: parseFloat(item.amount || '0'),
      }))
      .filter(
        (item) =>
          item.title.length > 0 && !Number.isNaN(item.amount) && item.amount > 0
      );

    const params = new URLSearchParams();
    sanitizedExtras.forEach((item, index) => {
      params.append(`extra_items[${index}][title]`, item.title);
      params.append(`extra_items[${index}][amount]`, item.amount.toString());
    });

    const fullUrl =
      sanitizedExtras.length > 0 ? `${url}?${params.toString()}` : url;

    window.open(fullUrl, '_blank');
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
          <FormControl mt={6}>
            <FormLabel display="flex" alignItems="center">
              Extra Invoice Items
              <IconButton
                aria-label="Add invoice item"
                size="sm"
                colorScheme="brand"
                icon={<PlusIcon width={16} />}
                ml={3}
                onClick={addExtraItem}
              />
            </FormLabel>
            <VStack spacing={3} align="stretch">
              {extraItems.map((item, index) => (
                <HStack key={index} spacing={3} align={'center'}>
                  <Input
                    placeholder="Title"
                    value={item.title}
                    onChange={(e) =>
                      updateExtraItem(index, 'title', e.target.value)
                    }
                  />
                  <Input
                    placeholder="Amount"
                    type="number"
                    min="0"
                    value={item.amount}
                    onChange={(e) =>
                      updateExtraItem(index, 'amount', e.target.value)
                    }
                  />
                  <IconButton
                    aria-label="Remove item"
                    size="sm"
                    colorScheme="red"
                    icon={<MinusIcon width={16} />}
                    onClick={() => removeExtraItem(index)}
                  />
                </HStack>
              ))}
            </VStack>
          </FormControl>
        </ModalBody>
        <ModalFooter>
          <Button variant="ghost" mr={3} onClick={onClose}>
            Cancel
          </Button>
          <Button colorScheme="brand" onClick={handleGenerateInvoice}>
            Generate
          </Button>
        </ModalFooter>
      </ModalContent>
    </Modal>
  );
}
