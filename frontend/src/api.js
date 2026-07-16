const API_BASE = import.meta.env.VITE_API_BASE_URL || '';
const AUTH_STORAGE_KEY = 'support_auth';

function extractErrorMessage(payload, fallback) {
  if (!payload || typeof payload !== 'object') {
    return fallback;
  }

  if (payload.message) {
    return payload.message;
  }

  if (payload.errors && typeof payload.errors === 'object') {
    return JSON.stringify(payload.errors);
  }

  return fallback;
}

function readAuth() {
  const raw = localStorage.getItem(AUTH_STORAGE_KEY);
  if (!raw) {
    return { token: null, user: null };
  }

  try {
    return JSON.parse(raw);
  } catch {
    return { token: null, user: null };
  }
}

function writeAuth(auth) {
  localStorage.setItem(AUTH_STORAGE_KEY, JSON.stringify(auth));
}

function authHeaders(headers = {}) {
  const { token } = readAuth();
  return token
    ? {
        ...headers,
        Authorization: `Bearer ${token}`,
      }
    : headers;
}

export function getAuthUser() {
  return readAuth().user;
}

export function logout() {
  localStorage.removeItem(AUTH_STORAGE_KEY);
}

function requireAuthData(payload, fallbackMessage) {
  if (!payload || !payload.success || !payload.data || !payload.data.token || !payload.data.user) {
    throw new Error(payload?.message || fallbackMessage);
  }

  return payload.data;
}

export async function login(username, password) {
  const res = await fetch(`${API_BASE}/api/auth/login.json`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ username, password }),
  });

  const payload = await res.json();
  if (!res.ok) {
    throw new Error(payload.message || 'Login failed');
  }

  const authData = requireAuthData(payload, 'Login succeeded but no JWT token was returned');
  writeAuth(authData);
  return authData.user;
}

export async function register(username, password) {
  const res = await fetch(`${API_BASE}/api/auth/register.json`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ username, password }),
  });

  const payload = await res.json();
  if (!res.ok) {
    throw new Error(payload.message || 'Registration failed');
  }

  const authData = requireAuthData(payload, 'Registration succeeded but no JWT token was returned');
  writeAuth(authData);
  return authData.user;
}

export async function fetchMe() {
  const res = await fetch(`${API_BASE}/api/auth/me.json`, {
    headers: authHeaders(),
  });

  const payload = await res.json();
  if (!res.ok || !payload.success) {
    throw new Error(payload.message || 'Not authenticated');
  }

  const auth = readAuth();
  writeAuth({ ...auth, user: payload.data });
  return payload.data;
}

export async function fetchTickets() {
  const res = await fetch(`${API_BASE}/api/tickets.json`);
  if (!res.ok) {
    throw new Error('Failed to load tickets');
  }
  const payload = await res.json();
  return payload.data || [];
}

export async function createTicket(input) {
  const res = await fetch(`${API_BASE}/api/tickets.json`, {
    method: 'POST',
    headers: authHeaders({
      'Content-Type': 'application/json',
    }),
    body: JSON.stringify(input),
  });

  const payload = await res.json();
  if (!res.ok || !payload.success) {
    throw new Error(extractErrorMessage(payload, 'Ticket save failed'));
  }

  return payload.data;
}

export async function fetchTicketById(id) {
  const res = await fetch(`${API_BASE}/api/tickets/${id}.json`);
  const payload = await res.json();
  if (!res.ok || !payload.success) {
    throw new Error(extractErrorMessage(payload, 'Failed to load ticket'));
  }
  return payload.data;
}

export async function updateTicketStatus(id, status) {
  const res = await fetch(`${API_BASE}/api/tickets/${id}/status.json`, {
    method: 'PATCH',
    headers: authHeaders({
      'Content-Type': 'application/json',
    }),
    body: JSON.stringify({ status }),
  });

  const payload = await res.json();
  if (!res.ok || !payload.success) {
    throw new Error(extractErrorMessage(payload, 'Failed to update status'));
  }

  return payload.data;
}

export async function createComment(ticketId, input) {
  const res = await fetch(`${API_BASE}/api/tickets/${ticketId}/comments.json`, {
    method: 'POST',
    headers: authHeaders({
      'Content-Type': 'application/json',
    }),
    body: JSON.stringify(input),
  });

  const payload = await res.json();
  if (!res.ok || !payload.success) {
    throw new Error(extractErrorMessage(payload, 'Failed to add comment'));
  }

  return payload.data;
}
