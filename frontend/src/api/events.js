import http from "./http";

export const getEvents = async (search) => {
  const response = await http.get("/events", {
    params: search ? { search } : undefined,
  });

  return response.data;
};

export const getEvent = async (eventId) => {
  const response = await http.get(`/events/${eventId}`);

  return response.data;
};
