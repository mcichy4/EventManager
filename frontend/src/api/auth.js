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
