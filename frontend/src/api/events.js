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

export const applyToEvent = async (eventId) => {
  const response = await http.post(`/events/${eventId}/applications`);
  return response.data;
};

export const getMyApplications = async () => {
  const response = await http.get("/me/event-applications");
  return response.data;
};

export const cancelApplication = async (applicationId) => {
  const response = await http.delete(`/event-applications/${applicationId}`);
  return response.data;
};

export const publishEvent = async (eventId) => {
  const response = await http.post(`/events/${eventId}/publish`);

  return response.data;
};
