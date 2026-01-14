# CI/CD Setup for Rumahweb Deployment

## Overview

Setup automatic deployment dari GitHub ke Rumahweb cPanel menggunakan GitHub Actions.

## Branch Strategy

-   **`dev`** → Development/Staging
-   **`main`** → Production

## Deployment Flow

```
Push to dev  → Auto deploy ke dev.yourdomain.com (optional)
Push to main → Auto deploy ke api.yourdomain.com (production)
```

## Requirements

1. SSH access di Rumahweb (cPanel Terminal)
2. GitHub repository secrets

## Setup Steps

### 1. Setup SSH Key di cPanel Rumahweb

1. Login cPanel → **Terminal** atau **SSH Access**
2. Generate SSH key:
    ```bash
    ssh-keygen -t rsa -b 4096 -C "github-deploy"
    ```
3. Copy public key ke authorized_keys:
    ```bash
    cat ~/.ssh/id_rsa.pub >> ~/.ssh/authorized_keys
    ```
4. Copy private key untuk GitHub:
    ```bash
    cat ~/.ssh/id_rsa
    ```

### 2. Setup GitHub Secrets

Di GitHub repo → Settings → Secrets → Actions, tambahkan:

-   `SSH_HOST` = IP atau hostname Rumahweb
-   `SSH_USER` = username cPanel
-   `SSH_KEY` = Private key SSH (isi id_rsa)
-   `SSH_PORT` = 22 (atau port SSH Rumahweb)
-   `DEPLOY_PATH` = /home/username/laravel-app

### 3. GitHub Actions Workflow

File: `.github/workflows/deploy.yml`

---

## Alternative: Git Pull Deployment (Simpler)

Jika SSH tidak tersedia, gunakan cPanel Git Version Control:

1. cPanel → Git Version Control → Create
2. Clone URL: https://github.com/rikoarik/backend-management-toko.git
3. Deploy Path: /home/username/laravel-app
4. Auto-deploy saat push ke main
