import React, { useMemo, useState } from 'react';
import { InertiaLink } from '@inertiajs/inertia-react';
import {
  Badge,
  Box,
  Button,
  Collapse,
  Container,
  Flex,
  HStack,
  Heading,
  Icon,
  Input,
  InputGroup,
  InputLeftElement,
  SimpleGrid,
  Stack,
  Text,
  VStack,
  useColorModeValue,
} from '@chakra-ui/react';
import {
  BookOpenIcon,
  ChevronDownIcon,
  ChevronRightIcon,
  MagnifyingGlassIcon,
} from '@heroicons/react/24/outline';
import DOMPurify from 'dompurify';
import { Faq } from '@/types/models';
import { FaqType } from '@/types/types';
import route from '@/util/route';

type FaqSearchResult = Faq & {
  score: number;
  snippet: string;
};

const popularSearches = [
  'result',
  'receipt',
  'student',
  'session',
  'teacher',
  'login',
  'payment',
  'attendance',
];

const normalize = (value: string) => value.toLowerCase().trim();

const htmlToText = (html: string) =>
  DOMPurify.sanitize(html, { ALLOWED_TAGS: [] }).replace(/\s+/g, ' ').trim();

const scoreFaq = (faq: Faq, normalizedQuery: string) => {
  const name = normalize(faq.name);
  const code = normalize(faq.code);
  const answer = normalize(htmlToText(faq.description_html));
  const tokens = normalizedQuery.split(' ').filter(Boolean);
  let score = 0;

  if (name.includes(normalizedQuery)) score += 90;
  if (name.startsWith(normalizedQuery)) score += 25;
  if (code.includes(normalizedQuery)) score += 50;
  if (answer.includes(normalizedQuery)) score += 15;

  tokens.forEach((token) => {
    if (name.includes(token)) score += 18;
    if (code.includes(token)) score += 14;
    if (answer.includes(token)) score += 4;
  });

  return score;
};

const buildSnippet = (faq: Faq, normalizedQuery: string) => {
  const answer = htmlToText(faq.description_html);
  const normalizedAnswer = normalize(answer);
  const matchIndex = normalizedAnswer.indexOf(normalizedQuery);

  if (matchIndex === -1) {
    return answer.slice(0, 180);
  }

  return answer.slice(Math.max(0, matchIndex - 60), matchIndex + 160);
};

interface Props {
  faqs: Faq[];
  search?: string;
  contentType?: FaqType;
}

export default function KnowledgeBasePage({
  faqs,
  search = '',
  contentType = FaqType.KnowledgeBase,
}: Props) {
  const isFaqPage = contentType === FaqType.Faq;
  const contentLabel = isFaqPage ? 'FAQ' : 'Knowledge Base guide';
  const [query, setQuery] = useState(search);
  const [openIds, setOpenIds] = useState<string[]>([
    faqs[0] ? String(faqs[0].id) : '',
  ]);
  const [focusedId, setFocusedId] = useState<string | null>(null);

  const pageBg = useColorModeValue('gray.50', 'gray.900');
  const panelBg = useColorModeValue('white', 'gray.800');
  const mutedText = useColorModeValue('gray.600', 'gray.300');
  const borderColor = useColorModeValue('gray.200', 'gray.700');
  const softBg = useColorModeValue('brand.50', 'gray.700');
  const subtleCardBg = useColorModeValue('gray.50', 'gray.900');

  const searchResults = useMemo<FaqSearchResult[]>(() => {
    const normalizedQuery = normalize(query);

    if (!normalizedQuery) {
      return [];
    }

    return faqs
      .map((faq) => ({
        ...faq,
        score: scoreFaq(faq, normalizedQuery),
        snippet: buildSnippet(faq, normalizedQuery),
      }))
      .filter((faq) => faq.score > 0)
      .sort(
        (first, second) =>
          second.score - first.score || first.name.localeCompare(second.name)
      )
      .slice(0, 12);
  }, [faqs, query]);

  const toggleItem = (id: string) => {
    setOpenIds((current) =>
      current.includes(id)
        ? current.filter((itemId) => itemId !== id)
        : [...current, id]
    );
  };

  const openAndScrollToItem = (id: string) => {
    setOpenIds((current) =>
      current.includes(id) ? current : [...current, id]
    );
    setFocusedId(id);
    window.setTimeout(() => {
      document.getElementById(id)?.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
      });
    }, 50);
  };

  return (
    <Box minH="100vh" bg={pageBg}>
      <Box bg={panelBg} borderBottomWidth={1} borderColor={borderColor}>
        <Container maxW="7xl" py={4}>
          <Flex
            align={{ base: 'stretch', md: 'center' }}
            justify="space-between"
            gap={3}
            direction={{ base: 'column', md: 'row' }}
          >
            <HStack spacing={3}>
              <Box
                boxSize={10}
                rounded="md"
                bg="brand.600"
                color="white"
                display="grid"
                placeItems="center"
                fontWeight="bold"
              >
                EM
              </Box>
              <Box>
                <Text fontWeight="bold" color="brand.700">
                  EduManager
                </Text>
                <Text fontSize="sm" color={mutedText}>
                  {isFaqPage ? 'FAQ' : 'Knowledge Base'}
                </Text>
              </Box>
            </HStack>
            <HStack spacing={3}>
              <Button
                as={InertiaLink}
                href={route('login')}
                size="sm"
                variant="outline"
                colorScheme="brand"
              >
                Login
              </Button>
              <Button
                as={InertiaLink}
                href={route('home')}
                size="sm"
                colorScheme="brand"
              >
                Home
              </Button>
            </HStack>
          </Flex>
        </Container>
      </Box>

      <Container maxW="7xl" py={{ base: 8, md: 12 }}>
        <Stack spacing={8}>
          <Box
            bg={panelBg}
            borderWidth={1}
            borderColor={borderColor}
            rounded="lg"
            p={{ base: 5, md: 8 }}
          >
            <Stack spacing={6}>
              <Stack spacing={3} maxW="4xl">
                <Badge alignSelf="flex-start" colorScheme="brand" rounded="md">
                  Help Center
                </Badge>
                <Heading size={{ base: 'xl', md: '2xl' }}>
                  {isFaqPage
                    ? 'Frequently Asked Questions'
                    : 'Knowledge Base Guides'}
                </Heading>
                <Text color={mutedText} fontSize={{ base: 'md', md: 'lg' }}>
                  Find quick answers about students, results, payments, classes,
                  staff, report sheets, attendance, messages, admissions,
                  payroll, exams, and school administration.
                </Text>
              </Stack>

              <InputGroup size="lg">
                <InputLeftElement pointerEvents="none">
                  <Icon as={MagnifyingGlassIcon} color="gray.400" boxSize={5} />
                </InputLeftElement>
                <Input
                  value={query}
                  onChange={(event) => setQuery(event.target.value)}
                  placeholder="Search result, receipt, student, session, teacher, login..."
                  bg={useColorModeValue('white', 'gray.900')}
                  borderColor={borderColor}
                />
              </InputGroup>

              <HStack spacing={2} flexWrap="wrap">
                <Text fontSize="sm" color={mutedText} mr={1}>
                  Popular:
                </Text>
                {popularSearches.map((item) => (
                  <Button
                    key={item}
                    size="xs"
                    variant="outline"
                    colorScheme="brand"
                    onClick={() => setQuery(item)}
                  >
                    {item}
                  </Button>
                ))}
              </HStack>
            </Stack>
          </Box>

          {query.trim() && (
            <Box
              bg={panelBg}
              borderWidth={1}
              borderColor={borderColor}
              rounded="lg"
              p={{ base: 4, md: 5 }}
            >
              <Flex
                align={{ base: 'stretch', md: 'center' }}
                justify="space-between"
                gap={3}
                mb={4}
                direction={{ base: 'column', md: 'row' }}
              >
                <Box>
                  <Heading size="md">Search results</Heading>
                  <Text color={mutedText} fontSize="sm">
                    {searchResults.length} {contentLabel}
                    {searchResults.length === 1 ? '' : 's'} found for "{query}"
                  </Text>
                </Box>
                <Button size="sm" variant="ghost" onClick={() => setQuery('')}>
                  Clear search
                </Button>
              </Flex>

              {searchResults.length > 0 ? (
                <Stack spacing={3}>
                  {searchResults.map((result) => (
                    <Box
                      key={result.id}
                      as="button"
                      type="button"
                      textAlign="left"
                      borderWidth={1}
                      borderColor={borderColor}
                      rounded="md"
                      p={4}
                      bg={subtleCardBg}
                      _hover={{ borderColor: 'brand.400', bg: softBg }}
                      onClick={() => openAndScrollToItem(String(result.id))}
                    >
                      <HStack justify="space-between" align="start" spacing={4}>
                        <Box>
                          <Badge colorScheme="brand" mb={2}>
                            {result.code}
                          </Badge>
                          <Text fontWeight="bold">{result.name}</Text>
                          <Text color={mutedText} fontSize="sm" mt={1}>
                            {result.snippet}
                          </Text>
                        </Box>
                        <Icon
                          as={ChevronRightIcon}
                          boxSize={5}
                          color="brand.500"
                          flexShrink={0}
                          mt={1}
                        />
                      </HStack>
                    </Box>
                  ))}
                </Stack>
              ) : (
                <Box bg={softBg} rounded="md" p={5}>
                  <Text fontWeight="semibold">
                    No matching {contentLabel.toLowerCase()} found.
                  </Text>
                  <Text color={mutedText} mt={1}>
                    Try searching for result, payment, student, class, or report
                    sheet.
                  </Text>
                </Box>
              )}
            </Box>
          )}

          <SimpleGrid columns={{ base: 1, lg: 3 }} spacing={4}>
            <SummaryBox
              label={isFaqPage ? 'Published FAQs' : 'Published guides'}
              value={faqs.length}
            />
            <SummaryBox
              label="With video"
              value={faqs.filter((faq) => faq.youtube_embed_url).length}
            />
            <SummaryBox
              label="First result"
              value={faqs[0]?.name ?? `No ${contentLabel.toLowerCase()}s yet`}
              isText
            />
          </SimpleGrid>

          <Stack spacing={3}>
            {faqs.map((faq) => {
              const id = String(faq.id);
              const isOpen = openIds.includes(id);
              const isFocused = focusedId === id;
              const sanitizedHtml = DOMPurify.sanitize(faq.description_html);

              return (
                <Box
                  key={faq.id}
                  id={id}
                  bg={panelBg}
                  borderWidth={1}
                  borderColor={isFocused ? 'brand.400' : borderColor}
                  rounded="lg"
                  overflow="hidden"
                  boxShadow={
                    isFocused ? '0 0 0 2px rgba(56, 189, 248, 0.25)' : 'none'
                  }
                >
                  <Button
                    variant="ghost"
                    w="full"
                    h="auto"
                    minH="56px"
                    px={4}
                    py={3}
                    justifyContent="space-between"
                    rounded="none"
                    whiteSpace="normal"
                    textAlign="left"
                    onClick={() => toggleItem(id)}
                  >
                    <Text fontWeight="semibold" pr={3}>
                      {faq.name}
                    </Text>
                    <Icon
                      as={ChevronDownIcon}
                      boxSize={5}
                      transform={isOpen ? 'rotate(180deg)' : 'none'}
                      transition="transform 0.2s ease"
                      flexShrink={0}
                    />
                  </Button>
                  <Collapse in={isOpen} animateOpacity>
                    <Stack
                      spacing={4}
                      borderTopWidth={1}
                      borderColor={borderColor}
                      px={4}
                      py={4}
                    >
                      <Box
                        className="faq-content"
                        color={mutedText}
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

                      {faq.youtube_embed_url && (
                        <Box
                          position="relative"
                          overflow="hidden"
                          rounded="md"
                          borderWidth={1}
                          borderColor={borderColor}
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
                      )}
                    </Stack>
                  </Collapse>
                </Box>
              );
            })}
          </Stack>

          {faqs.length === 0 && (
            <Box
              bg={panelBg}
              borderWidth={1}
              borderColor={borderColor}
              rounded="lg"
              p={6}
            >
              <HStack spacing={3} align="start">
                <Icon as={BookOpenIcon} boxSize={6} color="brand.500" />
                <Box>
                  <Heading size="md">
                    No {contentLabel.toLowerCase()}s are available
                  </Heading>
                  <Text color={mutedText} mt={1}>
                    Please check back later or contact your school administrator
                    for support.
                  </Text>
                </Box>
              </HStack>
            </Box>
          )}

          <Box
            bg={panelBg}
            borderWidth={1}
            borderColor={borderColor}
            rounded="lg"
            p={{ base: 5, md: 6 }}
          >
            <VStack spacing={3} align="start">
              <Heading size="md">Still need support?</Heading>
              <Text color={mutedText}>
                Contact your school administrator or EduManager support with the
                school name, your role, the page you were using, the session and
                term where relevant, and a screenshot of the issue.
              </Text>
            </VStack>
          </Box>
        </Stack>
      </Container>
    </Box>
  );
}

function SummaryBox({
  label,
  value,
  isText = false,
}: {
  label: string;
  value: string | number;
  isText?: boolean;
}) {
  return (
    <Box bg="white" borderWidth={1} rounded="lg" p={4}>
      <Text fontSize="sm" color="gray.500">
        {label}
      </Text>
      <Text
        fontWeight="800"
        fontSize={isText ? 'md' : '2xl'}
        noOfLines={isText ? 2 : undefined}
      >
        {value}
      </Text>
    </Box>
  );
}
