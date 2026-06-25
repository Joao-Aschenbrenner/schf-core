import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

const errorRate = new Rate('errors');
const BASE_URL = __ENV.BASE_URL || 'http://localhost:8080';
const TEST_EMAIL = __ENV.TEST_EMAIL || 'admin@schf.local';
const TEST_PASSWORD = __ENV.TEST_PASSWORD || 'changeme';

export const options = {
  stages: [
    { duration: '1m', target: 50 },    // Ramp up to 50 VUs (stress)
    { duration: '2m', target: 50 },    // Stay at 50 VUs
    { duration: '30s', target: 100 },  // Spike to 100 VUs
    { duration: '1m', target: 100 },   // Stay at spike
    { duration: '30s', target: 0 },    // Ramp down
  ],
  thresholds: {
    http_req_duration: ['p(95)<1000'],  // 95% under 1s under stress
    http_req_failed: ['rate<0.05'],     // Less than 5% errors under stress
  },
};

export default function () {
  const healthRes = http.get(`${BASE_URL}/api/health`);
  check(healthRes, {
    'health status is 200': (r) => r.status === 200,
  });
  errorRate.add(healthRes.status !== 200);

  sleep(0.2);

// Login
  const loginRes = http.post(`${BASE_URL}/api/auth/login`, JSON.stringify({
    email: TEST_EMAIL,
    password: TEST_PASSWORD,
  }), {
    headers: { 'Content-Type': 'application/json' },
  });

  const token = loginRes.status === 200 ? loginRes.json('token') : null;

  if (token) {
    const authHeaders = {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json',
      },
    };

    // Concurrent reads
    http.get(`${BASE_URL}/api/dashboard/summary`, authHeaders);
    http.get(`${BASE_URL}/api/suppliers`, authHeaders);
    http.get(`${BASE_URL}/api/health-plans`, authHeaders);
    http.get(`${BASE_URL}/api/payables`, authHeaders);
    http.get(`${BASE_URL}/api/nfes`, authHeaders);
    http.get(`${BASE_URL}/api/audit-trail`, authHeaders);
  }

  sleep(0.2);
}

