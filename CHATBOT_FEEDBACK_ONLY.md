# HumaBot - Feedback Pages Only Setup

## Changes Made

### 1. Fixed "HumaBot is unavailable" Error
- Added fallback response system when Anthropic API key is not configured
- Chatbot now works with keyword-based responses for common questions
- No more "unavailable" messages

### 2. Limited Chatbot to Feedback Pages Only
- Removed HumaBot from all base templates
- Added chatbot only to feedback-related pages:
  - `/employ/feedback/` (Employee feedback index)
  - `/employ/feedback/new` (Employee create feedback)
  - `/employ/feedback/{id}` (Employee view feedback)
  - `/employ/feedback/{id}/edit` (Employee edit feedback)
  - `/admin/feedback/` (Admin feedback index)
  - `/admin/feedback/{id}/edit` (Admin edit feedback)

## Current Behavior

### Without Anthropic API Key:
The chatbot uses smart keyword matching to answer questions about:
- **Feedback submission**: "How do I submit feedback?"
- **Feedback tracking**: "How do I track my feedback?"
- **Reputation scores**: "What is my reputation score?"
- **Password reset**: "How do I reset my password?"
- **Profile editing**: "How do I edit my profile?"
- **Contact admin**: "How do I contact admin?"

### With Anthropic API Key:
Replace `ANTHROPIC_API_KEY=your_anthropic_api_key_here` in `.env` with your actual API key to enable full AI responses.

## Pages Where Chatbot Appears

### Employee Feedback Pages:
- Feedback list page (`/employ/feedback/`)
- Create new feedback (`/employ/feedback/new`)
- View feedback details (`/employ/feedback/{id}`)
- Edit feedback (`/employ/feedback/{id}/edit`)

### Admin Feedback Pages:
- Admin feedback dashboard (`/admin/feedback/`)
- Edit feedback (`/admin/feedback/{id}/edit`)

## How to Test

1. **Go to any feedback page** (e.g., `/employ/feedback/`)
2. **Look for floating button** in bottom-right corner with "ð¤ HumaBot"
3. **Click to open** the chat window
4. **Try these questions**:
   - "How do I submit feedback?"
   - "What is my reputation score?"
   - "How do I reset my password?"

## Fallback Responses Examples

**User:** "How do I submit feedback?"
**Bot:** "To submit feedback: 
-> Go to /feedback/new 
-> Fill out the form with your feedback 
-> Click submit 

Your feedback will be reviewed by the admin team."

**User:** "What is my reputation?"
**Bot:** "Your reputation score reflects your contributions: 
-> Bronze: 0-99 points 
-> Silver: 100-499 points 
-> Gold: 500+ points 

Earn points by quality feedback and participation!"

## To Enable Full AI (Optional)

1. Get Anthropic API key from https://console.anthropic.com/
2. Update `.env` file:
   ```env
   ANTHROPIC_API_KEY=sk-ant-your-actual-api-key-here
   ```
3. Clear cache: `php bin/console cache:clear`

The chatbot will then use Claude AI for more intelligent responses while maintaining the same helpful behavior.
