<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import {
  createComment,
  createTicket,
  fetchMe,
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
const redirectingToLogin = ref(false);

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
    tickets.value = await fetchTickets();
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
    redirectingToLogin.value = true;
    window.location.href = '/pages/login.html';
    return;
  }

  try {
    authUser.value = await fetchMe();
    commentForm.commenter_name = authUser.value.username;
  } catch {
    logout();
    authUser.value = null;
    redirectingToLogin.value = true;
    window.location.href = '/pages/login.html';
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
    <header class="app-hero">
      <div>
        <p class="hero-kicker">Support Desk</p>
        <h1 class="welcome-message">Ticket dashboard</h1>
        <p class="lead">
          Open, filter, update, and discuss support issues from a single dashboard.
        </p>
        <div class="cta-row">
          <a class="page-link secondary-cta" href="/pages/home.html">Home</a>
          <a class="page-link secondary-cta" href="/pages/about.html">About</a>
          <a class="page-link secondary-cta" href="/pages/contact.html">Contact</a>
          <button class="secondary-action" type="button" @click="refreshTickets">Refresh</button>
        </div>
      </div>
      <aside class="hero-card">
        <p class="hero-kicker">Ticket snapshot</p>
        <div class="hero-stats">
          <article class="mini-stat">
            <strong>{{ ticketCounts.total }}</strong>
            <span>Total</span>
          </article>
          <article class="mini-stat">
            <strong>{{ ticketCounts.open }}</strong>
            <span>Open</span>
          </article>
          <article class="mini-stat">
            <strong>{{ ticketCounts.in_progress }}</strong>
            <span>Active</span>
          </article>
          <article class="mini-stat">
            <strong>{{ ticketCounts.closed }}</strong>
            <span>Closed</span>
          </article>
        </div>
      </aside>
    </header>

    <section class="panel auth-panel">
      <div class="auth-header">
        <h2>Signed in</h2>
        <span class="auth-badge">Dashboard only</span>
      </div>
      <p v-if="authUser" class="meta">Logged in as {{ authUser.username }} (ID {{ authUser.id }})</p>
      <div class="auth-signed-in">
        <p class="meta">Manage tickets and comments from this page. Authentication is handled on the login page.</p>
        <div class="cta-row">
          <button type="button" @click="refreshTickets">Refresh tickets</button>
          <button class="secondary-action" type="button" @click="signOut">Logout</button>
        </div>
      </div>
    </section>

    <div class="dashboard-grid">
      <section class="panel">
        <div class="section-heading">
          <div>
            <p class="hero-kicker">Create ticket</p>
            <h2>Open a support issue</h2>
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
            <h2>Recent tickets</h2>
          </div>
          <button class="secondary-action" type="button" @click="refreshTickets">Refresh</button>
        </div>
        <div class="toolbar">
          <input v-model="ticketQuery" type="search" placeholder="Search tickets" />
          <select v-model="ticketStatusFilter">
            <option value="all">All statuses</option>
            <option value="open">Open</option>
            <option value="in_progress">In Progress</option>
            <option value="closed">Closed</option>
          </select>
        </div>
        <p class="meta">Showing {{ filteredTickets.length }} of {{ ticketCounts.total }} tickets</p>
        <p v-if="loading" class="meta">Loading tickets...</p>
        <p v-if="error" class="auth-error">{{ error }}</p>
        <div v-if="filteredTickets.length" class="list">
          <article v-for="ticket in filteredTickets" :key="ticket.id" class="ticket">
            <div class="ticket-head">
              <h3>{{ ticket.title }}</h3>
              <span class="status-pill" :class="ticket.status">{{ ticket.status.replace('_', ' ') }}</span>
            </div>
            <p>{{ ticket.body }}</p>
            <p class="meta">#{{ ticket.id }} | User {{ ticket.submitting_user_id }}</p>
            <div class="ticket-actions">
              <button type="button" @click="selectTicket(ticket.id)">View Ticket</button>
            </div>
          </article>
        </div>
        <div v-else class="empty-state">
          <h3>No tickets match this view.</h3>
          <p class="meta">Try clearing the search or creating a new ticket.</p>
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
              <strong>{{ comment.commenter_name }}</strong>
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
</template><script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import {
  createComment,
  createTicket,
  fetchMe,
  fetchTicketById,
  fetchTickets,
  getAuthUser,
  login,
  logout,
  register,
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

const loginForm = reactive({
  username: 'demo',
  password: 'demo',
});
const loginSaving = ref(false);
const registerMode = ref(false);

const registerForm = reactive({
  username: '',
  password: '',
  passwordConfirm: '',
});
const registerSaving = ref(false);

const registerChecks = computed(() => {
  const password = registerForm.password || '';
  const passwordConfirm = registerForm.passwordConfirm || '';

  return {
    minLength: password.length >= 8,
    hasLetter: /[A-Za-z]/.test(password),
    hasNumber: /\d/.test(password),
    matches: password !== '' && password === passwordConfirm,
  };
});

const ticketQuery = ref('');
const ticketStatusFilter = ref('all');

const ticketCounts = computed(() => {
  const counts = {
    total: tickets.value.length,
    open: 0,
    in_progress: 0,
    closed: 0,
  };

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
const isAuthenticated = computed(() => Boolean(authUser.value));

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
    tickets.value = await fetchTickets();
  } catch (e) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
}

async function submitTicket() {
  if (!authUser.value) {
    error.value = 'Please login first';
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
    error.value = 'Please login first';
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

async function submitLogin() {
  loginSaving.value = true;
  error.value = '';
  try {
    authUser.value = await login(loginForm.username, loginForm.password);
    commentForm.commenter_name = authUser.value.username;
    window.location.href = '/pages/app.html';
  } catch (e) {
    error.value = e.message;
  } finally {
    loginSaving.value = false;
  }
}

async function submitRegister() {
  if (registerForm.password !== registerForm.passwordConfirm) {
    error.value = 'Passwords do not match';
    return;
  }

  registerSaving.value = true;
  error.value = '';
  try {
    authUser.value = await register(registerForm.username, registerForm.password);
    commentForm.commenter_name = authUser.value.username;
    window.location.href = '/pages/app.html';
  } catch (e) {
    error.value = e.message;
  } finally {
    registerSaving.value = false;
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
}

async function refreshTickets() {
  await loadTickets();
}

function resetTicketDraft() {
  form.status = 'open';
  form.title = '';
  form.body = '';
}

onMounted(async () => {
  const path = window.location.pathname.toLowerCase();
  registerMode.value = path.endsWith('/register.html');

  await restoreAuth();
  await loadTickets();
});
</script>

<template>
  <main class="page app-page">
    <header class="app-hero">
      <div>
        <p class="hero-kicker">Support Desk</p>
        <h1 class="welcome-message">{{ registerMode ? 'Create your account' : 'Support tickets, simplified' }}</h1>
        <p class="lead">
          {{ registerMode ? 'Register once, then jump into ticket tracking and replies.' : 'A single place to open, filter, update, and discuss support issues.' }}
        </p>
        <div class="cta-row">
          <a class="page-link secondary-cta" href="/pages/home.html">Home</a>
          <a class="page-link secondary-cta" href="/pages/about.html">About</a>
          <a class="page-link secondary-cta" href="/pages/contact.html">Contact</a>
        </div>
      </div>
      <aside class="hero-card">
        <p class="hero-kicker">Ticket snapshot</p>
        <div class="hero-stats">
          <article class="mini-stat">
            <strong>{{ ticketCounts.total }}</strong>
            <span>Total</span>
          </article>
          <article class="mini-stat">
            <strong>{{ ticketCounts.open }}</strong>
            <span>Open</span>
          </article>
          <article class="mini-stat">
            <strong>{{ ticketCounts.in_progress }}</strong>
            <span>Active</span>
          </article>
          <article class="mini-stat">
            <strong>{{ ticketCounts.closed }}</strong>
            <span>Closed</span>
          </article>
        </div>
      </aside>
    </header>

    <section class="panel auth-panel">
      <div class="auth-header">
        <h2>{{ registerMode ? 'Create Your Account' : 'Sign In' }}</h2>
        <span class="auth-badge">Secure Access</span>
      </div>
      <p v-if="authUser" class="meta">Logged in as {{ authUser.username }} (ID {{ authUser.id }})</p>
      <div v-if="!authUser">
        <div class="auth-shell">
          <div>
            <p v-if="error" class="auth-error">{{ error }}</p>
            <p class="auth-subtitle">
              {{ registerMode ? 'Create your account to start opening tickets and tracking replies.' : 'Sign in to create tickets and comment.' }}
            </p>
            <form v-if="!registerMode" class="row auth-grid" @submit.prevent="submitLogin">
              <label>
                Username
                <input v-model="loginForm.username" type="text" autocomplete="username" placeholder="your.username" required />
              </label>
              <label>
                Password
                <input v-model="loginForm.password" type="password" autocomplete="current-password" placeholder="Enter password" required />
              </label>
              <div class="auth-actions auth-actions-inline">
                <button type="submit" :disabled="loginSaving">
                  {{ loginSaving ? 'Signing in...' : 'Login' }}
                </button>
              </div>
            </form>
            <form v-else class="row auth-grid" @submit.prevent="submitRegister">
              <label>
                Username
                <input v-model="registerForm.username" type="text" autocomplete="username" placeholder="Pick a username" required />
              </label>
              <label>
                Password
                <input v-model="registerForm.password" type="password" autocomplete="new-password" placeholder="Create a password" required />
              </label>
              <label>
                Confirm Password
                <input v-model="registerForm.passwordConfirm" type="password" autocomplete="new-password" placeholder="Re-enter password" required />
              </label>
              <ul class="password-checks">
                <li :class="{ pass: registerChecks.minLength }">At least 8 characters</li>
                <li :class="{ pass: registerChecks.hasLetter }">Includes a letter</li>
                <li :class="{ pass: registerChecks.hasNumber }">Includes a number</li>
                <li :class="{ pass: registerChecks.matches }">Passwords match</li>
              </ul>
              <div class="auth-actions auth-actions-inline">
                <button type="submit" :disabled="registerSaving">
                  {{ registerSaving ? 'Creating...' : 'Create Account' }}
                </button>
              </div>
            </form>
            <a class="auth-switch" v-if="!registerMode" href="/pages/register.html">Need an account?</a>
            <a class="auth-switch" v-else href="/pages/app.html">Back to ticket app login</a>
          </div>
          <aside class="auth-info">
            <h3>{{ registerMode ? 'Why Create an Account?' : 'Welcome Back' }}</h3>
            <ul>
              <li>Open and track ticket status updates.</li>
              <li>Comment directly on ticket threads.</li>
              <li>Keep your support history in one place.</li>
            </ul>
          </aside>
        </div>
      </div>
      <div v-else>
        <div class="auth-signed-in">
          <p class="meta">Account ready. You can manage tickets right away.</p>
          <div class="cta-row">
            <button @click="refreshTickets">Refresh tickets</button>
            <button class="secondary-action" @click="signOut">Logout</button>
            <a v-if="registerMode" class="page-link secondary-cta" href="/pages/app.html">Go to ticket app</a>
          </div>
        </div>
      </div>
    </section>

    <div v-if="!registerMode" class="dashboard-grid">
      <section class="panel">
        <div class="section-heading">
          <div>
            <p class="hero-kicker">Create ticket</p>
            <h2>Open a support issue</h2>
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
            <h2>Recent tickets</h2>
          </div>
          <button class="secondary-action" type="button" @click="refreshTickets">Refresh</button>
        </div>
        <div class="toolbar">
          <input v-model="ticketQuery" type="search" placeholder="Search tickets" />
          <select v-model="ticketStatusFilter">
            <option value="all">All statuses</option>
            <option value="open">Open</option>
            <option value="in_progress">In Progress</option>
            <option value="closed">Closed</option>
          </select>
        </div>
        <p class="meta">Showing {{ filteredTickets.length }} of {{ ticketCounts.total }} tickets</p>
        <p v-if="loading" class="meta">Loading tickets...</p>
        <p v-if="error" class="auth-error">{{ error }}</p>
        <div v-if="filteredTickets.length" class="list">
          <article v-for="ticket in filteredTickets" :key="ticket.id" class="ticket">
            <div class="ticket-head">
              <h3>{{ ticket.title }}</h3>
              <span class="status-pill" :class="ticket.status">{{ ticket.status.replace('_', ' ') }}</span>
            </div>
            <p>{{ ticket.body }}</p>
            <p class="meta">#{{ ticket.id }} | User {{ ticket.submitting_user_id }}</p>
            <div class="ticket-actions">
              <button type="button" @click="selectTicket(ticket.id)">View Ticket</button>
            </div>
          </article>
        </div>
        <div v-else class="empty-state">
          <h3>No tickets match this view.</h3>
          <p class="meta">Try clearing the search or creating a new ticket.</p>
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
              <strong>{{ comment.commenter_name }}</strong>
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
