import { BrowserRouter, Navigate, Route, Routes } from "react-router-dom";
import { AuthProvider } from "./auth/AuthContext";
import RequireAuth from "./auth/RequireAuth";
import AppLayout from "./components/layout/AppLayout";
import EventDetailsPage from "./pages/EventDetailPage";
import HomePage from "./pages/HomePage";
import LoginPage from "./pages/LoginPage";
import MyApplicationsPage from "./pages/MyApplicationsPage";
import MyOrganizersPage from "./pages/MyOrganizersPage";
import RegisterPage from "./pages/RegisterPage";
import OrganizerEventsPage from "./pages/OrganizerEventsPage";
import CreateEventPage from "./pages/CreateEventPage";
import "./App.css";
import CreateOrganizerPage from "./pages/CreateOrganizerPage";
import EventApplicationsPage from "./pages/EventApplicationsPage";
import EditEventPage from "./pages/EditEventPage";

export default function App() {
  return (
    <AuthProvider>
      <BrowserRouter>
        <Routes>
          <Route element={<AppLayout />}>
            <Route path="/" element={<HomePage />} />
            <Route path="/events" element={<Navigate to="/" replace />} />
            <Route path="/events/:eventId" element={<EventDetailsPage />} />
            <Route path="/login" element={<LoginPage />} />
            <Route path="/register" element={<RegisterPage />} />
            <Route
              path="/my-applications"
              element={
                <RequireAuth>
                  <MyApplicationsPage />
                </RequireAuth>
              }
            />
            <Route
              path="/my-organizers"
              element={
                <RequireAuth>
                  <MyOrganizersPage />
                </RequireAuth>
              }
            />
            <Route
              path="/organizers/new"
              element={
                <RequireAuth>
                  <CreateOrganizerPage />
                </RequireAuth>
              }
            />
            <Route
              path="/organizers/:organizerId/events/new"
              element={
                <RequireAuth>
                  <CreateEventPage />
                </RequireAuth>
              }
            />
            <Route
              path="/organizers/:organizerId/events/:eventId/edit"
              element={
                <RequireAuth>
                  <EditEventPage />
                </RequireAuth>
              }
            />
            <Route
              path="/organizers/:organizerId/events"
              element={
                <RequireAuth>
                  <OrganizerEventsPage />
                </RequireAuth>
              }
            />
            <Route
              path="/events/:eventId/applications"
              element={
                <RequireAuth>
                  <EventApplicationsPage />
                </RequireAuth>
              }
            />
            <Route path="*" element={<Navigate to="/" replace />} />
          </Route>
        </Routes>
      </BrowserRouter>
    </AuthProvider>
  );
}
