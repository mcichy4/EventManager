import http from "./http";
import { clearToken, hasToken, setToken } from "./token";

export const login = async ({ email, password }) => {
  const response = await http.post("/login", {
    email,
    password,
  });

  setToken(response.data.token);

  return response.data.user;
};

export const register = async ({
  name,
  email,
  password,
  passwordConfirmation,
}) => {
  if (password !== passwordConfirmation) {
    throw new Error("Hasła nie są takie same");
  }

  const response = await http.post("/register", {
    name,
    email,
    password,
    password_confirmation: passwordConfirmation,
  });

  setToken(response.data.token);
  return response.data.user;
};

export const getCurrentUser = async () => {
  const response = await http.get("/user");
  return response.data;
};

export const logout = async () => {
  try {
    await http.post("/logout");
  } finally {
    clearToken();
  }
};

export { clearToken, hasToken };
