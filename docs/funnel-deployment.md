# Funnel Deployment Documentation

## Overview
This document outlines the funnel deployment process, including server setup, permissions, and troubleshooting steps.

## Server Setup

### Directory Permissions
The `/var/www/funnels` directory needs specific permissions for proper operation:


# Set initial ownership
sudo chown www-data:www-data /var/www/funnels
sudo chmod 775 /var/www/funnels
sudo chmod g+s /var/www/funnels 

Add your user to www-data group (replace 'username' with your user)
sudo usermod -a -G www-data username

Create test files
touch /var/www/funnels/test.txt
mkdir /var/www/funnels/test_dir
touch /var/www/funnels/test_dir/inside.txt

Check permissions
ls -l /var/www/funnels/test.txt # Should show: -rw-rw-r-- www-data www-data
ls -ld /var/www/funnels/test_dir # Should show: drwxrwsr-x www-data www-data
ls -l /var/www/funnels/test_dir/inside.txt # Should show: -rw-rw-r-- www-data www-data


## NGINX Configuration
The NGINX configuration for funnel sites:

nginx
server {
listen 80;
server_name {{server_name}};
root {{root_path}};
# Add access and error logs
access_log /var/log/nginx/{{server_name}}.access.log;
error_log /var/log/nginx/{{server_name}}.error.log;
index index.html;
location / {
try_files $uri $uri/ /index.html;
# Add security headers
add_header X-Frame-Options "SAMEORIGIN";
add_header X-XSS-Protection "1; mode=block";
add_header X-Content-Type-Options "nosniff";
}
# Handle HTML files
location ~ \.html$ {
try_files $uri =404;
}
# Deny access to hidden files
location ~ /\. {
deny all;
}
}


## Deployment Process
The deployment process is handled by the `DeployFunnel` Artisan command. Key steps:

1. Copy funnel files to destination
2. Generate NGINX configuration
3. Create symlink
4. Set up SSL certificate
5. Configure DNS


## Troubleshooting

### 403 Forbidden Errors
If you encounter 403 Forbidden errors:

1. Check directory permissions:
ls -la /var/www/funnels/your-funnel-directory

2. Verify ownership:
ls -ld /var/www/funnels/your-funnel-directory

3. Check NGINX user:
ps aux | grep nginx

4. Check NGINX error logs:
sudo tail -f /var/log/nginx/error.log


### Permission Issues
If files are being created with wrong permissions:

1. Verify the application is running as www-data:
$whoami = new Process(['whoami']);
$whoami->run();
echo $whoami->getOutput(); // Should show: www-data

2. Check group membership:

groups www-data
groups your-username


## Best Practices

1. Never use `sudo` in the deployment code
2. Always let files inherit permissions from parent directories
3. Use the SGID bit to maintain group ownership
4. Keep logs for debugging
5. Use proper error handling in the deployment code

## Security Considerations

1. Ensure proper file permissions (775 for directories, 664 for files)
2. Use appropriate NGINX security headers
3. Implement SSL certificates
4. Restrict access to sensitive files
5. Regular security audits
