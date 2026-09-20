import React from 'react';
import AssistantPage from '@/components/ai-assistant/assistant-page';
import {
  AiAssistantConversation,
  AiAssistantConversationSummary,
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
  endpoints: { create: string };
}

export default function InstitutionAssistantPage(props: Props) {
  return <AssistantPage {...props} />;
}
