import React, { useState } from 'react';
import {
  HStack,
  Icon,
  IconButton,
  Input,
  InputGroup,
  InputRightAddon,
  Spacer,
  Text,
  Tooltip,
} from '@chakra-ui/react';
import {
  CheckIcon,
  PencilSquareIcon,
  TrashIcon,
  XMarkIcon,
} from '@heroicons/react/24/solid';
import FileObject from '@/components/file-dropper/file-object';

interface Props {
  file: FileObject;
  onDelete(): void;
  onNameChange(name: string): void;
  canRename: boolean;
}

export default function FileRow({
  file,
  onDelete,
  onNameChange,
  canRename,
}: Props) {
  const [name, setName] = useState(file.name);
  const [editing, setEditing] = useState(false);

  const isDisabled = name.length === 0 || name.includes('.');

  function onConfirm() {
    setEditing(false);
    onNameChange(name);
  }

  function onCancel() {
    setEditing(false);
    setName(file.name);
  }

  function onEdit() {
    setName(file.name);
    setEditing(true);
  }

  if (editing) {
    return (
      <HStack>
        <InputGroup>
          <Input
            value={name}
            onChange={(e) => setName(e.currentTarget.value)}
          />
          {file.extension && (
            <InputRightAddon>.{file.extension}</InputRightAddon>
          )}
        </InputGroup>
        <Spacer />
        <HStack>
          <Tooltip label={'Confirm'}>
            <IconButton
              aria-label={'Confirm'}
              isDisabled={isDisabled}
              icon={<Icon as={CheckIcon} />}
              onClick={onConfirm}
              variant={'ghost'}
              size={'sm'}
            />
          </Tooltip>
          <Tooltip label={'Discard'}>
            <IconButton
              aria-label={'Cancel'}
              icon={<Icon as={XMarkIcon} />}
              onClick={onCancel}
              variant={'ghost'}
              size={'sm'}
            />
          </Tooltip>
        </HStack>
      </HStack>
    );
  }

  return (
    <HStack>
      <HStack>
        <Text>{file.getNameWithExtension()}</Text>
        {canRename && (
          <Tooltip label={'Rename'}>
            <IconButton
              aria-label={'Rename'}
              icon={<Icon as={PencilSquareIcon} />}
              onClick={onEdit}
              size={'sm'}
              variant={'ghost'}
            />
          </Tooltip>
        )}
      </HStack>
      <Spacer />
      <Tooltip label={'Delete'}>
        <IconButton
          aria-label={'Delete'}
          icon={<Icon as={TrashIcon} />}
          onClick={onDelete}
          size={'sm'}
          variant={'ghost'}
          colorScheme={'red'}
        />
      </Tooltip>
    </HStack>
  );
}
