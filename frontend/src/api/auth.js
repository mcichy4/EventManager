import http from "./http";

export const login = async ({ email, password }) => {
  await http.get("/sanctum/csrf-cookie");

  await http.post("/login", {
    email,
    password,
  });
  return getCurrentUser();
};

export const getCurrentUser = async () => {
  const response = await http.get("/api/user");
  return response.data;
};

export const logout = async () => {
  await http.post("/logout");
};
