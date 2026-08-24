import React from 'react';
import ManagerDashboardLayout from '@/layout/managers/manager-dashboard-layout';
import Slab, { SlabBody, SlabHeading } from '@/components/slab';
import { Faq } from '@/types/models';
import { FaqType } from '@/types/types';
import {
  Badge,
  Box,
  Button,
  HStack,
  Heading,
  Stack,
  Text,
} from '@chakra-ui/react';
import { InertiaLink } from '@inertiajs/inertia-react';
import DOMPurify from 'dompurify';
import route from '@/util/route';

interface Props {
  faq: Faq;
}

export default function ShowFaq({ faq }: Props) {
  const sanitizedHtml = DOMPurify.sanitize(faq.description_html);

  return (
    <ManagerDashboardLayout>
      <Slab>
        <SlabHeading
          title={`Preview ${
            faq.type === FaqType.KnowledgeBase
              ? 'Knowledge Base Article'
              : 'FAQ'
          }`}
          rightElement={
            <Button
              as={InertiaLink}
              href={route('managers.faqs.edit', [faq])}
              colorScheme="brand"
              size="sm"
            >
              Edit
            </Button>
          }
        />
        <SlabBody>
          <Stack spacing={5}>
            <HStack spacing={2} flexWrap="wrap">
              <Badge colorScheme={faq.is_active ? 'green' : 'gray'}>
                {faq.is_active ? 'Active' : 'Inactive'}
              </Badge>
              <Badge colorScheme={faq.type === FaqType.Faq ? 'blue' : 'purple'}>
                {faq.type === FaqType.Faq ? 'FAQ' : 'Knowledge Base'}
              </Badge>
              <Badge colorScheme="brand">{faq.code}</Badge>
              {faq.sort_order !== null && faq.sort_order !== undefined && (
                <Badge colorScheme="purple">Order {faq.sort_order}</Badge>
              )}
            </HStack>

            <Box borderWidth={1} rounded="md" p={5}>
              <Heading size="md" mb={4}>
                {faq.name}
              </Heading>
              <Box
                color="gray.700"
                lineHeight="1.8"
                sx={{
                  '& h1, & h2, & h3, & h4': {
                    color: 'gray.800',
                    fontWeight: '700',
                    marginTop: 3,
                    marginBottom: 2,
                  },
                  '& ul, & ol': { paddingLeft: 6 },
                  '& a': {
                    color: 'brand.600',
                    textDecoration: 'underline',
                  },
                  '& table': {
                    width: '100%',
                    borderCollapse: 'collapse',
                  },
                  '& th, & td': {
                    borderWidth: '1px',
                    padding: 2,
                  },
                }}
                dangerouslySetInnerHTML={{ __html: sanitizedHtml }}
              />
            </Box>

            {faq.youtube_embed_url && (
              <Box>
                <Text fontWeight="700" mb={2}>
                  Video
                </Text>
                <Box
                  position="relative"
                  overflow="hidden"
                  rounded="md"
                  borderWidth={1}
                  pt="56.25%"
                >
                  <Box
                    as="iframe"
                    src={faq.youtube_embed_url}
                    title={`Video for ${faq.name}`}
                    position="absolute"
                    inset={0}
                    w="full"
                    h="full"
                    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                    allowFullScreen
                  />
                </Box>
              </Box>
            )}
          </Stack>
        </SlabBody>
      </Slab>
    </ManagerDashboardLayout>
  );
}
