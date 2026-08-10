# Wahana Project Management

Wahana Project Management adalah aplikasi manajemen proyek berbasis web yang dirancang untuk menyederhanakan alur kerja tim, mulai dari perencanaan proyek hingga evaluasi hasil kerja. Platform ini melayani tiga peran utama: Admin yang mengelola akun pengguna dan hierarki organisasi, Manager yang bertanggung jawab membuat proyek, mendistribusikan tugas, serta memverifikasi hasil pekerjaan bawahan, dan Karyawan yang menerima tugas, mengunggah bukti pekerjaan (seperti gambar atau dokumen), serta menerima umpan balik untuk perbaikan. Sistem ini dilengkapi dengan fitur otentikasi, manajemen pengguna, dan dashboard monitoring untuk memantau progres proyek maupun kinerja individu secara real-time.

Dalam penggunaannya, hierarki tim dibangun dengan struktur satu arah di mana satu karyawan hanya dapat berada di bawah satu manajer. Manager dapat membuat proyek utama yang dilengkapi dengan judul, deskripsi, deadline, dan status progres, lalu membaginya menjadi sub-proyek atau tugas-tugas spesifik. Setiap tugas memiliki bobot penilaian, prioritas (tinggi, sedang, rendah), dan status pengerjaan. Karyawan akan mengakses tugas yang diberikan, mengunggah hasil pekerjaan beserta catatan, dan mengirimkannya untuk direview. Manager kemudian dapat memberikan verifikasi dengan cara menerima, menolak, atau meminta revisi disertai feedback tertulis, sehingga proses kontrol kualitas pekerjaan berjalan dua arah secara transparan.

Dari sisi teknis, sistem ini dibangun dengan memenuhi standar kebutuhan non-fungsional, seperti performa yang mampu melayani hingga 50 pengguna bersamaan dengan waktu respons di bawah 3 detik, keamanan password terenkripsi (hashing), serta ketersediaan backup database harian. Tampilan antarmuka dirancang responsif untuk desktop dan mobile agar memudahkan akses kapan saja. Dengan ruang lingkup yang mencakup manajemen pengguna, proyek, distribusi tugas, review, dan monitoring progres berbasis bobot tugas, Wahana Project Management menjadi solusi terpadu untuk meningkatkan produktivitas dan akuntabilitas tim dalam setiap tahapan pengerjaan proyek.

## Table of Contents
- [Project Name Documentation](#project-name-documentation)
  - [Table of Contents](#table-of-contents)
  - [Introduction](#introduction)
  - [What is Project Name?](#what-is-project-name)
  - [Who Should Read This Documentation?](#who-should-read-this-documentation)
  - [How to Use This Guide](#how-to-use-this-guide)
  - [Getting Started](#getting-started)
  - [User Manual](#user-manual)
    - [User Manual: Admin](#user-manual-admin)
    - [User Manual: Sub-admin](#user-manual-sub-admin)
    - [User Manual: User](#user-manual-user)
  - [Troubleshooting](#troubleshooting)
    - [Issue: HTTP ERROR 500](#issue-http-error-500)
  - [Version History](#version-history)
    - [Version 1.0.0 (dd-mm-yyy)](#version-100-dd-mm-yyy)

## Introduction

This documentation is designed to guide you through the installation process and help you get started with using the software. This guide will provide you with the necessary information to set up and use the application effectively.

## What is Project Name?

Project Name is a web-based application built using Laravel 9 with PHP 8.1 designed to assist HR teams and recruiters find potential employees. With this system, you can streamline and optimize the prospective employee selection process by looking at the prospective employee's psychological test results.


## Who Should Read This Documentation?

This documentation is intended for various users, including:

- **Recruiters**: If you're responsible for evaluating and selecting employee candidates, this guide will walk you through the process and provide insights into using the system effectively.
- **Developers and Technical Users**: If you're a developer or have technical responsibilities, you'll find instructions on how to set up and configure the system for optimal performance.
- **Administrators**: For those managing the deployment and configuration of the system, this guide will help you make informed decisions.

## How to Use This Guide

This guide is organized into sections that cover various aspects of using Project Name:

- **Installation**: Step-by-step instructions for installing the software on your system.
- **Getting Started**: An overview of the software's main features and how to access them.
- **Usage Examples**: Practical examples that demonstrate how to use different features.
- **Troubleshooting**: Solutions for common issues you might encounter.
- **Appendix**: Additional resources and reference information.
- **Version History**: A log of changes made to the software and documentation across different versions.

Feel free to navigate through the sections based on your needs. If you have any questions or need assistance, don't hesitate to reach out to our support team.

Let's get started with the installation process!

## Getting Started

To start using Project Name, follow these steps:

### Application Setup

1. **Install Composer dependencies:**
   ```sh
   composer install
   ```

2. **Create a copy of the .env.example file and name it .env:**
   ```sh
   cp .env.example .env
   ```

3. **Generate an application key:**
   ```sh
   php artisan key:generate
   ```

4. **Run database migrations:**
   ```sh
   php artisan migrate
   ```

5. **Install Node.js dependencies:**
   ```sh
   npm install
   ```

### Server Configuration

After completing the application setup, you need to configure your server for optimal performance:

#### Step 1: Configure Nginx Main Configuration

Edit the file `/etc/nginx/nginx.conf` and add the following settings inside the `http {}` block:

```nginx
# Inside http {} block
client_max_body_size 512M;
proxy_read_timeout 600s;
proxy_connect_timeout 120s;
proxy_buffering on;
proxy_buffer_size 16k;
proxy_buffers 16 16k;
```

#### Step 2: Configure Default Nginx Site

Edit the file `/etc/nginx/sites-available/default` and add these buffer and timeout settings:

```nginx
# Buffer and timeout settings
client_body_buffer_size 512k;
client_body_temp_path /tmp/nginx_upload 1 2;
client_max_body_size 150M;
client_body_timeout 300s;
client_header_timeout 300s;
send_timeout 300s;
proxy_connect_timeout 300s;
proxy_send_timeout 300s;
proxy_read_timeout 300s;
fastcgi_send_timeout 300s;
fastcgi_read_timeout 300s;

# PHP block configuration
location ~ \.php$ {
    try_files $fastcgi_script_name = 404;
    include fastcgi_params;
    fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    fastcgi_param DOCUMENT_ROOT $realpath_root;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    fastcgi_read_timeout 600s;
    fastcgi_send_timeout 600s;
    fastcgi_connect_timeout 300s;
    fastcgi_buffer_size 128k;
    fastcgi_buffers 4 256k;
    fastcgi_busy_buffers_size 256k;
}
```

#### Step 3: Configure Your Domain-Specific Nginx Site

Create or edit your domain-specific configuration file (e.g., `/etc/nginx/sites-available/your-domain.conf`):

```nginx
server {
    # Replace example.com with your actual domain
    server_name your-domain.com; 
    root /var/www/your-project/html/public;

    client_max_body_size 512M;
    client_body_timeout 600s;
    client_header_timeout 600s;
    send_timeout 600s;

    # Add buffer settings
    client_body_buffer_size 1m;
    client_header_buffer_size 1k;
    large_client_header_buffers 4 8k;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;

        # Increased timeout settings
        fastcgi_read_timeout 600s;
        fastcgi_send_timeout 600s;
        fastcgi_connect_timeout 300s;

        # Increased buffer settings
        fastcgi_buffer_size 256k;
        fastcgi_buffers 8 256k;
        fastcgi_busy_buffers_size 512k;
        fastcgi_temp_file_write_size 512k;
    }

    # Enhanced security - block sensitive files
    location ~ /\.(env|git|aws) {
        deny all;
        return 404;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
    
    # Block common attack patterns
    location ~* \.(sql|bak|old|backup|log)$ {
        deny all;
        return 404;
    }

    # SSL configuration (if using HTTPS)
    listen [::]:443 ssl ipv6only=on;
    listen 443 ssl;
    ssl_certificate /etc/letsencrypt/live/your-domain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/your-domain.com/privkey.pem;
    include /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem;
}

# HTTP to HTTPS redirect
server {
    if ($host = your-domain.com) {
        return 301 https://$host$request_uri;
    }

    listen 80;
    listen [::]:80;
    server_name your-domain.com;
    return 404;
}
```

#### Step 4: Configure PHP Settings

Edit your PHP configuration file (`/etc/php/8.2/fpm/php.ini` or according to your PHP version) and update these settings:

```ini
max_input_time = 600
max_file_uploads = 20
memory_limit = 512M
max_execution_time = 600
upload_max_filesize = 512M 
post_max_size = 512M
```

**Important Note:** Ensure that `post_max_size` is greater than or equal to `upload_max_filesize`.

#### Step 5: Server Activation

To activate the Laravel scheduler on the server, you need to add a **cron job** on the server:

```bash
# Run this command in the server terminal
crontab -e

# Add this line:
* * * * * cd /path/to/your/project && php artisan schedule:run >> /dev/null 2>&1
```

**Important Note:** Replace `/path/to/your/project` with the actual path to your project directory on the server.

### Final Steps

After completing all the above steps:

1. **Restart Nginx:**
   ```sh
   sudo systemctl restart nginx
   ```

2. **Restart PHP-FPM:**
   ```sh
   sudo systemctl restart php8.2-fpm
   ```

3. **Test your application** by accessing it through your web browser.

For more detailed instructions, refer to the [Installation Guide](/docs/installation/installation.md).

## User Manual

### [User Manual: Admin](/docs/manual/admin/README.md) 

### [User Manual: Sub-admin](/docs/manual/sub-admin/README.md)

### [User Manual: User](/docs/manual/user/README.md)

## Troubleshooting

### Issue: HTTP ERROR 500
If the application crashes on startup and you encounter an HTTP ERROR 500, you can try the following steps to resolve the issue:

1. **Check Server .env File**: Verify that the `.env` file on your server contains accurate configuration settings. Ensure that the necessary database credentials, cache configurations, and other environment-specific settings are correctly defined.

2. **Verify APP_KEY**: The `APP_KEY` is a critical value used for encryption and security purposes. If this key is not correctly generated or configured, it can lead to unexpected errors. To ensure the `APP_KEY` is correct, follow these steps:

   - Open a terminal window.
   - Navigate to the root directory of your project.
   - Run the following command to generate a new `APP_KEY`:
     ```sh
     php artisan key:generate
     ```

3. **Check Log Files**: Sometimes, the error details are recorded in the application's log files. Check the log files (usually located in the `storage/logs` directory) for more specific error messages that can help diagnose the issue.

4. **Server Environment**: Ensure that your server environment meets the software's requirements. Check for compatibility issues with PHP version, required extensions, and other dependencies.

5. **Clear Cache**: Stale or corrupted cache files can lead to unexpected errors. Try clearing the cache by running the following command:
   ```sh
   php artisan cache:clear
   ```

## Version History

### Version 1.0.0 (dd-mm-yyy)
- Initial release of Project Name.
