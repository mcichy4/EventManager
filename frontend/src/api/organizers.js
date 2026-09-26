import http from "./http";

export const getMyOrganizers = async () => {
  const response = await http.get("/organizers/my");
  return response.data;
};

export const getOrganizerEvents = async (organizerId) => {
  const response = await http.get(`/organizers/${organizerId}/events`);
  return response.data;
};

export const getOrganizerMembers = async (organizerId) => {
  const response = await http.get(`/organizers/${organizerId}/members`);
  return response.data;
};

export const addOrganizerMember = async (organizerId, email) => {
  const response = await http.post(`/organizers/${organizerId}/members`, {
    email,
  });
  return response.data;
};

export const removeOrganizerMember = async (organizerId, memberId) => {
  await http.delete(`/organizers/${organizerId}/members/${memberId}`);
};

export const createOrganizer = async ({ name, type, description }) => {
  const response = await http.post("/organizers", {
    name,
    type,
    description,
  });
  return response.data;
};

export const createEvent = async (organizerId, eventData) => {
  const response = await http.post(
    `/organizers/${organizerId}/events`,
    eventData,
  );

  return response.data;
};
