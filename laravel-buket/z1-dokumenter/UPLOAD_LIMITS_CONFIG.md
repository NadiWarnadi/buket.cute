# Upload Limits Configuration Guide

## ⚠️ Issue Overview
The chat feature had issues with large file uploads exceeding PHP's POST size limit:
- **Error**: `POST Content-Length of 45221527 bytes exceeds the limit of 41943040 bytes`
- **Root Cause**: PHP `post_max_size` and `upload_max_filesize` were insufficient (40MB)
- **Solution**: Increase limits and add Laravel validation

---

## 🔧 Configuration Steps

### 1. **Update PHP Configuration** (php.ini)

Find your php.ini file location:
```bash
php -i | grep "php.ini"
# or on Windows:
php -i | findstr "php.ini"
```

Update these settings:
```ini
; Default was 40M (41943040 bytes)
post_max_size = 50M

; Default was 20M  
upload_max_filesize = 25M

; Also recommended:
memory_limit = 256M
max_execution_time = 300
```

After editing, restart PHP:
```bash
# For Apache
sudo systemctl restart apache2

# For Nginx + PHP-FPM
sudo systemctl restart php-fpm

# For local development (php artisan serve)
# Just restart the server
```

### 2. **Laravel Application Validation** ✅

Maximum file upload configured to **25MB** in:
- `app/Http/Controllers/Admin/ChatController.php` - validation rule: `'media' => 'nullable|file|max:25600'` (25600 KB = 25MB)
- `resources/views/admin/chat/show.blade.php` - client-side validation shows file size in MB

### 3. **Nginx Configuration** (if using Nginx)

Update `/etc/nginx/nginx.conf` or site config:
```nginx
http {
    # Default is 1m
    client_max_body_size 50m;
}
```

Then reload Nginx:
```bash
sudo systemctl reload nginx
```

### 4. **Apache Configuration** (if using Apache)

Update `.htaccess` or Apache config:
```apache
<IfModule mod_php.c>
    php_value post_max_size 50M
    php_value upload_max_filesize 25M
    php_value memory_limit 256M
    php_value max_execution_time 300
</IfModule>
```

---

## 📋 Current Limits

| Setting | Current | Recommended |
|---------|---------|-------------|
| `post_max_size` | 40M | 50M |
| `upload_max_filesize` | 20M | 25M |
| Laravel max file | - | 25MB |
| Client-side max | - | 25MB |

---

## ✨ Features Added

### Chat Name Display
- ✅ Customer name now shows for **incoming** messages
- ✅ Admin name shows for **outgoing** messages
- Location: `/resources/views/admin/chat/show.blade.php`

### File Upload Controls
- ✅ File upload button with "Lampir Berkas (Max 25MB)" text
- ✅ Real-time file size validation (25MB limit)
- ✅ Supported formats: Images, Videos, PDF, DOC, DOCX, XLS, XLSX
- ✅ Visual feedback when file is selected
- ✅ Error message if file is too large
- Location: `/resources/views/admin/chat/show.blade.php`

### Server-side Validation
- ✅ File validation in `ChatController->sendReply()`
- ✅ Max file size: 25MB (database stored)
- ✅ Proper error handling and logging

---

## 🧪 Testing File Uploads

1. **Small file** (1-5MB) - Should work ✅
2. **Medium file** (10-20MB) - Should work ✅
3. **Large file** (30MB+) - Should show error ❌

## 🚀 Development vs Production

### Development (using `php artisan serve`)
- PHP settings from php.ini apply automatically
- No Nginx/Apache needed

### Production (Apache/Nginx)
- Update both php.ini AND web server config
- Clear application cache: `php artisan cache:clear`
- Clear view cache: `php artisan view:clear`

---

## 📞 Troubleshooting

| Problem | Solution |
|---------|----------|
| Still getting "exceeds limit" | Restart PHP/web server after php.ini change |
| Nginx upload still fails | Make sure `client_max_body_size` is set in Nginx config |
| File validation fails | Ensure both php.ini and Laravel validation are updated |
| Changes not taking effect | Clear cache: `php artisan cache:clear` |

---

## 📝 Security Notes

- ✅ File types are restricted (only images, videos, documents)
- ✅ File sizes are limited to prevent abuse
- ✅ Uploaded files should be stored outside web root
- ✅ Always validate on server-side (never trust client validation)
- ✅ Consider adding virus scanning for production

---

## 📞 Questions?

Refer to:
- Laravel validation docs: https://laravel.com/docs/validation
- PHP configuration: https://www.php.net/manual/en/ini.php
- Nginx docs: https://nginx.org/en/docs/
- Apache docs: https://httpd.apache.org/docs/
