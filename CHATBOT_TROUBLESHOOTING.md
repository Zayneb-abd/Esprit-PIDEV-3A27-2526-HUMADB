# HumaBot Troubleshooting Guide

## Issue: Chatbot Not Visible on Interface

### Quick Checklist:
1. **User Authentication**: Make sure you're logged in as a user (admin, employee, etc.)
2. **Browser Console**: Press F12 and check for JavaScript errors
3. **Network Tab**: Check if CSS/JS files are loading correctly
4. **Cache**: Clear browser cache (Ctrl+F5 or Cmd+Shift+R)

### Steps to Verify:

#### 1. Check if User is Authenticated
- Look at the top of any page - you should see your username/profile
- If not logged in, login first then refresh the page

#### 2. Check Browser Console
- Press F12 to open Developer Tools
- Go to Console tab
- Look for any red error messages
- Common errors:
  - "Failed to load resource: chatbot.css" (CSS file issue)
  - "HumaChatbot is not defined" (JS file issue)
  - "404 Not Found" (File missing)

#### 3. Check Network Requests
- In Developer Tools, go to Network tab
- Refresh the page (F5)
- Look for:
  - `chatbot.css` - Should load with 200 status
  - `chatbot.js` - Should load with 200 status
  - Any 404 errors for these files

#### 4. Verify HTML Elements
- In Console, type: `document.getElementById('chatbot-toggle')`
- Should return: `<button id="chatbot-toggle" class="chatbot-fab">...</button>`
- If returns `null`, the widget HTML is not being included

#### 5. Check CSS Loading
- In Console, type: `getComputedStyle(document.querySelector('.chatbot-fab'))`
- Should return CSS properties, not empty object

#### 6. Test JavaScript
- In Console, type: `window.HumaChatbot`
- Should return the HumaChatbot object, not `undefined`

### Common Issues and Solutions:

#### Issue: Files Not Loading (404 Error)
**Solution**: Check file paths in templates
- CSS should be at: `/public/css/chatbot.css`
- JS should be at: `/public/js/chatbot.js`

#### Issue: Widget HTML Not Included
**Solution**: Verify template inheritance
- Make sure your page extends one of the base templates with chatbot integration
- Check `{% if app.user %}` condition - you must be logged in

#### Issue: CSS Conflicts
**Solution**: Check for CSS conflicts
- Some themes might override chatbot styles
- Check if other CSS has higher specificity

#### Issue: JavaScript Errors
**Solution**: Check for syntax errors
- Make sure no other JavaScript is causing errors
- Check jQuery conflicts (chatbot uses vanilla JS)

### Debug Commands to Run in Browser Console:

```javascript
// Check if widget exists
document.getElementById('chatbot-toggle')

// Check if chatbot class is loaded
window.HumaChatbot

// Force show widget (for testing)
document.getElementById('chatbot-toggle').style.display = 'block'

// Check CSS loading
Array.from(document.styleSheets).find(sheet => sheet.href?.includes('chatbot.css'))

// Manually open chatbot (if widget exists but not visible)
window.HumaChatbot?.open()
```

### If Still Not Working:

1. **Check Specific Template**: Find out which base template your page uses
2. **Verify Integration**: Ensure the chatbot includes are in that base template
3. **Check Permissions**: Verify user has correct role (ROLE_USER or higher)
4. **Test Different Pages**: Try accessing different pages (admin dashboard, employee dashboard)
5. **Check Server Logs**: Look at Symfony logs for any errors

### Template Integration Check:

The chatbot has been integrated into these base templates:
- `base.html.twig` - Main base template
- `base_user.html.twig` - User pages
- `base_admin.html.twig` - Admin pages  
- `base_candidat.html.twig` - Candidate pages
- `base_client.html.twig` - Client pages
- `base_employe.html.twig` - Employee pages
- `base_manager.html.twig` - Manager pages

### Final Verification:

If you've checked all above and still don't see the chatbot:

1. **Test Direct Access**: Go to `/chatbot/history` (must be logged in)
2. **Check Route**: If history page works, routes are fine, issue is with widget
3. **Clear Symfony Cache**: `php bin/console cache:clear`
4. **Restart Server**: Restart your web server if needed

---

**Expected Behavior**: 
- Floating button appears in bottom-right corner
- Button has cyberpunk neon styling with "ð¤ HumaBot" text
- Clicking opens chat window
- Chat window shows welcome message and input field
