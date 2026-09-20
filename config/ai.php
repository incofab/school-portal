<?php

return [
  /*
    |--------------------------------------------------------------------------
    | Default AI Provider Names
    |--------------------------------------------------------------------------
    |
    | Here you may specify which of the AI providers below should be the
    | default for AI operations when no explicit provider is provided
    | for the operation. This should be any provider defined below.
    |
    */

  'default' => 'openai',
  'default_for_images' => 'gemini',
  'default_for_audio' => 'openai',
  'default_for_transcription' => 'openai',
  'default_for_embeddings' => 'openai',
  'default_for_reranking' => 'cohere',

  'assistant' => [
    'enabled' => env('AI_ASSISTANT_ENABLED', true),
    'guest_access' => env('AI_ASSISTANT_GUEST_ACCESS', true),
    'provider' => env('AI_ASSISTANT_PROVIDER', 'openai'),
    'model' => env('AI_ASSISTANT_MODEL', env('OPENAI_MODEL', 'gpt-5.4-nano')),
    'fallback_provider' => env('AI_ASSISTANT_FALLBACK_PROVIDER'),
    'fallback_model' => env('AI_ASSISTANT_FALLBACK_MODEL'),
    'retry_attempts' => (int) env('AI_ASSISTANT_RETRY_ATTEMPTS', 1),
    'retry_delay_ms' => (int) env('AI_ASSISTANT_RETRY_DELAY_MS', 250),
    'timeout' => (int) env('AI_ASSISTANT_TIMEOUT', 60),
    'max_turn_seconds' => (int) env('AI_ASSISTANT_MAX_TURN_SECONDS', 75),
    'max_input_chars' => (int) env('AI_ASSISTANT_MAX_INPUT_CHARS', 4000),
    'max_context_messages' => (int) env(
      'AI_ASSISTANT_MAX_CONTEXT_MESSAGES',
      12
    ),
    'max_context_chars' => (int) env('AI_ASSISTANT_MAX_CONTEXT_CHARS', 12000),
    'summary_after_messages' => (int) env(
      'AI_ASSISTANT_SUMMARY_AFTER_MESSAGES',
      8
    ),
    'summary_max_chars' => (int) env('AI_ASSISTANT_SUMMARY_MAX_CHARS', 2000),
    'summary_message_sample' => (int) env(
      'AI_ASSISTANT_SUMMARY_MESSAGE_SAMPLE',
      20
    ),
    'max_conversations' => (int) env('AI_ASSISTANT_MAX_CONVERSATIONS', 50),
    'conversation_retention_days' => (int) env(
      'AI_ASSISTANT_CONVERSATION_RETENTION_DAYS',
      365
    ),
    'max_model_calls' => (int) env('AI_ASSISTANT_MAX_MODEL_CALLS', 3),
    'max_tool_calls' => (int) env('AI_ASSISTANT_MAX_TOOL_CALLS', 5),
    'max_retrieval_calls' => (int) env('AI_ASSISTANT_MAX_RETRIEVAL_CALLS', 3),
    'max_tokens' => (int) env('AI_ASSISTANT_MAX_TOKENS', 1600),
    'rate_limit' => (int) env('AI_ASSISTANT_RATE_LIMIT', 20),
    'rate_limit_window' => (int) env('AI_ASSISTANT_RATE_LIMIT_WINDOW', 1),
    'user_budget' => (int) env('AI_ASSISTANT_USER_BUDGET', 0),
    'institution_budget' => (int) env('AI_ASSISTANT_INSTITUTION_BUDGET', 0),
    'guest_budget' => (int) env('AI_ASSISTANT_GUEST_BUDGET', 0),
    'usage_window_minutes' => (int) env(
      'AI_ASSISTANT_USAGE_WINDOW_MINUTES',
      1440
    ),
    'public_cache_minutes' => (int) env('AI_ASSISTANT_PUBLIC_CACHE_MINUTES', 0),
    'guest_cookie' => env('AI_ASSISTANT_GUEST_COOKIE', 'edumanager_ai_guest'),
    'guest_cookie_minutes' => (int) env(
      'AI_ASSISTANT_GUEST_COOKIE_MINUTES',
      60 * 24 * 30
    )
  ],

  'telemetry' => [
    'enabled' => env('AI_TELEMETRY_ENABLED', true),
    'retention_days' => (int) env('AI_TELEMETRY_RETENTION_DAYS', 180)
  ],

  'knowledge' => [
    'enabled' => env('AI_KNOWLEDGE_ENABLED', true),
    'chunk_size' => (int) env('AI_KNOWLEDGE_CHUNK_SIZE', 1200),
    'chunk_overlap' => (int) env('AI_KNOWLEDGE_CHUNK_OVERLAP', 120),
    'max_candidates' => (int) env('AI_KNOWLEDGE_MAX_CANDIDATES', 200),
    'max_results' => (int) env('AI_KNOWLEDGE_MAX_RESULTS', 5)
  ],

  /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Below you may configure caching strategies for AI related operations
    | such as embedding generation. You are free to adjust these values
    | based on your application's available caching stores and needs.
    |
    */

  'caching' => [
    'embeddings' => [
      'cache' => false,
      'store' => env('CACHE_STORE', 'database'),
      'individually' => true
    ]
  ],

  /*
    |--------------------------------------------------------------------------
    | AI Providers
    |--------------------------------------------------------------------------
    |
    | Below are each of your AI providers defined for this application. Each
    | represents an AI provider and API key combination which can be used
    | to perform tasks like text, image, and audio creation via agents.
    |
    */

  'providers' => [
    'anthropic' => [
      'driver' => 'anthropic',
      'key' => env('ANTHROPIC_API_KEY'),
      'url' => env('ANTHROPIC_URL', 'https://api.anthropic.com/v1')
    ],

    'azure' => [
      'driver' => 'azure',
      'key' => env('AZURE_OPENAI_API_KEY'),
      'url' => env('AZURE_OPENAI_URL'),
      'api_version' => env('AZURE_OPENAI_API_VERSION', '2025-04-01-preview'),
      'deployment' => env('AZURE_OPENAI_DEPLOYMENT', 'gpt-4o'),
      'embedding_deployment' => env(
        'AZURE_OPENAI_EMBEDDING_DEPLOYMENT',
        'text-embedding-3-small'
      ),
      'image_deployment' => env('AZURE_OPENAI_IMAGE_DEPLOYMENT', 'gpt-image-1'),
      'store' => env('AZURE_OPENAI_STORE', true)
    ],

    'bedrock' => [
      'driver' => 'bedrock',
      'region' => env('AWS_BEDROCK_REGION', 'us-east-1'),
      'key' => env('AWS_BEARER_TOKEN_BEDROCK'),
      'access_key_id' => env('AWS_ACCESS_KEY_ID'),
      'secret_access_key' => env('AWS_SECRET_ACCESS_KEY'),
      'session_token' => env('AWS_SESSION_TOKEN'),
      'use_default_credential_provider' => env(
        'AWS_USE_DEFAULT_CREDENTIALS',
        true
      ),
      'assume_role' => [
        'arn' => env('AWS_BEDROCK_ASSUME_ROLE_ARN'),
        'session_name' => env('AWS_BEDROCK_ASSUME_ROLE_SESSION_NAME'),
        'duration_seconds' => env('AWS_BEDROCK_ASSUME_ROLE_DURATION_SECONDS'),
        'external_id' => env('AWS_BEDROCK_ASSUME_ROLE_EXTERNAL_ID')
      ]
    ],

    'cohere' => [
      'driver' => 'cohere',
      'key' => env('COHERE_API_KEY')
    ],

    'deepseek' => [
      'driver' => 'deepseek',
      'key' => env('DEEPSEEK_API_KEY')
    ],

    'eleven' => [
      'driver' => 'eleven',
      'key' => env('ELEVENLABS_API_KEY')
    ],

    'gemini' => [
      'driver' => 'gemini',
      'key' => env('GEMINI_API_KEY'),
      'url' => env(
        'GEMINI_URL',
        'https://generativelanguage.googleapis.com/v1beta/'
      )
    ],

    'groq' => [
      'driver' => 'groq',
      'key' => env('GROQ_API_KEY')
    ],

    'jina' => [
      'driver' => 'jina',
      'key' => env('JINA_API_KEY')
    ],

    'mistral' => [
      'driver' => 'mistral',
      'key' => env('MISTRAL_API_KEY')
    ],

    'ollama' => [
      'driver' => 'ollama',
      'key' => env('OLLAMA_API_KEY', ''),
      'url' => env('OLLAMA_URL', 'http://localhost:11434')
    ],

    'openai' => [
      'driver' => 'openai',
      'key' => env('OPENAI_API_KEY'),
      'url' => env('OPENAI_URL', 'https://api.openai.com/v1'),
      'store' => env('OPENAI_STORE', true)
    ],

    'openai-compatible' => [
      'driver' => 'openai-compatible',
      'url' => env('OPENAI_COMPATIBLE_URL'),
      'key' => env('OPENAI_COMPATIBLE_API_KEY')
    ],

    'openrouter' => [
      'driver' => 'openrouter',
      'key' => env('OPENROUTER_API_KEY')
    ],

    'voyageai' => [
      'driver' => 'voyageai',
      'key' => env('VOYAGEAI_API_KEY')
    ],

    'xai' => [
      'driver' => 'xai',
      'key' => env('XAI_API_KEY')
    ]
  ]
];
