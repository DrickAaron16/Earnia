# Railway Deployment Guide

## Setup Steps

### 1. Create Railway Project
1. Go to https://railway.app
2. Create a new project from GitHub repo: `DrickAaron16/Earnia`
3. Add a MySQL database service

### 2. Configure Environment Variables

In Railway Dashboard > Your Service > Variables, add:

```bash
APP_NAME=Earnia
APP_ENV=production
APP_KEY=base64:rzXfKtfVu2BnbB8gqEIOtm/wxeEE3Pwue6NhQFwOOa8=
APP_DEBUG=false
APP_URL=https://your-app.up.railway.app

# Database (Railway auto-provides these when MySQL is added)
DB_CONNECTION=mysql
DB_HOST=${{MYSQLHOST}}
DB_PORT=${{MYSQLPORT}}
DB_DATABASE=${{MYSQLDATABASE}}
DB_USERNAME=${{MYSQLUSER}}
DB_PASSWORD=${{MYSQLPASSWORD}}

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
LOG_CHANNEL=stack
LOG_LEVEL=error
```

### 3. Deploy

Railway will automatically:
- Detect the `railway.sh` script
- Run migrations
- Seed the database with games
- Start the server

### 4. Verify Deployment

Test these endpoints:
- `https://your-app.up.railway.app/api/health`
- `https://your-app.up.railway.app/api/games`

## Troubleshooting

### Error 500
1. Check Railway logs for detailed error messages
2. Verify all environment variables are set
3. Ensure `APP_KEY` is set correctly
4. Check database connection variables

### Database Issues
- Ensure MySQL service is added and linked
- Verify Railway auto-injected MySQL variables
- Check migrations ran successfully in logs

### Permission Issues
The `railway.sh` script handles permissions automatically

## Local Development with Railway MySQL

Update your local `.env`:
```bash
DB_CONNECTION=mysql
DB_HOST=centerbeam.proxy.rlwy.net
DB_PORT=25733
DB_DATABASE=railway
DB_USERNAME=root
DB_PASSWORD=your-password
```

Get the connection string from Railway Dashboard > MySQL > Connect
