# Custom Booknetic API

This plugin adds a custom REST API endpoint with dual authentication:

- ✅ JWT Bearer Token (for WordPress users)
- ✅ API Key (for external partners without WP login)

## 🔧 Installation

1. Upload the `custom-booknetic-api` folder to `/wp-content/plugins/`
2. Activate the plugin via **Plugins → Installed Plugins**
3. (Optional) Install and configure the **JWT Auth plugin**:
   - Set `JWT_AUTH_SECRET_KEY` in `wp-config.php`
4. Go to **Settings → Booknetic API** to manage partner API keys

## 🔐 Authentication Options

### 1. JWT (WordPress Users)

- Use `/wp-json/jwt-auth/v1/token` to get a Bearer token
- - you can use postman to send a body:

```
{
  "username": "wp-username",
  "password": "wp-password"
}
```

- - In return you will get an bearer token:

```
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJodHRwczovL2Rldi1lbnYuc2pvYnJpcy5vbmVkZXNpZ24ubnUiLCJpYXQiOjE3NDQwNDIwNzksIm5iZiI6MTc0NDA0MjA3OSwiZXhwIjoxNzQ0NjQ2ODc5LCJkYXRhIjp7InVzZXIiOnsiaWQiOiIxIn19fQ.hWp3-FcPJB8a-FDCq-cr5_KvGd3QsL-QfmP_MiIv4aw",
    "user_email": "youremail@domain.com",
    "user_nicename": "nickname",
    "user_display_name": "nickname"
```

- Include `Authorization: Bearer your-token` in requests

### 2. API Key (Partners)

- Use `x-api-key: your-partner-key` in headers
- Supported methods are set in admin
  - In the admin mangement page can you add/delete and enable/disable API key
  - You can manage if the key should be able to GET/POST/PUT/DELETE access

## 📦 API Endpoint
