# Simple Apache Site

A lightweight cleaning business site built to run on Apache with PHP backend support.

## Features

- Modern landing page for cleaning services
- Quote request form with CSV-backed persistence
- Admin login, request management, and status workflow
- Customer signup/login with a dashboard for quote and payment tracking
- Mock payment workflow for quotes
- SMTP-ready email notifications (configurable in `submit-quote.php`)
- Admin user management with super-admin role support
- Future-ready database migration scaffolding

## Running Locally

The site uses PHP for backend pages, so run it from the `public-html` folder:

```bash
cd /home/ben-j1/simple-apache-site/public-html
php -S localhost:8000
```

Then browse to:

- `http://localhost:8000/index.html` — homepage
- `http://localhost:8000/login.php` — admin login
- `http://localhost:8000/customer-login.php` — customer login
- `http://localhost:8000/customer-signup.php` — customer sign up

## Default Credentials

- Admin username: `admin`
- Admin password: `Clean@123`

## Data Storage

The current implementation stores data in CSV files under `public-html/data/`:

- `users.csv` — admin users
- `customers.csv` — customer accounts
- `requests.csv` — quote requests, status, and payment tracking

These files are excluded from git via `.gitignore`.

The project also supports runtime configuration through environment variables. Copy `.env.example` to `.env` and update values locally.

## Admin Workflow

Admin pages include:

- `admin-requests.php` — manage quote requests, set quote amounts, and update status
- `admin-users.php` — super-admin user management
- `request-details.php` — view request details and payment status

## Customer Workflow

Customer pages include:

- `customer-signup.php` — register a customer account
- `customer-login.php` — login
- `customer-dashboard.php` — view quotes and payment status
- `customer-request-detail.php` — view individual request details
- `pay.php` — mock payment page for completing quote payments

## Database Migration Roadmap

The app currently uses CSV storage, but database scaffolding is included for future migration.

### New files for database support

- `public-html/database.php` — PDO connection helper and migration runner
- `public-html/models/AdminUserModel.php` — admin user queries
- `public-html/models/CustomerModel.php` — customer account queries
- `public-html/models/RequestModel.php` — request and payment queries
- `public-html/sql/schema.sql` — SQL schema for `admin_users`, `customers`, and `requests`

### How to migrate to a database

1. Install and configure a MySQL/MariaDB database.
2. Set environment variables for database connectivity:

```bash
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_NAME=cleaning_service
export DB_USER=dbuser
export DB_PASS=dbpass
```

3. Create the database schema:

```php
php -r "require 'public-html/database.php'; Database::migrate();"
```

4. Update the PHP backend to use the model classes instead of CSV helper functions.

### Suggested migration steps

- Replace `read_csv()` and `write_csv()` logic in `submit-quote.php` with `RequestModel::create()` and database queries.
- Replace admin user CSV lookups in `auth.php` with `AdminUserModel` methods.
- Replace customer CSV auth in `customer-auth.php` with `CustomerModel` methods.
- Replace request filtering and details page lookups with `RequestModel` queries.

## Notes

- All customer and admin passwords are hashed with PHP `password_hash()` before storage.
- The mock payment flow is intentionally simple and not a real payment gateway. It is ready for future replacement with Stripe, PayPal, or another processor.
- `public-html/data/` is kept out of git and should remain as runtime storage for this CSV-based prototype.

## Container Packaging

This project can be packaged as a Docker image for easy deployment.

### Build the container image

From the project root:

```bash
docker build -t simple-apache-site .
```

### Run the container

Use environment variables or mount a `.env` file into the container for SMTP and optional DB configuration.

Example with `.env` mount and persistent CSV storage:

```bash
cp .env.example .env
# edit .env as needed

docker run --rm -p 8080:80 \
  -v "$PWD/public-html/data:/var/www/html/data" \
  -v "$PWD/.env:/var/www/.env" \
  simple-apache-site
```

Then browse to `http://localhost:8080`.

### Notes for container use

- The web root is served from `/var/www/html` inside the container.
- The app reads `.env` from `/var/www/.env` if present, or directly from container environment variables.
- Mount `public-html/data` from the host to preserve runtime CSV records across container restarts.

## Kubernetes Integration

A Kubernetes deployment is included under `k8s/`.

- `k8s/deployment.yaml` — Deployment for the Apache/PHP web app
- `k8s/service.yaml` — LoadBalancer Service exposing port 80
- `k8s/configmap.yaml` — non-sensitive configuration values
- `k8s/secret.yaml` — sensitive environment values such as passwords
- `k8s/pvc.yaml` — persistent volume claim for CSV storage

### Deploy to Kubernetes

1. Build the image:

```bash
docker build -t simple-apache-site:latest .
```

2. If you use `kind`, load the image into the cluster:

```bash
kind load docker-image simple-apache-site:latest
```

3. Apply the manifests:

```bash
kubectl apply -f k8s/pvc.yaml
kubectl apply -f k8s/configmap.yaml
kubectl apply -f k8s/secret.yaml
kubectl apply -f k8s/deployment.yaml
kubectl apply -f k8s/service.yaml
kubectl apply -f k8s/networkpolicy.yaml  # Security: restricts pod traffic
kubectl apply -f k8s/ingress.yaml  # Optional: for TLS and domain routing
```

4. Access the service:

- Use `kubectl get svc simple-apache-site-service` to see the external IP or node port.
- For `minikube`, run `minikube service simple-apache-site-service`.
- With Ingress, access via your domain (update `k8s/ingress.yaml` first).

### Environment management

The app reads environment values from Kubernetes `ConfigMap` and `Secret`. You can update values with `kubectl apply -f k8s/configmap.yaml` or `kubectl apply -f k8s/secret.yaml`.

### Persistent data

`k8s/pvc.yaml` preserves `public-html/data/` storage in the cluster so quote requests and user records survive pod restarts.

### Security notes

- The deployment runs as non-root user (`www-data`) with restricted capabilities.
- NetworkPolicy limits ingress traffic to the ingress controller and same namespace.
- Use Ingress with TLS for encrypted external access.
- Secrets are base64-encoded; consider external secret management for production.

### Improving Secret Management

For production deployments, avoid storing sensitive values directly in Kubernetes Secrets (even base64-encoded). Instead, use an external secret management system like HashiCorp Vault, AWS Secrets Manager, or Azure Key Vault, integrated via the External Secrets Operator (ESO).

#### Install External Secrets Operator

First, install ESO in your cluster (requires Helm):

```bash
helm repo add external-secrets https://charts.external-secrets.io
helm install external-secrets external-secrets/external-secrets -n external-secrets-system --create-namespace
```

#### Create a SecretStore

This example uses a dummy backend for illustration. Replace with your actual secret store (Vault, AWS, etc.).

```yaml
apiVersion: external-secrets.io/v1beta1
kind: SecretStore
metadata:
  name: simple-apache-site-secret-store
  namespace: default
spec:
  provider:
    # Example: AWS Secrets Manager
    aws:
      service: SecretsManager
      region: us-east-1
      auth:
        jwt:
          serviceAccountRef:
            name: external-secrets-sa
    # For Vault:
    # vault:
    #   server: "https://vault.example.com"
    #   path: "secret"
    #   auth:
    #     kubernetes:
    #       mountPath: "kubernetes"
    #       role: "external-secrets"
```

#### Create an ExternalSecret

This pulls secrets from the external store and creates a Kubernetes Secret.

```yaml
apiVersion: external-secrets.io/v1beta1
kind: ExternalSecret
metadata:
  name: simple-apache-site-external-secret
  namespace: default
spec:
  refreshInterval: 15s
  secretStoreRef:
    name: simple-apache-site-secret-store
    kind: SecretStore
  target:
    name: simple-apache-site-secret
    creationPolicy: Owner
  data:
    - secretKey: SMTP_USER
      remoteRef:
        key: prod/cleaning-service/smtp
        property: username
    - secretKey: SMTP_PASS
      remoteRef:
        key: prod/cleaning-service/smtp
        property: password
    - secretKey: DB_USER
      remoteRef:
        key: prod/cleaning-service/db
        property: username
    - secretKey: DB_PASS
      remoteRef:
        key: prod/cleaning-service/db
        property: password
```

Apply these manifests, then update the deployment to reference the ExternalSecret-generated Secret. This keeps sensitive data out of your Git repository and rotates automatically.

## Change Timeline

This timeline documents the main changes made during development and why they were implemented.

1. Initial site skeleton
   - Added a clean landing page and basic service pages.
   - Included a quote request form and static content for the cleaning business.
   - Purpose: provide a simple customer-facing site foundation.

2. CSV-backed persistence
   - Added CSV storage in `public-html/data/` for requests, customers, and admin users.
   - Purpose: keep the prototype lightweight without requiring a database.

3. Admin login and request management
   - Added `login.php`, `auth.php`, `admin-requests.php`, and `request-details.php`.
   - Implemented admin session handling and request status updates.
   - Purpose: let administrators review quote requests and manage work flow.

4. Customer signup/login and dashboard
   - Added `customer-signup.php`, `customer-login.php`, `customer-dashboard.php`, and `customer-request-detail.php`.
   - Implemented customer account creation, login, and request status visibility.
   - Purpose: allow customers to sign up, track requests, and view their quote details.

5. Mock payment workflow
   - Added `pay.php` and payment status tracking for quote requests.
   - Updated request status flow to include paid/unpaid tracking.
   - Purpose: provide a simple payment completion experience that can be upgraded later.

6. Admin user management
   - Added `admin-users.php` for super-admin user management.
   - Purpose: support multiple admin users and manage access securely.

7. Email notification support
   - Added SMTP-ready notification logic in `submit-quote.php`.
   - Purpose: enable alerting when new quote requests arrive.

8. Environment configuration and security hardening
   - Added `public-html/config.php` and `.env.example` for environment-driven settings.
   - Switched SMTP configuration to environment variables and removed hard-coded secrets.
   - Added secure session handling and request logging.
   - Purpose: improve secret management, reduce configuration drift, and harden the application.

9. Database migration scaffolding
   - Added `public-html/database.php`, model classes, and `sql/schema.sql`.
   - Purpose: prepare the app for future migration from CSV files to a database-backed architecture.

10. Documentation and operational notes
    - Updated `README.md` with running instructions, environment guidance, and migration notes.
    - Purpose: make it easier to deploy, maintain, and extend the project.
