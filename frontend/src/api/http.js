import axios from "axios";

const http = axios.create({
  baseURL: "http://localhost:8000",
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: "application/json",
  },
});

export default http;
