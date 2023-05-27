import React, { useState } from 'react';
import {
  Icon,
  IconButton,
  Input,
  InputGroup,
  InputProps,
  InputRightElement,
} from '@chakra-ui/react';
import { EyeIcon, EyeSlashIcon } from '@heroicons/react/24/solid';

interface Props {
  onPasswordToggle?: (isVisible: boolean) => void;
}

export default function PasswordInput({
  onPasswordToggle,
  ...props
}: Props & InputProps) {
  const [showPassword, setShowPassword] = useState(false);
  return (
    <InputGroup>
      <Input type={showPassword ? 'text' : 'password'} {...props} />
      <InputRightElement>
        <IconButton
          aria-label={showPassword ? 'Hide password' : 'Show password'}
          icon={<Icon as={showPassword ? EyeSlashIcon : EyeIcon} />}
          onClick={() => {
            setShowPassword(!showPassword);
            if (onPasswordToggle) {
              onPasswordToggle(!showPassword);
            }
          }}
          variant="ghost"
          tabIndex={-1}
        />
      </InputRightElement>
    </InputGroup>
  );
}
