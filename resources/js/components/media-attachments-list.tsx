import React from 'react';
import {
  Badge,
  HStack,
  Link,
  SimpleGrid,
  Text,
  VStack,
} from '@chakra-ui/react';
import { Media } from '@/types/models';
import { BrandButton } from '@/components/buttons';

interface Props {
  media?: Media[];
  emptyText?: string;
  onDelete?: (media: Media) => void;
  deletingMediaId?: number | null;
}

export default function MediaAttachmentsList({
  media = [],
  emptyText = 'No media uploaded.',
  onDelete,
  deletingMediaId = null,
}: Props) {
  if (media.length === 0) {
    return <Text color={'blackAlpha.700'}>{emptyText}</Text>;
  }

  return (
    <SimpleGrid columns={{ base: 1, md: 2 }} spacing={3}>
      {media.map((item) => (
        <VStack
          key={item.id}
          align={'start'}
          borderWidth={'1px'}
          rounded={'md'}
          p={3}
          spacing={1}
        >
          <HStack justify={'space-between'} width={'full'} align={'start'}>
            <Badge>{item.kind}</Badge>
            {onDelete && (
              <BrandButton
                title="Delete"
                size="xs"
                type="button"
                colorScheme="red"
                isLoading={deletingMediaId === item.id}
                onClick={() => onDelete(item)}
              />
            )}
          </HStack>
          <Link
            href={item.url}
            isExternal
            color={'blue.500'}
            fontWeight={'semibold'}
          >
            {item.original_name || item.filename}
          </Link>
          <Text fontSize={'sm'} color={'blackAlpha.700'}>
            {item.mime_type || 'Unknown file type'}
          </Text>
        </VStack>
      ))}
    </SimpleGrid>
  );
}
