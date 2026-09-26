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

export const updateEvent = async (eventId, eventData) => {
  const response = await http.patch(`/events/${eventId}`, eventData);
  return response.data;
};

export const cancelEvent = async (eventId) => {
  const response = await http.post(`/events/${eventId}/cancel`);
  return response.data;
};

export const deleteEvent = async (eventId) => {
  await http.delete(`/events/${eventId}`);
};

export const getEventApplications = async (eventId) => {
  const response = await http.get(`/events/${eventId}/applications`);
  return response.data;
};

export const acceptEventApplication = async (applicationId) => {
  const response = await http.post(
    `/event-applications/${applicationId}/accept`,
  );
  return response.data;
};

export const rejectEventApplication = async (applicationId) => {
  const response = await http.post(
    `/event-applications/${applicationId}/reject`,
  );
  return response.data;
};
