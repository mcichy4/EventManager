import axios from "axios";
import { getToken } from "./token";

const http = axios.create({
  baseURL: import.meta.env.VITE_API_URL ?? "http://localhost:8000/api",
  headers: {
    Accept: "application/json",
  },
});

http.interceptors.request.use((config) => {
  const token = getToken();

  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }

  return config;
});

export default http;
