const SESSION_KEY = 'gaso-session';

export function getSession() {
  const raw = window.localStorage.getItem(SESSION_KEY);

  if (!raw) {
    return null;
  }

  try {
    return JSON.parse(raw);
  } catch {
    window.localStorage.removeItem(SESSION_KEY);
    return null;
  }
}

export function setSession(payload) {
  window.localStorage.setItem(SESSION_KEY, JSON.stringify(payload));
}

export function clearSession() {
  window.localStorage.removeItem(SESSION_KEY);
}