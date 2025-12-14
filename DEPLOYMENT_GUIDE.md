# SPMURI Backend Deployment Guide

## 🚀 Simple Deployment Steps (No Technical Knowledge Required)

### Step 1: Prepare Project
1. Zip the entire `SPMURI_BACKEND` folder
2. Make sure all files are included

### Step 2: Upload to Hostinger
1. Login to Hostinger Dashboard
2. Go to File Manager (hPanel)
3. Navigate to `public_html` folder
4. Upload the zip file
5. Extract the zip file

### Step 3: Setup Environment
1. Go to `SPMURI_BACKEND` folder
2. Rename `.env.production` to `.env`
3. Edit `.env` file and update:
   - Database credentials
   - Domain URL
   - Email settings

### Step 4: Setup Public Access
1. Copy all files from `SPMURI_BACKEND/public/` 
2. Paste them to `public_html/api/` folder
3. Edit `public_html/api/index.php`:
   - Change `__DIR__.'/../` to `__DIR__.'/../../SPMURI_BACKEND/`

### Step 5: Database Setup
1. Create MySQL database in hPanel
2. Import your local database
3. Update database credentials in `.env`

### Step 6: Test APIs
- Login: `https://yourdomain.com/api/user/login`
- Test: `https://yourdomain.com/api/simple-test`

## 🔗 API Endpoints After Deployment

### Public APIs (No Token Required):
- `POST /api/user/login`
- `GET /api/simple-test`
- `GET /api/simple-users`
- `GET /api/products`

### Protected APIs (Token Required):
- `GET /api/user/` (with Authorization header)
- `GET /api/dashboard/`
- `GET /api/product/`

## 🆘 If Something Goes Wrong:
1. Check `.env` file exists
2. Check database connection
3. Check file permissions (755)
4. Check `.htaccess` file exists in public folder

## ✅ Success Indicators:
- Login API returns JWT token
- Simple test API returns success message
- Protected APIs work with token

Your project is 100% ready for deployment!