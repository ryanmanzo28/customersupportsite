const API_BASE = import.meta.env.VITE_API_BASE_URL || '';

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
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(input),
  });

  const payload = await res.json();
  if (!res.ok || !payload.success) {
    throw new Error('Ticket save failed');
  }

  return payload.data;
}

export async function fetchTicketById(id) {
  const res = await fetch(`${API_BASE}/api/tickets/${id}.json`);
  const payload = await res.json();
  if (!res.ok || !payload.success) {
    throw new Error(payload.message || 'Failed to load ticket');
  }
  return payload.data;
}

export async function updateTicketStatus(id, status) {
  const res = await fetch(`${API_BASE}/api/tickets/${id}/status.json`, {
    method: 'PATCH',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({ status }),
  });

  const payload = await res.json();
  if (!res.ok || !payload.success) {
    throw new Error(payload.message || 'Failed to update status');
  }

  return payload.data;
}

export async function createComment(ticketId, input) {
  const res = await fetch(`${API_BASE}/api/tickets/${ticketId}/comments.json`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(input),
  });

  const payload = await res.json();
  if (!res.ok || !payload.success) {
    throw new Error(payload.message || 'Failed to add comment');
  }

  return payload.data;
}
