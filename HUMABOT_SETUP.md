# HumaBot AI Support Chatbot - Setup Instructions

## Overview
HumaBot is a fully functional AI-powered support chatbot integrated into your Symfony application. It uses the Anthropic Claude API to provide intelligent assistance to employees.

## Features Completed
- **Floating Chat Widget**: Cyberpunk-themed neon UI with Orbitron/Rajdhani fonts
- **AI Integration**: Claude API via Symfony backend with proper error handling
- **Conversation History**: Database storage with session management
- **Quick Actions**: Pre-defined questions for common support topics
- **Rate Limiting**: 20 messages per minute per user protection
- **Mobile Responsive**: Full-screen chat on mobile devices
- **History Management**: View and clear chat sessions

## Files Created
1. `src/Entity/ChatMessage.php` - Entity for chat messages
2. `src/Repository/ChatMessageRepository.php` - Repository with custom queries
3. `src/Controller/ChatbotController.php` - API endpoints and routes
4. `config/services.yaml` - Anthropic API key configuration
5. `public/css/chatbot.css` - Complete cyberpunk neon styling
6. `public/js/chatbot.js` - Vanilla JavaScript chatbot implementation
7. `templates/chatbot/_widget.html.twig` - Floating widget template
8. `templates/chatbot/history.html.twig` - Chat history page
9. `templates/chatbot/session_detail.html.twig` - Individual session view
10. `migrations/Version20260422020000.php` - Database migration

## Database Setup
The `chat_messages` table has been created with the following structure:
- `id` (Primary Key, Auto Increment)
- `user_id` (Foreign Key to users table)
- `role` ('user' or 'assistant')
- `content` (LONGTEXT for message content)
- `created_at` (DATETIME)
- `session_id` (VARCHAR for conversation grouping)

## Required Setup Steps

### 1. Configure Anthropic API Key
Edit your `.env` file and replace the placeholder:
```env
ANTHROPIC_API_KEY=your_actual_anthropic_api_key_here
```

Get your API key from: https://console.anthropic.com/

### 2. Clear Cache
```bash
php bin/console cache:clear
php bin/console cache:clear --env=prod
```

### 3. Verify Routes
The following routes are now available:
- `POST /chatbot/message` - Send message to AI
- `GET /chatbot/history` - View chat history
- `GET /chatbot/session/{sessionId}` - View specific session
- `POST /chatbot/clear` - Clear chat session

### 4. Test the Integration
1. Log in as any user (ROLE_USER or higher)
2. You should see the HumaBot floating button in the bottom-right corner
3. Click to open and test the chat functionality

## Usage Instructions

### For Employees
- **Access**: Click the floating HumaBot button on any page
- **Quick Questions**: Use the pre-defined buttons for common topics
- **History**: View past conversations via the "History" link
- **Clear Chat**: Use the clear button to reset conversation

### Supported Topics
HumaBot helps with:
- Submitting feedback (/feedback/new)
- Tracking feedback status (/feedback/my)
- Understanding reputation scores (Bronze 0-99, Silver 100-499, Gold 500+)
- Password reset (Forgot Password on login page)
- Profile editing (/user/edit)
- Contacting admin (feedback form with "Support" category)
- General platform navigation

## Security Features
- **Authentication Required**: All routes require IS_AUTHENTICATED_FULLY
- **CSRF Protection**: X-Requested-With header validation
- **Rate Limiting**: 20 messages per minute per user
- **Input Sanitization**: All user input sanitized before API calls
- **Session Isolation**: Users can only access their own conversations

## Customization

### Modify System Prompt
Edit the `getSystemPrompt()` method in `ChatbotController.php` to change the AI's behavior and supported topics.

### Update Styling
Modify `public/css/chatbot.css` to adjust the cyberpunk theme:
- Colors: `#00f5ff` (cyan), `#bf00ff` (purple), `#ff006e` (pink)
- Fonts: Orbitron (headings), Rajdhani (body)
- Animations: pulse-glow, typing-bounce, slide-up

### Add New Quick Actions
Edit `templates/chatbot/_widget.html.twig` to add more quick question buttons.

## Troubleshooting

### Chatbot Not Showing
1. Verify user is authenticated
2. Check browser console for JavaScript errors
3. Ensure CSS and JS files are loading correctly

### API Errors
1. Verify ANTHROPIC_API_KEY is correctly set
2. Check API key has sufficient credits
3. Review Symfony logs for detailed error messages

### Database Issues
1. Verify chat_messages table exists: `SHOW TABLES LIKE 'chat_messages';`
2. Check foreign key constraints: `SHOW CREATE TABLE chat_messages;`

## Performance Considerations
- Chat history is loaded on-demand (no eager loading)
- Session-based conversation limiting (last 10 messages)
- Efficient database queries with proper indexing
- Minimal external dependencies (vanilla JS only)

## Future Enhancements
- File attachment support
- Multi-language support
- Admin dashboard for chat analytics
- Integration with ticketing system
- Voice input/output capabilities

---

**HumaBot is now ready to assist your employees!** ð¤
