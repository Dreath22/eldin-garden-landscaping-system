/**
 * UserAPI.js — Centralized API utility for all user operations.
 *
 * Every method routes through the single controller:
 *   /landscape/USER_API/UsersController.php?action=<action>
 *
 * Parameter keys are verified against UsersController.php and admin_user.js
 * to prevent silent mismatches.
 *
 * Usage (ES module):
 *   import UserAPI from './utils/UserAPI.js'
 *   const data = await UserAPI.list({ page: 1, status: 'active' })
 */

const BASE_URL = '/landscape/USER_API/UsersController.php'

// ─── Internal helpers ─────────────────────────────────────────────────────────

/**
 * Shared POST helper. Sends JSON body and centralises response.ok + JSON parse.
 * Throws a structured Error on HTTP errors or non-success API responses so that
 * callers can rely on a single try/catch pattern.
 *
 * @param {string} action   - Maps to ?action= in the controller router.
 * @param {object} body     - Plain object to JSON.stringify.
 * @param {string} [qs='']  - Optional extra query-string (e.g. "?id=5").
 * @returns {Promise<object>} Parsed JSON response from the server.
 */
async function _post(action, body, qs = '') {
  const url = `${BASE_URL}?action=${action}${qs}`

  const response = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(body),
  })

  const data = await response.json().catch(() => {
    // Guard: if the server sends a non-JSON body on error
    throw new Error(`Server returned a non-JSON response (HTTP ${response.status})`)
  })

  if (!response.ok || data.status !== 'success') {
    const msg = data?.message ?? `HTTP ${response.status}`
    throw new Error(msg)
  }

  return data
}

/**
 * Shared GET helper. Appends a URLSearchParams object to the action URL.
 *
 * @param {string}           action  - Maps to ?action= in the controller router.
 * @param {Record<string,*>} params  - Key/value pairs appended as query string.
 * @returns {Promise<object>} Parsed JSON response.
 */
async function _get(action, params = {}) {
  const qs = new URLSearchParams({ action, ...params }).toString()
  const url = `${BASE_URL}?${qs}`

  const response = await fetch(url)

  const data = await response.json().catch(() => {
    throw new Error(`Server returned a non-JSON response (HTTP ${response.status})`)
  })

  if (!response.ok || data.status !== 'success') {
    const msg = data?.message ?? `HTTP ${response.status}`
    throw new Error(msg)
  }

  return data
}

// ─── Public API ───────────────────────────────────────────────────────────────

const UserAPI = {

  /**
   * Fetch a paginated, filtered list of users.
   *
   * Controller handler : handleList()
   * Matching keys      : page, status, role, order, from, to
   *
   * @param {{
   *   page?:   number,
   *   status?: 'active'|'pending'|'banned'|'all',
   *   role?:   'admin'|'staff'|'customer'|'all',
   *   order?:  'newest'|'oldest'|'name-az'|'name-za',
   *   from?:   string,   // YYYY-MM-DD
   *   to?:     string,   // YYYY-MM-DD
   * }} options
   * @returns {Promise<{ summary: object, users: object[] }>}
   */
  list({
    page   = 1,
    status = 'all',
    role   = 'all',
    order  = 'newest',
    from   = '',
    to     = '',
  } = {}) {
    return _get('list', { page, status, role, order, from, to })
  },

  /**
   * Fetch ALL users matching the current filters (no pagination).
   * Used for CSV export. Sends limit=10000 so the controller returns everything
   * in a single response.
   *
   * Accepts the same filter options as list() (page is forced to 1).
   *
   * @param {object} options  - Same shape as list() options.
   * @returns {Promise<{ summary: object, users: object[] }>}
   */
  listAll({
    status = 'all',
    role   = 'all',
    order  = 'newest',
    from   = '',
    to     = '',
  } = {}) {
    return _get('list', { page: 1, limit: 10000, status, role, order, from, to })
  },

  /**
   * Fetch a single user by ID.
   *
   * Controller handler : handleGet()
   * Matching keys      : id (GET param)
   *
   * @param {number} id
   * @returns {Promise<{ user: object }>}
   */
  get(id) {
    if (!id) return Promise.reject(new Error('UserAPI.get: id is required'))
    return _get('get', { id })
  },

  /**
   * Create a new user.
   *
   * Controller handler : handleAdd()
   * Matching keys (verified against handleAdd + admin_user.js confirmAddUser):
   *   firstName, lastName, email, role, phone_number, temporaryPassword, notes
   *
   * @param {{
   *   firstName:         string,
   *   lastName:          string,
   *   email:             string,
   *   role?:             string,
   *   phone_number?:     string|null,
   *   temporaryPassword: string,
   *   notes?:            string,
   * }} userData
   * @returns {Promise<{ message: string }>}
   */
  add(userData) {
    const { firstName, email, temporaryPassword } = userData
    if (!firstName || !email || !temporaryPassword) {
      return Promise.reject(
        new Error('UserAPI.add: firstName, email, and temporaryPassword are required'),
      )
    }
    return _post('add', userData)
  },

  /**
   * Update an existing user's profile.
   *
   * Controller handler : handleUpdate()
   * Matching keys (verified against handleUpdate + admin_user.js confirmEditUser):
   *   id, firstName, lastName, email, role, status, phone_number, notes
   *
   * @param {{
   *   id:            number,
   *   firstName:     string,
   *   lastName:      string,
   *   email:         string,
   *   role:          string,
   *   status:        string,
   *   phone_number?: string|null,
   *   notes?:        string,
   * }} userData
   * @returns {Promise<{ message: string }>}
   */
  update(userData) {
    if (!userData.id) {
      return Promise.reject(new Error('UserAPI.update: id is required'))
    }
    return _post('update', userData)
  },

  /**
   * Ban a user.
   *
   * Controller handler : handleBan()
   * Matching keys (verified against handleBan + admin_user.js confirmBan):
   *   id, status ('banned'), reason, notes
   *
   * @param {{
   *   id:      number,
   *   reason:  string,
   *   notes?:  string,
   * }} banData
   * @returns {Promise<{ message: string }>}
   */
  ban({ id, reason, notes = '' }) {
    if (!id || !reason) {
      return Promise.reject(new Error('UserAPI.ban: id and reason are required'))
    }
    return _post('ban', {
      id,
      status: 'banned', // controller whitelist expects this exact value
      reason,
      notes,
    })
  },

  /**
   * Permanently delete a user.
   *
   * Controller handler : handleDelete()
   * Method             : POST (requirePost enforced server-side)
   * ID passed as       : GET param (?id=) — matches handleDelete() in controller
   *
   * @param {number} id
   * @returns {Promise<{ message: string }>}
   */
  delete(id) {
    if (!id) return Promise.reject(new Error('UserAPI.delete: id is required'))
    // ID goes in the query string; controller reads $_GET['id']
    return _post('delete', {}, `&id=${encodeURIComponent(id)}`)
  },
}

export default UserAPI
