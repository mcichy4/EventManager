const TOKEN_KEY = "event-manager-token";

export const getToken = () => localStorage.getItem(TOKEN_KEY);

export const hasToken = () => Boolean(getToken());

export const setToken = (token) => localStorage.setItem(TOKEN_KEY, token);

export const clearToken = () => localStorage.removeItem(TOKEN_KEY);
