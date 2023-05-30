import { useState } from 'react';
import { Nullable } from '@/types/types';

export default function useModalToggle() {
  const [isOpen, setIsOpen] = useState(false);

  function open() {
    setIsOpen(true);
  }

  function close() {
    setIsOpen(false);
  }

  return {
    isOpen,
    open,
    close,
    // convenience for spreading
    props: {
      isOpen,
      onClose: close,
    },
  };
}

/**
 * Useful for tracking modal state related to an entity
 * e.g. editing a user and knowing which user to show in a modal
 */
export function useModalValueToggle<T>() {
  const [state, setState] = useState<{ obj: Nullable<T>; isOpen: boolean }>({
    obj: null,
    isOpen: false,
  });

  function open(nextValue: T) {
    setState({ obj: nextValue, isOpen: true });
  }

  function close() {
    setState({ obj: null, isOpen: false });
  }

  return {
    open,
    close,
    isOpen: state.isOpen,
    state: state.obj,
    // convenience for spreading
    props: {
      isOpen: state.isOpen,
      onClose: close,
    },
  };
}
