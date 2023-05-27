import React from 'react';
import { Box, BoxProps } from '@chakra-ui/react';

/**
 * Avoiding `<Box>` hell with Chakra
 * It can be hard to read many nested <Box> calls, so we can
 * immitate normal html by exporting semantically named components
 */

export const Div = (props: BoxProps) => <Box as={'div'} {...props} />;
export const Ul = (props: BoxProps) => <Box as={'ul'} {...props} />;
export const Li = (props: BoxProps) => <Box as={'li'} {...props} />;
