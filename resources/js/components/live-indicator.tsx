import { Box, BoxProps } from '@chakra-ui/react';
import { keyframes } from '@emotion/react';
import React from 'react';

const pulse = keyframes`
  0% {
    box-shadow: 0 0 0 0 rgba(72, 187, 120, 0.7);
  }
  70% {
    box-shadow: 0 0 0 10px rgba(72, 187, 120, 0);
  }
  100% {
    box-shadow: 0 0 0 0 rgba(72, 187, 120, 0);
  }
`;

interface LiveIndicatorProps {
  size?: number;
}

const LiveIndicator: React.FC<LiveIndicatorProps & BoxProps> = ({
  size = 12,
  ...props
}) => {
  return (
    <Box
      width={`${size}px`}
      height={`${size}px`}
      borderRadius="50%"
      bg="green.400"
      animation={`${pulse} 1.5s infinite`}
      display="inline-block"
      {...props}
    />
  );
};

export default LiveIndicator;
