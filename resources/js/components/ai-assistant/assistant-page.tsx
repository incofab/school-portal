import React, {
  FormEvent,
  KeyboardEvent,
  useEffect,
  useMemo,
  useRef,
  useState,
} from 'react';
import {
  Accordion,
  AccordionButton,
  AccordionIcon,
  AccordionItem,
  AccordionPanel,
  Alert,
  AlertDescription,
  AlertIcon,
  AlertTitle,
  Badge,
  Box,
  Button,
  Container,
  Divider,
  Drawer,
  DrawerBody,
  DrawerCloseButton,
  DrawerContent,
  DrawerHeader,
  DrawerOverlay,
  Flex,
  Heading,
  HStack,
  Icon,
  IconButton,
  Input,
  Link,
  List,
  ListItem,
  Menu,
  MenuButton,
  MenuDivider,
  MenuItem,
  MenuList,
  Modal,
  ModalBody,
  ModalCloseButton,
  ModalContent,
  ModalFooter,
  ModalHeader,
  ModalOverlay,
  SimpleGrid,
  Spinner,
  Stack,
  Tag,
  Text,
  Textarea,
  Tooltip,
  VStack,
  VisuallyHidden,
  useBreakpointValue,
  useColorModeValue,
  useDisclosure,
} from '@chakra-ui/react';
import {
  ArchiveBoxIcon,
  ArrowPathIcon,
  Bars3Icon,
  ChatBubbleLeftRightIcon,
  CheckIcon,
  ClipboardDocumentIcon,
  EllipsisVerticalIcon,
  HandThumbDownIcon,
  HandThumbUpIcon,
  PaperAirplaneIcon,
  PencilSquareIcon,
  PlusIcon,
  ShieldCheckIcon,
  SparklesIcon,
  TrashIcon,
  WrenchScrewdriverIcon,
  XCircleIcon,
} from '@heroicons/react/24/outline';
import DashboardLayout from '@/layout/dashboard-layout';
import { useWeb } from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import {
  AiAssistantAction,
  AiAssistantConversation,
  AiAssistantConversationSummary,
  AiAssistantMessage,
} from '@/types/models';

interface Props {
  conversations: AiAssistantConversationSummary[];
  conversation?: AiAssistantConversation | null;
  actor: {
    is_guest: boolean;
    role?: string | null;
    institution_name?: string | null;
    can_run_actions?: boolean;
  };
  suggestions?: string[];
  endpoints: {
    create: string;
  };
  isPublic?: boolean;
}

const MAX_MESSAGE_LENGTH = 4000;

function safeSourceUrl(url?: string | null): string | null {
  if (!url) {
    return null;
  }

  try {
    const parsed = new URL(url, window.location.origin);

    return parsed.protocol === 'http:' || parsed.protocol === 'https:'
      ? parsed.href
      : null;
  } catch {
    return null;
  }
}

function renderInlineMarkdown(text: string): React.ReactNode[] {
  return text
    .split(/(\*\*[^*]+\*\*|`[^`]+`|\*[^*]+\*)/g)
    .filter(Boolean)
    .map((part, index) => {
      if (part.startsWith('**') && part.endsWith('**')) {
        return <strong key={`${part}-${index}`}>{part.slice(2, -2)}</strong>;
      }

      if (part.startsWith('`') && part.endsWith('`')) {
        return (
          <Box
            key={`${part}-${index}`}
            as="code"
            px={1}
            rounded="sm"
            bg="blackAlpha.100"
            _dark={{ bg: 'whiteAlpha.200' }}
          >
            {part.slice(1, -1)}
          </Box>
        );
      }

      if (part.startsWith('*') && part.endsWith('*')) {
        return <em key={`${part}-${index}`}>{part.slice(1, -1)}</em>;
      }

      return <React.Fragment key={`${part}-${index}`}>{part}</React.Fragment>;
    });
}

function renderAssistantContent(content: string): React.ReactNode {
  const lines = content.split(/\r?\n/);

  return (
    <Stack spacing={2}>
      {lines.map((line, index) => {
        if (!line.trim()) {
          return <Box key={`blank-${index}`} h={1} />;
        }

        const ordered = line.match(/^(\d+)\.\s+(.*)$/);
        if (ordered) {
          return (
            <HStack key={`ordered-${index}`} align="flex-start" spacing={2}>
              <Text flexShrink={0} fontWeight="semibold">
                {ordered[1]}.
              </Text>
              <Text>{renderInlineMarkdown(ordered[2])}</Text>
            </HStack>
          );
        }

        const unordered = line.match(/^[-*]\s+(.*)$/);
        if (unordered) {
          return (
            <HStack key={`unordered-${index}`} align="flex-start" spacing={2}>
              <Text flexShrink={0}>•</Text>
              <Text>{renderInlineMarkdown(unordered[1])}</Text>
            </HStack>
          );
        }

        return <Text key={`line-${index}`}>{renderInlineMarkdown(line)}</Text>;
      })}
    </Stack>
  );
}

/**
 * Staged labels shown while a turn is in flight. The assistant answers in one
 * request, so this communicates honest progress rather than faking token
 * streaming: each label describes a stage the backend actually performs.
 */
const WORKING_STAGES = [
  'Reading your message…',
  'Checking what you are allowed to see…',
  'Looking through verified EduManager guidance…',
  'Putting the answer together…',
];

export default function AssistantPage({
  conversations,
  conversation = null,
  actor,
  suggestions = [],
  endpoints,
  isPublic = false,
}: Props) {
  const web = useWeb();
  const { toastSuccess, toastError } = useMyToast();
  const [items, setItems] = useState(conversations);
  const [activeConversation, setActiveConversation] =
    useState<AiAssistantConversation | null>(conversation);
  const [draft, setDraft] = useState('');
  const [pendingMessage, setPendingMessage] = useState<string | null>(null);
  const [failedMessage, setFailedMessage] = useState<string | null>(null);
  const [workingStage, setWorkingStage] = useState(0);
  const [processing, setProcessing] = useState(false);
  const [loadingConversation, setLoadingConversation] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [renameTitle, setRenameTitle] = useState('');
  const [actionToConfirm, setActionToConfirm] =
    useState<AiAssistantAction | null>(null);
  const renameDialog = useDisclosure();
  const deleteDialog = useDisclosure();
  const historyDrawer = useDisclosure();
  const composerRef = useRef<HTMLTextAreaElement | null>(null);
  const transcriptEndRef = useRef<HTMLDivElement | null>(null);
  const confirmButtonRef = useRef<HTMLButtonElement | null>(null);
  const deleteButtonRef = useRef<HTMLButtonElement | null>(null);

  const isDesktop = useBreakpointValue({ base: false, lg: true }) ?? false;
  const pageBg = useColorModeValue('gray.50', 'gray.900');
  const panelBg = useColorModeValue('white', 'gray.800');
  const softBg = useColorModeValue('brand.50', 'gray.700');
  const borderColor = useColorModeValue('gray.200', 'gray.700');
  const mutedText = useColorModeValue('gray.600', 'gray.300');
  const assistantBubble = useColorModeValue('gray.50', 'gray.700');
  const clarificationBg = useColorModeValue('orange.50', 'orange.900');

  const subtitle = useMemo(() => {
    if (actor.is_guest) {
      return 'Ask about EduManager, onboarding, and public platform features.';
    }

    return actor.institution_name
      ? `A private conversation space for ${actor.institution_name}.`
      : 'Ask questions about EduManager in your own words.';
  }, [actor]);

  const messages = activeConversation?.messages ?? [];
  const hasTranscript = messages.length > 0 || Boolean(pendingMessage);
  const isBusy = processing || loadingConversation;

  // Advance the working label while a turn is in flight so a slow reply still
  // shows honest progress instead of a frozen spinner.
  useEffect(() => {
    if (!pendingMessage) {
      setWorkingStage(0);
      return;
    }

    const timer = window.setInterval(() => {
      setWorkingStage((stage) =>
        Math.min(stage + 1, WORKING_STAGES.length - 1)
      );
    }, 2500);

    return () => window.clearInterval(timer);
  }, [pendingMessage]);

  useEffect(() => {
    transcriptEndRef.current?.scrollIntoView({ block: 'end' });
  }, [messages.length, pendingMessage]);

  async function createConversation(): Promise<AiAssistantConversation | null> {
    const response = await web.post(endpoints.create);
    const created = response.data?.conversation as AiAssistantConversation;

    setActiveConversation(created);
    upsertConversation(created);
    return created;
  }

  function upsertConversation(next: AiAssistantConversation) {
    const summary: AiAssistantConversationSummary = {
      id: next.id,
      title: next.title,
      updated_at: next.updated_at,
      archived_at: next.archived_at,
      topic: next.topic,
      links: next.links,
    };

    setItems((current) => [
      summary,
      ...current.filter((item) => item.id !== summary.id),
    ]);
  }

  function readError(requestError: any, fallback: string): string {
    return requestError?.response?.data?.message ?? fallback;
  }

  function startNewConversation() {
    if (isBusy) {
      return;
    }

    setActiveConversation(null);
    setError(null);
    setFailedMessage(null);
    historyDrawer.onClose();
    composerRef.current?.focus();
  }

  async function selectConversation(item: AiAssistantConversationSummary) {
    if (isBusy) {
      return;
    }

    historyDrawer.onClose();

    if (item.id === activeConversation?.id) {
      return;
    }

    setLoadingConversation(true);
    setError(null);
    setFailedMessage(null);

    try {
      const response = await web.get(item.links.self);
      setActiveConversation(response.data?.conversation ?? null);
    } catch (requestError: any) {
      setError(
        readError(
          requestError,
          'This conversation could not be loaded. Please try again.'
        )
      );
    } finally {
      setLoadingConversation(false);
    }
  }

  async function submitMessage(message: string) {
    if (!message || isBusy) {
      return;
    }

    setProcessing(true);
    setError(null);
    setFailedMessage(null);
    setPendingMessage(message);

    try {
      const current = activeConversation ?? (await createConversation());
      if (!current) {
        return;
      }

      const response = await web.post(current.links.messages, { message });
      const updated = response.data?.conversation as AiAssistantConversation;

      setActiveConversation(updated);
      upsertConversation(updated);
      setDraft('');
    } catch (requestError: any) {
      setFailedMessage(message);
      setError(
        readError(
          requestError,
          'The assistant could not respond. Your message was not lost — you can retry it.'
        )
      );
    } finally {
      setPendingMessage(null);
      setProcessing(false);
    }
  }

  function sendMessage(event?: FormEvent) {
    event?.preventDefault();
    void submitMessage(draft.trim());
  }

  function onComposerKeyDown(event: KeyboardEvent<HTMLTextAreaElement>) {
    if (event.key === 'Enter' && (event.metaKey || event.ctrlKey)) {
      event.preventDefault();
      sendMessage();
    }
  }

  function useSuggestion(suggestion: string) {
    setDraft(suggestion);
    composerRef.current?.focus();
  }

  async function archiveConversation() {
    if (!activeConversation || isBusy) {
      return;
    }

    setProcessing(true);
    setError(null);

    try {
      await web.delete(activeConversation.links.archive);
      setItems((current) =>
        current.filter((item) => item.id !== activeConversation.id)
      );
      setActiveConversation(null);
      toastSuccess('Conversation archived.');
    } catch (requestError: any) {
      setError(
        readError(
          requestError,
          'The conversation could not be archived. Please try again.'
        )
      );
    } finally {
      setProcessing(false);
    }
  }

  async function deleteConversation() {
    if (!activeConversation || isBusy) {
      return;
    }

    setProcessing(true);
    setError(null);

    try {
      await web.delete(activeConversation.links.delete);
      setItems((current) =>
        current.filter((item) => item.id !== activeConversation.id)
      );
      setActiveConversation(null);
      deleteDialog.onClose();
      toastSuccess('Conversation deleted.');
    } catch (requestError: any) {
      setError(
        readError(
          requestError,
          'The conversation could not be deleted. Please try again.'
        )
      );
    } finally {
      setProcessing(false);
    }
  }

  function openRenameDialog() {
    if (!activeConversation) {
      return;
    }

    setRenameTitle(activeConversation.title);
    renameDialog.onOpen();
  }

  async function renameConversation() {
    if (!activeConversation || !renameTitle.trim() || isBusy) {
      return;
    }

    setProcessing(true);
    setError(null);

    try {
      const response = await web.patch(activeConversation.links.update, {
        title: renameTitle.trim(),
      });
      const updated = response.data?.conversation as AiAssistantConversation;

      setActiveConversation(updated);
      upsertConversation(updated);
      renameDialog.onClose();
    } catch (requestError: any) {
      setError(
        readError(
          requestError,
          'The conversation title could not be updated. Please try again.'
        )
      );
    } finally {
      setProcessing(false);
    }
  }

  async function copyMessage(message: AiAssistantMessage) {
    try {
      await navigator.clipboard.writeText(message.content);
      toastSuccess('Answer copied.');
    } catch {
      toastError('Your browser blocked copying. Select the text instead.');
    }
  }

  async function rateMessage(
    message: AiAssistantMessage,
    rating: 'helpful' | 'not_helpful'
  ) {
    if (!message.links?.feedback) {
      return;
    }

    try {
      const response = await web.post(message.links.feedback, { rating });
      setActiveConversation(
        response.data?.conversation as AiAssistantConversation
      );
      toastSuccess('Thanks — your feedback was recorded.');
    } catch (requestError: any) {
      toastError(
        readError(
          requestError,
          'Your feedback could not be recorded. Please try again.'
        )
      );
    }
  }

  async function confirmAction() {
    const action = actionToConfirm;

    if (!action?.confirmation_token || !action.links?.confirm || isBusy) {
      return;
    }

    setProcessing(true);
    setError(null);

    try {
      const response = await web.post(action.links.confirm, {
        confirmation_token: action.confirmation_token,
      });
      const updated = response.data?.conversation as AiAssistantConversation;
      setActiveConversation(updated);
      upsertConversation(updated);
      setActionToConfirm(null);
      toastSuccess(response.data?.message ?? 'The action was completed.');
    } catch (requestError: any) {
      setError(
        readError(
          requestError,
          'The action could not be confirmed. Please review it and try again.'
        )
      );
    } finally {
      setProcessing(false);
    }
  }

  async function cancelAction(action: AiAssistantAction) {
    if (!action.confirmation_token || !action.links?.cancel || isBusy) {
      return;
    }

    setProcessing(true);
    setError(null);

    try {
      const response = await web.post(action.links.cancel, {
        confirmation_token: action.confirmation_token,
      });
      const updated = response.data?.conversation as AiAssistantConversation;
      setActiveConversation(updated);
      upsertConversation(updated);
      setActionToConfirm(null);
    } catch (requestError: any) {
      setError(
        readError(
          requestError,
          'The action could not be cancelled. Please try again.'
        )
      );
    } finally {
      setProcessing(false);
    }
  }

  const conversationList = (
    <Stack spacing={2} as="nav" aria-label="Your conversations">
      {items.length === 0 ? (
        <Box py={4}>
          <Text fontSize="sm" color={mutedText}>
            You have no saved conversations yet.
          </Text>
          <Text fontSize="sm" color={mutedText} mt={1}>
            Ask your first question and it will be saved here so you can come
            back to it.
          </Text>
        </Box>
      ) : (
        items.map((item) => {
          const isActive = item.id === activeConversation?.id;
          return (
            <Button
              key={item.id}
              variant={isActive ? 'solid' : 'ghost'}
              colorScheme={isActive ? 'brand' : undefined}
              justifyContent="flex-start"
              textAlign="left"
              h="auto"
              minH={12}
              py={2}
              whiteSpace="normal"
              aria-current={isActive ? 'true' : undefined}
              isDisabled={isBusy}
              onClick={() => selectConversation(item)}
            >
              <Icon as={ChatBubbleLeftRightIcon} boxSize={4} mr={2} />
              <Text noOfLines={2} fontSize="sm">
                {item.title}
              </Text>
            </Button>
          );
        })
      )}
    </Stack>
  );

  function renderToolObservations(message: AiAssistantMessage) {
    if (!message.tools?.length) {
      return null;
    }

    return (
      <Accordion allowToggle mt={3}>
        <AccordionItem border="none">
          <AccordionButton px={0} _hover={{ bg: 'transparent' }}>
            <HStack flex="1" spacing={2} justify="flex-start">
              <Icon as={WrenchScrewdriverIcon} boxSize={4} color={mutedText} />
              <Text fontSize="xs" fontWeight="bold" color={mutedText}>
                What I checked ({message.tools.length})
              </Text>
            </HStack>
            <AccordionIcon />
          </AccordionButton>
          <AccordionPanel px={0} pb={2}>
            <List spacing={2}>
              {message.tools.map((tool, index) => (
                <ListItem
                  key={`${message.id}-${tool.name}-${index}`}
                  borderWidth={1}
                  borderColor={borderColor}
                  rounded="md"
                  p={2}
                >
                  <HStack justify="space-between" align="flex-start" gap={2}>
                    <Text fontSize="xs" fontWeight="semibold">
                      {tool.name.replace(/_/g, ' ')}
                    </Text>
                    <Badge
                      colorScheme={tool.result.ok ? 'green' : 'gray'}
                      flexShrink={0}
                    >
                      {tool.result.ok ? 'Allowed' : 'Not available'}
                    </Badge>
                  </HStack>
                  <Text fontSize="xs" color={mutedText} mt={1}>
                    {tool.result.message}
                  </Text>
                </ListItem>
              ))}
            </List>
          </AccordionPanel>
        </AccordionItem>
      </Accordion>
    );
  }

  function renderSources(message: AiAssistantMessage) {
    if (!message.sources?.length) {
      return null;
    }

    return (
      <Box mt={3} pt={3} borderTopWidth={1} borderColor={borderColor}>
        <HStack spacing={2} mb={2}>
          <Icon as={ShieldCheckIcon} boxSize={4} color={mutedText} />
          <Text fontSize="xs" fontWeight="bold" color={mutedText}>
            Verified sources
          </Text>
        </HStack>
        <Stack spacing={1}>
          {message.sources.map((source) => {
            const href = safeSourceUrl(source.url);

            return href ? (
              <Link
                key={source.id}
                href={href}
                isExternal
                fontSize="xs"
                color="brand.600"
                _dark={{ color: 'brand.200' }}
              >
                {source.title}
              </Link>
            ) : (
              <Text key={source.id} fontSize="xs" color={mutedText}>
                {source.title}
              </Text>
            );
          })}
        </Stack>
      </Box>
    );
  }

  function renderActionCard(message: AiAssistantMessage) {
    const action = message.action;
    if (!action) {
      return null;
    }

    const isPending = action.status === 'pending';
    const statusScheme =
      action.status === 'pending'
        ? 'orange'
        : action.status === 'executed'
        ? 'green'
        : action.status === 'failed'
        ? 'red'
        : 'gray';

    return (
      <Box
        mt={3}
        borderWidth={1}
        borderColor={isPending ? 'orange.300' : borderColor}
        bg={isPending ? clarificationBg : softBg}
        rounded="md"
        p={3}
        role="group"
      >
        <HStack justify="space-between" align="flex-start" gap={3}>
          <Box minW={0}>
            <Text fontSize="xs" fontWeight="bold">
              {action.preview?.title ?? 'Assistant action'}
            </Text>
            <Text fontSize="xs" mt={1}>
              {action.preview?.summary ??
                action.result?.message ??
                'Review this action before it runs.'}
            </Text>
          </Box>
          <Badge colorScheme={statusScheme} flexShrink={0}>
            {action.status}
          </Badge>
        </HStack>
        {isPending && action.confirmation_token ? (
          <>
            <Text fontSize="xs" color={mutedText} mt={2}>
              Nothing has changed yet.
            </Text>
            <Stack direction={{ base: 'column', sm: 'row' }} mt={3} spacing={2}>
              <Button
                size="sm"
                colorScheme="brand"
                leftIcon={<Icon as={CheckIcon} boxSize={4} />}
                onClick={() => setActionToConfirm(action)}
                isDisabled={isBusy}
              >
                Review and confirm
              </Button>
              <Button
                size="sm"
                variant="ghost"
                leftIcon={<Icon as={XCircleIcon} boxSize={4} />}
                onClick={() => cancelAction(action)}
                isDisabled={isBusy}
              >
                Discard
              </Button>
            </Stack>
          </>
        ) : null}
      </Box>
    );
  }

  function renderMessageControls(message: AiAssistantMessage) {
    const rating = message.feedback?.rating;

    return (
      <HStack mt={3} spacing={1}>
        <Tooltip label="Copy answer">
          <IconButton
            aria-label="Copy this answer"
            icon={<Icon as={ClipboardDocumentIcon} boxSize={4} />}
            size="xs"
            variant="ghost"
            onClick={() => copyMessage(message)}
          />
        </Tooltip>
        {message.links?.feedback ? (
          <>
            <Tooltip label="This was helpful">
              <IconButton
                aria-label="Mark this answer as helpful"
                aria-pressed={rating === 'helpful'}
                icon={<Icon as={HandThumbUpIcon} boxSize={4} />}
                size="xs"
                variant={rating === 'helpful' ? 'solid' : 'ghost'}
                colorScheme={rating === 'helpful' ? 'green' : undefined}
                onClick={() => rateMessage(message, 'helpful')}
              />
            </Tooltip>
            <Tooltip label="This was not helpful">
              <IconButton
                aria-label="Mark this answer as not helpful"
                aria-pressed={rating === 'not_helpful'}
                icon={<Icon as={HandThumbDownIcon} boxSize={4} />}
                size="xs"
                variant={rating === 'not_helpful' ? 'solid' : 'ghost'}
                colorScheme={rating === 'not_helpful' ? 'red' : undefined}
                onClick={() => rateMessage(message, 'not_helpful')}
              />
            </Tooltip>
          </>
        ) : null}
        {rating ? (
          <VisuallyHidden aria-live="polite">
            Feedback recorded for this answer.
          </VisuallyHidden>
        ) : null}
      </HStack>
    );
  }

  const suggestionChips = suggestions.length ? (
    <Box>
      <Text fontSize="xs" color={mutedText} mb={2}>
        Optional starting points — you can ignore these and type anything.
      </Text>
      <Flex gap={2} wrap="wrap">
        {suggestions.map((suggestion) => (
          <Tag
            key={suggestion}
            as="button"
            type="button"
            size="md"
            variant="subtle"
            colorScheme="brand"
            cursor="pointer"
            textAlign="left"
            whiteSpace="normal"
            onClick={() => useSuggestion(suggestion)}
          >
            {suggestion}
          </Tag>
        ))}
      </Flex>
    </Box>
  ) : null;

  const content = (
    <Box minH="100vh" bg={pageBg} py={{ base: 4, md: 8 }}>
      <Container maxW="7xl">
        <Stack spacing={5}>
          <Box
            as="header"
            bg={panelBg}
            borderWidth={1}
            borderColor={borderColor}
            rounded={{ base: 'xl', md: '2xl' }}
            px={{ base: 5, md: 8 }}
            py={{ base: 5, md: 7 }}
            boxShadow="0 18px 50px rgba(15, 23, 42, 0.07)"
          >
            <Flex
              align={{ base: 'flex-start', md: 'center' }}
              justify="space-between"
              gap={4}
              direction={{ base: 'column', md: 'row' }}
            >
              <HStack align="flex-start" spacing={4}>
                <Box
                  boxSize={{ base: 11, md: 12 }}
                  rounded="xl"
                  bg="brand.600"
                  color="white"
                  display="grid"
                  placeItems="center"
                  flexShrink={0}
                >
                  <Icon as={SparklesIcon} boxSize={6} />
                </Box>
                <Box>
                  <HStack spacing={2} mb={1} wrap="wrap">
                    <Heading as="h1" size={{ base: 'md', md: 'lg' }}>
                      EduManager Assistant
                    </Heading>
                    <Badge
                      colorScheme={actor.is_guest ? 'gray' : 'green'}
                      variant="subtle"
                    >
                      {actor.is_guest ? 'Public' : 'Private'}
                    </Badge>
                  </HStack>
                  <Text color={mutedText} maxW="3xl">
                    {subtitle} You can type anything naturally; suggestions are
                    optional starting points.
                  </Text>
                </Box>
              </HStack>
              <HStack spacing={2} alignSelf={{ base: 'stretch', md: 'auto' }}>
                {!isDesktop && (
                  <Button
                    leftIcon={<Icon as={Bars3Icon} boxSize={4} />}
                    variant="ghost"
                    isDisabled={isBusy}
                    onClick={historyDrawer.onOpen}
                  >
                    History
                  </Button>
                )}
                <Button
                  leftIcon={<Icon as={PlusIcon} boxSize={4} />}
                  variant="outline"
                  colorScheme="brand"
                  flex={{ base: 1, md: 'none' }}
                  isDisabled={isBusy}
                  onClick={startNewConversation}
                >
                  New conversation
                </Button>
              </HStack>
            </Flex>
          </Box>

          {actor.is_guest && (
            <Alert status="info" rounded="xl" alignItems="flex-start">
              <AlertIcon />
              <Box>
                <AlertTitle fontSize="sm">
                  You are using the public assistant
                </AlertTitle>
                <AlertDescription fontSize="sm">
                  It answers general questions about EduManager. Sign in to your
                  school to ask about your own classes, results, fees, or
                  records.
                </AlertDescription>
              </Box>
            </Alert>
          )}

          <SimpleGrid columns={{ base: 1, lg: 4 }} spacing={5}>
            {isDesktop && (
              <Box
                as="aside"
                bg={panelBg}
                borderWidth={1}
                borderColor={borderColor}
                rounded="xl"
                p={4}
                minH="560px"
              >
                <HStack justify="space-between" mb={3}>
                  <Heading as="h2" size="sm">
                    Conversations
                  </Heading>
                  <Badge colorScheme="brand" variant="subtle">
                    {items.length}
                  </Badge>
                </HStack>
                <Divider mb={3} />
                {conversationList}
              </Box>
            )}

            <Box
              gridColumn={{ lg: 'span 3' }}
              bg={panelBg}
              borderWidth={1}
              borderColor={borderColor}
              rounded="xl"
              overflow="hidden"
              minH={{ base: '620px', lg: '560px' }}
              display="flex"
              flexDirection="column"
            >
              <Flex
                align="center"
                justify="space-between"
                gap={3}
                px={{ base: 4, md: 6 }}
                py={4}
                borderBottomWidth={1}
                borderColor={borderColor}
              >
                <HStack spacing={3} minW={0}>
                  <Box
                    boxSize={9}
                    rounded="lg"
                    bg={softBg}
                    color="brand.600"
                    display="grid"
                    placeItems="center"
                    flexShrink={0}
                  >
                    <Icon as={SparklesIcon} boxSize={5} />
                  </Box>
                  <Box minW={0}>
                    <Text fontWeight="bold" noOfLines={1}>
                      {activeConversation?.title ?? 'Start a conversation'}
                    </Text>
                    <Text fontSize="xs" color={mutedText}>
                      {loadingConversation
                        ? 'Loading conversation…'
                        : 'Ask in your own words'}
                    </Text>
                  </Box>
                </HStack>
                {activeConversation && (
                  <Menu>
                    <MenuButton
                      as={IconButton}
                      aria-label="Conversation options"
                      icon={<Icon as={EllipsisVerticalIcon} boxSize={5} />}
                      variant="ghost"
                      isDisabled={isBusy}
                    />
                    <MenuList>
                      <MenuItem
                        icon={<Icon as={PencilSquareIcon} boxSize={4} />}
                        onClick={openRenameDialog}
                      >
                        Rename conversation
                      </MenuItem>
                      <MenuItem
                        icon={<Icon as={ArchiveBoxIcon} boxSize={4} />}
                        onClick={archiveConversation}
                      >
                        Archive conversation
                      </MenuItem>
                      <MenuDivider />
                      <MenuItem
                        icon={<Icon as={TrashIcon} boxSize={4} />}
                        color="red.500"
                        onClick={deleteDialog.onOpen}
                      >
                        Delete permanently
                      </MenuItem>
                    </MenuList>
                  </Menu>
                )}
              </Flex>

              <VStack
                align="stretch"
                flex="1"
                overflowY="auto"
                px={{ base: 4, md: 6 }}
                py={5}
                spacing={4}
                role="log"
                aria-live="polite"
                aria-label="Conversation transcript"
              >
                {loadingConversation ? (
                  <HStack justify="center" py={10} spacing={3}>
                    <Spinner size="sm" color="brand.500" />
                    <Text fontSize="sm" color={mutedText}>
                      Loading conversation…
                    </Text>
                  </HStack>
                ) : !hasTranscript ? (
                  <Box
                    bg={softBg}
                    rounded="xl"
                    p={{ base: 5, md: 8 }}
                    textAlign="center"
                    my="auto"
                  >
                    <Icon
                      as={SparklesIcon}
                      boxSize={8}
                      color="brand.500"
                      mb={3}
                    />
                    <Heading as="h2" size="sm" mb={2}>
                      What can I help you with?
                    </Heading>
                    <Text
                      color={mutedText}
                      fontSize="sm"
                      maxW="md"
                      mx="auto"
                      mb={suggestionChips ? 5 : 0}
                    >
                      Ask a question, describe a problem, or explain what you
                      are trying to do. You do not need to use a predefined
                      command.
                    </Text>
                    {suggestionChips}
                  </Box>
                ) : (
                  <>
                    {messages.map((message) => {
                      const isUser = message.role === 'user';
                      return (
                        <Flex
                          key={message.id}
                          justify={isUser ? 'flex-end' : 'flex-start'}
                        >
                          <Box
                            maxW={{ base: '92%', md: '78%' }}
                            bg={isUser ? 'brand.600' : assistantBubble}
                            color={isUser ? 'white' : undefined}
                            rounded="xl"
                            roundedBottomRight={isUser ? 'sm' : 'xl'}
                            roundedBottomLeft={isUser ? 'xl' : 'sm'}
                            px={4}
                            py={3}
                          >
                            <VisuallyHidden>
                              {isUser ? 'You said:' : 'Assistant replied:'}
                            </VisuallyHidden>
                            {isUser ? (
                              <Text
                                whiteSpace="pre-wrap"
                                fontSize="sm"
                                lineHeight="1.7"
                              >
                                {message.content}
                              </Text>
                            ) : (
                              <Box fontSize="sm" lineHeight="1.7">
                                {renderAssistantContent(message.content)}
                              </Box>
                            )}
                            {!isUser && message.clarification ? (
                              <Box
                                mt={3}
                                borderWidth={1}
                                borderColor="orange.300"
                                bg={clarificationBg}
                                rounded="md"
                                p={3}
                              >
                                <Text fontSize="xs" fontWeight="bold" mb={1}>
                                  More context needed
                                </Text>
                                <Text fontSize="xs">
                                  {message.clarification.question}
                                </Text>
                              </Box>
                            ) : null}
                            {!isUser ? renderToolObservations(message) : null}
                            {!isUser ? renderSources(message) : null}
                            {!isUser ? renderActionCard(message) : null}
                            {!isUser ? renderMessageControls(message) : null}
                          </Box>
                        </Flex>
                      );
                    })}
                    {pendingMessage ? (
                      <>
                        <Flex justify="flex-end">
                          <Box
                            maxW={{ base: '92%', md: '78%' }}
                            bg="brand.600"
                            color="white"
                            rounded="xl"
                            roundedBottomRight="sm"
                            px={4}
                            py={3}
                            opacity={0.75}
                          >
                            <Text
                              whiteSpace="pre-wrap"
                              fontSize="sm"
                              lineHeight="1.7"
                            >
                              {pendingMessage}
                            </Text>
                          </Box>
                        </Flex>
                        <Flex justify="flex-start">
                          <HStack
                            bg={assistantBubble}
                            rounded="xl"
                            roundedBottomLeft="sm"
                            px={4}
                            py={3}
                            spacing={3}
                          >
                            <Spinner size="sm" color="brand.500" />
                            <Text fontSize="sm" color={mutedText}>
                              {WORKING_STAGES[workingStage]}
                            </Text>
                          </HStack>
                        </Flex>
                      </>
                    ) : null}
                  </>
                )}
                <div ref={transcriptEndRef} />
              </VStack>

              <Box
                borderTopWidth={1}
                borderColor={borderColor}
                p={{ base: 4, md: 5 }}
              >
                {error && (
                  <Alert
                    status="error"
                    rounded="md"
                    mb={3}
                    alignItems="flex-start"
                  >
                    <AlertIcon />
                    <Box flex="1">
                      <AlertDescription fontSize="sm">{error}</AlertDescription>
                      {failedMessage && (
                        <Button
                          mt={2}
                          size="sm"
                          colorScheme="red"
                          variant="outline"
                          leftIcon={<Icon as={ArrowPathIcon} boxSize={4} />}
                          onClick={() => submitMessage(failedMessage)}
                          isDisabled={isBusy}
                        >
                          Retry that message
                        </Button>
                      )}
                    </Box>
                    <IconButton
                      aria-label="Dismiss error"
                      icon={<Icon as={XCircleIcon} boxSize={4} />}
                      variant="ghost"
                      size="sm"
                      onClick={() => setError(null)}
                    />
                  </Alert>
                )}
                {hasTranscript && suggestionChips ? (
                  <Box mb={3}>{suggestionChips}</Box>
                ) : null}
                <form onSubmit={sendMessage}>
                  <Stack direction={{ base: 'column', sm: 'row' }} spacing={3}>
                    <Box flex="1">
                      <VisuallyHidden as="label" htmlFor="assistant-composer">
                        Message the EduManager Assistant
                      </VisuallyHidden>
                      <Textarea
                        id="assistant-composer"
                        ref={composerRef}
                        value={draft}
                        onChange={(event) => setDraft(event.target.value)}
                        onKeyDown={onComposerKeyDown}
                        placeholder="Ask anything about EduManager…"
                        resize="none"
                        rows={3}
                        maxLength={MAX_MESSAGE_LENGTH}
                        isDisabled={isBusy}
                      />
                    </Box>
                    <Button
                      type="submit"
                      colorScheme="brand"
                      leftIcon={<Icon as={PaperAirplaneIcon} boxSize={4} />}
                      isLoading={processing}
                      loadingText="Sending"
                      isDisabled={isBusy || !draft.trim()}
                      alignSelf={{ base: 'stretch', sm: 'flex-end' }}
                      minW={{ sm: 28 }}
                    >
                      Send
                    </Button>
                  </Stack>
                  <Flex
                    mt={2}
                    gap={2}
                    justify="space-between"
                    direction={{ base: 'column', sm: 'row' }}
                  >
                    <Text fontSize="xs" color={mutedText}>
                      Do not share passwords or other secrets in a message.
                      Press Ctrl or Cmd + Enter to send.
                    </Text>
                    <Text fontSize="xs" color={mutedText} flexShrink={0}>
                      {draft.length}/{MAX_MESSAGE_LENGTH}
                    </Text>
                  </Flex>
                </form>
              </Box>
            </Box>
          </SimpleGrid>
        </Stack>
      </Container>

      <Drawer
        isOpen={historyDrawer.isOpen}
        placement="left"
        onClose={historyDrawer.onClose}
      >
        <DrawerOverlay />
        <DrawerContent>
          <DrawerCloseButton />
          <DrawerHeader>Conversations</DrawerHeader>
          <DrawerBody pb={6}>{conversationList}</DrawerBody>
        </DrawerContent>
      </Drawer>

      <Modal
        isOpen={renameDialog.isOpen}
        onClose={renameDialog.onClose}
        isCentered
      >
        <ModalOverlay />
        <ModalContent mx={4}>
          <ModalHeader>Rename conversation</ModalHeader>
          <ModalCloseButton />
          <ModalBody>
            <VisuallyHidden as="label" htmlFor="assistant-rename">
              Conversation title
            </VisuallyHidden>
            <Input
              id="assistant-rename"
              value={renameTitle}
              onChange={(event) => setRenameTitle(event.target.value)}
              maxLength={100}
              autoFocus
            />
          </ModalBody>
          <ModalFooter>
            <Button variant="ghost" mr={3} onClick={renameDialog.onClose}>
              Cancel
            </Button>
            <Button
              colorScheme="brand"
              onClick={renameConversation}
              isLoading={processing}
              isDisabled={isBusy || !renameTitle.trim()}
            >
              Save title
            </Button>
          </ModalFooter>
        </ModalContent>
      </Modal>

      <Modal
        isOpen={deleteDialog.isOpen}
        onClose={deleteDialog.onClose}
        initialFocusRef={deleteButtonRef}
        isCentered
      >
        <ModalOverlay />
        <ModalContent mx={4}>
          <ModalHeader>Delete this conversation?</ModalHeader>
          <ModalCloseButton />
          <ModalBody>
            <Text fontSize="sm">
              “{activeConversation?.title}” and every message in it will be
              removed permanently. This cannot be undone. Archive it instead if
              you only want it out of your list.
            </Text>
          </ModalBody>
          <ModalFooter>
            <Button variant="ghost" mr={3} onClick={deleteDialog.onClose}>
              Keep conversation
            </Button>
            <Button
              ref={deleteButtonRef}
              colorScheme="red"
              onClick={deleteConversation}
              isLoading={processing}
              isDisabled={isBusy}
            >
              Delete conversation
            </Button>
          </ModalFooter>
        </ModalContent>
      </Modal>

      <Modal
        isOpen={Boolean(actionToConfirm)}
        onClose={() => setActionToConfirm(null)}
        initialFocusRef={confirmButtonRef}
        closeOnOverlayClick={false}
        isCentered
      >
        <ModalOverlay />
        <ModalContent mx={4}>
          <ModalHeader>
            {actionToConfirm?.preview?.title ?? 'Confirm this action'}
          </ModalHeader>
          <ModalCloseButton />
          <ModalBody>
            <Text fontSize="sm" mb={3}>
              {actionToConfirm?.preview?.summary}
            </Text>
            {actionToConfirm?.preview?.changes ? (
              <Stack
                spacing={2}
                borderWidth={1}
                borderColor={borderColor}
                rounded="md"
                p={3}
              >
                {Object.entries(actionToConfirm.preview.changes)
                  .filter(([, value]) => value !== null && value !== '')
                  .map(([key, value]) => (
                    <HStack
                      key={key}
                      justify="space-between"
                      align="flex-start"
                      gap={4}
                    >
                      <Text fontSize="xs" color={mutedText} flexShrink={0}>
                        {key.replace(/_/g, ' ')}
                      </Text>
                      <Text fontSize="xs" textAlign="right">
                        {String(value)}
                      </Text>
                    </HStack>
                  ))}
              </Stack>
            ) : null}
            <Text fontSize="xs" color={mutedText} mt={3}>
              This runs against your live school records using your own
              permissions. Nothing has changed yet.
            </Text>
          </ModalBody>
          <ModalFooter>
            <Button
              variant="ghost"
              mr={3}
              onClick={() => setActionToConfirm(null)}
            >
              Go back
            </Button>
            <Button
              ref={confirmButtonRef}
              colorScheme="brand"
              onClick={confirmAction}
              isLoading={processing}
              isDisabled={isBusy}
            >
              Run this action
            </Button>
          </ModalFooter>
        </ModalContent>
      </Modal>
    </Box>
  );

  return isPublic ? content : <DashboardLayout>{content}</DashboardLayout>;
}
