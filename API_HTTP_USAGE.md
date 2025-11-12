# API HTTP Client Usage Guide

This guide explains how to use the `api.http` file with JetBrains HTTP Client
(available in PhpStorm, IntelliJ IDEA, WebStorm, etc.) to test the Mentor Wizard
Web Application endpoints.

## Quick Start

### Prerequisites

- JetBrains IDE with HTTP Client support (PhpStorm, IntelliJ IDEA, WebStorm,
  etc.)
- Docker containers running (`docker compose up -d`)
- Application accessible at `http://localhost`

### Step-by-Step: Register a New User

1. **Open the api.http file** in your JetBrains IDE
    - Located at the project root: `api.http`

2. **Get CSRF Token** (Required first step)
    - Navigate to the "CSRF Token Setup (Run This First!)" section
    - Click the ▶️ run icon next to "Get CSRF Token"
    - The response handler script will automatically:
        - Extract the XSRF-TOKEN cookie from the response
        - Decode it
        - Set the `{{csrfToken}}` variable
    - You'll see a console message: "CSRF Token extracted and set:
      [token-value]"

3. **Register New User**
    - Navigate to the "Authentication - Guest Routes" section
    - Find the "Register New User" request
    - Click the ▶️ run icon
    - The request will use the `{{csrfToken}}` variable automatically
    - Check the response for success or validation errors

### Example Registration Request

```http
### Register New User
POST http://localhost/register
Content-Type: application/json
X-Requested-With: XMLHttpRequest
Accept: application/json
X-CSRF-TOKEN: {{csrfToken}}
Cookie: XSRF-TOKEN={{csrfToken}}

{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123"
}
```

## Understanding CSRF Protection

Laravel applications use CSRF tokens to protect against Cross-Site Request
Forgery attacks. The application requires:

1. **XSRF-TOKEN Cookie**: Set by the server on GET requests
2. **X-CSRF-TOKEN Header**: Must match the cookie value in POST/PATCH/DELETE
   requests

The "Get CSRF Token" request handles this automatically by:

- Making a GET request to `/register` (or any page)
- Extracting the `XSRF-TOKEN` cookie from the response
- Decoding the URL-encoded value
- Setting it as the `{{csrfToken}}` variable

## Environment Variables

The api.http file uses these variables:

- `{{baseUrl}}`: Application base URL (default: `http://localhost`)
- `{{csrfToken}}`: CSRF token (automatically set by "Get CSRF Token" request)
- `{{sessionCookie}}`: Session cookie value (manually set after login)

### Changing the Base URL

If your application runs on a different URL/port, update the variable at the top
of api.http:

```
@baseUrl = http://localhost:8000
```

## Common Issues & Solutions

### Issue: "CSRF token mismatch" error

**Solution**: Run the "Get CSRF Token" request again before making
POST/PATCH/DELETE requests. CSRF tokens expire after a period of inactivity.

### Issue: "Unauthenticated" error for protected routes

**Solution**:

1. Login using the "Login User" request
2. Extract the `laravel_session` cookie from the response
3. Manually set: `@sessionCookie = <your-session-cookie-value>` in api.http
4. Run your authenticated request again

### Issue: "The email has already been taken" validation error

**Solution**: The email address is already registered. Use a different email
address or delete the existing user from the database.

### Issue: HTTP requests not working

**Solution**:

1. Verify Docker containers are running: `docker compose ps`
2. Check application is accessible: `curl http://localhost/up`
3. Check Docker logs: `docker compose logs app --tail 50`

## Available Request Sections

The api.http file is organized into these sections:

1. **Public Pages**: Accessible without authentication
2. **CSRF Token Setup**: Get CSRF token (required first step)
3. **Authentication - Guest Routes**: Register, login, password reset
4. **Authentication - Authenticated Routes**: Email verification, password
   update, logout
5. **Dashboard & Profile**: User profile management
6. **Mentor Programs**: CRUD operations for mentor programs (requires 'mentor'
   role)
7. **Health Check**: Application health status

## Advanced Usage

### Using Environment Files

For better token management, create an `http-client.env.json` file:

```json
{
    "dev": {
        "baseUrl": "http://localhost",
        "csrfToken": "your-token-here",
        "sessionCookie": "your-session-cookie-here"
    },
    "production": {
        "baseUrl": "https://your-production-url.com",
        "csrfToken": "",
        "sessionCookie": ""
    }
}
```

### Response Handler Scripts

The "Get CSRF Token" request includes a response handler script that
automatically processes the response. You can add similar scripts to other
requests for automation.

## Need Help?

- Check the comprehensive usage guide at the bottom of `api.http`
- Review Laravel documentation: https://laravel.com/docs/csrf
- Check Inertia.js documentation: https://inertiajs.com/

## Security Note

**Never commit the `http-client.env.json` file** with real tokens or session
cookies to version control. Add it to `.gitignore` if you create one.
