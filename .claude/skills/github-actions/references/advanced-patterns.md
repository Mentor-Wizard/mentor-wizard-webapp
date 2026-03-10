# Advanced GitHub Actions Patterns

## OIDC Authentication for Cloud Deployments

Eliminate long-lived secrets by using OpenID Connect:

### AWS

```yaml
permissions:
  id-token: write
  contents: read
steps:
  - uses: aws-actions/configure-aws-credentials@v4
    with:
      role-to-assume: arn:aws:iam::123456789:role/deploy-role
      aws-region: eu-central-1
```

### Azure

```yaml
  - uses: azure/login@v2
    with:
      client-id: ${{ secrets.AZURE_CLIENT_ID }}
      tenant-id: ${{ secrets.AZURE_TENANT_ID }}
      subscription-id: ${{ secrets.AZURE_SUBSCRIPTION_ID }}
```

### Google Cloud

```yaml
  - uses: google-github-actions/auth@v2
    with:
      workload_identity_provider: projects/123/locations/global/workloadIdentityPools/pool/providers/github
      service_account: deploy@project.iam.gserviceaccount.com
```

## Docker Multi-Stage Build and Push

### Build and Push to GHCR

```yaml
deploy:
  runs-on: ubuntu-latest
  permissions:
    contents: read
    packages: write
  steps:
    - uses: actions/checkout@v4
    - uses: docker/setup-buildx-action@v3
    - uses: docker/login-action@v3
      with:
        registry: ghcr.io
        username: ${{ github.actor }}
        password: ${{ secrets.GITHUB_TOKEN }}
    - uses: docker/metadata-action@v5
      id: meta
      with:
        images: ghcr.io/${{ github.repository }}
        tags: |
          type=sha
          type=ref,event=branch
          type=semver,pattern={{version}}
    - uses: docker/build-push-action@v6
      with:
        context: .
        push: true
        tags: ${{ steps.meta.outputs.tags }}
        labels: ${{ steps.meta.outputs.labels }}
        cache-from: type=gha
        cache-to: type=gha,mode=max
```

### Multi-Platform Docker Build

```yaml
    - uses: docker/setup-qemu-action@v3
    - uses: docker/setup-buildx-action@v3
    - uses: docker/build-push-action@v6
      with:
        platforms: linux/amd64,linux/arm64
        push: true
        tags: ghcr.io/${{ github.repository }}:latest
```

## Environment Promotion

### Staged Deployments

```yaml
deploy-staging:
  runs-on: ubuntu-latest
  environment: staging
  needs: [test]
  steps:
    - run: echo "Deploying to staging"

deploy-production:
  runs-on: ubuntu-latest
  environment: production
  needs: [deploy-staging]
  steps:
    - run: echo "Deploying to production"
```

### Environment-Specific Variables

```yaml
    env:
      APP_URL: ${{ vars.APP_URL }}
      DB_HOST: ${{ secrets.DB_HOST }}
```

## Monorepo Strategies

### Path-Based Triggers

```yaml
on:
  push:
    paths:
      - 'packages/api/**'
      - 'packages/shared/**'
      - '.github/workflows/api.yml'
```

### Detect Changed Packages

```yaml
    - uses: dorny/paths-filter@v3
      id: changes
      with:
        filters: |
          api:
            - 'packages/api/**'
          web:
            - 'packages/web/**'
    - run: echo "API changed"
      if: steps.changes.outputs.api == 'true'
```

## Release Automation

### Create GitHub Release on Tag

```yaml
on:
  push:
    tags: ['v*']
jobs:
  release:
    runs-on: ubuntu-latest
    permissions:
      contents: write
    steps:
      - uses: actions/checkout@v4
      - uses: softprops/action-gh-release@v2
        with:
          generate_release_notes: true
```

### Changelog Generation

```yaml
      - uses: mikepenz/release-changelog-builder-action@v5
        id: changelog
        with:
          configuration: .github/changelog-config.json
        env:
          GITHUB_TOKEN: ${{ secrets.GITHUB_TOKEN }}
```

## Scheduled Workflows

### Dependency Updates Check

```yaml
on:
  schedule:
    - cron: '0 8 * * 1'
jobs:
  audit:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - run: composer audit
      - run: npm audit
```

### Security Scanning

```yaml
on:
  schedule:
    - cron: '0 6 * * *'
jobs:
  trivy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: aquasecurity/trivy-action@master
        with:
          scan-type: fs
          severity: 'HIGH,CRITICAL'
```

## PR Automation

### Auto-Label PRs

```yaml
on:
  pull_request:
    types: [opened, synchronize]
jobs:
  label:
    runs-on: ubuntu-latest
    permissions:
      pull-requests: write
    steps:
      - uses: actions/labeler@v5
        with:
          repo-token: ${{ secrets.GITHUB_TOKEN }}
```

### PR Size Check

```yaml
      - uses: codelytv/pr-size-labeler@v1
        with:
          xs_max_size: 10
          s_max_size: 50
          m_max_size: 200
          l_max_size: 500
```

## Notification Patterns

### Slack Notification on Failure

```yaml
    - uses: slackapi/slack-github-action@v2
      if: failure()
      with:
        webhook: ${{ secrets.SLACK_WEBHOOK }}
        webhook-type: incoming-webhook
        payload: |
          {
            "text": "CI failed on ${{ github.repository }}: ${{ github.server_url }}/${{ github.repository }}/actions/runs/${{ github.run_id }}"
          }
```

## Workflow Debugging

### Enable Debug Logging

Set repository secret `ACTIONS_STEP_DEBUG` to `true` for verbose step output.

### SSH Debug Session

```yaml
    - uses: mxschmitt/action-tmate@v3
      if: failure()
      timeout-minutes: 15
```

## Artifact Management

### Upload on Failure

```yaml
    - uses: actions/upload-artifact@v4
      if: failure()
      with:
        name: debug-logs
        path: |
          storage/logs/
          tests/reports/
        retention-days: 7
```

### Cross-Job Artifacts

```yaml
# Job 1: Build
    - uses: actions/upload-artifact@v4
      with:
        name: build-output
        path: dist/

# Job 2: Deploy (needs build)
    - uses: actions/download-artifact@v4
      with:
        name: build-output
        path: dist/
```