import React from 'react';
import {
  Modal,
  ModalBody,
  ModalBodyProps,
  ModalCloseButton,
  ModalContent,
  ModalFooter,
  ModalFooterProps,
  ModalHeader,
  ModalHeaderProps,
  ModalOverlay,
  ModalProps,
} from '@chakra-ui/react';

export interface ModalToggleProps {
  isOpen: boolean;
  onClose(): void;
}

interface Props {
  props: Omit<ModalProps, 'children'>;
  headerProps?: ModalHeaderProps;
  bodyProps?: ModalBodyProps;
  footerProps?: ModalFooterProps;
  headerContent?: JSX.Element | string;
  bodyContent?: JSX.Element | string;
  footerContent?: JSX.Element | string;
}

export interface BaseModalProps {
  isOpen: boolean;
  onClose(): void;
}

export default function GenericModal({
  props,
  headerProps,
  bodyProps,
  footerProps,
  headerContent,
  bodyContent,
  footerContent,
}: Props) {
  return (
    <Modal {...props}>
      <ModalOverlay />
      <ModalContent>
        <ModalHeader {...headerProps}>{headerContent}</ModalHeader>
        <ModalCloseButton />
        <ModalBody {...bodyProps}>{bodyContent}</ModalBody>
        <ModalFooter {...footerProps}>{footerContent}</ModalFooter>
      </ModalContent>
    </Modal>
  );
}
