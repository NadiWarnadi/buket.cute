# Module 8 - Media Upload System Implementation Guide

## Overview
Module 8 implements polymorphic media storage for Products, Messages, and other models with support for images, documents, audio, and video files.

**Status:** ✅ Model created | ✅ Controller created | ✅ Routes registered | ⏳ Views pending

## Architecture

### 1. Polymorphic Relationship Design

```
Media Table (Central Storage)
    ↓
References multiple models via:
    - Product (gallery images, featured image)
    - Message (chat images/videos/documents)
    - Customer (profile pictures)
```

### 2. File Storage Structure

```
storage/app/uploads/
├── Product/
│   ├── 1/
│   │   ├── timestamp_image1.jpg
│   │   └── timestamp_image2.jpg
│   └── 2/
│       └── timestamp_product.jpg
├── Message/
│   ├── 50/
│   │   └── timestamp_screenshot.png
│   └── 51/
│       └── timestamp_document.pdf
└── Customer/
    └── 5/
        └── timestamp_avatar.jpg
```

## Database Schema

### Media Table
```
id                 - Primary key
model_type         - Polymorphic type (App\Models\Product, etc)
model_id           - Polymorphic ID (product_id, message_id, etc)
file_path          - Storage path relative to storage/app/
file_name          - Original filename
mime_type          - MIME type (image/jpeg, application/pdf, etc)
size               - File size in bytes
is_featured        - Boolean: is this the featured/main image?
created_at
updated_at
```

**Indexes:**
- Primary: id
- Composite: (model_type, model_id, is_featured)
- Scanning: (model_type, model_id) for gallery

## Components

### 1. Media Model
**File:** `app/Models/Media.php`

**Methods:**

#### Relationships
- `mediable()` - MorphTo relationship to the parent model

#### Getters
- `getUrl()` - Generates storage URL for media
- `isImage()` - Check if MIME type is image/*
- `isDocument()` - Check if PDF or Office document
- `isAudio()` - Check if MIME type is audio/*
- `isVideo()` - Check if MIME type is video/*

#### Lifecycle
- `booted()` - Observer: Auto-delete physical file on model deletion

### 2. MediaController
**File:** `app/Http/Controllers/MediaController.php`

**Endpoints:**

#### POST /api/media/upload
Upload a file for a model

**Request:**
```json
{
  "file": <UploadedFile>,
  "model_type": "App\\Models\\Product",
  "model_id": 1
}
```

**Validation:**
- file: required, file, max 10MB (10240KB)
- model_type: required, in allowed list (Product, Message, Customer)
- model_id: required, integer

**Response (201):**
```json
{
  "success": true,
  "media": {
    "id": 1,
    "file_name": "image.jpg",
    "url": "https://...",
    "mime_type": "image/jpeg",
    "size": 2048,
    "is_image": true
  }
}
```

#### GET /api/media/list
List all media for a model

**Query:**
```
GET /api/media/list?model_type=App\Models\Product&model_id=1
```

**Response:**
```json
[
  {
    "id": 1,
    "file_name": "image1.jpg",
    "url": "https://...",
    "mime_type": "image/jpeg",
    "size": 2048,
    "is_featured": true,
    "is_image": true,
    "created_at": "2026-02-24T..."
  },
  ...
]
```

#### GET /api/media/{media}
Get media details

**Response:**
```json
{
  "id": 1,
  "file_name": "image.jpg",
  "url": "https://...",
  "mime_type": "image/jpeg",
  "size": 2048,
  "is_image": true,
  "is_document": false,
  "is_audio": false,
  "is_video": false
}
```

#### GET /api/media/{media}/download
Download media file

**Response:** File stream with proper headers for download

#### DELETE /api/media/{media}
Delete media file

**Response:**
```json
{
  "success": true
}
```

#### POST /api/media/{media}/featured
Set media as featured

**Response:**
```json
{
  "success": true
}
```

### 3. API Routes
**File:** `routes/api.php`

| Method | Path | Name | Auth | Purpose |
|--------|------|------|------|---------|
| POST | `/media/upload` | - | sanctum | Upload file |
| GET | `/media/list` | - | sanctum | List media |
| GET | `/media/{media}` | - | sanctum | Show details |
| GET | `/media/{media}/download` | - | sanctum | Download file |
| DELETE | `/media/{media}` | - | sanctum | Delete file |
| POST | `/media/{media}/featured` | - | sanctum | Set featured |

**Middleware:** `auth:sanctum` (requires valid API token)

## Model Integration

### Product Model
```php
// In app/Models/Product.php
public function media(): MorphMany
{
    return $this->morphMany(Media::class, 'model');
}

// Get featured or first image
public function getFeaturedImage()
{
    return $this->media()->where('is_featured', true)->first() 
        ?? $this->media()->first();
}
```

### Message Model (Updated)
```php
// In app/Models/Message.php
public function media(): MorphMany
{
    return $this->morphMany(Media::class, 'model');
}
```

## Usage Examples

### Frontend JavaScript - Upload Image

```javascript
const formData = new FormData();
formData.append('file', fileInput.files[0]);
formData.append('model_type', 'App\\Models\\Product');
formData.append('model_id', productId);

fetch('/api/media/upload', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + token,
        'X-CSRF-TOKEN': csrfToken
    },
    body: formData
})
.then(r => r.json())
.then(data => {
    console.log('Uploaded:', data.media.url);
    displayImage(data.media);
});
```

### Frontend - List Product Images

```javascript
fetch(`/api/media/list?model_type=App\\Models\\Product&model_id=${productId}`, {
    headers: {
        'Authorization': 'Bearer ' + token
    }
})
.then(r => r.json())
.then(media => {
    media.forEach(m => {
        if (m.is_image) {
            addImageToGallery(m);
        }
    });
});
```

### Frontend - Set Featured Image

```javascript
fetch(`/api/media/${mediaId}/featured`, {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + token,
        'X-CSRF-TOKEN': csrfToken
    }
})
.then(r => r.json())
.then(data => console.log('Featured set'));
```

### Backend Blade - Display Product Gallery

```blade
@if($product->media->count())
    <div class="gallery">
        @foreach($product->media as $media)
            @if($media->isImage())
                <div class="gallery-item">
                    <img src="{{ $media->getUrl() }}" 
                         alt="{{ $media->file_name }}"
                         @if($media->is_featured) class="featured" @endif>
                </div>
            @endif
        @endforeach
    </div>
@endif
```

## Storage Configuration

### Setup Local Storage (Development)

**Laravel Default:** Files stored in `storage/app/`

```php
// config/filesystems.php
'disks' => [
    'local' => [
        'driver' => 'local',
        'root' => storage_path('app'),
        'url' => env('APP_URL') . '/storage',
        'visibility' => 'private',
    ],
]
```

### Make Storage Public (Optional)

```bash
php artisan storage:link
# Creates: public/storage -> storage/app/public
```

Then modify MediaController getUrl():
```php
public function getUrl(): string
{
    return asset('storage/' . $this->file_path);
}
```

### Production: S3 Storage (Recommended)

```php
// config/filesystems.php
's3' => [
    'driver' => 's3',
    'key' => env('AWS_ACCESS_KEY_ID'),
    'secret' => env('AWS_SECRET_ACCESS_KEY'),
    'region' => env('AWS_DEFAULT_REGION'),
    'bucket' => env('AWS_BUCKET'),
]
```

Then switch in .env:
```
FILESYSTEM_DISK=s3
```

## Security Considerations

### 1. File Upload Validation
- ✅ Max file size: 10MB
- ✅ MIME type checking
- ❌ TODO: Whitelist specific formats (jpg, png, pdf, etc)
- ❌ TODO: Scan uploaded files for malware

### 2. Access Control
- ✅ Requires authentication (auth:sanctum)
- ❌ TODO: Implement authorization policies (only admin/owner can upload/delete)
- ❌ TODO: Public vs private media access

### 3. Rate Limiting
- ❌ TODO: Implement upload rate limits (e.g., max 10 files/hour per user)

### 4. Recommendations
```php
// Add to MediaController::store()
$validated = $request->validate([
    'file' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,doc,docx',
    ...
]);

// Implement policy
Gate::define('upload-media', function ($user) {
    return $user->is_admin || $user->id === $media->model_id;
});
```

## Testing

### Test 1: Upload Image to Product
```bash
curl -X POST http://localhost:8000/api/media/upload \
  -H "Authorization: Bearer TOKEN" \
  -F "file=@/path/to/image.jpg" \
  -F "model_type=App\\Models\\Product" \
  -F "model_id=1"
```

### Test 2: List Product Media
```bash
curl http://localhost:8000/api/media/list \
  -H "Authorization: Bearer TOKEN" \
  "?model_type=App\\Models\\Product&model_id=1"
```

### Test 3: Set Featured
```bash
curl -X POST http://localhost:8000/api/media/1/featured \
  -H "Authorization: Bearer TOKEN"
```

### Test 4: Delete Media
```bash
curl -X DELETE http://localhost:8000/api/media/1 \
  -H "Authorization: Bearer TOKEN"
```

## Known Limitations

1. **No real image processing:** Images stored as-is (no resizing/thumbnails)
2. **No image validation:** Doesn't verify image integrity (could be corrupted)
3. **Single file storage:** No incremental/chunked uploads for large files
4. **No CDN integration:** Files served from Laravel (not optimized for scale)
5. **No file deduplication:** Duplicate files stored separately
6. **Storage path visible in code:** Security through obscurity, not proper access control

## Roadmap

### Phase 2 (Image Optimization)
- [ ] Auto-resize images on upload (thumbnail, medium, large)
- [ ] Generate WEBP format for browsers
- [ ] Image compression before storage
- [ ] EXIF data removal for privacy

### Phase 3 (Advanced Features)
- [ ] Image cropping and editing UI
- [ ] Batch upload support
- [ ] Drag-and-drop upload areas
- [ ] Image CDN integration (Cloudinary, ImgIX)
- [ ] Video thumbnail generation

### Phase 4 (Performance)
- [ ] File deduplication (hash-based)
- [ ] Scheduled cleanup of orphaned files
- [ ] S3/Cloud storage optimization
- [ ] Caching and CDN integration

## Files Created/Modified

**Created:**
- ✅ `app/Http/Controllers/MediaController.php`
- ✅ (Already existed) `app/Models/Media.php`

**Modified:**
- ✅ `routes/api.php` - Added media endpoints
- ✅ `app/Models/Message.php` - Added media() relationship

**Existing (Pre-created):**
- `database/migrations/2026_02_24_104929_media.php`
- `database/migrations/2026_02_24_130000_add_is_featured_to_media.php`

## Summary

Module 8 provides a complete polymorphic media management system allowing:
1. **Upload** files for Products, Messages, and other models
2. **Organize** files in logical folder hierarchy
3. **Retrieve** files with proper URLs and metadata
4. **Download** files with correct headers
5. **Delete** files with automatic cleanup
6. **Mark Featured** images for gallery displays

API is RESTful, authenticated, and ready for integration with frontend UI components.
