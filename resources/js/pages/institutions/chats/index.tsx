import React, { useEffect, useMemo, useRef, useState } from 'react';
import DashboardLayout from '@/layout/dashboard-layout';
import {
  Avatar,
  Badge,
  Box,
  Button,
  Flex,
  Grid,
  HStack,
  Icon,
  Input,
  Select,
  SimpleGrid,
  Stack,
  Text,
  Textarea,
  useColorModeValue,
  VStack,
} from '@chakra-ui/react';
import { PageTitle } from '@/components/page-header';
import useInstitutionRoute from '@/hooks/use-institution-route';
import useSharedProps from '@/hooks/use-shared-props';
import useWebForm from '@/hooks/use-web-form';
import useMyToast from '@/hooks/use-my-toast';
import {
  ChatComposerOptions,
  ChatThreadDetail,
  ChatThreadSummary,
} from '@/types/models';
import {
  ChatBubbleBottomCenterTextIcon,
  BuildingLibraryIcon,
  MagnifyingGlassIcon,
  PaperAirplaneIcon,
  UserCircleIcon,
  UserGroupIcon,
} from '@heroicons/react/24/outline';
import { ChatThreadType, InstitutionUserType } from '@/types/types';
import { Inertia } from '@inertiajs/inertia';
import { InertiaLink } from '@inertiajs/inertia-react';

interface Props {
  threads: ChatThreadSummary[];
  activeThread?: ChatThreadDetail | null;
  chatComposerOptions: ChatComposerOptions;
}

type NewChatType =
  | ChatThreadType.Institution
  | ChatThreadType.Role
  | ChatThreadType.DirectUser;

export default function ChatIndex({
  threads,
  activeThread,
  chatComposerOptions,
}: Props) {
  const { currentInstitutionUser } = useSharedProps();
  const { instRoute } = useInstitutionRoute();
  const { handleResponseToast, toastError } = useMyToast();
  const [search, setSearch] = useState('');
  const [newChatType, setNewChatType] = useState<NewChatType>(
    ChatThreadType.Institution
  );
  const [isComposerOpen, setIsComposerOpen] = useState(
    !activeThread && threads.length === 0
  );
  const messagesEndRef = useRef<HTMLDivElement | null>(null);

  const threadForm = useWebForm({
    type: ChatThreadType.Institution,
    target_role: '',
    target_user_id: '',
    message: '',
  });
  const replyForm = useWebForm({
    message: '',
  });

  useEffect(() => {
    messagesEndRef.current?.scrollIntoView({ behavior: 'smooth' });
  }, [activeThread?.id, activeThread?.messages.length]);

  useEffect(() => {
    if (activeThread) {
      setIsComposerOpen(false);
      return;
    }

    if (threads.length === 0) {
      setIsComposerOpen(true);
    }
  }, [activeThread, threads.length]);

  const filteredThreads = useMemo(() => {
    const term = search.trim().toLowerCase();
    if (!term) {
      return threads;
    }

    return threads.filter((thread) =>
      [thread.title, thread.subtitle, thread.last_message_preview]
        .filter(Boolean)
        .join(' ')
        .toLowerCase()
        .includes(term)
    );
  }, [search, threads]);

  async function startConversation() {
    if (newChatType === ChatThreadType.Role && !threadForm.data.target_role) {
      toastError('Please select a staff role.');
      return;
    }

    if (
      newChatType === ChatThreadType.DirectUser &&
      !threadForm.data.target_user_id
    ) {
      toastError('Please select a staff member.');
      return;
    }

    const res = await threadForm.submit((data, web) =>
      web.post(instRoute('chats.store'), {
        ...data,
        type: newChatType,
      })
    );

    if (!handleResponseToast(res)) {
      return;
    }

    threadForm.setData({
      type: newChatType,
      target_role: '',
      target_user_id: '',
      message: '',
    });
    setIsComposerOpen(false);
    Inertia.visit(instRoute('chats.show', [res.data.thread_id]));
  }

  async function sendReply() {
    if (!activeThread) {
      return;
    }

    const res = await replyForm.submit((data, web) =>
      web.post(instRoute('chats.messages.store', [activeThread.id]), data)
    );

    if (!handleResponseToast(res)) {
      return;
    }

    replyForm.reset();
    Inertia.visit(instRoute('chats.show', [activeThread.id]));
  }

  const surfaceBg = useColorModeValue('white', 'gray.800');
  const panelBg = useColorModeValue('gray.50', 'gray.900');
  const softBorder = useColorModeValue('gray.200', 'gray.700');
  const subtleText = useColorModeValue('gray.600', 'gray.400');
  const showComposer = isComposerOpen;
  const canOpenThreadProfile = [
    InstitutionUserType.Admin,
    InstitutionUserType.Accountant,
    InstitutionUserType.Teacher,
  ].includes(currentInstitutionUser.role);

  return (
    <DashboardLayout>
      <Stack spacing={6}>
        <Box
          rounded="2xl"
          px={{ base: 5, md: 7 }}
          py={{ base: 5, md: 6 }}
          bg={useColorModeValue(
            'linear-gradient(135deg, rgba(244, 114, 182, 0.10), rgba(56, 189, 248, 0.10), rgba(34, 197, 94, 0.08))',
            'linear-gradient(135deg, rgba(131, 24, 67, 0.35), rgba(12, 74, 110, 0.35), rgba(20, 83, 45, 0.25))'
          )}
          borderWidth={1}
          borderColor={softBorder}
          boxShadow="0 24px 60px rgba(15, 23, 42, 0.08)"
        >
          <SimpleGrid columns={{ base: 1, lg: 2 }} spacing={4}>
            <Box>
              <Text
                textTransform="uppercase"
                letterSpacing="0.16em"
                fontWeight="bold"
                fontSize="xs"
                color={useColorModeValue('brand.600', 'brand.200')}
              >
                Messaging
              </Text>
              <PageTitle mb={2}>Institution Chat</PageTitle>
              <Text color={subtleText} maxW="2xl">
                Start direct conversations with staff, role-based help desks, or
                the institution admin inbox. Each thread is tied to your{' '}
                institution and keeps replies organized in one place.
              </Text>
            </Box>
            <HStack
              justify={{ base: 'start', lg: 'end' }}
              align="stretch"
              spacing={3}
              flexWrap="wrap"
            >
              <StatPill label="Threads" value={threads.length.toString()} />
              <StatPill
                label="Unread"
                value={threads
                  .filter((thread) => thread.has_unread)
                  .length.toString()}
              />
            </HStack>
          </SimpleGrid>
        </Box>

        <Grid
          templateColumns={{ base: '1fr', xl: '360px minmax(0, 1fr)' }}
          gap={6}
        >
          <Box
            rounded="2xl"
            bg={surfaceBg}
            borderWidth={1}
            borderColor={softBorder}
            boxShadow="0 20px 45px rgba(15, 23, 42, 0.06)"
            overflow="hidden"
          >
            <Box p={5} borderBottomWidth={1} borderColor={softBorder}>
              <VStack align="stretch" spacing={4}>
                <Box position="relative">
                  <Input
                    pl={10}
                    placeholder="Search conversations"
                    value={search}
                    onChange={(e) => setSearch(e.currentTarget.value)}
                    bg={panelBg}
                    borderColor="transparent"
                    _focusVisible={{ borderColor: 'brand.400', bg: surfaceBg }}
                  />
                  <Icon
                    as={MagnifyingGlassIcon}
                    boxSize={4}
                    position="absolute"
                    left={3}
                    top="50%"
                    transform="translateY(-50%)"
                    color={subtleText}
                  />
                </Box>

                <Box
                  rounded="xl"
                  borderWidth={1}
                  borderColor={softBorder}
                  bg={panelBg}
                  p={4}
                >
                  <Button
                    colorScheme="brand"
                    leftIcon={<Icon as={PaperAirplaneIcon} />}
                    borderRadius="full"
                    onClick={() => setIsComposerOpen(true)}
                    w={{ base: '100%', md: 'auto' }}
                  >
                    Start New Chat
                  </Button>
                </Box>
              </VStack>
            </Box>

            <VStack
              align="stretch"
              spacing={0}
              maxH={{ base: '360px', xl: 'calc(100vh - 290px)' }}
              overflowY="auto"
            >
              {filteredThreads.length > 0 ? (
                filteredThreads.map((thread) => (
                  <ThreadListItem
                    key={thread.id}
                    thread={thread}
                    isActive={activeThread?.id === thread.id}
                    href={instRoute('chats.show', [thread.id])}
                  />
                ))
              ) : (
                <EmptyPanel
                  icon={ChatBubbleBottomCenterTextIcon}
                  title="No conversations yet"
                  text="Conversations you start or receive will appear here."
                />
              )}
            </VStack>
          </Box>

          <Box
            rounded="2xl"
            bg={surfaceBg}
            borderWidth={1}
            borderColor={softBorder}
            boxShadow="0 20px 45px rgba(15, 23, 42, 0.06)"
            overflow="hidden"
            minH={{ base: '520px', xl: '640px' }}
          >
            {showComposer ? (
              <StartConversationPanel
                newChatType={newChatType}
                chatComposerOptions={chatComposerOptions}
                threadForm={threadForm}
                surfaceBg={surfaceBg}
                panelBg={panelBg}
                subtleText={subtleText}
                onChangeType={setNewChatType}
                onStartConversation={startConversation}
              />
            ) : activeThread ? (
              <Flex direction="column" h="100%">
                <Flex
                  px={{ base: 5, md: 6 }}
                  py={5}
                  justify="space-between"
                  align={{ base: 'start', md: 'center' }}
                  direction={{ base: 'column', md: 'row' }}
                  borderBottomWidth={1}
                  borderColor={softBorder}
                  bg={panelBg}
                  gap={4}
                >
                  <HStack spacing={4} align="center">
                    {activeThread.profile_url && canOpenThreadProfile ? (
                      <Box
                        as={InertiaLink}
                        href={activeThread.profile_url}
                        _hover={{ textDecoration: 'none' }}
                      >
                        <Avatar
                          src={activeThread.photo_url ?? undefined}
                          name={activeThread.title}
                          bg="brand.500"
                          cursor="pointer"
                        />
                      </Box>
                    ) : (
                      <Avatar
                        src={activeThread.photo_url ?? undefined}
                        name={activeThread.title}
                        bg="brand.500"
                      />
                    )}
                    <Box>
                      <PageTitle mb={0}>{activeThread.title}</PageTitle>
                      <Text color={subtleText} fontSize="sm">
                        {activeThread.subtitle}
                      </Text>
                    </Box>
                  </HStack>
                  <Badge
                    colorScheme="brand"
                    px={3}
                    py={1}
                    rounded="full"
                    textTransform="capitalize"
                  >
                    {activeThread.type.replace('-', ' ')}
                  </Badge>
                </Flex>

                <VStack
                  align="stretch"
                  spacing={4}
                  px={{ base: 4, md: 6 }}
                  py={5}
                  bg={useColorModeValue(
                    'linear-gradient(180deg, rgba(248, 250, 252, 0.7), rgba(241, 245, 249, 0.9))',
                    'linear-gradient(180deg, rgba(17, 24, 39, 0.92), rgba(10, 15, 25, 0.98))'
                  )}
                  flex="1"
                  overflowY="auto"
                >
                  {activeThread.messages.map((message) => (
                    <MessageBubble key={message.id} message={message} />
                  ))}
                  <Box ref={messagesEndRef} />
                </VStack>

                <Box
                  px={{ base: 4, md: 6 }}
                  py={5}
                  borderTopWidth={1}
                  borderColor={softBorder}
                  bg={surfaceBg}
                >
                  <Textarea
                    value={replyForm.data.message}
                    onChange={(e) =>
                      replyForm.setValue('message', e.currentTarget.value)
                    }
                    placeholder="Type your reply"
                    minH="110px"
                    resize="vertical"
                    bg={panelBg}
                    borderColor="transparent"
                    _focusVisible={{ borderColor: 'brand.400', bg: surfaceBg }}
                  />
                  {replyForm.errors.message && (
                    <Text color="red.500" fontSize="sm" mt={2}>
                      {replyForm.errors.message}
                    </Text>
                  )}
                  <Flex
                    mt={3}
                    justify="space-between"
                    align={{ base: 'stretch', md: 'center' }}
                    direction={{ base: 'column', md: 'row' }}
                    gap={4}
                  >
                    <Text fontSize="sm" color={subtleText}>
                      Replies stay inside this conversation for everyone allowed
                      to attend it.
                    </Text>
                    <Button
                      colorScheme="brand"
                      borderRadius="full"
                      leftIcon={<Icon as={PaperAirplaneIcon} />}
                      isLoading={replyForm.processing}
                      onClick={sendReply}
                    >
                      Send Reply
                    </Button>
                  </Flex>
                </Box>
              </Flex>
            ) : (
              <EmptyPanel
                icon={ChatBubbleBottomCenterTextIcon}
                title="Select a conversation"
                text="Choose a thread from the inbox or click Start New Chat to begin chatting."
                fullHeight
              />
            )}
          </Box>
        </Grid>
      </Stack>
    </DashboardLayout>
  );
}

function StartConversationPanel({
  newChatType,
  chatComposerOptions,
  threadForm,
  surfaceBg,
  panelBg,
  subtleText,
  onChangeType,
  onStartConversation,
}: {
  newChatType: NewChatType;
  chatComposerOptions: ChatComposerOptions;
  threadForm: ReturnType<typeof useWebForm>;
  surfaceBg: string;
  panelBg: string;
  subtleText: string;
  onChangeType: (type: NewChatType) => void;
  onStartConversation: () => void;
}) {
  return (
    <Flex direction="column" h="100%">
      <Box
        px={{ base: 5, md: 6 }}
        py={5}
        borderBottomWidth={1}
        borderColor={useColorModeValue('gray.200', 'gray.700')}
        bg={panelBg}
      >
        <PageTitle mb={1}>Start a new conversation</PageTitle>
        <Text color={subtleText} fontSize="sm">
          Choose who you want to reach, then send the first message from the
          main workspace.
        </Text>
      </Box>

      <Box px={{ base: 4, md: 6 }} py={5} flex="1" overflowY="auto">
        <HStack spacing={2} flexWrap="wrap" mb={4}>
          <QuickModeButton
            active={newChatType === ChatThreadType.Institution}
            icon={BuildingLibraryIcon}
            label="Institution"
            onClick={() => onChangeType(ChatThreadType.Institution)}
          />
          <QuickModeButton
            active={newChatType === ChatThreadType.Role}
            icon={UserGroupIcon}
            label="Staff Role"
            onClick={() => onChangeType(ChatThreadType.Role)}
          />
          {chatComposerOptions.canDirectMessageStaff && (
            <QuickModeButton
              active={newChatType === ChatThreadType.DirectUser}
              icon={UserCircleIcon}
              label="Staff Member"
              onClick={() => onChangeType(ChatThreadType.DirectUser)}
            />
          )}
        </HStack>

        <Text fontSize="sm" color={subtleText} mb={3}>
          {newChatType === ChatThreadType.Institution
            ? chatComposerOptions.institutionTarget.description
            : newChatType === ChatThreadType.Role
            ? 'Choose a role. Any matching staff member or admin can respond.'
            : 'Pick a specific staff member for a direct one-to-one conversation.'}
        </Text>

        {newChatType === ChatThreadType.Role && (
          <Select
            mb={3}
            value={threadForm.data.target_role}
            onChange={(e) =>
              threadForm.setValue('target_role', e.currentTarget.value)
            }
            placeholder="Select staff role"
            bg={surfaceBg}
          >
            {chatComposerOptions.roleTargets.map((target) => (
              <option key={target.value} value={target.value}>
                {target.label}
              </option>
            ))}
          </Select>
        )}

        {newChatType === ChatThreadType.DirectUser && (
          <Select
            mb={3}
            value={threadForm.data.target_user_id}
            onChange={(e) =>
              threadForm.setValue('target_user_id', e.currentTarget.value)
            }
            placeholder="Select staff member"
            bg={surfaceBg}
          >
            {chatComposerOptions.staffTargets.map((target) => (
              <option key={target.value} value={target.value}>
                {target.label} • {target.description}
              </option>
            ))}
          </Select>
        )}

        <Textarea
          value={threadForm.data.message}
          onChange={(e) =>
            threadForm.setValue('message', e.currentTarget.value)
          }
          placeholder="Write your first message"
          bg={surfaceBg}
          minH="180px"
          resize="vertical"
        />
        {threadForm.errors.message && (
          <Text color="red.500" fontSize="sm" mt={2}>
            {threadForm.errors.message}
          </Text>
        )}

        <Button
          mt={4}
          colorScheme="brand"
          leftIcon={<Icon as={PaperAirplaneIcon} />}
          borderRadius="full"
          isLoading={threadForm.processing}
          onClick={onStartConversation}
        >
          Start Chat
        </Button>
      </Box>
    </Flex>
  );
}

function StatPill({ label, value }: { label: string; value: string }) {
  return (
    <Box
      rounded="xl"
      px={4}
      py={3}
      borderWidth={1}
      borderColor={useColorModeValue('blackAlpha.100', 'whiteAlpha.200')}
      bg={useColorModeValue('white', 'whiteAlpha.100')}
      minW={{ base: '120px', md: '132px' }}
      boxShadow="sm"
    >
      <Text
        fontSize="xs"
        textTransform="uppercase"
        letterSpacing="0.08em"
        color={useColorModeValue('gray.500', 'gray.400')}
      >
        {label}
      </Text>
      <Text
        mt={1}
        fontSize="2xl"
        lineHeight="1"
        fontWeight="semibold"
        color={useColorModeValue('gray.900', 'white')}
      >
        {value}
      </Text>
    </Box>
  );
}

function QuickModeButton({
  active,
  icon,
  label,
  onClick,
}: {
  active: boolean;
  icon: any;
  label: string;
  onClick: () => void;
}) {
  return (
    <Button
      size="sm"
      borderRadius="full"
      variant={active ? 'solid' : 'outline'}
      colorScheme="brand"
      leftIcon={<Icon as={icon} />}
      onClick={onClick}
    >
      {label}
    </Button>
  );
}

function ThreadListItem({
  thread,
  isActive,
  href,
}: {
  thread: ChatThreadSummary;
  isActive: boolean;
  href: string;
}) {
  const activeBg = useColorModeValue('brand.50', 'gray.700');
  const hoverBg = useColorModeValue('gray.50', 'gray.750');
  const subtleText = useColorModeValue('gray.600', 'gray.400');

  return (
    <Box
      as={InertiaLink}
      href={href}
      px={5}
      py={4}
      display="block"
      bg={isActive ? activeBg : 'transparent'}
      borderLeftWidth={isActive ? '4px' : '4px'}
      borderLeftColor={isActive ? 'brand.500' : 'transparent'}
      _hover={{ textDecoration: 'none', bg: hoverBg }}
      transition="0.2s ease"
    >
      <HStack align="start" spacing={3}>
        <Avatar
          size="sm"
          src={thread.photo_url ?? undefined}
          name={thread.title}
          bg="brand.500"
        />
        <Box flex="1" minW={0}>
          <Flex justify="space-between" gap={3} align="center">
            <Text fontWeight="semibold" noOfLines={1}>
              {thread.title}
            </Text>
            {thread.has_unread && (
              <Badge colorScheme="brand" rounded="full">
                New
              </Badge>
            )}
          </Flex>
          <Text fontSize="sm" color={subtleText} noOfLines={1}>
            {thread.subtitle}
          </Text>
          <Text mt={1} fontSize="sm" color={subtleText} noOfLines={2}>
            {thread.last_message_preview ?? 'No messages yet'}
          </Text>
        </Box>
      </HStack>
    </Box>
  );
}

function MessageBubble({
  message,
}: {
  message: ChatThreadDetail['messages'][0];
}) {
  const bubbleBg = useColorModeValue(
    message.is_mine ? 'brand.500' : 'white',
    message.is_mine ? 'brand.500' : 'gray.800'
  );
  const bubbleColor = message.is_mine
    ? 'white'
    : useColorModeValue('gray.800', 'gray.100');
  const subtleText = useColorModeValue(
    message.is_mine ? 'whiteAlpha.800' : 'gray.500',
    message.is_mine ? 'whiteAlpha.800' : 'gray.400'
  );

  return (
    <Flex justify={message.is_mine ? 'flex-end' : 'flex-start'}>
      <Box maxW={{ base: '92%', md: '72%' }}>
        {!message.is_mine && (
          <Text fontSize="xs" color={subtleText} mb={1} px={1}>
            {message.sender.full_name}
            {message.sender.role ? ` • ${message.sender.role}` : ''}
          </Text>
        )}
        <Box
          px={4}
          py={3}
          rounded="2xl"
          bg={bubbleBg}
          color={bubbleColor}
          boxShadow="0 10px 24px rgba(15, 23, 42, 0.08)"
        >
          <Text whiteSpace="pre-wrap">{message.body}</Text>
        </Box>
        <Text
          fontSize="xs"
          color={subtleText}
          mt={1}
          textAlign={message.is_mine ? 'right' : 'left'}
          px={1}
        >
          {new Date(message.created_at).toLocaleString()}
        </Text>
      </Box>
    </Flex>
  );
}

function EmptyPanel({
  icon,
  title,
  text,
  fullHeight,
}: {
  icon: any;
  title: string;
  text: string;
  fullHeight?: boolean;
}) {
  return (
    <Flex
      direction="column"
      align="center"
      justify="center"
      textAlign="center"
      minH={fullHeight ? { base: '520px', xl: '640px' } : '240px'}
      px={6}
      py={10}
      color={useColorModeValue('gray.500', 'gray.400')}
    >
      <Box
        rounded="full"
        bg={useColorModeValue('gray.100', 'gray.800')}
        p={4}
        mb={4}
      >
        <Icon as={icon} boxSize={8} />
      </Box>
      <Text fontWeight="semibold" fontSize="lg" color="inherit">
        {title}
      </Text>
      <Text mt={2} maxW="md">
        {text}
      </Text>
    </Flex>
  );
}
