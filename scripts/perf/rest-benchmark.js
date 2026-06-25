import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate, Trend } from 'k6/metrics';

const errorRate = new Rate('errors');
const httpDuration = new Trend('http_duration');

const BASE_URL = __ENV.BASE_URL || 'http://nginx';

export const options = {
  stages: [
    { duration: '30s', target: 10 },   // Ramp up to 10 VUs
    { duration: '1m', target: 10 },    // Stay at 10 VUs
    { duration: '30s', target: 0 },    // Ramp down
  ],
  thresholds: {
    http_req_duration: ['p(95)<500'],  // 95% of requests under 500ms
    http_req_failed: ['rate<0.01'],    // Less than 1% errors
    errors: ['rate<0.01'],
  },
};

const TEST_EMAIL = __ENV.TEST_EMAIL || 'admin@schf.local';
const TEST_PASSWORD = __ENV.TEST_PASSWORD || 'changeme';

export default function () {
  // Health check
  const healthRes = http.get(`${BASE_URL}/api/health`);
  check(healthRes, {
    'health status is 200': (r) => r.status === 200,
  });
  httpDuration.add(healthRes.timings.duration);
  errorRate.add(healthRes.status !== 200);

  sleep(1);

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

    // Dashboard
    const dashRes = http.get(`${BASE_URL}/api/dashboard/summary`, authHeaders);
    check(dashRes, {
      'dashboard status is 200': (r) => r.status === 200,
    });
    httpDuration.add(dashRes.timings.duration);
    errorRate.add(dashRes.status !== 200);

    sleep(0.5);

    // List suppliers
    const suppliersRes = http.get(`${BASE_URL}/api/suppliers`, authHeaders);
    check(suppliersRes, {
      'suppliers status is 200': (r) => r.status === 200,
    });
    httpDuration.add(suppliersRes.timings.duration);
    errorRate.add(suppliersRes.status !== 200);

    sleep(0.5);

    // List health plans
    const plansRes = http.get(`${BASE_URL}/api/health-plans`, authHeaders);
    check(plansRes, {
      'health plans status is 200': (r) => r.status === 200,
    });
    httpDuration.add(plansRes.timings.duration);
    errorRate.add(plansRes.status !== 200);

    sleep(0.5);

    // List payables
    const payablesRes = http.get(`${BASE_URL}/api/payables`, authHeaders);
    check(payablesRes, {
      'payables status is 200': (r) => r.status === 200,
    });
    httpDuration.add(payablesRes.timings.duration);
    errorRate.add(payablesRes.status !== 200);

    sleep(0.5);

    // List NF-e
    const nfesRes = http.get(`${BASE_URL}/api/nfes`, authHeaders);
    check(nfesRes, {
      'nfes status is 200': (r) => r.status === 200,
    });
    httpDuration.add(nfesRes.timings.duration);
    errorRate.add(nfesRes.status !== 200);
  }

  sleep(1);
}

export function handleSummary(data) {
  return {
    'scripts/perf/results/rest-summary.json': JSON.stringify(data, null, 2),
    stdout: textSummary(data, { indent: ' ', enableColors: true }),
  };
}

function textSummary(data, options) {
  const lines = [];
  lines.push('');
  lines.push('â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•');
  lines.push('  SCHF â€” REST API Benchmark');
  lines.push('â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•');
  lines.push('');

  if (data.metrics.http_req_duration) {
    const d = data.metrics.http_req_duration.values;
    lines.push(`  HTTP Duration:`);
    lines.push(`    Avg: ${d.avg?.toFixed(2)}ms`);
    lines.push(`    P50: ${d.med?.toFixed(2)}ms`);
    lines.push(`    P95: ${d['p(95)']?.toFixed(2)}ms`);
    lines.push(`    P99: ${d['p(99)']?.toFixed(2)}ms`);
    lines.push(`    Max: ${d.max?.toFixed(2)}ms`);
  }

  if (data.metrics.http_req_failed) {
    lines.push(`  Failed: ${(data.metrics.http_req_failed.values.rate * 100).toFixed(2)}%`);
  }

  if (data.metrics.http_reqs) {
    lines.push(`  Total Requests: ${data.metrics.http_reqs.values.count}`);
    lines.push(`  RPS: ${data.metrics.http_reqs.values.rate?.toFixed(2)}`);
  }

  lines.push('');
  return lines.join('\n');
}


