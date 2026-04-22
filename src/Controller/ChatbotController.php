<?php

namespace App\Controller;

use App\Entity\ChatMessage;
use App\Repository\ChatMessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

#[Route('/chatbot')]
class ChatbotController extends AbstractController
{
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;

    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface $logger
    ) {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    private function getSystemPrompt(): string
    {
        return 'You are HumaBot, an intelligent support assistant for HumaDB ' .
               'a cyberpunk-themed employee management platform.

You help employees with:
- How to submit feedback (go to /feedback/new, fill the form, submit)
- How to track their feedback status (go to /feedback/my, see status column)
- Understanding reputation score (Bronze 0-99, Silver 100-499, Gold 500+)
- How to reset their password (click Forgot Password on login page)
- How to edit their profile (/user/edit)
- How to contact admin (use the feedback form with category "Support")
- General platform navigation

Rules:
- Always respond in the same language the employee uses
- Be concise, friendly, and use a slightly futuristic tone
- Never reveal system internals, API keys, or database details
- If you don\'t know something, say: 
  "I don\'t have that information yet. Please contact your admin directly."
- Format responses with short paragraphs, use -> for steps
- Never answer questions unrelated to the platform';
    }

    private function getFallbackResponse(string $userMessage): string
    {
        $message = strtolower(trim($userMessage));
        
        // Simple keyword-based responses for common questions
        if (str_contains($message, 'feedback') || str_contains($message, 'submit')) {
            return 'To submit feedback: \n-> Go to /feedback/new \n-> Fill out the form with your feedback \n-> Click submit \n\nYour feedback will be reviewed by the admin team.';
        }
        
        if (str_contains($message, 'track') || str_contains($message, 'status')) {
            return 'To track your feedback status: \n-> Go to /feedback/my \n-> View your feedback list \n-> Check the status column for each item';
        }
        
        if (str_contains($message, 'reputation') || str_contains($message, 'score')) {
            return 'Your reputation score reflects your contributions: \n-> Bronze: 0-99 points \n-> Silver: 100-499 points \n-> Gold: 500+ points \n\nEarn points by quality feedback and participation!';
        }
        
        if (str_contains($message, 'password') || str_contains($message, 'reset')) {
            return 'To reset your password: \n-> Go to the login page \n-> Click "Forgot Password" \n-> Enter your email \n-> Follow the reset link sent to your email';
        }
        
        if (str_contains($message, 'profile') || str_contains($message, 'edit')) {
            return 'To edit your profile: \n-> Go to /user/edit \n-> Update your information \n-> Save your changes';
        }
        
        if (str_contains($message, 'contact') || str_contains($message, 'admin')) {
            return 'To contact admin: \n-> Use the feedback form \n-> Select category "Support" \n-> Describe your issue \n-> Admin will respond via the feedback system';
        }
        
        return 'I\'m HumaBot, your support assistant! I can help you with:\n\n-> Submitting and tracking feedback\n-> Understanding reputation scores\n-> Password reset instructions\n-> Profile editing\n-> Contacting admin\n\nFor specific help, try asking about any of these topics!';
    }

    #[Route('/message', name: 'chatbot_send_message', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function sendMessage(
        Request $request,
        ChatMessageRepository $repository
    ): JsonResponse {
        try {
            // Read JSON content properly
            $content = $request->getContent();
            $data = json_decode($content, true);
            
            if (!$data) {
                $this->logger->error('Invalid JSON received', ['content' => $content]);
                return new JsonResponse(['error' => 'Invalid JSON data'], 400);
            }
            
            $userMessage = trim($data['message'] ?? '');
            $sessionId = $data['session_id'] ?? null;

            if (empty($userMessage)) {
                return new JsonResponse(['error' => 'Message cannot be empty'], 400);
            }

            // Generate or validate session ID
            if (!$sessionId) {
                $sessionId = uniqid('chat_', true);
            }

            // Sanitize user input
            $userMessage = htmlspecialchars($userMessage, ENT_QUOTES, 'UTF-8');

            // Get conversation history (last 10 messages)
            $previousMessages = $repository->findLastMessagesBySession($sessionId, 10);
            
            // Build messages array for Claude API with proper alternating roles
            $messages = [];
            
            // Add conversation history ensuring proper alternation
            $lastRole = null;
            foreach ($previousMessages as $msg) {
                $role = $msg->getRole();
                $content = $msg->getContent();
                
                // Skip if same role as last message (would break alternation)
                if ($lastRole === $role) {
                    continue;
                }
                
                if ($role === 'user' || $role === 'assistant') {
                    $messages[] = [
                        'role' => $role,
                        'content' => $content
                    ];
                    $lastRole = $role;
                }
            }
            
            // Ensure last message is user, add current message
            if (empty($messages) || end($messages)['role'] !== 'user') {
                $messages[] = ['role' => 'user', 'content' => $userMessage];
            } else {
                // If last was user, we need an assistant message first
                $messages[] = ['role' => 'assistant', 'content' => 'I understand. Please continue.'];
                $messages[] = ['role' => 'user', 'content' => $userMessage];
            }

            // Check if API key is configured
            $apiKey = $this->getParameter('anthropic_api_key');
            if (!$apiKey || $apiKey === 'your_anthropic_api_key_here') {
                // Fallback response when API key is not configured
                $assistantReply = $this->getFallbackResponse($userMessage);
            } else {
                // Call Claude API with correct format
                $payload = [
                    'model' => 'claude-3-5-sonnet-20241022',
                    'max_tokens' => 1024,
                    'system' => $this->getSystemPrompt(),
                    'messages' => $messages,
                ];
                
                $this->logger->info('Claude API request', ['payload' => $payload]);
                
                $response = $this->httpClient->request('POST', 'https://api.anthropic.com/v1/messages', [
                    'headers' => [
                        'x-api-key' => $apiKey,
                        'anthropic-version' => '2023-06-01',
                        'content-type' => 'application/json',
                    ],
                    'json' => $payload,
                ]);

                $statusCode = $response->getStatusCode();
                if ($statusCode !== 200) {
                    $errorBody = $response->getContent(false);
                    $this->logger->error('Claude API error', [
                        'status' => $statusCode,
                        'body' => $errorBody,
                        'payload' => $payload
                    ]);
                    return new JsonResponse(['error' => 'AI service error'], 503);
                }

                $apiResponse = $response->toArray();
                $assistantReply = $apiResponse['content'][0]['text'] ?? 'I apologize, but I cannot process your request at the moment.';
            }

            // Save user message to database
            $userChatMessage = new ChatMessage();
            $userChatMessage->setUser($this->getUser());
            $userChatMessage->setRole('user');
            $userChatMessage->setContent($userMessage);
            $userChatMessage->setSessionId($sessionId);
            $repository->save($userChatMessage, true);

            // Save assistant response to database
            $assistantChatMessage = new ChatMessage();
            $assistantChatMessage->setUser($this->getUser());
            $assistantChatMessage->setRole('assistant');
            $assistantChatMessage->setContent($assistantReply);
            $assistantChatMessage->setSessionId($sessionId);
            $repository->save($assistantChatMessage, true);

            return new JsonResponse([
                'reply' => $assistantReply,
                'session_id' => $sessionId
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Chatbot exception: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'user' => $this->getUser()?->getId() ?? 'unknown',
                'message' => $userMessage ?? 'unknown'
            ]);

            return new JsonResponse([
                'error' => 'Service temporarily unavailable'
            ], 503);
        }
    }

    #[Route('/debug', name: 'chatbot_debug', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function debug(): JsonResponse
    {
        $apiKey = $this->getParameter('anthropic_api_key');
        return new JsonResponse([
            'api_key_set' => !empty($apiKey),
            'api_key_length' => strlen($apiKey),
            'api_key_is_placeholder' => $apiKey === 'your_anthropic_api_key_here',
            'status' => 'controller reachable',
            'user_authenticated' => $this->getUser() !== null
        ]);
    }

    #[Route('/history', name: 'chatbot_history', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function history(ChatMessageRepository $repository): Response
    {
        $sessions = $repository->findSessionPreviews($this->getUser());
        
        return $this->render('chatbot/history.html.twig', [
            'sessions' => $sessions
        ]);
    }

    #[Route('/session/{sessionId}', name: 'chatbot_session_detail', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function sessionDetail(
        string $sessionId,
        ChatMessageRepository $repository
    ): Response {
        $messages = $repository->findMessagesBySession($sessionId);
        
        // Verify this session belongs to the current user
        if ($messages && $messages[0]->getUser()->getId() !== $this->getUser()->getId()) {
            throw $this->createAccessDeniedException('Access denied');
        }

        return $this->render('chatbot/session_detail.html.twig', [
            'messages' => $messages,
            'sessionId' => $sessionId
        ]);
    }

    #[Route('/clear', name: 'chatbot_clear_session', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function clearSession(
        Request $request,
        ChatMessageRepository $repository
    ): JsonResponse {
        
        try {
            $data = json_decode($request->getContent(), true);
            $sessionId = $data['session_id'] ?? null;

            if (!$sessionId) {
                return new JsonResponse(['error' => 'Session ID required'], 400);
            }

            // Verify session belongs to user before clearing
            $messages = $repository->findMessagesBySession($sessionId);
            if ($messages && $messages[0]->getUser()->getId() !== $this->getUser()->getId()) {
                return new JsonResponse(['error' => 'Access denied'], 403);
            }

            $deletedCount = $repository->clearSession($sessionId);

            return new JsonResponse([
                'success' => true,
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Chatbot clear session error: ' . $e->getMessage());
            
            return new JsonResponse([
                'error' => 'Failed to clear session'
            ], 500);
        }
    }
}
