<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import {
  createComment,
  createTicket,
  fetchMe,
  fetchStats,
  fetchTicketById,
  fetchTickets,
  getAuthUser,
  logout,
  updateTicketStatus,
} from './api';

const loading = ref(false);
const saving = ref(false);
const tickets = ref([]);
const selectedTicket = ref(null);
const detailLoading = ref(false);
const statusSaving = ref(false);
const commentSaving = ref(false);
const error = ref('');
const authUser = ref(getAuthUser());
const lastUpdatedAt = ref(null);
const backendStats = ref(null);

const ticketQuery = ref('');
const ticketStatusFilter = ref('all');

const ticketCounts = computed(() => {
  const counts = { total: tickets.value.length, open: 0, in_progress: 0, closed: 0 };

  for (const ticket of tickets.value) {
    if (ticket.status === 'open') counts.open += 1;
    if (ticket.status === 'in_progress') counts.in_progress += 1;
    if (ticket.status === 'closed') counts.closed += 1;
  }

  return counts;
});

const filteredTickets = computed(() => {
  const query = ticketQuery.value.trim().toLowerCase();

  return tickets.value.filter((ticket) => {
    const matchesStatus = ticketStatusFilter.value === 'all' || ticket.status === ticketStatusFilter.value;
    const haystack = `${ticket.title || ''} ${ticket.body || ''} ${ticket.status || ''}`.toLowerCase();
    const matchesQuery = query === '' || haystack.includes(query);

    return matchesStatus && matchesQuery;
  });
});

const selectedCommentCount = computed(() => selectedTicket.value?.comments?.length || 0);
const hasActiveFilters = computed(() => ticketStatusFilter.value !== 'all' || ticketQuery.value.trim() !== '');
const hasAnyTickets = computed(() => ticketCounts.value.total > 0);
const backendCommentCount = computed(() => backendStats.value?.comments?.total ?? 0);
const backendTicketTotal = computed(() => backendStats.value?.tickets?.total ?? ticketCounts.value.total);
const backendOpenCount = computed(() => backendStats.value?.tickets?.open ?? ticketCounts.value.open);
const backendActiveCount = computed(() => backendStats.value?.tickets?.in_progress ?? ticketCounts.value.in_progress);
const backendClosedCount = computed(() => backendStats.value?.tickets?.closed ?? ticketCounts.value.closed);
const backendHealthLabel = computed(() => {
  if (!backendStats.value) {
    return 'Syncing...';
  }

  return 'Backend synced';
});
const lastUpdatedLabel = computed(() => {
  if (!lastUpdatedAt.value) {
    return 'Not refreshed yet';
  }

  return new Date(lastUpdatedAt.value).toLocaleTimeString();
});

function formatActivityTime(value) {
  if (!value) {
    return 'Recently';
  }

  const date = new Date(value);
  if (Number.isNaN(date.getTime())) {
    return 'Recently';
  }

  const minutes = Math.floor((Date.now() - date.getTime()) / 60000);
  if (minutes < 1) return 'Just now';
  if (minutes < 60) return `${minutes}m ago`;

  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours}h ago`;

  const days = Math.floor(hours / 24);
  if (days < 7) return `${days}d ago`;

  return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}

const form = reactive({
  status: 'open',
  title: '',
  body: '',
});

const commentForm = reactive({
  user_id: null,
  commenter_name: 'Guest',
  comment_body: '',
});

async function loadTickets() {
  loading.value = true;
  error.value = '';
  try {
    const [ticketResults, stats] = await Promise.all([
      fetchTickets({
        q: ticketQuery.value.trim(),
        status: ticketStatusFilter.value === 'all' ? '' : ticketStatusFilter.value,
        limit: 50,
      }),
      fetchStats(),
    ]);

    tickets.value = ticketResults;
    backendStats.value = stats;

    if (selectedTicket.value?.id) {
      const refreshedTicket = tickets.value.find((ticket) => ticket.id === selectedTicket.value.id);
      if (refreshedTicket) {
        selectedTicket.value = await fetchTicketById(refreshedTicket.id);
      }
    }

    lastUpdatedAt.value = Date.now();
  } catch (e) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
}

async function submitTicket() {
  if (!authUser.value) {
    error.value = 'Please login first.';
    return;
  }

  if (!form.title.trim() || !form.body.trim()) {
    error.value = 'Please add both a title and description.';
    return;
  }

  saving.value = true;
  error.value = '';
  try {
    const createdTicket = await createTicket({
      status: form.status,
      title: form.title,
      body: form.body,
      submitting_user_id: authUser.value.id,
    });

    form.status = 'open';
    form.title = '';
    form.body = '';

    await loadTickets();
    if (createdTicket?.id) {
      selectedTicket.value = await fetchTicketById(createdTicket.id);
    }
  } catch (e) {
    error.value = e.message;
  } finally {
    saving.value = false;
  }
}

async function selectTicket(id) {
  detailLoading.value = true;
  error.value = '';
  try {
    selectedTicket.value = await fetchTicketById(id);
    commentForm.comment_body = '';
  } catch (e) {
    error.value = e.message;
  } finally {
    detailLoading.value = false;
  }
}

async function saveStatus() {
  if (!selectedTicket.value) {
    return;
  }

  statusSaving.value = true;
  error.value = '';
  try {
    selectedTicket.value = await updateTicketStatus(selectedTicket.value.id, selectedTicket.value.status);
    await loadTickets();
  } catch (e) {
    error.value = e.message;
  } finally {
    statusSaving.value = false;
  }
}

async function submitComment() {
  if (!selectedTicket.value) {
    return;
  }

  if (!authUser.value) {
    error.value = 'Please login first.';
    return;
  }

  if (!commentForm.comment_body.trim()) {
    error.value = 'Please enter a comment before sending.';
    return;
  }

  commentSaving.value = true;
  error.value = '';
  try {
    await createComment(selectedTicket.value.id, commentForm);
    commentForm.comment_body = '';
    selectedTicket.value = await fetchTicketById(selectedTicket.value.id);
    await loadTickets();
  } catch (e) {
    error.value = e.message;
  } finally {
    commentSaving.value = false;
  }
}

async function restoreAuth() {
  if (!authUser.value) {
    return;
  }

  try {
    authUser.value = await fetchMe();
    commentForm.commenter_name = authUser.value.username;
  } catch {
    logout();
    authUser.value = null;
  }
}

function signOut() {
  logout();
  authUser.value = null;
  selectedTicket.value = null;
  window.location.href = '/pages/login.html';
}

async function refreshTickets() {
  await loadTickets();
}

function resetTicketDraft() {
  form.status = 'open';
  form.title = '';
  form.body = '';
}

async function clearFilters() {
  ticketQuery.value = '';
  ticketStatusFilter.value = 'all';
  selectedTicket.value = null;
  await refreshTickets();
}

async function filterByStatus(status) {
  ticketQuery.value = '';
  ticketStatusFilter.value = status;
  selectedTicket.value = null;
  await loadTickets();
}

onMounted(async () => {
  await restoreAuth();
  if (!authUser.value) {
    return;
  }

  await loadTickets();
});
</script>

<template>
  <main class="page app-page">
    <nav class="dashboard-nav" aria-label="Dashboard navigation">
      <a class="page-brand" href="/pages/home.html">
        <span class="brand-mark" aria-hidden="true">S</span>
        Support Desk
      </a>
      <div class="dashboard-nav-actions">
        <span v-if="authUser" class="user-chip">{{ authUser.username }}</span>
        <button class="icon-button" type="button" title="Refresh tickets" aria-label="Refresh tickets" :disabled="loading" @click="refreshTickets">
          ↻
        </button>
      </div>
    </nav>
    <header class="app-hero">
      <div>
        <p class="hero-kicker">Your support workspace</p>
        <h1 class="welcome-message">How can we help today?</h1>
        <p class="lead">
          Keep every request, update, and conversation in one calm place.
        </p>
        <div class="team-note">
          <span class="team-avatar" aria-hidden="true">SD</span>
          <p>
            <strong>{{ authUser ? `Good to see you, ${authUser.username}.` : 'A friendly place to get help.' }}</strong>
            We’ll keep the conversation clear from first message to resolution.
          </p>
        </div>
        <div class="cta-row">
          <a class="page-link secondary-cta" href="/pages/home.html">Home</a>
          <a class="page-link secondary-cta" href="/pages/about.html">About</a>
          <a class="page-link secondary-cta" href="/pages/contact.html">Contact</a>
          <button class="secondary-action" type="button" :disabled="loading" @click="refreshTickets">
            {{ loading ? 'Refreshing...' : 'Refresh' }}
          </button>
        </div>
      </div>
      <aside class="hero-card">
        <p class="hero-kicker">At a glance</p>
        <div class="hero-stats">
          <article class="mini-stat">
            <strong>{{ backendTicketTotal }}</strong>
            <span>Total</span>
          </article>
          <button class="mini-stat stat-filter" type="button" :class="{ 'is-active': ticketStatusFilter === 'open' }" @click="filterByStatus('open')">
            <strong>{{ backendOpenCount }}</strong>
            <span>Open</span>
          </button>
          <button class="mini-stat stat-filter" type="button" :class="{ 'is-active': ticketStatusFilter === 'in_progress' }" @click="filterByStatus('in_progress')">
            <strong>{{ backendActiveCount }}</strong>
            <span>Active</span>
          </button>
          <button class="mini-stat stat-filter" type="button" :class="{ 'is-active': ticketStatusFilter === 'closed' }" @click="filterByStatus('closed')">
            <strong>{{ backendClosedCount }}</strong>
            <span>Closed</span>
          </button>
        </div>
        <p class="meta snapshot-note">{{ backendHealthLabel }} · {{ backendCommentCount }} replies shared · Updated {{ lastUpdatedLabel }}</p>
      </aside>
    </header>

    <section class="panel auth-panel">
      <div class="auth-header">
        <h2>{{ authUser ? 'You’re signed in' : 'Welcome back' }}</h2>
        <span class="auth-badge">{{ authUser ? 'Dashboard access' : 'Account access' }}</span>
      </div>
      <p v-if="authUser" class="meta">Logged in as {{ authUser.username }} (ID {{ authUser.id }})</p>
      <p v-else class="meta">Sign in to see your support requests, or create an account to get started.</p>
      <div v-if="authUser" class="auth-signed-in">
        <p class="meta">Everything you need is here. Pick up where you left off or start a new conversation with the team.</p>
        <div class="cta-row">
          <button type="button" :disabled="loading" @click="refreshTickets">
            {{ loading ? 'Refreshing...' : 'Refresh tickets' }}
          </button>
          <button class="secondary-action" type="button" @click="signOut">Logout</button>
        </div>
      </div>
      <div v-else class="cta-row">
        <a class="signup-link primary-cta" href="/pages/login.html">Sign in</a>
        <a class="page-link secondary-cta" href="/pages/register.html">Create account</a>
      </div>
    </section>

    <div v-if="authUser" class="dashboard-grid">
      <section class="panel">
        <div class="section-heading">
          <div>
            <p class="hero-kicker">Need a hand?</p>
            <h2>Start a conversation</h2>
            <p class="section-description">A few clear details help the team give you a useful answer sooner.</p>
          </div>
          <button class="secondary-action" type="button" @click="resetTicketDraft">Reset</button>
        </div>
        <form class="ticket-form" @submit.prevent="submitTicket">
          <div class="row">
            <label>
              Status
              <select v-model="form.status">
                <option value="open">Open</option>
                <option value="in_progress">In Progress</option>
                <option value="closed">Closed</option>
              </select>
            </label>
          </div>
          <div class="row">
            <label>
              Title
              <input v-model="form.title" type="text" placeholder="Issue summary" />
            </label>
          </div>
          <div class="row">
            <label>
              Body
              <textarea v-model="form.body" placeholder="Describe the issue"></textarea>
            </label>
          </div>
          <button :disabled="saving" type="submit">
            {{ saving ? 'Saving...' : 'Submit Ticket' }}
          </button>
        </form>
      </section>

      <section class="panel ticket-list-panel">
        <div class="section-heading">
          <div>
            <p class="hero-kicker">Ticket feed</p>
            <h2>Recent conversations</h2>
            <p class="section-description">The latest questions and updates from your support space.</p>
          </div>
          <button class="secondary-action" type="button" :disabled="loading" @click="refreshTickets">
            {{ loading ? 'Refreshing...' : 'Refresh' }}
          </button>
        </div>
        <div class="toolbar">
          <input v-model="ticketQuery" type="search" placeholder="Search tickets" @keyup.enter="refreshTickets" />
          <select v-model="ticketStatusFilter" @change="refreshTickets">
            <option value="all">All statuses</option>
            <option value="open">Open</option>
            <option value="in_progress">In Progress</option>
            <option value="closed">Closed</option>
          </select>
        </div>
        <div class="cta-row" v-if="hasActiveFilters">
          <button class="secondary-action" type="button" @click="clearFilters">Clear filters</button>
          <button type="button" @click="refreshTickets">Search</button>
        </div>
        <p class="meta">Showing {{ filteredTickets.length }} of {{ ticketCounts.total }} tickets</p>
        <p v-if="loading" class="meta">Loading tickets...</p>
        <p v-if="error" class="auth-error">{{ error }}</p>
        <div v-if="filteredTickets.length" class="list">
          <article v-for="ticket in filteredTickets" :key="ticket.id" class="ticket" :class="{ 'is-selected': selectedTicket?.id === ticket.id }">
            <div class="ticket-head">
              <h3>{{ ticket.title }}</h3>
              <span class="status-pill" :class="ticket.status">{{ ticket.status.replace('_', ' ') }}</span>
            </div>
            <p>{{ ticket.body }}</p>
            <p class="meta">Conversation #{{ ticket.id }} · Started by member {{ ticket.submitting_user_id }} · {{ formatActivityTime(ticket.created) }}</p>
            <div class="ticket-actions">
              <button class="view-ticket-button" type="button" @click="selectTicket(ticket.id)">
                {{ selectedTicket?.id === ticket.id ? 'Viewing ticket' : 'View ticket' }}
              </button>
            </div>
          </article>
        </div>
        <div v-else class="empty-state">
          <h3>{{ hasAnyTickets ? 'No tickets match this view.' : 'No tickets yet.' }}</h3>
          <p class="meta">
            {{ hasAnyTickets ? 'Try clearing filters or search to see more results.' : 'Create your first ticket using the form on the left.' }}
          </p>
        </div>
      </section>

      <section class="panel ticket-detail-panel" v-if="selectedTicket">
        <div class="section-heading">
          <div>
            <p class="hero-kicker">Ticket detail</p>
            <h2>Ticket #{{ selectedTicket.id }}</h2>
          </div>
          <button class="secondary-action" type="button" @click="selectedTicket = null">Close</button>
        </div>
        <p v-if="detailLoading" class="meta">Loading detail...</p>
        <template v-else>
          <div class="ticket-detail-grid">
            <div>
              <h3>{{ selectedTicket.title }}</h3>
              <p>{{ selectedTicket.body }}</p>
              <p class="meta">User {{ selectedTicket.submitting_user_id }}</p>
            </div>
            <div class="ticket-summary-card">
              <p class="hero-kicker">Status</p>
              <div class="status-row">
                <span class="status-pill" :class="selectedTicket.status">{{ selectedTicket.status.replace('_', ' ') }}</span>
                <span class="meta">{{ selectedCommentCount }} comment{{ selectedCommentCount === 1 ? '' : 's' }}</span>
              </div>
              <label>
                Update status
                <select v-model="selectedTicket.status">
                  <option value="open">Open</option>
                  <option value="in_progress">In Progress</option>
                  <option value="closed">Closed</option>
                </select>
              </label>
              <button :disabled="statusSaving" @click="saveStatus">
                {{ statusSaving ? 'Saving...' : 'Update Status' }}
              </button>
            </div>
          </div>

          <hr />

          <h3>Comments</h3>
          <div class="list" v-if="selectedTicket.comments?.length">
            <article v-for="comment in selectedTicket.comments" :key="comment.id" class="ticket comment-card">
              <div class="comment-heading">
                <strong>{{ comment.commenter_name }}</strong>
                <span class="meta">{{ formatActivityTime(comment.created) }}</span>
              </div>
              <p>{{ comment.comment_body }}</p>
            </article>
          </div>
          <p v-else class="meta">No comments yet.</p>

          <div class="row">
            <p class="meta">Commenting as {{ authUser?.username || 'Guest' }}</p>
          </div>
          <div class="row">
            <label>
              Comment
              <textarea v-model="commentForm.comment_body" placeholder="Add a reply"></textarea>
            </label>
          </div>
          <button :disabled="commentSaving" @click="submitComment">
            {{ commentSaving ? 'Saving...' : 'Add Comment' }}
          </button>
        </template>
      </section>
    </div>
  </main>
</template>
