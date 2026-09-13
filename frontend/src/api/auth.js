import http from "./http";

export const login = async ({ email, password }) => {
  await http.get("/sanctum/csrf-cookie");

  await http.post("/login", {
    email,
    password,
  });

  const response = await http.get("/api/user");

  return response.data;
};
