# Security Testing

> Reference for: Test Master
> Load when: Security tests, vulnerability testing

## Authentication Tests

```typescript
describe('Authentication Security', () => {
  it('rejects invalid credentials', async () => {
    await request(app)
      .post('/api/login')
      .send({ email: 'user@test.com', password: 'wrong' })
      .expect(401);
  });

  it('rejects expired tokens', async () => {
    const expiredToken = createExpiredToken();
    await request(app)
      .get('/api/protected')
      .set('Authorization', `Bearer ${expiredToken}`)
      .expect(401);
  });

  it('enforces rate limiting on login', async () => {
    for (let i = 0; i < 6; i++) {
      await request(app)
        .post('/api/login')
        .send({ email: 'user@test.com', password: 'wrong' });
    }
    await request(app)
      .post('/api/login')
      .send({ email: 'user@test.com', password: 'correct' })
      .expect(429);
  });
});
```

## Security Test Checklist

| Category | Tests |
|----------|-------|
| **Auth** | Invalid creds, token expiry, tampering |
| **Input** | SQL injection, XSS, command injection |
| **Access** | IDOR, privilege escalation |
| **Rate Limit** | Brute force, API abuse |
| **Headers** | CSP, HSTS, X-Frame-Options |
