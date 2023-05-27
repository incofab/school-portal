import React, { useMemo } from 'react';
import { Div, Li } from '@/components/semantic';
import {
  Button,
  Divider,
  HStack,
  Icon,
  Spacer,
  Text,
  VStack,
} from '@chakra-ui/react';
import { CloudArrowUpIcon } from '@heroicons/react/24/outline';
import { useDropzone } from 'react-dropzone';
import produce from 'immer';
import {
  bytesToMb,
  FileAcceptObject,
  FileDropperType,
  MAX_FILE_SIZE_BYTES,
} from '@/components/file-dropper/common';
import FileRow from '@/components/file-dropper/file-row';
import FileObject from '@/components/file-dropper/file-object';

interface Props {
  files: FileObject[];
  onChange(files: FileObject[]): void;
  accept?: FileAcceptObject[];
  canRename?: boolean;
  maxSize?: number;
  multiple?: boolean;
}

export default function FileDropper({
  files,
  onChange,
  accept = [FileDropperType.Image, FileDropperType.Pdf, FileDropperType.Zip],
  canRename = true,
  maxSize = MAX_FILE_SIZE_BYTES,
  multiple,
}: Props) {
  const { extensions, mimes } = useMemo(() => {
    const extensions: string[] = [];
    let mimes: { [mime: string]: string[] } = {};
    for (const obj of accept) {
      extensions.push(...obj.extensionLabels);
      mimes = { ...mimes, ...obj.mimes };
    }
    return { extensions, mimes };
  }, [accept]);

  const { getRootProps, getInputProps, isDragActive, open } = useDropzone({
    onDrop,
    noClick: files.length > 0,
    accept: mimes,
    multiple,
    maxSize,
  });

  function onDrop(acceptedFiles: File[]) {
    onChange([...files, ...acceptedFiles.map((file) => new FileObject(file))]);
  }

  function onFileRemove(idx: number) {
    onChange(
      produce(files, (draft) => {
        draft.splice(idx, 1);
      })
    );
  }

  function onFileNameChange(idx: number, name: string) {
    onChange(
      produce(files, (draft) => {
        draft[idx].name = name;
      })
    );
  }

  const containerHoverStyle = {
    border: '1px solid',
    borderColor: 'blue.400',
    bg: 'blue.50',
    opacity: 0.5,
    rounded: 'md',
  };

  if (files.length === 0) {
    return (
      <VStack
        {...getRootProps()}
        p={4}
        {...(isDragActive
          ? containerHoverStyle
          : {
              border: '1px solid',
              borderColor: 'blackAlpha.400',
              rounded: 'md',
            })}
      >
        <input {...getInputProps()} />
        <Icon as={CloudArrowUpIcon} w={16} h={16} color={'blue.400'} />
        <VStack spacing={1}>
          <Text>Drop {multiple ? 'files' : 'a file'} or click here</Text>
          <Text fontSize={'sm'} color={'blackAlpha.700'}>
            Allowed extensions {extensions.join('/')}
          </Text>
          <Text fontSize={'sm'} color={'blackAlpha.700'}>
            Maximum size {Math.floor(bytesToMb(maxSize))}MB
          </Text>
        </VStack>
      </VStack>
    );
  }

  return (
    <div {...getRootProps()}>
      <Div {...(isDragActive ? containerHoverStyle : {})}>
        <input {...getInputProps()} />

        <VStack
          align={'stretch'}
          as={'ul'}
          listStyleType={'none'}
          divider={<Divider />}
        >
          {files.map((file, i) => (
            <Li key={i}>
              <FileRow
                file={file}
                onDelete={() => onFileRemove(i)}
                onNameChange={(name) => onFileNameChange(i, name)}
                canRename={canRename}
              />
            </Li>
          ))}
        </VStack>

        {multiple && (
          <HStack mt={4}>
            <Spacer />
            <Button
              variant={'ghost'}
              size={'sm'}
              leftIcon={<Icon as={CloudArrowUpIcon} />}
              colorScheme={'blue'}
              onClick={open}
            >
              Drop files or click to upload more
            </Button>
            <Spacer />
          </HStack>
        )}
      </Div>
    </div>
  );
}
